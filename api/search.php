<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = createDatabaseConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Limitar tamanho da query para evitar abusos
if (mb_strlen($q) > 100) {
    $q = mb_substr($q, 0, 100);
}

$like = '%' . $q . '%';

try {
    $stmt = $pdo->prepare(
        "SELECT a.title, a.slug, a.excerpt, c.name AS category_name
         FROM articles a
         JOIN categories c ON a.category_id = c.id
         WHERE a.is_published = 1 AND (
             a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?
         )
         ORDER BY 
            CASE 
                WHEN a.title LIKE ? THEN 1
                WHEN a.excerpt LIKE ? THEN 2
                ELSE 3
            END,
            a.title ASC
         LIMIT 10"
    );
    $stmt->execute([$like, $like, $like, $like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Search failed']);
    }
}
