<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Verificar se é uma imagem
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $response['error'] = 'Tipo de arquivo não permitido';
        echo json_encode($response);
        exit;
    }
    
    // Verificar tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $response['error'] = 'Arquivo muito grande (máximo 5MB)';
        echo json_encode($response);
        exit;
    }
    
    // Criar diretório de uploads se não existir
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Gerar nome único para o arquivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $response['success'] = true;
        $response['location'] = '/uploads/' . $filename;
    } else {
        $response['error'] = 'Erro ao fazer upload do arquivo';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>