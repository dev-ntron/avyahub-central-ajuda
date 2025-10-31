<?php
function getSiteSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

$settings = getSiteSettings($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin' ?> - AvyaHub Admin</title>
    
    <!-- Favicon dinâmico -->
    <?php if (!empty($settings['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($settings['favicon_url']) ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <?php endif; ?>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1a202c; }
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2d3748; color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .admin-sidebar-header { padding: 1.5rem; border-bottom: 1px solid #4a5568; display: flex; align-items: center; gap: 0.75rem; }
        .admin-logo { font-size: 1.2rem; font-weight: bold; color: #63b3ed; display: flex; align-items: center; gap: 0.5rem; }
        .admin-logo img { max-height: 32px; max-width: 120px; object-fit: contain; }
        .admin-nav { padding: 1rem 0; }
        .admin-nav-item { display: block; padding: 0.75rem 1.5rem; color: #e2e8f0; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .admin-nav-item:hover, .admin-nav-item.active { background: #4a5568; border-left-color: #63b3ed; color: #63b3ed; }
        .admin-main { margin-left: 250px; flex: 1; padding: 2rem; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
        .admin-title { font-size: 1.8rem; font-weight: bold; color: #2d3748; }
        .admin-user { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e53e3e; color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: background-color 0.2s; }
        .logout-btn:hover { background: #c53030; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3182ce; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; transition: background-color 0.2s; }
        .btn:hover { background: #2c5282; }
        .btn-success { background: #38a169; } .btn-success:hover { background: #2f855a; }
        .btn-danger { background: #e53e3e; } .btn-danger:hover { background: #c53030; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3748; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; }
        .form-textarea { min-height: 100px; resize: vertical; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }
        .table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f7fafc; font-weight: 600; color: #2d3748; }
        .table tbody tr:hover { background: #f7fafc; }
        .alert { padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 6px; }
        .alert-success { background: #f0fff4; color: #2f855a; border: 1px solid #9ae6b4; }
        .alert-danger { background: #fed7d7; color: #c53030; border: 1px solid #f56565; }
        @media (max-width: 768px) { .admin-sidebar { transform: translateX(-100%); z-index: 1000; } .admin-main { margin-left: 0; padding: 1rem; } }
    </style>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <div class="admin-logo">
                    <?php if (!empty($settings['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="AvyaHub">
                    <?php else: ?>
                        <span>🏠</span><span>AvyaHub Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="admin-nav">
                <a href="<?= url('/admin') ?>" class="admin-nav-item <?= ($_SERVER['REQUEST_URI'] === BASE_PATH . '/admin' || $_SERVER['REQUEST_URI'] === BASE_PATH . '/admin/' || $_SERVER['REQUEST_URI'] === BASE_PATH . '/admin/dashboard' || $_SERVER['REQUEST_URI'] === BASE_PATH . '/admin/dashboard.php') ? 'active' : '' ?>">📊 Dashboard</a>
                <a href="<?= url('/admin/categories') ?>" class="admin-nav-item <?= strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/admin/categories') === 0 ? 'active' : '' ?>">📁 Categorias</a>
                <a href="<?= url('/admin/articles') ?>" class="admin-nav-item <?= strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/admin/articles') === 0 ? 'active' : '' ?>">📝 Artigos</a>
                <a href="<?= url('/admin/media') ?>" class="admin-nav-item <?= strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/admin/media') === 0 ? 'active' : '' ?>">🖼️ Mídia</a>
                <a href="<?= url('/admin/settings') ?>" class="admin-nav-item <?= strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/admin/settings') === 0 ? 'active' : '' ?>">⚙️ Configurações</a>
                <a href="<?= url('/admin/check') ?>" class="admin-nav-item <?= strpos($_SERVER['REQUEST_URI'], BASE_PATH . '/admin/check') === 0 ? 'active' : '' ?>">🔍 Verificações</a>
                <a href="<?= url('/admin?action=logout') ?>" class="admin-nav-item">🚪 Sair</a>
            </nav>
        </aside>
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title"><?= $page_title ?? 'Dashboard' ?></h1>
                <div class="admin-user">
                    <span>Olá, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="<?= url('/admin?action=logout') ?>" class="logout-btn">Sair</a>
                </div>
            </div>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); endif; ?>
            <?= $content ?? '' ?>
        </main>
    </div>
    <script>
        if (document.querySelector('textarea[name="content"]')) {
            tinymce.init({
                selector: 'textarea[name="content"]', height: 500, menubar: true,
                plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount', 'codesample'],
                toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help | code | codesample | link image media | table',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px }',
                paste_data_images: true, automatic_uploads: true, file_picker_types: 'image', images_upload_url: '<?= url('/admin/upload') ?>',
                setup: function (editor) { editor.on('change', function () { editor.save(); }); }
            });
        }
    </script>
</body>
</html>