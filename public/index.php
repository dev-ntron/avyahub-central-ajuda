<?php
// Incluir configurações se não foram carregadas
if (!defined('DB_HOST')) {
    require_once '../config.php';
}

// Obter configurações do site
function getSiteSettings($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
// ... conteúdo original mantido ...
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_article ? htmlspecialchars($current_article['title']) . ' - ' : '' ?><?= htmlspecialchars($settings['site_title']) ?></title>
    <meta name="description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <?php if (!empty($settings['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($settings['favicon_url']) ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">;
    <?php endif; ?>
    <meta name="robots" content="index, follow">
    <meta name="author" content="AvyaHub">
    <link rel="canonical" href="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= $current_article ? htmlspecialchars($current_article['title']) : htmlspecialchars($settings['site_title']) ?>">
    <meta property="og:description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <meta property="og:type" content="<?= $current_article ? 'article' : 'website' ?>">
    <meta property="og:url" content="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <?php if (!empty($settings['logo_url'])): ?>
    <meta property="og:image" content="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . htmlspecialchars($settings['logo_url']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
    <div class="container">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
        <!-- ...restante do HTML... -->
    </div>
    <script src="/assets/site.js"></script>
</body>
</html>
