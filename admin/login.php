<?php
require_once __DIR__ . '/../config.php';
session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function check_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !check_csrf($_POST['csrf_token'])) {
        $error = 'Falha de validação CSRF';
    } else {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        if ($user === '' || $pass === '') {
            $error = 'Usuário ou senha inválidos';
        } else {
            try {
                $pdo = createDatabaseConnection();
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
                $stmt->execute([$user]);
                $row = $stmt->fetch();
                if (!$row || !password_verify($pass, $row['password_hash'])) {
                    $error = 'Usuário ou senha inválidos';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    unset($_SESSION['csrf_token']);
                    header('Location: ' . BASE_PATH . '/admin');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Erro ao conectar ao banco';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/site.css">
    <style>body{background:linear-gradient(135deg,#8093f1 0%,#a3cef1 100%);}</style>
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;">
        <form method="post" style="background:white;max-width:360px;width:100%;padding:2.5rem 1.5rem;border-radius:16px;box-shadow:0 6px 32px 0 #2222a052;display:flex;flex-direction:column;gap:1.5rem;align-items:center;">
            <div style="margin-bottom:1rem;text-align:center;">
                <div style="font-size:2.4rem;font-weight:700;margin-bottom:.3rem;background:#2563eb;width:64px;height:64px;border-radius:12px;color:white;display:flex;align-items:center;justify-content:center;">AH</div>
                <h2 style="margin:0;font-weight:700;">Painel Administrativo</h2>
                <span style="color:#666;font-size:.94rem">Central de Ajuda AvyaHub</span>
            </div>
            <?php if ($error): ?>
                <div style="background:#fee2e2;color:#c53030;padding:.8rem 1rem;border-radius:8px;text-align:center;font-size:.98rem;"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <div style="width:100%">
                <input style="width:100%" required name="username" autocomplete="username" placeholder="Usuário" class="form-input">
            </div>
            <div style="width:100%">
                <input style="width:100%" required type="password" name="password" autocomplete="current-password" placeholder="Senha" class="form-input">
            </div>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1.1rem;padding:.7rem 0;">Entrar</button>
        </form>
    </div>
</body>
</html>