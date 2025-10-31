<?php
function createTables($pdo) {
    // Tabela de categorias
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
    
    // Tabela de artigos
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
    
    // Tabela de configurações
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB");
    
    // Tabela de índice de busca
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
    
    // Inserir configurações padrão (incluindo favicon)
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
    
    // Inserir dados exemplo
    insertSampleData($pdo);
}

function insertSampleData($pdo) {
    // Categorias exemplo
    $categories = [
        ['name' => 'Primeiros Passos', 'slug' => 'primeiros-passos', 'description' => 'Guia inicial para usar a plataforma AvyaHub', 'order_position' => 1],
        ['name' => 'Gestão de Atendentes', 'slug' => 'gestao-atendentes', 'description' => 'Como gerenciar sua equipe de atendimento', 'order_position' => 2],
        ['name' => 'Canais de Comunicação', 'slug' => 'canais-comunicacao', 'description' => 'Conecte e gerencie seus canais de atendimento', 'order_position' => 3],
        ['name' => 'Automação e Chatbots', 'slug' => 'automacao-chatbots', 'description' => 'Automatize seus atendimentos com fluxos inteligentes', 'order_position' => 4]
    ];
    
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, order_position) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category['name'], $category['slug'], $category['description'], $category['order_position']]);
    }
    
    // Artigos exemplo adaptados para AvyaHub
    $articles = [
        [
            'category_slug' => 'primeiros-passos',
            'title' => 'O que é o AvyaHub',
            'slug' => 'o-que-e-avyahub',
            'content' => '<h1>O que é o AvyaHub</h1>\n<p>O AvyaHub é uma plataforma completa de atendimento e automação projetada para centralizar a comunicação de múltiplos canais, com foco principal no WhatsApp.</p>\n<p>A plataforma se destaca por sua flexibilidade, suportando tanto a API Oficial do WhatsApp quanto APIs não oficiais (via QR Code), além de integrar com outros canais como Instagram, Facebook Messenger e Telegram.</p>\n<h2>Principais Funcionalidades</h2>\n<ul>\n<li>Gestão de múltiplos atendentes</li>\n<li>Criação de chatbots para automação de conversas</li>\n<li>Integração com ferramentas de inteligência artificial</li>\n<li>Plataformas de automação avançadas</li>\n<li>Relatórios e dashboard em tempo real</li>\n<li>Sistema de etiquetas e notas</li>\n</ul>\n<h2>Benefícios</h2>\n<p>Com o AvyaHub você pode:</p>\n<ul>\n<li>Centralizar todos os atendimentos em uma única plataforma</li>\n<li>Automatizar respostas frequentes com chatbots inteligentes</li>\n<li>Acompanhar métricas de desempenho da equipe</li>\n<li>Melhorar a experiência do cliente com respostas mais rápidas</li>\n</ul>',
            'excerpt' => 'Conheça a plataforma AvyaHub e suas principais funcionalidades para otimizar seu atendimento.'
        ],
        [
            'category_slug' => 'primeiros-passos',
            'title' => 'Configuração Inicial',
            'slug' => 'configuracao-inicial',
            'content' => '<h1>Configuração Inicial</h1>\n<p>Este guia apresenta os primeiros passos para configurar sua conta no AvyaHub após o primeiro acesso.</p>\n<h2>Primeiro Login</h2>\n<p>Após receber suas credenciais, acesse a plataforma e siga estes passos:</p>\n<ol>\n<li>Faça login com suas credenciais</li>\n<li>Complete seu perfil de administrador</li>\n<li>Configure as informações da sua empresa</li>\n<li>Defina os horários de atendimento</li>\n<li>Conecte seu primeiro canal de comunicação</li>\n</ol>\n<h2>Configurações Básicas</h2>\n<p>Configure as informações essenciais da sua empresa:</p>\n<ul>\n<li><strong>Dados da Empresa:</strong> Nome, endereço, telefone</li>\n<li><strong>Horários:</strong> Defina quando sua equipe está disponível</li>\n<li><strong>Mensagens Automáticas:</strong> Boas-vindas, ausência, despedida</li>\n<li><strong>Integrações:</strong> Conecte WhatsApp, Instagram, etc.</li>\n</ul>\n<h2>Próximos Passos</h2>\n<p>Após a configuração inicial, recomendamos:</p>\n<ol>\n<li>Criar usuários para sua equipe</li>\n<li>Configurar departamentos ou setores</li>\n<li>Definir mensagens rápidas</li>\n<li>Testar os fluxos de atendimento</li>\n</ol>',
            'excerpt' => 'Aprenda a configurar sua conta AvyaHub pela primeira vez.'
        ],
        [
            'category_slug' => 'gestao-atendentes',
            'title' => 'Criando e Gerenciando Usuários',
            'slug' => 'criando-usuarios',
            'content' => '<h1>Criando e Gerenciando Usuários</h1>\n<p>Saiba como adicionar e gerenciar atendentes no sistema AvyaHub.</p>\n<h2>Tipos de Usuário</h2>\n<p>O AvyaHub possui três níveis de acesso:</p>\n<ul>\n<li><strong>Administrador:</strong> Acesso completo ao sistema, configurações e relatórios</li>\n<li><strong>Supervisor:</strong> Pode supervisionar atendimentos, transferir conversas e ver relatórios da equipe</li>\n<li><strong>Atendente:</strong> Acesso às conversas, mensagens rápidas e ferramentas básicas de atendimento</li>\n</ul>\n<h2>Como Adicionar um Usuário</h2>\n<ol>\n<li>No menu principal, acesse <strong>Usuários</strong></li>\n<li>Clique em <strong>Adicionar Usuário</strong></li>\n<li>Preencha os dados pessoais:\n   <ul>\n   <li>Nome completo</li>\n   <li>E-mail (usado para login)</li>\n   <li>Telefone</li>\n   <li>Cargo/função</li>\n   </ul>\n</li>\n<li>Defina o perfil de permissão (Administrador, Supervisor ou Atendente)</li>\n<li>Configure horários de trabalho (opcional)</li>\n<li>Salve as informações</li>\n</ol>\n<h2>Dicas Importantes</h2>\n<ul>\n<li>Sempre teste as credenciais após criar um usuário</li>\n<li>Configure horários para controlar quando cada atendente fica disponível</li>\n<li>Use o perfil de Supervisor para líderes de equipe</li>\n<li>Mantenha os dados de contato sempre atualizados</li>\n</ul>',
            'excerpt' => 'Tutorial completo para criar e gerenciar usuários na plataforma.'
        ],
        [
            'category_slug' => 'canais-comunicacao',
            'title' => 'Conectando o WhatsApp',
            'slug' => 'conectando-whatsapp',
            'content' => '<h1>Conectando o WhatsApp</h1>\n<p>O WhatsApp é o principal canal do AvyaHub. Aprenda como conectar e configurar corretamente.</p>\n<h2>Métodos de Conexão</h2>\n<p>O AvyaHub oferece duas formas de conectar o WhatsApp:</p>\n<ul>\n<li><strong>API Oficial (Recomendado):</strong> Mais estável, suporte completo a recursos</li>\n<li><strong>Web WhatsApp (QR Code):</strong> Configuração mais rápida, ideal para testes</li>\n</ul>\n<h2>Conexão via API Oficial</h2>\n<ol>\n<li>Acesse <strong>Canais &gt; WhatsApp &gt; API Oficial</strong></li>\n<li>Insira o token fornecido pelo WhatsApp Business</li>\n<li>Configure o webhook URL</li>\n<li>Teste a conexão</li>\n<li>Configure mensagens de template (se necessário)</li>\n</ol>\n<h2>Conexão via QR Code</h2>\n<ol>\n<li>Acesse <strong>Canais &gt; WhatsApp &gt; Web</strong></li>\n<li>Clique em <strong>Conectar Novo Dispositivo</strong></li>\n<li>Escaneie o QR Code com seu WhatsApp</li>\n<li>Aguarde a confirmação de conexão</li>\n<li>Configure as mensagens automáticas</li>\n</ol>\n<h2>Configurações Importantes</h2>\n<h3>Mensagens Automáticas</h3>\n<ul>\n<li><strong>Boas-vindas:</strong> Primeira mensagem que o cliente recebe</li>\n<li><strong>Ausência:</strong> Enviada quando não há atendentes disponíveis</li>\n<li><strong>Despedida:</strong> Mensagem de encerramento do atendimento</li>\n</ul>\n<h3>Horários de Funcionamento</h3>\n<p>Configure quando sua equipe está disponível para:</p>\n<ul>\n<li>Receber novas conversas</li>\n<li>Enviar mensagens automáticas de ausência</li>\n<li>Ativar chatbots fora do horário</li>\n</ul>',
            'excerpt' => 'Passo a passo para conectar e configurar o WhatsApp na plataforma.'
        ]
    ];
    
    foreach ($articles as $article) {
        // Buscar ID da categoria
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$article['category_slug']]);
        $category = $stmt->fetch();
        
        if ($category) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO articles (category_id, title, slug, content, excerpt) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category['id'], $article['title'], $article['slug'], $article['content'], $article['excerpt']]);
        }
    }
}
?>