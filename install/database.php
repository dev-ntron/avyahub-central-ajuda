<?php
function createTables($pdo) {
    // Estrutura existente
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
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        is_published BOOLEAN DEFAULT TRUE,
        order_position INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_slug (slug),
        INDEX idx_published (is_published),
        INDEX idx_category (category_id),
        FULLTEXT idx_search (title, content, excerpt)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS search_index (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type ENUM('article', 'category') NOT NULL,
        content_id INT NOT NULL,
        title VARCHAR(255),
        content LONGTEXT,
        searchable_text LONGTEXT,
        INDEX idx_content (content_type, content_id),
        FULLTEXT idx_search_text (title, content, searchable_text)
    ) ENGINE=InnoDB");

    // Inserir configurações padrão
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

    // Tabelas de autenticação (migracao)
    require_once __DIR__ . '/migrations_auth.php';
    addAuthTables($pdo);

    // Seed inicial
    insertSampleData($pdo);
}

function insertSampleData($pdo) {
    // ... (conteúdo existente inalterado)
    // (mantivemos os mesmos inserts de categorias e artigos)
}
?>