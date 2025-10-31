<?php
session_start();

require_once __DIR__ . '/../config.php';

// Se já existe auth por banco, delega para auth.php
if (!isset($_SESSION['user_id']) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    include __DIR__ . '/auth.php';
    exit;
}

// Verificar timeout de sessão (2 horas)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    header('Location: /admin');
    exit;
}

// Roteamento simples admin
$admin_path = trim(str_replace('/admin', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

switch ($admin_path) {
    case '':
    case 'dashboard':
        include __DIR__ . '/dashboard.php';
        break;
    case 'categories':
        include __DIR__ . '/categories.php';
        break;
    case 'articles':
        include __DIR__ . '/articles.php';
        break;
    case 'media':
        include __DIR__ . '/media.php';
        break;
    case 'settings':
        include __DIR__ . '/settings.php';
        break;
    case 'check':
        include __DIR__ . '/check.php';
        break;
    case 'logout':
        include __DIR__ . '/auth.php?action=logout';
        break;
    default:
        include __DIR__ . '/dashboard.php';
        break;
}
