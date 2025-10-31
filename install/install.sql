-- Central de Ajuda AvyaHub - Estrutura do Banco de Dados
-- Execute este arquivo SQL manualmente caso prefira não usar a instalação automática

CREATE DATABASE IF NOT EXISTS avyahub_help CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE avyahub_help;

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categories (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de artigos
CREATE TABLE IF NOT EXISTS articles (
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
    -- FULLTEXT opcional (pode falhar em versões antigas):
    -- , FULLTEXT idx_search (title, content, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de índice de busca (FULLTEXT opcional)
CREATE TABLE IF NOT EXISTS search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('article', 'category') NOT NULL,
    content_id INT NOT NULL,
    title VARCHAR(255),
    content LONGTEXT,
    searchable_text LONGTEXT,
    INDEX idx_content (content_type, content_id)
    -- FULLTEXT opcional:
    -- , FULLTEXT idx_search_text (title, content, searchable_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_title', 'Central de Ajuda AvyaHub'),
('site_description', 'Documentação completa da plataforma de atendimento AvyaHub'),
('primary_color', '#2563eb'),
('secondary_color', '#64748b'),
('logo_url', ''),
('favicon_url', ''),
('footer_text', '© 2025 AvyaHub. Todos os direitos reservados.');

-- Inserir categorias exemplo
INSERT IGNORE INTO categories (name, slug, description, order_position) VALUES
('Primeiros Passos', 'primeiros-passos', 'Guia inicial para usar a plataforma AvyaHub', 1),
('Gestão de Atendentes', 'gestao-atendentes', 'Como gerenciar sua equipe de atendimento', 2),
('Canais de Comunicação', 'canais-comunicacao', 'Conecte e gerencie seus canais de atendimento', 3),
('Automação e Chatbots', 'automacao-chatbots', 'Automatize seus atendimentos com fluxos inteligentes', 4);

-- Inserir artigos exemplo
SET @cat1 = (SELECT id FROM categories WHERE slug = 'primeiros-passos');
SET @cat2 = (SELECT id FROM categories WHERE slug = 'gestao-atendentes');
SET @cat3 = (SELECT id FROM categories WHERE slug = 'canais-comunicacao');
SET @cat4 = (SELECT id FROM categories WHERE slug = 'automacao-chatbots');

INSERT IGNORE INTO articles (category_id, title, slug, content, excerpt, is_published) VALUES
(@cat1, 'O que é o AvyaHub', 'o-que-e-avyahub', 
'<h1>O que é o AvyaHub</h1>
<p>O AvyaHub é uma plataforma completa de atendimento e automação projetada para centralizar a comunicação de múltiplos canais, com foco principal no WhatsApp.</p>
<p>A plataforma se destaca por sua flexibilidade, suportando tanto a API Oficial do WhatsApp quanto APIs não oficiais (via QR Code), além de integrar com outros canais como Instagram, Facebook Messenger e Telegram.</p>
<h2>Principais Funcionalidades</h2>
<ul>
<li>Gestão de múltiplos atendentes</li>
<li>Criação de chatbots para automação de conversas</li>
<li>Integração com ferramentas de inteligência artificial</li>
<li>Plataformas de automação avançadas</li>
<li>Relatórios e dashboard em tempo real</li>
<li>Sistema de etiquetas e notas</li>
</ul>
<h2>Benefícios</h2>
<p>Com o AvyaHub você pode:</p>
<ul>
<li>Centralizar todos os atendimentos em uma única plataforma</li>
<li>Automatizar respostas frequentes com chatbots inteligentes</li>
<li>Acompanhar métricas de desempenho da equipe</li>
<li>Melhorar a experiência do cliente com respostas mais rápidas</li>
</ul>', 
'Conheça a plataforma AvyaHub e suas principais funcionalidades para otimizar seu atendimento.', 1),

(@cat1, 'Configuração Inicial', 'configuracao-inicial',
'<h1>Configuração Inicial</h1>
<p>Este guia apresenta os primeiros passos para configurar sua conta no AvyaHub após o primeiro acesso.</p>
<h2>Primeiro Login</h2>
<p>Após receber suas credenciais, acesse a plataforma e siga estes passos:</p>
<ol>
<li>Faça login com suas credenciais</li>
<li>Complete seu perfil de administrador</li>
<li>Configure as informações da sua empresa</li>
<li>Defina os horários de atendimento</li>
<li>Conecte seu primeiro canal de comunicação</li>
</ol>
<h2>Configurações Básicas</h2>
<p>Configure as informações essenciais da sua empresa:</p>
<ul>
<li><strong>Dados da Empresa:</strong> Nome, endereço, telefone</li>
<li><strong>Horários:</strong> Defina quando sua equipe está disponível</li>
<li><strong>Mensagens Automáticas:</strong> Boas-vindas, ausência, despedida</li>
<li><strong>Integrações:</strong> Conecte WhatsApp, Instagram, etc.</li>
</ul>
<h2>Próximos Passos</h2>
<p>Após a configuração inicial, recomendamos:</p>
<ol>
<li>Criar usuários para sua equipe</li>
<li>Configurar departamentos ou setores</li>
<li>Definir mensagens rápidas</li>
<li>Testar os fluxos de atendimento</li>
</ol>',
'Aprenda a configurar sua conta AvyaHub pela primeira vez.', 1),

(@cat2, 'Criando e Gerenciando Usuários', 'criando-usuarios',
'<h1>Criando e Gerenciando Usuários</h1>
<p>Saiba como adicionar e gerenciar atendentes no sistema AvyaHub.</p>
<h2>Tipos de Usuário</h2>
<p>O AvyaHub possui três níveis de acesso:</p>
<ul>
<li><strong>Administrador:</strong> Acesso completo ao sistema, configurações e relatórios</li>
<li><strong>Supervisor:</strong> Pode supervisionar atendimentos, transferir conversas e ver relatórios da equipe</li>
<li><strong>Atendente:</strong> Acesso às conversas, mensagens rápidas e ferramentas básicas de atendimento</li>
</ul>
<h2>Como Adicionar um Usuário</h2>
<ol>
<li>No menu principal, acesse <strong>Usuários</strong></li>
<li>Clique em <strong>Adicionar Usuário</strong></li>
<li>Preencha os dados pessoais:
   <ul>
   <li>Nome completo</li>
   <li>E-mail (usado para login)</li>
   <li>Telefone</li>
   <li>Cargo/função</li>
   </ul>
</li>
<li>Defina o perfil de permissão (Administrador, Supervisor ou Atendente)</li>
<li>Configure horários de trabalho (opcional)</li>
<li>Salve as informações</li>
</ol>
<h2>Gerenciamento de Permissões</h2>
<p>Cada tipo de usuário possui permissões específicas:</p>
<table border="1" style="width: 100%; border-collapse: collapse;">
<tr>
<th>Funcionalidade</th>
<th>Administrador</th>
<th>Supervisor</th>
<th>Atendente</th>
</tr>
<tr>
<td>Atender conversas</td>
<td>✅</td>
<td>✅</td>
<td>✅</td>
</tr>
<tr>
<td>Transferir conversas</td>
<td>✅</td>
<td>✅</td>
<td>❌</td>
</tr>
<tr>
<td>Ver relatórios</td>
<td>✅</td>
<td>✅ (limitado)</td>
<td>❌</td>
</tr>
<tr>
<td>Configurar sistema</td>
<td>✅</td>
<td>❌</td>
<td>❌</td>
</tr>
</table>
<h2>Dicas Importantes</h2>
<ul>
<li>Sempre teste as credenciais após criar um usuário</li>
<li>Configure horários para controlar quando cada atendente fica disponível</li>
<li>Use o perfil de Supervisor para líderes de equipe</li>
<li>Mantenha os dados de contato sempre atualizados</li>
</ul>',
'Tutorial completo para criar e gerenciar usuários na plataforma.', 1),

(@cat3, 'Conectando o WhatsApp', 'conectando-whatsapp',
'<h1>Conectando o WhatsApp</h1>
<p>O WhatsApp é o principal canal do AvyaHub. Aprenda como conectar e configurar corretamente.</p>
<h2>Métodos de Conexão</h2>
<p>O AvyaHub oferece duas formas de conectar o WhatsApp:</p>
<ul>
<li><strong>API Oficial (Recomendado):</strong> Mais estável, suporte completo a recursos</li>
<li><strong>Web WhatsApp (QR Code):</strong> Configuração mais rápida, ideal para testes</li>
</ul>
<h2>Conexão via API Oficial</h2>
<ol>
<li>Acesse <strong>Canais > WhatsApp > API Oficial</strong></li>
<li>Insira o token fornecido pelo WhatsApp Business</li>
<li>Configure o webhook URL</li>
<li>Teste a conexão</li>
<li>Configure mensagens de template (se necessário)</li>
</ol>
<h2>Conexão via QR Code</h2>
<ol>
<li>Acesse <strong>Canais > WhatsApp > Web</strong></li>
<li>Clique em <strong>Conectar Novo Dispositivo</strong></li>
<li>Escaneie o QR Code com seu WhatsApp</li>
<li>Aguarde a confirmação de conexão</li>
<li>Configure as mensagens automáticas</li>
</ol>
<h2>Configurações Importantes</h2>
<h3>Mensagens Automáticas</h3>
<ul>
<li><strong>Boas-vindas:</strong> Primeira mensagem que o cliente recebe</li>
<li><strong>Ausência:</strong> Enviada quando não há atendentes disponíveis</li>
<li><strong>Despedida:</strong> Mensagem de encerramento do atendimento</li>
</ul>
<h3>Horários de Funcionamento</h3>
<p>Configure quando sua equipe está disponível para:</p>
<ul>
<li>Receber novas conversas</li>
<li>Enviar mensagens automáticas de ausência</li>
<li>Ativar chatbots fora do horário</li>
</ul>
<h2>Solução de Problemas</h2>
<table border="1" style="width: 100%; border-collapse: collapse;">
<tr>
<th>Problema</th>
<th>Solução</th>
</tr>
<tr>
<td>QR Code não aparece</td>
<td>Limpar cache do navegador e tentar novamente</td>
</tr>
<tr>
<td>Conexão perdida</td>
<td>Verificar se o WhatsApp não foi usado em outro dispositivo</td>
</tr>
<tr>
<td>Mensagens não chegam</td>
<td>Verificar webhook e configurações de API</td>
</tr>
</table>',
'Passo a passo para conectar e configurar o WhatsApp na plataforma.', 1);

-- Índices adicionais para otimização (verifique compatibilidade da sua versão MySQL)
-- CREATE INDEX idx_articles_updated ON articles(updated_at DESC);
-- CREATE INDEX idx_categories_published ON categories(order_position, name);
-- CREATE INDEX idx_settings_key ON site_settings(setting_key);

-- Comentários das tabelas
ALTER TABLE categories COMMENT = 'Categorias dos artigos da central de ajuda';
ALTER TABLE articles COMMENT = 'Artigos da central de ajuda com conteúdo HTML';
ALTER TABLE site_settings COMMENT = 'Configurações gerais do site (título, cores, etc.)';
ALTER TABLE search_index COMMENT = 'Índice de busca para otimização de pesquisas';

-- Informações sobre a instalação
SELECT 
    'AvyaHub Central de Ajuda instalado com sucesso!' as status,
    (SELECT COUNT(*) FROM categories) as categorias_criadas,
    (SELECT COUNT(*) FROM articles) as artigos_exemplo,
    (SELECT COUNT(*) FROM site_settings) as configuracoes_iniciais;
