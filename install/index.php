<?php
// Verificar se já foi instalado
$installed_file = __DIR__ . '/.installed';
if (file_exists($installed_file)) { /* ... unchanged header block ... */ }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utils.php'; // normalizeBasePath

function checkRequirements() { /* ... unchanged ... */ }
function generateSecretKey() { return bin2hex(random_bytes(32)); }

session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'test_connection') { /* ... unchanged ... */ }
    if ($action === 'install') {
        $errors = [];

        // Detectar BASE_PATH automaticamente com fallback para RAIZ '/', e remover '/install' do final
        $detectedBasePath = defined('BASE_PATH') ? BASE_PATH : '/';
        $detectedBasePath = normalizeBasePath($detectedBasePath);

        $cfg = [
            'DB_HOST' => trim((string)($_POST['db_host'] ?? '')),
            'DB_NAME' => trim((string)($_POST['db_name'] ?? '')),
            'DB_USER' => trim((string)($_POST['db_user'] ?? '')),
            'DB_PASS' => (string)($_POST['db_pass'] ?? ''),
            'ADMIN_USERNAME' => trim((string)($_POST['admin_username'] ?? '')),
            'ADMIN_PASSWORD' => (string)($_POST['admin_password'] ?? ''),
            'ADMIN_NAME' => trim((string)($_POST['admin_name'] ?? 'Administrador')),
            'APP_ENV' => ($_POST['app_env'] ?? 'development') === 'production' ? 'production' : 'development',
            'APP_DEBUG' => (($_POST['app_env'] ?? 'development') === 'development') ? 'true' : 'false',
            'APP_SECRET_KEY' => bin2hex(random_bytes(32)),
            'BASE_PATH' => $detectedBasePath
        ];
        /* ... unchanged validation, connection ... */
        // Executar migrations
        runCompleteMigrations($pdo);

        // Criar .env (inclui BASE_PATH normalizado, sem '/install')
        $env = "DB_HOST={$cfg['DB_HOST']}\nDB_NAME={$cfg['DB_NAME']}\nDB_USER={$cfg['DB_USER']}\nDB_PASS={$cfg['DB_PASS']}\nBASE_PATH={$cfg['BASE_PATH']}\nSITE_URL=/\nUPLOADS_DIR=uploads/\nMAX_UPLOAD_SIZE=5242880\nAPP_ENV={$cfg['APP_ENV']}\nAPP_DEBUG={$cfg['APP_DEBUG']}\nAPP_TIMEZONE=America/Sao_Paulo\nENABLE_CACHE=true\nCACHE_DURATION=3600\nAPP_SECRET_KEY={$cfg['APP_SECRET_KEY']}\n";
        /* ... unchanged save env ... */

        // Redirecionamentos usando BASE_PATH explícito (evita install/install)
        header('Location: ' . ($cfg['BASE_PATH'] === '/' ? '/install/?step=3' : $cfg['BASE_PATH'] . '/install/?step=3'));
        exit;
    }
}
?>
<!DOCTYPE html>
<!-- Restante do HTML permanece igual; ajustar também os links para usar BASE_PATH direto -->
