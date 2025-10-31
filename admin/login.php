<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin AvyaHub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; }
        .login-container { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-title { font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 0.5rem; }
        .login-subtitle { color: #666; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
        .form-input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        .form-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .login-btn { width: 100%; padding: 0.75rem; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background-color 0.3s; }
        .login-btn:hover { background: #1d4ed8; }
        .error-message { background: #fee2e2; color: #dc2626; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }
        .logo { width: 60px; height: 60px; background: #2563eb; border-radius: 12px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.5rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">AH</div>
            <h1 class="login-title">Painel Administrativo</h1>
            <p class="login-subtitle">Central de Ajuda AvyaHub</p>
        </div>
        
        <?php if (isset($login_error)): ?>
        <div class="error-message">
            <?= htmlspecialchars($login_error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Usuário</label>
                <input type="text" id="username" name="username" class="form-input" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Senha</label>
                <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">Entrar</button>
        </form>
    </div>
</body>
</html>