<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $current_article ? htmlspecialchars($current_article['title']) . ' - ' : '' ?><?= htmlspecialchars($settings['site_title']) ?></title>
    <meta name="description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <link rel="canonical" href="<?= url(trim($_SERVER['REQUEST_URI'] ?? '/', '/')) ?>">
    <meta property="og:url" content="<?= url(trim($_SERVER['REQUEST_URI'] ?? '/', '/')) ?>">
    <meta property="og:title" content="<?= $current_article ? htmlspecialchars($current_article['title']) : htmlspecialchars($settings['site_title']) ?>">
    <meta property="og:description" content="<?= $current_article ? htmlspecialchars($current_article['excerpt']) : htmlspecialchars($settings['site_description']) ?>">
    <?php if (!empty($settings['logo_url'])): ?>
    <meta property="og:image" content="<?= url(ltrim($settings['logo_url'],'/')) ?>">
    <?php endif; ?>
    <?php if (!empty($settings['favicon_url'])): ?>
    <link rel="icon" href="<?= htmlspecialchars($settings['favicon_url']) ?>">
    <?php endif; ?>
    <style>:root{--primary-color:<?= htmlspecialchars($settings['primary_color'] ?? '#2563eb') ?>;--secondary-color:<?= htmlspecialchars($settings['secondary_color'] ?? '#64748b') ?>}</style>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/site.css">
    <script>window.BASE_PATH='<?= BASE_PATH ?>';</script>
</head>
<body>
    <div class="overlay"></div>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
    <div class="container">
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
                <input type="text" class="search-input" placeholder="Buscar artigos..." autocomplete="off">
                <div class="search-results" id="searchResults"></div>
            </div>
            <nav class="nav-menu">
                <?php foreach ($categories_with_articles as $category): ?>
                <div class="nav-category" data-slug="<?= htmlspecialchars($category['slug']) ?>">
                    <div class="nav-category-title" onclick="toggleCategory(this)">
                        <span><?= htmlspecialchars($category['name']) ?></span>
                        <span class="icon">▼</span>
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
            <div class="content-wrapper">
                <div class="top-bar">
                    <div class="breadcrumbs">
                        <a href="<?= BASE_PATH ?>/">Início</a>
                        <?php if ($current_category): ?> <span class="breadcrumb-separator">›</span> <span><?= htmlspecialchars($current_category['name']) ?></span> <?php endif; ?>
                        <?php if ($current_article): ?> <span class="breadcrumb-separator">›</span> <span><?= htmlspecialchars($current_article['title']) ?></span> <?php endif; ?>
                    </div>
                    <div class="controls">
                        <button class="dark-toggle" onclick="toggleDarkMode()" title="Alternar modo escuro">🌙</button>
                    </div>
                </div>
                <?php if (isset($search_results)): ?>
                    <div class="search-page fade-in">
                        <h1>Resultados da busca: "<?= htmlspecialchars($search_query) ?>"</h1>
                        <p><?= count($search_results) ?> resultado(s) encontrado(s)</p>
                        <?php foreach ($search_results as $result): ?>
                        <div class="search-result-card">
                            <h3><a href="<?= BASE_PATH . '/' . $result['slug'] ?>"><?= htmlspecialchars($result['title']) ?></a></h3>
                            <p class="result-meta">📁 <?= htmlspecialchars($result['category_name']) ?></p>
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
                            <div class="article-actions">
                                <button class="action-btn" onclick="copyCurrentLink()">🔗 Copiar Link</button>
                                <a href="https://wa.me/?text=<?= urlencode($current_article['title'] . ' - ' . url($_SERVER['REQUEST_URI'] ?? '/')) ?>" target="_blank" class="action-btn">📱 WhatsApp</a>
                                <a href="https://t.me/share/url?url=<?= urlencode(url($_SERVER['REQUEST_URI'] ?? '/')) ?>&text=<?= urlencode($current_article['title']) ?>" target="_blank" class="action-btn">✈️ Telegram</a>
                            </div>
                        </header>
                        <div class="article-body"><?= $current_article['content'] ?></div>
                    </article>
                <?php else: ?>
                    <div class="home-content fade-in">
                        <h1 class="home-title"><?= htmlspecialchars($settings['site_title']) ?></h1>
                        <p class="home-description"><?= htmlspecialchars($settings['site_description']) ?></p>
                        <div class="home-cta">
                            <h3>🔍 Como Encontrar as Informações</h3>
                            <p>Existem duas formas principais de localizar o conteúdo:</p>
                            <div class="info-methods">
                                <div class="method">
                                    <h4>🔍 Barra de Busca</h4>
                                    <p>Utilize a barra de busca no topo esquerdo com termos-chave.</p>
                                </div>
                                <div class="method">
                                    <h4>📂 Navegação por Categorias</h4>
                                    <p>Navegue pelo menu lateral. Cada categoria reúne artigos relacionados.</p>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($categories_with_articles)): ?>
                        <section class="categories-overview">
                            <h2>Categorias Disponíveis</h2>
                            <div class="categories-grid">
                                <?php foreach ($categories_with_articles as $category): ?>
                                    <?php if (!empty($category['articles'])): ?>
                                    <article class="category-card">
                                        <header>
                                            <h3><a href="<?= BASE_PATH ?>/categoria/<?= $category['slug'] ?>"><?= htmlspecialchars($category['name']) ?></a></h3>
                                            <p class="category-meta"><?= count($category['articles']) ?> artigo(s)</p>
                                        </header>
                                        <div class="category-articles">
                                            <?php foreach (array_slice($category['articles'], 0, 3) as $article): ?>
                                            <a href="<?= BASE_PATH . '/' . $article['slug'] ?>" class="article-tag"><?= htmlspecialchars($article['title']) ?></a>
                                            <?php endforeach; ?>
                                            <?php if (count($category['articles']) > 3): ?>
                                            <span class="more-articles">+<?= count($category['articles']) - 3 ?> mais</span>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <footer class="footer">
                <p><?= htmlspecialchars($settings['footer_text'] ?? '') ?></p>
            </footer>
        </main>
    </div>
    <script src="<?= BASE_PATH ?>/assets/site.js"></script>
    <script>function copyCurrentLink(){if(navigator.clipboard){navigator.clipboard.writeText(window.location.href).then(()=>alert('Link copiado!')).catch(()=>fallbackCopy())}else fallbackCopy();function fallbackCopy(){const t=document.createElement('textarea');t.value=window.location.href;document.body.appendChild(t);t.select();try{document.execCommand('copy');alert('Link copiado!')}catch(e){alert('Erro ao copiar')}document.body.removeChild(t)}}</script>
</body>
</html>