<?php
$page_title = 'Configurações';

// Processar atualizações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $settings_to_update = [
            'site_title',
            'site_description',
            'primary_color',
            'secondary_color',
            'footer_text'
        ];
        
        foreach ($settings_to_update as $setting) {
            if (isset($_POST[$setting])) {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$setting, $_POST[$setting], $_POST[$setting]]);
            }
        }
        
        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
        header('Location: /admin/settings');
        exit;
    }
}

// Obter configurações atuais
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

ob_start();
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem;">Configurações do Site</h2>
    <p style="color: #718096;">Personalize a aparência e configurações gerais da central de ajuda.</p>
</div>

<form method="POST">
    <input type="hidden" name="action" value="update_settings">
    
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">
        <!-- Configurações principais -->
        <div>
            <!-- Configurações Gerais -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: #2d3748; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                    ⚙️ Configurações Gerais
                </h3>
                
                <div class="form-group">
                    <label class="form-label" for="site_title">Título do Site</label>
                    <input type="text" id="site_title" name="site_title" class="form-input" 
                           value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>" required>
                    <small style="color: #718096;">Este título aparecerá no cabeçalho e na aba do navegador.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="site_description">Descrição do Site</label>
                    <textarea id="site_description" name="site_description" class="form-textarea" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                    <small style="color: #718096;">Descrição que aparece na página inicial e nos mecanismos de busca.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="footer_text">Texto do Rodapé</label>
                    <input type="text" id="footer_text" name="footer_text" class="form-input" 
                           value="<?= htmlspecialchars($settings['footer_text'] ?? '') ?>">
                    <small style="color: #718096;">Texto que aparece no rodapé do site.</small>
                </div>
            </div>
            
            <!-- Personalização Visual -->
            <div class="card">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: #2d3748; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                    🎨 Personalização Visual
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="primary_color">Cor Primária</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" id="primary_color" name="primary_color" 
                                   value="<?= $settings['primary_color'] ?? '#2563eb' ?>" 
                                   style="width: 50px; height: 40px; border: 1px solid #d1d5db; border-radius: 6px;">
                            <input type="text" class="form-input" style="flex: 1;" 
                                   value="<?= $settings['primary_color'] ?? '#2563eb' ?>" 
                                   onchange="document.getElementById('primary_color').value = this.value">
                        </div>
                        <small style="color: #718096;">Cor principal dos links e botões.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="secondary_color">Cor Secundária</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" id="secondary_color" name="secondary_color" 
                                   value="<?= $settings['secondary_color'] ?? '#64748b' ?>" 
                                   style="width: 50px; height: 40px; border: 1px solid #d1d5db; border-radius: 6px;">
                            <input type="text" class="form-input" style="flex: 1;" 
                                   value="<?= $settings['secondary_color'] ?? '#64748b' ?>" 
                                   onchange="document.getElementById('secondary_color').value = this.value">
                        </div>
                        <small style="color: #718096;">Cor dos textos secundários.</small>
                    </div>
                </div>
                
                <div style="background: #f7fafc; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-weight: 500;">👀 Preview das Cores</span>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div id="preview-primary" style="width: 20px; height: 20px; border-radius: 4px; background: <?= $settings['primary_color'] ?? '#2563eb' ?>;"></div>
                            <span>Cor Primária</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div id="preview-secondary" style="width: 20px; height: 20px; border-radius: 4px; background: <?= $settings['secondary_color'] ?? '#64748b' ?>;"></div>
                            <span>Cor Secundária</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar de ações -->
        <div>
            <div class="card" style="position: sticky; top: 2rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem;">Ações</h3>
                
                <button type="submit" class="btn btn-success" style="width: 100%; margin-bottom: 0.5rem;">
                    💾 Salvar Configurações
                </button>
                
                <a href="/" class="btn" target="_blank" style="width: 100%; text-align: center; display: block; margin-bottom: 0.5rem; background: #38a169;">
                    🌍 Visualizar Site
                </a>
                
                <a href="/admin/media" class="btn" style="width: 100%; text-align: center; display: block; margin-bottom: 0.5rem; background: #d69e2e;">
                    🖼️ Gerenciar Mídia
                </a>
                
                <div style="border-top: 1px solid #e2e8f0; padding-top: 1rem; margin-top: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #718096;">ESTATÍSTICAS RÁPIDAS</h4>
                    
                    <?php
                    // Obter estatísticas rápidas
                    $stats = [];
                    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
                    $stats['categories'] = $stmt->fetchColumn();
                    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1");
                    $stats['published'] = $stmt->fetchColumn();
                    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 0");
                    $stats['drafts'] = $stmt->fetchColumn();
                    ?>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem;">Categorias:</span>
                        <strong><?= $stats['categories'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem;">Publicados:</span>
                        <strong style="color: #2f855a;"><?= $stats['published'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-size: 0.8rem;">Rascunhos:</span>
                        <strong style="color: #d69e2e;"><?= $stats['drafts'] ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Atualizar preview das cores em tempo real
document.getElementById('primary_color').addEventListener('change', function() {
    document.getElementById('preview-primary').style.background = this.value;
    this.nextElementSibling.value = this.value;
});

document.getElementById('secondary_color').addEventListener('change', function() {
    document.getElementById('preview-secondary').style.background = this.value;
    this.nextElementSibling.value = this.value;
});

// Sincronizar campos de texto com color pickers
document.querySelectorAll('input[type="text"]').forEach(function(input) {
    if (input.previousElementSibling && input.previousElementSibling.type === 'color') {
        input.addEventListener('change', function() {
            if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                input.previousElementSibling.value = this.value;
                if (input.previousElementSibling.id === 'primary_color') {
                    document.getElementById('preview-primary').style.background = this.value;
                } else if (input.previousElementSibling.id === 'secondary_color') {
                    document.getElementById('preview-secondary').style.background = this.value;
                }
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>