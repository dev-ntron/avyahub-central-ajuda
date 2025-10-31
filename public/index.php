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

// Obter categorias e artigos
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY order_position ASC");
    return $stmt->fetchAll();
}

function getCategoryArticles($pdo, $category_id) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE category_id = ? AND is_published = 1 ORDER BY order_position ASC");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

function getArticleBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT a.*, c.name as category_name, c.slug as category_slug FROM articles a JOIN categories c ON a.category_id = c.id WHERE a.slug = ? AND a.is_published = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function searchContent($pdo, $query) {
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug 
        FROM articles a 
        JOIN categories c ON a.category_id = c.id 
        WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?) 
        AND a.is_published = 1 
        ORDER BY 
            CASE 
                WHEN a.title LIKE ? THEN 1
                WHEN a.excerpt LIKE ? THEN 2
                ELSE 3
            END,
            a.title ASC
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll();
}

$settings = getSiteSettings($pdo);
$categories = getCategories($pdo);

// Roteamento simples
$path = $_GET['path'] ?? '';
$search_query = $_GET['search'] ?? '';

if (!empty($search_query)) {
    $search_results = searchContent($pdo, $search_query);
}

$current_article = null;
$current_category = null;

if (!empty($path)) {
    $current_article = getArticleBySlug($pdo, $path);
    if ($current_article) {
        // Buscar categoria atual
        foreach ($categories as $cat) {
            if ($cat['id'] == $current_article['category_id']) {
                $current_category = $cat;
                break;
            }
        }
    }
}

// Organizar categorias com seus artigos
$categories_with_articles = [];
foreach ($categories as $category) {
    $category['articles'] = getCategoryArticles($pdo, $category['id']);
    $categories_with_articles[] = $category;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_article ? htmlspecialchars($current_article['title']) . ' - ' : '' ?><?= htmlspecialchars($settings['site_title']) ?></title>
    <meta name="description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    
    <!-- Favicon dinâmico -->
    <?php if (!empty($settings['favicon_url'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($settings['favicon_url']) ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <?php endif; ?>
    
    <!-- SEO Meta Tags -->
    <meta name="robots" content="index, follow">
    <meta name="author" content="AvyaHub">
    <link rel="canonical" href="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $current_article ? htmlspecialchars($current_article['title']) : htmlspecialchars($settings['site_title']) ?>">
    <meta property="og:description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <meta property="og:type" content="<?= $current_article ? 'article' : 'website' ?>">
    <meta property="og:url" content="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <?php if (!empty($settings['logo_url'])): ?>
    <meta property="og:image" content="<?= 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . htmlspecialchars($settings['logo_url']) ?>">
    <?php endif; ?>
    
    <style>
        :root { --primary-color: <?= $settings['primary_color'] ?>; --secondary-color: <?= $settings['secondary_color'] ?>; --bg-light: #ffffff; --bg-dark: #1a1a1a; --text-light: #333333; --text-dark: #ffffff; --border-light: #e5e7eb; --border-dark: #374151; --sidebar-bg-light: #f9fafb; --sidebar-bg-dark: #111827; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: var(--text-light); background: var(--bg-light); transition: all 0.3s ease; }
        body.dark { color: var(--text-dark); background: var(--bg-dark); }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 300px; background: var(--sidebar-bg-light); border-right: 1px solid var(--border-light); overflow-y: auto; position: fixed; height: 100vh; z-index: 1000; transition: transform 0.3s ease; }
        body.dark .sidebar { background: var(--sidebar-bg-dark); border-right-color: var(--border-dark); }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--border-light); }
        body.dark .sidebar-header { border-bottom-color: var(--border-dark); }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--primary-color); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .logo img { max-height: 40px; max-width: 200px; object-fit: contain; }
        .search-box { margin: 1rem; position: relative; }
        .search-input { width: 100%; padding: 0.75rem; border: 1px solid var(--border-light); border-radius: 8px; background: var(--bg-light); color: var(--text-light); font-size: 0.9rem; }
        body.dark .search-input { border-color: var(--border-dark); background: var(--bg-dark); color: var(--text-dark); }
        .search-results { position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-light); border: 1px solid var(--border-light); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1001; display: none; }
        body.dark .search-results { background: var(--bg-dark); border-color: var(--border-dark); }
        .search-result-item { padding: 0.75rem; border-bottom: 1px solid var(--border-light); cursor: pointer; transition: background-color 0.2s; }
        .search-result-item:hover { background: rgba(37, 99, 235, 0.1); }
        body.dark .search-result-item { border-bottom-color: var(--border-dark); }
        .nav-menu { padding: 1rem 0; }
        .nav-category { margin-bottom: 1rem; }
        .nav-category-title { padding: 0.5rem 1.5rem; font-weight: 600; font-size: 0.9rem; color: var(--secondary-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .nav-articles { margin-left: 1rem; }
        .nav-article { display: block; padding: 0.5rem 1.5rem; color: var(--text-light); text-decoration: none; font-size: 0.9rem; transition: all 0.2s; border-left: 3px solid transparent; }
        body.dark .nav-article { color: var(--text-dark); }
        .nav-article:hover, .nav-article.active { background: rgba(37, 99, 235, 0.1); border-left-color: var(--primary-color); color: var(--primary-color); }
        .main-content { margin-left: 300px; flex: 1; padding: 2rem; max-width: calc(100vw - 300px); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-light); }
        body.dark .top-bar { border-bottom-color: var(--border-dark); }
        .breadcrumbs { font-size: 0.9rem; color: var(--secondary-color); }
        .breadcrumbs a { color: var(--primary-color); text-decoration: none; }
        .dark-toggle { background: none; border: 1px solid var(--border-light); padding: 0.5rem; border-radius: 6px; cursor: pointer; color: var(--text-light); transition: all 0.2s; }
        body.dark .dark-toggle { border-color: var(--border-dark); color: var(--text-dark); }
        .dark-toggle:hover { background: var(--primary-color); color: white; }
        .article-content { max-width: 800px; }
        .article-header { margin-bottom: 2rem; }
        .article-title { font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem; color: var(--text-light); }
        body.dark .article-title { color: var(--text-dark); }
        .article-excerpt { font-size: 1.1rem; color: var(--secondary-color); margin-bottom: 1rem; }
        .article-body { font-size: 1rem; line-height: 1.7; }
        .article-body h1, .article-body h2, .article-body h3 { margin: 2rem 0 1rem 0; color: var(--text-light); }
        body.dark .article-body h1, body.dark .article-body h2, body.dark .article-body h3 { color: var(--text-dark); }
        .article-body p { margin-bottom: 1rem; }
        .article-body ul, .article-body ol { margin-left: 2rem; margin-bottom: 1rem; }
        .article-body code { background: rgba(0,0,0,0.1); padding: 0.2rem 0.4rem; border-radius: 4px; font-family: 'Courier New', monospace; }
        .article-body pre { background: rgba(0,0,0,0.1); padding: 1rem; border-radius: 8px; overflow-x: auto; margin: 1rem 0; }
        .home-content { text-align: center; max-width: 600px; margin: 4rem auto; }
        .home-title { font-size: 3rem; font-weight: bold; margin-bottom: 1rem; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .home-description { font-size: 1.2rem; color: var(--secondary-color); margin-bottom: 2rem; }
        .mobile-menu-btn { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 1002; background: var(--primary-color); color: white; border: none; padding: 0.75rem; border-radius: 8px; cursor: pointer; }
        .footer { margin-top: 4rem; padding: 2rem 0; border-top: 1px solid var(--border-light); text-align: center; color: var(--secondary-color); font-size: 0.9rem; }
        body.dark .footer { border-top-color: var(--border-dark); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } .main-content { margin-left: 0; max-width: 100vw; padding: 1rem; } .mobile-menu-btn { display: block; } .home-title { font-size: 2rem; } }
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="container">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="/" class="logo">
                    <?php if (!empty($settings['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($settings['logo_url']) ?>" alt="<?= htmlspecialchars($settings['site_title']) ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($settings['site_title']) ?>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Buscar artigos..." onkeyup="performSearch(this.value)" autocomplete="off">
                <div class="search-results" id="searchResults"></div>
            </div>
            
            <nav class="nav-menu">
                <?php foreach ($categories_with_articles as $category): ?>
                <div class="nav-category">
                    <div class="nav-category-title" onclick="toggleCategory(this)">
                        <span><?= htmlspecialchars($category['name']) ?></span>
                        <span>▼</span>
                    </div>
                    <div class="nav-articles">
                        <?php foreach ($category['articles'] as $article): ?>
                        <a href="/<?= $article['slug'] ?>" class="nav-article <?= $current_article && $current_article['id'] == $article['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($article['title']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="breadcrumbs">
                    <a href="/">Início</a>
                    <?php if ($current_category): ?> › <?= htmlspecialchars($current_category['name']) ?> <?php endif; ?>
                    <?php if ($current_article): ?> › <?= htmlspecialchars($current_article['title']) ?> <?php endif; ?>
                </div>
                <button class="dark-toggle" onclick="toggleDarkMode()" title="Alternar modo escuro">🌙</button>
            </div>
            
            <?php if (isset($search_results)): ?>
                <div class="search-page fade-in">
                    <h1>Resultados da busca: "<?= htmlspecialchars($search_query) ?>"</h1>
                    <p><?= count($search_results) ?> resultado(s) encontrado(s)</p>
                    <?php foreach ($search_results as $result): ?>
                    <div style="border-bottom: 1px solid var(--border-light); padding: 1rem 0;">
                        <h3><a href="/<?= $result['slug'] ?>" style="color: var(--primary-color); text-decoration: none;"><?= htmlspecialchars($result['title']) ?></a></h3>
                        <p style="color: var(--secondary-color); font-size: 0.9rem;">em <?= htmlspecialchars($result['category_name']) ?></p>
                        <p><?= htmlspecialchars($result['excerpt']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($current_article): ?>
                <article class="article-content fade-in">
                    <header class="article-header">
                        <h1 class="article-title"><?= htmlspecialchars($current_article['title']) ?></h1>
                        <?php if ($current_article['excerpt']): ?>
                        <p class="article-excerpt"><?= htmlspecialchars($current_article['excerpt']) ?></p>
                        <?php endif; ?>
                    </header>
                    <div class="article-body"><?= $current_article['content'] ?></div>
                </article>
            <?php else: ?>
                <div class="home-content fade-in">
                    <h1 class="home-title"><?= htmlspecialchars($settings['site_title']) ?></h1>
                    <p class="home-description"><?= htmlspecialchars($settings['site_description']) ?></p>
                    <p>Selecione um artigo na barra lateral ou use a busca para encontrar o que precisa.</p>
                </div>
            <?php endif; ?>
            
            <footer class="footer">
                <p><?= htmlspecialchars($settings['footer_text'] ?? '') ?></p>
            </footer>
        </main>
    </div>
    
    <script>
        function toggleDarkMode() { document.body.classList.toggle('dark'); localStorage.setItem('darkMode', document.body.classList.contains('dark')); }
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        function toggleCategory(element) { const articles = element.nextElementSibling; const arrow = element.querySelector('span:last-child'); if (articles.style.display === 'none') { articles.style.display = 'block'; arrow.textContent = '▼'; } else { articles.style.display = 'none'; arrow.textContent = '▶'; } }
        let searchTimeout;
        function performSearch(query) { clearTimeout(searchTimeout); const resultsDiv = document.getElementById('searchResults'); if (query.length < 2) { resultsDiv.style.display = 'none'; return; } searchTimeout = setTimeout(() => { fetch(`/api/search.php?q=${encodeURIComponent(query)}`).then(response => response.json()).then(results => { if (results.length > 0) { resultsDiv.innerHTML = results.map(result => `<div class="search-result-item" onclick="window.location.href='/${result.slug}'"><strong>${result.title}</strong><br><small style="color: var(--secondary-color);">${result.category_name}</small></div>`).join(''); resultsDiv.style.display = 'block'; } else { resultsDiv.innerHTML = '<div class="search-result-item">Nenhum resultado encontrado</div>'; resultsDiv.style.display = 'block'; } }); }, 300); }
        document.addEventListener('click', function(e) { const searchBox = document.querySelector('.search-box'); const resultsDiv = document.getElementById('searchResults'); if (!searchBox.contains(e.target)) { resultsDiv.style.display = 'none'; } });
        if (localStorage.getItem('darkMode') === 'true') { document.body.classList.add('dark'); }
        document.addEventListener('keydown', function(e) { if (e.ctrlKey && e.key === '/') { e.preventDefault(); document.querySelector('.search-input').focus(); } });
    </script>
</body>
</html>