<?php
/**
 * Configurações gerais do sistema AvyaHub Central de Ajuda
 * Loader .env robusto, headers de segurança e helpers
 */

// Loader .env robusto
function loadEnvFile($path = '.env') {
    if (!file_exists($path)) { return false; }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') { continue; }
        if (strpos($line, '=') === false) { continue; }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Remover aspas simples/duplas
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        // Conversões comuns
        $lower = strtolower($value);
        if ($lower === 'true') { $value = true; }
        elseif ($lower === 'false') { $value = false; }
        elseif ($lower === 'null') { $value = null; }
        elseif ($lower === 'empty') { $value = ''; }
        elseif (is_numeric($value)) { $value = $value + 0; }
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Carregar arquivo .env
loadEnvFile(__DIR__ . '/.env');

// Helper env
function env($key, $default = null) {
    $value = getenv($key);
    return ($value === false) ? $default : $value;
}

// Configurações do banco de dados
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'avyahub_help'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Configurações do sistema
define('SITE_URL', env('SITE_URL', '/'));
define('ADMIN_URL', '/admin');
define('UPLOADS_DIR', env('UPLOADS_DIR', 'uploads/'));
define('MAX_UPLOAD_SIZE', env('MAX_UPLOAD_SIZE', 5 * 1024 * 1024)); // 5MB

// Segurança
define('APP_SECRET_KEY', env('APP_SECRET_KEY', 'change_this_secret_key'));

// Ambiente
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));

// Performance
define('ENABLE_CACHE', env('ENABLE_CACHE', true));
define('CACHE_DURATION', env('CACHE_DURATION', 3600));

// Timezone com validação
$tz = env('APP_TIMEZONE', 'America/Sao_Paulo');
if (!in_array($tz, timezone_identifiers_list())) { $tz = 'America/Sao_Paulo'; }
date_default_timezone_set($tz);

// Erros por ambiente
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Sessão mais segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));

// Headers de segurança em produção
if (APP_ENV === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer');
}

// Helper PDO
function createDatabaseConnection() {
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        throw new RuntimeException('Configuração de banco incompleta');
    }
    return new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        defined('DB_PASS') ? DB_PASS : '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}
