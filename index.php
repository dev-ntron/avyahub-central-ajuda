<?php
session_start();

$env_path = __DIR__ . '/.env';
$install_flag = __DIR__ . '/install/.installed';

// Se não instalado, redireciona para instalador
if (!file_exists($env_path) || !file_exists($install_flag)) {
    require_once __DIR__ . '/config.php';
    header('Location: ' . url('install/'));
    exit;
}

require_once __DIR__ . '/config.php';

// Conexão ao banco
try { $pdo = createDatabaseConnection(); }
catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('[DB-ERROR] ' . $e->getMessage());
        echo "<div style='background:#fee2e2;border:1px solid #f87171;padding:1rem;border-radius:8px;margin:2rem;font-family:monospace;'>";
        echo "<h3 style='color:#dc2626;margin:0 0 1rem 0;'>❌ Erro de Conexão</h3>";
        echo "<p style='margin:0 0 1rem 0;color:#7f1d1d;'>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p style='margin:0;'><a href='" . url('install/') . "' style='color:#dc2626;font-weight:bold;'>→ Ir ao Instalador</a></p>";
        echo "</div>";
    } else { header('Location: ' . url('install/')); }
    exit;
}

// Resolver caminho relativo ao BASE_PATH
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = (defined('BASE_PATH') ? BASE_PATH : '/');
if ($base !== '/' && strpos($uri, $base) === 0) { $uri = substr($uri, strlen($base)); }
$uri = '/' . ltrim($uri, '/');

// Rota do instalador e admin (por segurança)
if ($uri === '/install' || strpos($uri, '/install/') === 0) { include __DIR__ . '/install/index.php'; exit; }
if ($uri === '/admin' || strpos($uri, '/admin/') === 0) { include __DIR__ . '/admin/index.php'; exit; }

// Carregar configurações do site
$settings = [
    'site_title' => 'Central de Ajuda',
    'site_description' => 'Documentação da plataforma',
    'primary_color' => '#2563eb',
    'secondary_color' => '#64748b',
    'logo_url' => '',
    'favicon_url' => '',
    'footer_text' => ''
];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $settings[$row['setting_key']] = $row['setting_value']; }
} catch (Throwable $e) { /* ignore */ }

// Estruturas para o template
$current_category = null;
$current_article = null;
$categories_with_articles = [];
$search_results = null; $search_query = '';

// Sidebar: categorias + artigos
try {
    $cats = $pdo->query("SELECT id, name, slug FROM categories ORDER BY order_position, name")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cats as $cat) {
        $stmt = $pdo->prepare("SELECT id, title, slug FROM articles WHERE category_id=? AND is_published=1 ORDER BY order_position, title");
        $stmt->execute([$cat['id']]);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categories_with_articles[] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'slug' => $cat['slug'],
            'articles' => $articles,
        ];
    }
} catch (Throwable $e) { /* ignore */ }

// Roteamento simples
// / -> home
// /categoria/{slug}
// /{article-slug}
// ?q=busca
if (isset($_GET['q'])) {
    $search_query = trim((string)$_GET['q']);
    if ($search_query !== '' && mb_strlen($search_query) >= 2) {
        $like = '%' . $search_query . '%';
        $stmt = $pdo->prepare("SELECT a.id, a.title, a.slug, a.excerpt, c.name AS category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.is_published=1 AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?) ORDER BY a.title LIMIT 50");
        $stmt->execute([$like, $like, $like]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else { $search_results = []; }
}

$parts = array_values(array_filter(explode('/', $uri)));
if (!empty($parts)) {
    if ($parts[0] === 'categoria' && !empty($parts[1])) {
        $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE slug=? LIMIT 1");
        $stmt->execute([$parts[1]]);
        $current_category = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        $stmt = $pdo->prepare("SELECT a.*, c.name AS category_name, c.id AS category_id FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.slug=? AND a.is_published=1 LIMIT 1");
        $stmt->execute([$parts[0]]);
        $current_article = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($current_article) { $current_category = ['id'=>$current_article['category_id'],'name'=>$current_article['category_name']]; }
    }
}

// Renderizar
include __DIR__ . '/public/index.php';
exit;
