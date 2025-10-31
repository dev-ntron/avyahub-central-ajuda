<?php
// ... topo permanece igual ...
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utils.php';

function checkRequirements() { /* ... unchanged ... */ }
function generateSecretKey() { return bin2hex(random_bytes(32)); }

session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Detectar BASE_PATH normalizado para exibir no passo 2
$detected = detectedBasePath();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'test_connection') { /* ... unchanged ... */ }
    if ($action === 'install') {
        // ...
        $detectedBasePath = normalizeBasePath(defined('BASE_PATH') ? BASE_PATH : '/');
        // ...
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- ... estilos ... -->
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🏠 AvyaHub Central de Ajuda</h1>
            <p>Assistente de Instalação Completa</p>
        </div>
        <div class="content">
            <?php if ($step === 1): $req = checkRequirements(); $ok=true; ?>
                <!-- ... passo 1 ... -->
            <?php elseif ($step === 2): ?>
                <div class="steps"><div class="step completed">1. Requisitos</div><div class="step active">2. Configuração</div><div class="step">3. Finalização</div></div>
                <h2>Configurações</h2>

                <div class="alert alert-info">
                    <strong>📍 BASE_PATH detectado:</strong> <code><?= htmlspecialchars($detected) ?></code>
                    <div style="font-size: 0.9rem; color:#374151; margin-top:0.25rem;">Este valor será salvo no .env como <code>BASE_PATH</code>.</div>
                </div>

                <?php if (!empty($_SESSION['install_errors'])): ?>
                    <!-- ... erros ... -->
                <?php unset($_SESSION['install_errors']); endif; ?>

                <form method="post" id="configForm" action="<?= $detected === '/' ? '/install/' : $detected . '/install/' ?>">
                    <input type="hidden" name="action" value="install">
                    <!-- ... campos de banco e admin ... -->
                    <div style="display:flex; gap:0.5rem; margin-top: 2rem;">
                        <a class="btn btn-secondary" href="<?= $detected === '/' ? '/install/?step=1' : $detected . '/install/?step=1' ?>">← Voltar</a>
                        <button class="btn btn-success" type="submit" onclick="return confirm('Confirmar instalação com estas configurações?')">🚀 Instalar Sistema Completo</button>
                    </div>
                </form>
            <?php elseif ($step === 3): ?>
                <!-- ... passo 3 ... -->
            <?php endif; ?>
        </div>
    </div>
</body>
</html>