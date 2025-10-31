<?php
function createTables($pdo) {
    // Tabelas principais
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        order_position INT DEFAULT 0,
        parent_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_slug (slug),
        INDEX idx_order (order_position)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        is_published TINYINT(1) DEFAULT 1,
        order_position INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_slug (slug),
        INDEX idx_published (is_published),
        INDEX idx_category (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Índice auxiliar de busca (opcional sem FULLTEXT)
    $pdo->exec("CREATE TABLE IF NOT EXISTS search_index (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type ENUM('article', 'category') NOT NULL,
        content_id INT NOT NULL,
        title VARCHAR(255),
        content LONGTEXT,
        searchable_text LONGTEXT,
        INDEX idx_content (content_type, content_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Configurações padrão
    $settings = [
        ['setting_key' => 'site_title', 'setting_value' => 'Central de Ajuda AvyaHub'],
        ['setting_key' => 'site_description', 'setting_value' => 'Documentação completa da plataforma de atendimento AvyaHub'],
        ['setting_key' => 'primary_color', 'setting_value' => '#2563eb'],
        ['setting_key' => 'secondary_color', 'setting_value' => '#64748b'],
        ['setting_key' => 'logo_url', 'setting_value' => ''],
        ['setting_key' => 'favicon_url', 'setting_value' => ''],
        ['setting_key' => 'footer_text', 'setting_value' => '© 2025 AvyaHub. Todos os direitos reservados.']
    ];
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$setting['setting_key'], $setting['setting_value']]);
    }
}

/**
 * Função completa que executa todas as migrations necessárias
 * Inclui tabelas básicas + segurança + mídia + configurações avançadas
 */
function runCompleteMigrations($pdo) {
    // 1. Criar tabelas principais
    createTables($pdo);
    
    // 2. Tabelas de segurança e autenticação
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
        INDEX idx_role (role),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(60) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip),
        INDEX idx_user (username),
        INDEX idx_time (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
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
    
    // 3. Tabela de arquivos de mídia
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
    
    // 4. Configurações avançadas do sistema
    $advancedSettings = [
        'enable_registration' => '0',
        'maintenance_mode' => '0',
        'max_upload_size' => '5242880', // 5MB
        'allowed_file_types' => 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx',
        'session_timeout' => '7200', // 2 horas
        'enable_audit_log' => '1',
        'max_login_attempts' => '10',
        'login_attempt_window' => '600' // 10 minutos
    ];
    
    foreach ($advancedSettings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    
    // 5. Seeds opcionais (categorias exemplo)
    $categories = [
        ['name' => 'Primeiros Passos', 'slug' => 'primeiros-passos', 'description' => 'Guia inicial para usar a plataforma AvyaHub', 'order' => 1],
        ['name' => 'Gestão de Atendentes', 'slug' => 'gestao-atendentes', 'description' => 'Como gerenciar sua equipe de atendimento', 'order' => 2],
        ['name' => 'Canais de Comunicação', 'slug' => 'canais-comunicacao', 'description' => 'Conecte e gerencie seus canais de atendimento', 'order' => 3],
        ['name' => 'Automação e Chatbots', 'slug' => 'automacao-chatbots', 'description' => 'Automatize seus atendimentos com fluxos inteligentes', 'order' => 4]
    ];
    
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, order_position) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['order']]);
    }
    
    return true;
}
?>