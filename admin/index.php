<?php
session_start();

// Incluir configurações se não foram carregadas
if (!defined('ADMIN_USERNAME')) {
    require_once '../config.php';
}

// Verificar se é acesso ao admin
$admin_path = str_replace('/admin', '', $_SERVER['REQUEST_URI']);
$admin_path = trim($admin_path, '/');

// Login com credenciais das variáveis de ambiente
if (!isset($_SESSION['admin_logged_in']) && $admin_path !== 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $_POST['username'];
            $_SESSION['login_time'] = time();
            header('Location: /admin');
            exit;
        } else {
            $login_error = 'Credenciais inválidas';
        }
    }
    
    include 'login.php';
    exit;
}

// Verificar timeout de sessão (2 horas)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    header('Location: /admin');
    exit;
}

// Logout
if ($admin_path === 'logout') {
    session_destroy();
    header('Location: /admin');
    exit;
}

// Incluir arquivo baseado na rota
switch ($admin_path) {
    case '':
    case 'dashboard':
        include 'dashboard.php';
        break;
    case 'categories':
        include 'categories.php';
        break;
    case 'articles':
        include 'articles.php';
        break;
    case 'media':
        include 'media.php';
        break;
    case 'settings':
        include 'settings.php';
        break;
    case 'api':
        include 'api.php';
        break;
    default:
        include 'dashboard.php';
        break;
}
?>