<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../index.php';

if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'];
$searchTerm = '%' . $query . '%';

$stmt = $pdo->prepare("
    SELECT a.title, a.slug, a.excerpt, c.name as category_name 
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
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>