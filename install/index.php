<?php
// Verificar se já foi instalado
$installed_file = __DIR__ . '/.installed';
if (file_exists($installed_file)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema já instalado - AvyaHub</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
            .container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
            h1 { color: #2d3748; margin-bottom: 1rem; }
            p { color: #718096; margin-bottom: 1.5rem; }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: background 0.3s; margin: 0.25rem; }
            .btn:hover { background: #1d4ed8; }
        </style>
    </head>
    <body>
        <div class="container">
            <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
            <h1>Sistema já instalado</h1>
            <p>A instalação do AvyaHub Central de Ajuda já foi concluída. Para reinstalar, remova a pasta <code>/install</code> do servidor.</p>
            <div>
                <a href="/" class="btn">🏠 Ver Site</a>
                <a href="/admin" class="btn">🔐 Painel Admin</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function checkRequirements() {
    $checks = [];
    $checks['php_version'] = ['name' => 'PHP 7.4+', 'status' => version_compare(PHP_VERSION, '7.4.0', '>='), 'current' => PHP_VERSION];
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'gd', 'mbstring'];
    foreach ($required_extensions as $ext) {
        $checks["ext_$ext"] = ['name' => "Extensão $ext", 'status' => extension_loaded($ext), 'current' => extension_loaded($ext) ? 'Instalada' : 'Não encontrada'];
    }
    $write_dirs = ['../' => 'Pasta raiz (para .env)', '../uploads/' => 'Pasta uploads', '../assets/' => 'Pasta assets'];
    foreach ($write_dirs as $dir => $description) {
        $full_path = __DIR__ . '/' . $dir;
        if (!is_dir($full_path)) { @mkdir($full_path, 0755, true); }
        $writable = is_writable($full_path);
        $checks["write_" . md5($dir)] = ['name' => $description, 'status' => $writable, 'current' => $writable ? 'Gravável' : 'Sem permissão'];
    }
    return $checks;
}

function generateSecretKey() { return bin2hex(random_bytes(32)); }

session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'test_connection') {
        $response = ['success' => false, 'message' => ''];
        try {
            $host = trim($_POST['db_host']);
            $name = trim($_POST['db_name']);
            $user = trim($_POST['db_user']);
            $pass = $_POST['db_pass'];
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query("SHOW DATABASES LIKE '" . addslashes($name) . "'");
            $exists = $stmt->rowCount() > 0;
            if ($exists) {
                new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
                $response = ['success' => true, 'message' => 'Conexão perfeita! Database existe e está acessível.'];
            } else {
                try { $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name`\n  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $pdo->exec("DROP DATABASE `$name`"); $response = ['success' => true, 'message' => 'Conexão OK. Database será criado automaticamente na instalação.']; } catch (Exception $e) { $response = ['success' => true, 'message' => 'Conexão OK, mas sem permissão CREATE DATABASE. Crie manualmente o database: ' . htmlspecialchars($name)]; }
            }
        } catch (Exception $e) { $response['message'] = 'Erro de conexão: ' . $e->getMessage(); }
        header('Content-Type: application/json'); echo json_encode($response); exit;
    }
    if ($action === 'install') {
        $errors = [];
        $cfg = [
            'DB_HOST' => trim($_POST['db_host'] ?? ''),
            'DB_NAME' => trim($_POST['db_name'] ?? ''),
            'DB_USER' => trim($_POST['db_user'] ?? ''),
            'DB_PASS' => $_POST['db_pass'] ?? '',
            'ADMIN_USERNAME' => trim($_POST['admin_username'] ?? ''),
            'ADMIN_PASSWORD' => $_POST['admin_password'] ?? '',
            'APP_ENV' => $_POST['app_env'] ?? 'development',
            'APP_DEBUG' => ($_POST['app_env'] ?? 'development') === 'development' ? 'true' : 'false',
            'APP_SECRET_KEY' => bin2hex(random_bytes(32))
        ];
        if ($cfg['DB_HOST'] === '') $errors[] = 'Host do banco é obrigatório';
        if ($cfg['DB_NAME'] === '') $errors[] = 'Nome do banco é obrigatório';
        if ($cfg['DB_USER'] === '') $errors[] = 'Usuário do banco é obrigatório';
        if ($cfg['ADMIN_USERNAME'] === '') $errors[] = 'Usuário admin é obrigatório';
        if (strlen($cfg['ADMIN_PASSWORD']) < 8) $errors[] = 'Senha admin deve ter pelo menos 8 caracteres';
        if (empty($errors)) {
            try {
                $pdo = new PDO("mysql:host={$cfg['DB_HOST']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if (isset($_POST['create_database'])) { $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['DB_NAME']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); }
                $pdo = new PDO("mysql:host={$cfg['DB_HOST']};dbname={$cfg['DB_NAME']};charset=utf8mb4", $cfg['DB_USER'], $cfg['DB_PASS']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // 1) Criar tabelas
                require_once __DIR__ . '/database.php';
                require_once __DIR__ . '/migrations_auth.php';
                createTables($pdo);
                addAuthTables($pdo);

                // 2) Criar .env sem ADMIN_ (somente DB + app)
                $env = "DB_HOST={$cfg['DB_HOST']}\nDB_NAME={$cfg['DB_NAME']}\nDB_USER={$cfg['DB_USER']}\nDB_PASS={$cfg['DB_PASS']}\n\nSITE_URL=/\nUPLOADS_DIR=uploads/\nMAX_UPLOAD_SIZE=5242880\nAPP_ENV={$cfg['APP_ENV']}\nAPP_DEBUG={$cfg['APP_DEBUG']}\nAPP_TIMEZONE=America/Sao_Paulo\nENABLE_CACHE=true\nCACHE_DURATION=3600\nAPP_SECRET_KEY={$cfg['APP_SECRET_KEY']}\n";
                if (!file_put_contents(__DIR__ . '/../.env', $env)) { throw new Exception('Não foi possível criar .env (permissões)'); }
                @chmod(__DIR__ . '/../.env', 0640);

                // 3) Criar usuário admin na tabela users
                $hash = password_hash($cfg['ADMIN_PASSWORD'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (name, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute(['Administrador', $cfg['ADMIN_USERNAME'], $hash, 'admin']);

                // 4) Flag de instalação
                file_put_contents($installed_file, date('Y-m-d H:i:s'));
                
                header('Location: /install/?step=3'); exit;
            } catch (Exception $e) { $errors[] = $e->getMessage(); }
        }
        $_SESSION['install_errors'] = $errors; $_SESSION['form_data'] = $_POST; header('Location: /install/?step=2'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - AvyaHub Central de Ajuda</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 1rem; min-height: 100vh; }
        .installer { max-width: 900px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #2563eb; color: white; padding: 2rem; text-align: center; }
        .content { padding: 2rem; }
        .steps { display: flex; justify-content: center; margin-bottom: 2rem; gap: 0.5rem; }
        .step { padding: 0.6rem 1.2rem; border-radius: 999px; background: #f1f5f9; color: #64748b; font-size: 0.9rem; font-weight: 600; }
        .step.active { background: #2563eb; color: #fff; }
        .step.completed { background: #059669; color: #fff; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.4rem; font-weight: 600; color: #374151; }
        .form-input, .form-select { width: 100%; padding: 0.7rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; transition: all 0.2s; }
        .form-input:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none; }
        .btn { display: inline-block; padding: 0.7rem 1.2rem; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-success { background: #059669; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #ecfdf5; border: 2px solid #10b981; color: #065f46; }
        .alert-error { background: #fef2f2; border: 2px solid #ef4444; color: #991b1b; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🏠 AvyaHub Central de Ajuda</h1>
            <p>Assistente de Instalação</p>
        </div>
        <div class="content">
            <?php if ($step === 1): $req = checkRequirements(); $ok=true; ?>
                <div class="steps"><div class="step active">1. Requisitos</div><div class="step">2. Configuração</div><div class="step">3. Finalização</div></div>
                <h2>Verificação de Requisitos</h2>
                <ul style="list-style:none; padding:0;">
                    <?php foreach ($req as $item): ?>
                        <li class="alert <?= $item['status'] ? 'alert-success' : 'alert-error' ?>">
                            <strong><?= htmlspecialchars($item['name']) ?>:</strong> <?= htmlspecialchars($item['current']) ?>
                        </li>
                        <?php if(!$item['status']) $ok=false; ?>
                    <?php endforeach; ?>
                </ul>
                <div style="text-align:center; margin-top:1rem;">
                    <?php if ($ok): ?>
                        <a href="?step=2" class="btn btn-primary">Continuar →</a>
                    <?php else: ?>
                        <div class="alert alert-error">Corrija os itens acima e recarregue a página.</div>
                        <a href="?step=1" class="btn btn-secondary">Recarregar</a>
                    <?php endif; ?>
                </div>
            <?php elseif ($step === 2): ?>
                <div class="steps"><div class="step completed">1. Requisitos</div><div class="step active">2. Configuração</div><div class="step">3. Finalização</div></div>
                <h2>Configurações</h2>
                <?php if (!empty($_SESSION['install_errors'])): ?>
                    <div class="alert alert-error">
                        <ul style="margin:0; padding-left:1rem;">
                            <?php foreach ($_SESSION['install_errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php unset($_SESSION['install_errors']); endif; ?>
                <form method="post" id="configForm">
                    <input type="hidden" name="action" value="install">
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label" for="db_host">Host do Banco</label>
                            <input class="form-input" id="db_host" name="db_host" value="<?= htmlspecialchars($_SESSION['form_data']['db_host'] ?? 'localhost') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="db_name">Nome do Banco</label>
                            <input class="form-input" id="db_name" name="db_name" value="<?= htmlspecialchars($_SESSION['form_data']['db_name'] ?? 'avyahub_help') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="db_user">Usuário do Banco</label>
                            <input class="form-input" id="db_user" name="db_user" value="<?= htmlspecialchars($_SESSION['form_data']['db_user'] ?? 'root') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="db_pass">Senha do Banco</label>
                            <input type="password" class="form-input" id="db_pass" name="db_pass" value="<?= htmlspecialchars($_SESSION['form_data']['db_pass'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" id="testBtn" onclick="testConn()">🔌 Testar Conexão</button>
                        <div id="connResult" style="margin-top:0.5rem;"></div>
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:0.5rem;">
                            <input type="checkbox" name="create_database" value="1" checked>
                            Criar database automaticamente (se não existir)
                        </label>
                    </div>
                    <hr>
                    <div class="grid">
                        <div class="form-group">
                            <label class="form-label" for="admin_username">Usuário Admin</label>
                            <input class="form-input" id="admin_username" name="admin_username" value="<?= htmlspecialchars($_SESSION['form_data']['admin_username'] ?? 'avyahub') ?>" required minlength="3">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="admin_password">Senha Admin</label>
                            <input type="password" class="form-input" id="admin_password" name="admin_password" required minlength="8">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="app_env">Ambiente</label>
                        <select class="form-select" id="app_env" name="app_env">
                            <option value="development" <?= (($_SESSION['form_data']['app_env'] ?? 'development')==='development')?'selected':'' ?>>Desenvolvimento</option>
                            <option value="production" <?= (($_SESSION['form_data']['app_env'] ?? '')==='production')?'selected':'' ?>>Produção</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <a class="btn btn-secondary" href="?step=1">← Voltar</a>
                        <button class="btn btn-success" type="submit" onclick="return confirm('Confirmar instalação com estas configurações?')">🚀 Instalar</button>
                    </div>
                </form>
                <script>
                function testConn(){
                    const btn=document.getElementById('testBtn');
                    const res=document.getElementById('connResult');
                    btn.disabled=true; btn.textContent='⏳ Testando...';
                    const fd=new FormData();
                    fd.append('action','test_connection');
                    fd.append('db_host',document.getElementById('db_host').value);
                    fd.append('db_name',document.getElementById('db_name').value);
                    fd.append('db_user',document.getElementById('db_user').value);
                    fd.append('db_pass',document.getElementById('db_pass').value);
                    fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                        res.innerHTML='<div class="alert '+(d.success?'alert-success':'alert-error')+'">'+(d.success?'✅ ':'❌ ')+d.message+'</div>';
                        btn.disabled=false; btn.textContent='🔌 Testar Conexão';
                    }).catch(e=>{res.innerHTML='<div class="alert alert-error">❌ '+e.message+'</div>'; btn.disabled=false; btn.textContent='🔌 Testar Conexão';});
                }
                </script>
            <?php elseif ($step === 3): ?>
                <div class="steps"><div class="step completed">1. Requisitos</div><div class="step completed">2. Configuração</div><div class="step active">3. Finalização</div></div>
                <h2>Instalação Concluída</h2>
                <div class="alert alert-success">O sistema foi instalado com sucesso.</div>
                <div class="alert alert-error">Por segurança, remova a pasta <code>/install</code> do servidor.</div>
                <div style="display:flex; gap:0.5rem;">
                    <a class="btn btn-primary" href="/admin">🔐 Abrir Admin</a>
                    <a class="btn btn-success" href="/">🏠 Ver Site</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
