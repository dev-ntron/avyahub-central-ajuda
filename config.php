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
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
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

// Descobrir protocolo e host para suportar domínio, subdomínio e subpasta
function detectScheme() {
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')) {
        return 'https';
    }
    return 'http';
}

function detectHost() {
    if (!empty($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
    if (!empty($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
    return 'localhost';
}

// Detectar BASE_PATH automaticamente se .env não existir
function detectBasePath() {
    // Se .env existe, usar o valor lá definido
    $envBasePath = (string)env('BASE_PATH', '');
    if ($envBasePath !== '') {
        $envBasePath = '/' . ltrim(rtrim($envBasePath, '/'), '/');
        if ($envBasePath === '//') { $envBasePath = '/'; }
        return $envBasePath;
    }
    
    // Se .env não existe, detectar baseado na URL
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Tentar detectar se está em subpasta
    $scriptDir = dirname($scriptName);
    if ($scriptDir !== '/' && $scriptDir !== '') {
        return $scriptDir;
    }
    
    // Se detectar /ajuda na URL, usar como BASE_PATH
    if (strpos($requestUri, '/ajuda') !== false) {
        return '/ajuda';
    }
    
    // Padrão para raiz
    return '/';
}

$detectedBasePath = detectBasePath();
define('BASE_PATH', $detectedBasePath);

define('BASE_SCHEME', detectScheme());
define('BASE_HOST', detectHost());
define('BASE_URL', BASE_SCHEME . '://' . BASE_HOST . (BASE_PATH === '/' ? '' : BASE_PATH));

function url($path = '') {
    $path = '/' . ltrim((string)$path, '/');
    $prefix = BASE_PATH === '/' ? '' : BASE_PATH;
    return BASE_SCHEME . '://' . BASE_HOST . $prefix . $path;
}

// Configurações do banco de dados
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'avyahub_help'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Configurações do sistema
define('SITE_URL', env('SITE_URL', BASE_URL));
define('ADMIN_URL', (BASE_PATH === '/' ? '' : BASE_PATH) . '/admin');
define('UPLOADS_DIR', env('UPLOADS_DIR', 'uploads/'));
define('MAX_UPLOAD_SIZE', env('MAX_UPLOAD_SIZE', 5 * 1024 * 1024));

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
if (!in_array($tz, timezone_identifiers_list(), true)) { $tz = 'America/Sao_Paulo'; }
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
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        defined('DB_PASS') ? DB_PASS : '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}

// Debug helper (apenas em desenvolvimento)
function debug($var, $label = 'DEBUG') {
    if (APP_DEBUG) {
        echo "<pre style='background:#f0f0f0;padding:1rem;border:1px solid #ccc;margin:1rem;'>";
        echo "<strong>$label:</strong>\n";
        if (is_string($var) || is_numeric($var)) {
            echo htmlspecialchars($var);
        } else {
            print_r($var);
        }
        echo "</pre>";
    }
}
?>