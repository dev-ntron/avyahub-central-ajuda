<?php
/**
 * Migrations completas: usuários, tentativas de login, auditoria e mídia
 * Execute este arquivo após a instalação básica
 */

require_once __DIR__ . '/../config.php';

function runCompleteMigrations() {
    try {
        $pdo = createDatabaseConnection();
        
        echo "<h2>Executando Migrations Completas...</h2>";
        
        // 1. Tabela de usuários administradores
        echo "<p>✅ Criando tabela 'users'...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            username VARCHAR(60) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','editor') DEFAULT 'admin',
            is_active TINYINT(1) DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 2. Tabela de tentativas de login (rate limiting)
        echo "<p>✅ Criando tabela 'login_attempts'...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(60) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip),
            INDEX idx_user (username),
            INDEX idx_time (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 3. Tabela de log de auditoria
        echo "<p>✅ Criando tabela 'audit_log'...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(50) NOT NULL,
            entity VARCHAR(50) NULL,
            entity_id INT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_action (action),
            INDEX idx_user (user_id),
            INDEX idx_time (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 4. Tabela de arquivos de mídia
        echo "<p>✅ Criando tabela 'media_files'...</p>";
        $pdo->exec("CREATE TABLE IF NOT EXISTS media_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_url VARCHAR(500) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            width INT NULL,
            height INT NULL,
            alt_text VARCHAR(255) NULL,
            uploaded_by INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_filename (filename),
            INDEX idx_mime (mime_type),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // 5. Verificar se já existe usuário admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            echo "<p>⚠️ Nenhum usuário admin encontrado.</p>";
            echo "<p>📝 Crie um usuário admin pelo instalador ou execute:</p>";
            echo "<code>INSERT INTO users (name, username, password_hash, role) VALUES ('Admin', 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin');</code>";
        } else {
            echo "<p>✅ Usuário(s) admin já existem: {$adminCount}</p>";
        }
        
        // 6. Atualizar site_settings com novas configurações
        echo "<p>✅ Atualizando configurações do sistema...</p>";
        $newSettings = [
            'enable_registration' => '0',
            'maintenance_mode' => '0',
            'max_upload_size' => '5242880', // 5MB
            'allowed_file_types' => 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx',
            'session_timeout' => '7200', // 2 horas
            'enable_audit_log' => '1'
        ];
        
        foreach ($newSettings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        // 7. Estatísticas finais
        echo "<h3>✅ Migrations Executadas com Sucesso!</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Tabela</th><th>Registros</th></tr>";
        
        $tables = ['users', 'login_attempts', 'audit_log', 'media_files', 'categories', 'articles', 'site_settings', 'search_index'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $count = $stmt->fetchColumn();
                echo "<tr><td>{$table}</td><td>{$count}</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>{$table}</td><td>Erro: " . $e->getMessage() . "</td></tr>";
            }
        }
        echo "</table>";
        
        // 8. Criar arquivo de flag
        file_put_contents(__DIR__ . '/.migrations_complete', date('Y-m-d H:i:s'));
        echo "<p>📁 Flag de migrations criada: install/.migrations_complete</p>";
        
        return true;
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Executar migrations se chamado diretamente
if (basename($_SERVER['SCRIPT_NAME']) === 'migrations_complete.php') {
    echo "<!DOCTYPE html><html><head><title>Migrations AvyaHub</title></head><body>";
    echo "<h1>AvyaHub - Migrations Completas</h1>";
    
    if (file_exists(__DIR__ . '/.migrations_complete')) {
        echo "<p>✅ Migrations já foram executadas em: " . file_get_contents(__DIR__ . '/.migrations_complete') . "</p>";
        echo "<p><a href='migrations_complete.php?force=1'>Forçar re-execução</a></p>";
        
        if (!isset($_GET['force'])) {
            echo "</body></html>";
            exit;
        }
    }
    
    runCompleteMigrations();
    echo "<p><a href='../admin'>Ir para Painel Admin</a></p>";
    echo "</body></html>";
}
?>