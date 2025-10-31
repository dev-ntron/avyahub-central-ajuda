<?php
$page_title = 'Mídia e Assets';

// Processar uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_logo':
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                    $file = $_FILES['logo'];
                    
                    // Verificar se é uma imagem
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                    if (in_array($file['type'], $allowed_types)) {
                        // Verificar tamanho (máximo 2MB para logo)
                        if ($file['size'] <= 2 * 1024 * 1024) {
                            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $filename = 'logo-avyahub.' . $extension;
                            $filepath = '../assets/' . $filename;
                            
                            // Criar diretório assets se não existir
                            if (!is_dir('../assets/')) {
                                mkdir('../assets/', 0755, true);
                            }
                            
                            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                                // Atualizar configuração no banco
                                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'logo_url'");
                                $stmt->execute(['/assets/' . $filename]);
                                
                                $_SESSION['success'] = 'Logo atualizado com sucesso!';
                            } else {
                                $_SESSION['error'] = 'Erro ao fazer upload do logo.';
                            }
                        } else {
                            $_SESSION['error'] = 'Logo muito grande. Máximo 2MB.';
                        }
                    } else {
                        $_SESSION['error'] = 'Formato de arquivo não suportado para logo.';
                    }
                }
                break;
                
            case 'upload_favicon':
                if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
                    $file = $_FILES['favicon'];
                    
                    // Verificar se é uma imagem adequada para favicon
                    $allowed_types = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg'];
                    if (in_array($file['type'], $allowed_types)) {
                        if ($file['size'] <= 100 * 1024) { // 100KB max para favicon
                            $filename = 'favicon.ico';
                            $filepath = '../assets/' . $filename;
                            
                            if (!is_dir('../assets/')) {
                                mkdir('../assets/', 0755, true);
                            }
                            
                            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('favicon_url', '/assets/favicon.ico') ON DUPLICATE KEY UPDATE setting_value = '/assets/favicon.ico'");
                                $stmt->execute();
                                
                                $_SESSION['success'] = 'Favicon atualizado com sucesso!';
                            } else {
                                $_SESSION['error'] = 'Erro ao fazer upload do favicon.';
                            }
                        } else {
                            $_SESSION['error'] = 'Favicon muito grande. Máximo 100KB.';
                        }
                    } else {
                        $_SESSION['error'] = 'Formato não suportado. Use .ico, .png ou .jpg para favicon.';
                    }
                }
                break;
                
            case 'delete_media':
                $filename = $_POST['filename'];
                $filepath = '../assets/' . basename($filename);
                
                if (file_exists($filepath) && strpos(realpath($filepath), realpath('../assets/')) === 0) {
                    if (unlink($filepath)) {
                        // Remover referência do banco se for logo ou favicon
                        if (strpos($filename, 'logo') !== false) {
                            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = '' WHERE setting_key = 'logo_url'");
                            $stmt->execute();
                        } elseif (strpos($filename, 'favicon') !== false) {
                            $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = 'favicon_url'");
                            $stmt->execute();
                        }
                        
                        $_SESSION['success'] = 'Arquivo removido com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao remover arquivo.';
                    }
                }
                break;
        }
        
        header('Location: /admin/media');
        exit;
    }
}

// Obter configurações atuais
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('logo_url', 'favicon_url')");
$media_settings = [];
while ($row = $stmt->fetch()) {
    $media_settings[$row['setting_key']] = $row['setting_value'];
}

// Listar arquivos na pasta assets
$assets_files = [];
if (is_dir('../assets/')) {
    $files = scandir('../assets/');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file('../assets/' . $file)) {
            $filepath = '../assets/' . $file;
            $assets_files[] = [
                'name' => $file,
                'size' => filesize($filepath),
                'modified' => filemtime($filepath),
                'url' => '/assets/' . $file
            ];
        }
    }
}

ob_start();
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem;">Mídia e Assets</h2>
    <p style="color: #718096;">Gerencie logo, favicon e outros arquivos de mídia do site.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Upload de Logo -->
    <div class="card">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.2rem; color: #2d3748; display: flex; align-items: center; gap: 0.5rem;">
            🖼️ Logo do Site
        </h3>
        
        <?php if (!empty($media_settings['logo_url'])): ?>
        <div style="margin-bottom: 1rem; text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
            <img src="<?= htmlspecialchars($media_settings['logo_url']) ?>" alt="Logo atual" style="max-width: 200px; max-height: 100px; border-radius: 4px;">
            <div style="margin-top: 0.5rem;">
                <small style="color: #718096;">Logo atual</small>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_logo">
            
            <div class="form-group">
                <label class="form-label" for="logo">Novo Logo</label>
                <input type="file" id="logo" name="logo" class="form-input" accept="image/*" required>
                <small style="color: #718096;">Formatos: JPG, PNG, GIF, WebP, SVG. Máximo 2MB.</small>
            </div>
            
            <button type="submit" class="btn btn-success">📤 Enviar Logo</button>
            
            <?php if (!empty($media_settings['logo_url'])): ?>
            <form method="POST" style="display: inline; margin-left: 0.5rem;" onsubmit="return confirm('Tem certeza que deseja remover o logo atual?')">
                <input type="hidden" name="action" value="delete_media">
                <input type="hidden" name="filename" value="<?= basename($media_settings['logo_url']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑️ Remover</button>
            </form>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Upload de Favicon -->
    <div class="card">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.2rem; color: #2d3748; display: flex; align-items: center; gap: 0.5rem;">
            🔖 Favicon
        </h3>
        
        <?php if (!empty($media_settings['favicon_url'])): ?>
        <div style="margin-bottom: 1rem; text-align: center; padding: 1rem; background: #f7fafc; border-radius: 8px;">
            <img src="<?= htmlspecialchars($media_settings['favicon_url']) ?>" alt="Favicon atual" style="width: 32px; height: 32px;">
            <div style="margin-top: 0.5rem;">
                <small style="color: #718096;">Favicon atual</small>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_favicon">
            
            <div class="form-group">
                <label class="form-label" for="favicon">Novo Favicon</label>
                <input type="file" id="favicon" name="favicon" class="form-input" accept=".ico,.png,.jpg,.jpeg" required>
                <small style="color: #718096;">Preferencialmente .ico 16x16 ou 32x32px. Máximo 100KB.</small>
            </div>
            
            <button type="submit" class="btn btn-success">📤 Enviar Favicon</button>
            
            <?php if (!empty($media_settings['favicon_url'])): ?>
            <form method="POST" style="display: inline; margin-left: 0.5rem;" onsubmit="return confirm('Tem certeza que deseja remover o favicon atual?')">
                <input type="hidden" name="action" value="delete_media">
                <input type="hidden" name="filename" value="<?= basename($media_settings['favicon_url']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑️ Remover</button>
            </form>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista de Arquivos -->
<?php if (!empty($assets_files)): ?>
<div class="card">
    <h3 style="margin: 0 0 1rem 0; font-size: 1.2rem; color: #2d3748;">📁 Arquivos de Mídia</h3>
    
    <div class="table-container" style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Tamanho</th>
                    <th>Modificado</th>
                    <th>Preview</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assets_files as $file): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($file['name']) ?></strong>
                        <div style="font-size: 0.8rem; color: #718096;">
                            <code><?= htmlspecialchars($file['url']) ?></code>
                        </div>
                    </td>
                    <td><?= number_format($file['size'] / 1024, 1) ?> KB</td>
                    <td><?= date('d/m/Y H:i', $file['modified']) ?></td>
                    <td>
                        <?php if (in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])): ?>
                        <img src="<?= htmlspecialchars($file['url']) ?>" alt="Preview" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                        <div style="width: 40px; height: 40px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 0.8rem;">📄</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="<?= htmlspecialchars($file['url']) ?>" target="_blank" class="btn btn-sm" style="background: #38a169;">👁️</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                <input type="hidden" name="action" value="delete_media">
                                <input type="hidden" name="filename" value="<?= htmlspecialchars($file['name']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card" style="background: #f0f9ff; border: 1px solid #0ea5e9;">
    <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; color: #0c4a6e; display: flex; align-items: center; gap: 0.5rem;">
        💡 Dicas de Uso
    </h3>
    <ul style="margin: 0; color: #0c4a6e; line-height: 1.6;">
        <li><strong>Logo:</strong> Use uma imagem horizontal, preferencialmente PNG com fundo transparente.</li>
        <li><strong>Favicon:</strong> Arquivo .ico 16x16 ou 32x32 pixels para melhor compatibilidade.</li>
        <li><strong>Performance:</strong> Otimize imagens antes do upload para reduzir tempo de carregamento.</li>
        <li><strong>Backup:</strong> Mantenha cópias dos arquivos originais antes de fazer alterações.</li>
    </ul>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>