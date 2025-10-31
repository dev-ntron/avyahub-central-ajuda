<?php
session_start();
require_once __DIR__ . '/../config.php';

// Verificar se usuário está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Headers de segurança para API
header('Content-Type: application/json');
if (BASE_PATH !== '/') {
    header('Access-Control-Allow-Origin: ' . BASE_URL);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    try {
        // Verificar se é uma imagem
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            $response['error'] = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.';
            echo json_encode($response);
            exit;
        }
        
        // Verificar tamanho (máximo 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            $response['error'] = 'Arquivo muito grande (máximo 5MB)';
            echo json_encode($response);
            exit;
        }
        
        // Verificar se é realmente uma imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $response['error'] = 'Arquivo não é uma imagem válida';
            echo json_encode($response);
            exit;
        }
        
        // Criar diretório de uploads se não existir
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $response['error'] = 'Erro ao criar diretório de uploads';
                echo json_encode($response);
                exit;
            }
        }
        
        // Gerar nome único para o arquivo
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Verificar se o arquivo já existe (embora seja muito improvável com uniqid)
        if (file_exists($filepath)) {
            $filename = uniqid('img_alt_', true) . '.' . $extension;
            $filepath = $upload_dir . $filename;
        }
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Usar url() helper para BASE_PATH correto
            $fileUrl = url('/uploads/' . $filename);
            
            $response['success'] = true;
            $response['location'] = $fileUrl;
            $response['filename'] = $filename;
            $response['size'] = $file['size'];
            
            // Log de upload bem-sucedido
            error_log('Upload bem-sucedido: ' . $filename . ' por usuário ' . ($_SESSION['admin_username'] ?? 'desconhecido'));
        } else {
            $response['error'] = 'Erro ao fazer upload do arquivo';
        }
        
    } catch (Exception $e) {
        $response['error'] = 'Erro interno: ' . $e->getMessage();
        error_log('Erro no upload: ' . $e->getMessage());
    }
} else {
    $response['error'] = 'Método inválido ou nenhum arquivo enviado';
}

echo json_encode($response);
?>