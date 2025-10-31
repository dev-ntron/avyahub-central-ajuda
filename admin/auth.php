<?php
// Autenticação baseada em banco de dados
session_start();

require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erro de conexão com banco de dados';
    exit;
}

// CSRF helpers
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting básico por IP (últimos 10 min, máx 10 tentativas)
function record_login_attempt($pdo, $username) {
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, username) VALUES (?, ?)');
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $username]);
}
function too_many_attempts($pdo, $username) {
    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM login_attempts WHERE (ip = ? OR username = ?) AND attempted_at >= (NOW() - INTERVAL 10 MINUTE)');
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $username]);
    $count = (int)$stmt->fetchColumn();
    return $count >= 10;
}

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /admin');
    exit;
}

// Se já logado, segue fluxo admin
if (!empty($_SESSION['user_id'])) {
    // Mantém compatibilidade com páginas existentes
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $_SESSION['username'];
    $_SESSION['login_time'] = $_SESSION['login_time'] ?? time();
    include __DIR__ . '/dashboard.php';
    exit;
}

// Tratamento do login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $login_error = 'Falha de validação CSRF';
    } elseif ($username === '' || $password === '') {
        $login_error = 'Preencha usuário e senha';
    } elseif (too_many_attempts($pdo, $username)) {
        $login_error = 'Muitas tentativas. Tente novamente em alguns minutos.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login OK
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['username'];

            // Atualiza last_login
            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
            
            // Limpa tentativas para o IP/usuário
            $pdo->prepare('DELETE FROM login_attempts WHERE (ip = ? OR username = ?)')->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $username]);
            
            // Auditoria
            $pdo->prepare('INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)')->execute([$user['id'], 'login_success', 'Usuário autenticado']);
            
            header('Location: /admin');
            exit;
        } else {
            record_login_attempt($pdo, $username);
            $pdo->prepare('INSERT INTO audit_log (action, details) VALUES (?, ?)')->execute(['login_failed', 'Tentativa falha para '.$username]);
            $login_error = 'Credenciais inválidas';
        }
    }
}

// Renderizar tela de login
$csrf_token = get_csrf_token();
include __DIR__ . '/login.php';
