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

    <link rel="stylesheet" href="/assets/site.css">
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
    
    <script src="/assets/site.js"></script>
</body>
</html>
