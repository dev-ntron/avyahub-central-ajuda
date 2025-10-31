<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_article ? htmlspecialchars($current_article['title']) . ' - ' : '' ?><?= htmlspecialchars($settings['site_title']) ?></title>
    <meta name="description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <?php require_once __DIR__ . '/../config.php'; ?>
    <link rel="canonical" href="<?= url(trim($_SERVER['REQUEST_URI'] ?? '/', '/')) ?>">
    <meta property="og:url" content="<?= url(trim($_SERVER['REQUEST_URI'] ?? '/', '/')) ?>">
    <meta property="og:title" content="<?= $current_article ? htmlspecialchars($current_article['title']) : htmlspecialchars($settings['site_title']) ?>">
    <meta property="og:description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <?php if (!empty($settings['logo_url'])): ?>
    <meta property="og:image" content="<?= url(ltrim($settings['logo_url'],'/')) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/site.css">
</head>
<body>
    <div class="container">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?= BASE_PATH ?>/" class="logo">
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
                        <a href="<?= BASE_PATH . '/' . $article['slug'] ?>" class="nav-article <?= $current_article && $current_article['id'] == $article['id'] ? 'active' : '' ?>">
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
                    <a href="<?= BASE_PATH ?>/">Início</a>
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
                        <h3><a href="<?= BASE_PATH . '/' . $result['slug'] ?>" style="color: var(--primary-color); text-decoration: none;"><?= htmlspecialchars($result['title']) ?></a></h3>
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
    <script src="<?= BASE_PATH ?>/assets/site.js"></script>
</body>
</html>
