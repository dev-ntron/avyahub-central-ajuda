<?php
session_start();

// Incluir configurações
require_once 'config.php';

// Conectar ao banco
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Tentar criar o banco se não existir
    try {
        $pdo_temp = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar tabelas
        include_once 'install/database.php';
        createTables($pdo);
    } catch(PDOException $e2) {
        if (APP_DEBUG) {
            die("Erro de conexão: " . $e2->getMessage());
        } else {
            die("Erro de conexão com o banco de dados. Verifique as configurações.");
        }
    }
}

// Router simples
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');

// Rotas
if ($path === '/admin' || strpos($path, '/admin') === 0) {
    include 'admin/index.php';
} else {
    include 'public/index.php';
}
?>