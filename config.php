<?php
/**
 * Configurações gerais do sistema AvyaHub Central de Ajuda
 * Agora com suporte a variáveis de ambiente
 */

// Função para carregar variáveis de ambiente do arquivo .env
function loadEnvFile($path = '.env') {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignorar comentários
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
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

// Função helper para obter variáveis de ambiente com fallback
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Converter strings boolean
    if (strtolower($value) === 'true') return true;
    if (strtolower($value) === 'false') return false;
    if (strtolower($value) === 'null') return null;
    
    return $value;
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

// Configurações de segurança
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'avyahub'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'Avh#2025'));
define('APP_SECRET_KEY', env('APP_SECRET_KEY', 'change_this_secret_key'));

// Configurações de ambiente
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', true));

// Configurações de performance
define('ENABLE_CACHE', env('ENABLE_CACHE', true));
define('CACHE_DURATION', env('CACHE_DURATION', 3600)); // 1 hora

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'America/Sao_Paulo'));

// Configurações de erro baseadas no ambiente
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Configurações de sessão mais seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

?>