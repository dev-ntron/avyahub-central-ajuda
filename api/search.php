<?php
require_once __DIR__ . '/../config.php';

// Headers de segurança para API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// CORS para BASE_PATH
if (defined('BASE_URL')) {
    header('Access-Control-Allow-Origin: ' . BASE_URL);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = createDatabaseConnection();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com banco de dados'], JSON_UNESCAPED_UNICODE);
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
        "SELECT 
            a.title, 
            a.slug, 
            a.excerpt, 
            c.name AS category_name,
            c.slug AS category_slug
         FROM articles a
         LEFT JOIN categories c ON a.category_id = c.id
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
    
    // Adicionar URLs completas com BASE_PATH
    foreach ($results as &$result) {
        $result['url'] = url('/' . $result['slug']);
        $result['category_url'] = $result['category_slug'] ? url('/categoria/' . $result['category_slug']) : null;
        
        // Limpar excerpt se muito longo
        if (!empty($result['excerpt']) && mb_strlen($result['excerpt']) > 150) {
            $result['excerpt'] = mb_substr($result['excerpt'], 0, 150) . '...';
        }
    }
    
    echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Throwable $e) {
    http_response_code(500);
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo json_encode(['error' => 'Erro de busca: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => 'Erro interno de busca'], JSON_UNESCAPED_UNICODE);
    }
}
?>