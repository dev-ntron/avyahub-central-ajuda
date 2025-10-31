<?php
session_start();

$env_path = __DIR__ . '/.env';
$install_flag = __DIR__ . '/install/.installed';

if (!file_exists($env_path) || !file_exists($install_flag)) {
    if (strpos($_SERVER['REQUEST_URI'], '/install') !== 0) {
        header('Location: /install/');
        exit;
    }
}

if (file_exists($env_path)) {
    require_once 'config.php';
    try {
        // Usa helper centralizado
        $pdo = createDatabaseConnection();
    } catch(Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('[DB-ERROR] ' . $e->getMessage());
            echo "<div style='background: #fee2e2; border: 1px solid #f87171; padding: 1rem; border-radius: 8px; margin: 2rem; font-family: monospace;'>";
            echo "<h3 style='color: #dc2626; margin: 0 0 1rem 0;'>❌ Erro de Conexão com o Banco</h3>";
            echo "<p style='margin: 0 0 1rem 0; color: #7f1d1d;'>Detalhes: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='margin: 0;'><a href='/install/' style='color: #dc2626; font-weight: bold;'>→ Ir ao Instalador</a></p>";
            echo "</div>";
        } else {
            header('Location: /install/');
        }
        exit;
    }
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');

if ($path === '/install' || strpos($path, '/install') === 0) {
    include 'install/index.php';
    exit;
}
if ($path === '/admin' || strpos($path, '/admin') === 0) {
    include 'admin/index.php';
    exit;
}
include 'public/index.php';
exit;
?>