# Central de Ajuda AvyaHub

Sistema completo de central de ajuda inspirado no GitBook, desenvolvido especificamente para a plataforma AvyaHub.

## 🚀 Funcionalidades

### Frontend Público
- ✅ Design inspirado no GitBook com sidebar navegável
- ✅ Sistema de busca inteligente em tempo real
- ✅ Dark mode com persistência local
- ✅ Layout 100% responsivo
- ✅ URLs amigáveis (SEO otimizado)
- ✅ Navegação por breadcrumbs
- ✅ Sistema de categorias hierárquico
- ✅ Logo e favicon personalizáveis
- ✅ Meta tags Open Graph para redes sociais

### Painel Administrativo
- ✅ Dashboard com estatísticas
- ✅ Gestão completa de categorias
- ✅ Editor robusto de artigos com TinyMCE
- ✅ Sistema de upload de imagens
- ✅ Gerenciamento de mídia (logo, favicon)
- ✅ Personalização de cores e configurações
- ✅ Preview em tempo real
- ✅ Sistema de rascunhos
- ✅ Autenticação baseada em banco de dados (users)
- ✅ Log de auditoria de acesso/admin
- ✅ Rate limiting e CSRF no login

### Funcionalidades Técnicas
- ✅ PHP puro com configuração por .env
- ✅ Banco MySQL com estrutura otimizada
- ✅ Sistema de busca com indexação
- ✅ Cache e otimizações de performance
- ✅ Upload seguro de arquivos
- ✅ Proteção contra XSS e SQL Injection
- ✅ Timeout de sessão administrativo

## 📋 Requisitos

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache com mod_rewrite
- Extensões PHP: PDO, GD, JSON, mbstring

## 🛠️ Instalação (Instalador Web Recomendado)

1. Suba os arquivos do projeto no seu servidor.
2. Acesse: `https://seudominio.com/install/`
3. Siga o assistente em 3 etapas:
   - Requisitos do ambiente
   - Configuração (.env + banco de dados + admin)
   - Finalização
4. Usuário admin é criado direto no banco de dados (tabela users) usando as credenciais cadastradas no instalador.
5. Acesse:
   - Frontend: `https://seudominio.com`
   - Admin: `https://seudominio.com/admin`

> Importante: Após concluir, remova a pasta `/install` do servidor por segurança.

## 🛠️ Instalação Manual (Alternativa)

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/dev-ntron/avyahub-central-ajuda.git
   cd avyahub-central-ajuda
   ```

2. **Configure as variáveis de ambiente:**
   ```bash
   cp .env.example .env
   nano .env
   ```
   
   Edite o arquivo `.env` com suas configurações:
   ```env
   # Banco de dados
   DB_HOST=localhost
   DB_NAME=avyahub_help
   DB_USER=seu_usuario
   DB_PASS=sua_senha
   
   # Ambiente
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Configure o banco de dados:**
   ```bash
   mysql -u root -p < install/install.sql
   php -r "require 'install/database.php'; require 'install/migrations_auth.php'; addAuthTables(new PDO('mysql:host=localhost;dbname=avyahub_help','user','senha'))"
   ```

4. **Configure o Apache:**
   - Certifique-se que o mod_rewrite está ativado
   - Aponte o DocumentRoot para a pasta do projeto
   - O arquivo `.htaccess` já está configurado

5. **Configurar permissões:**
   ```bash
   chmod 755 uploads/
   chmod 755 assets/
   ```

6. **Crie o usuário admin manualmente:**
   ```sql
   INSERT INTO users (name, username, password_hash, role, is_active) VALUES ('Administrador','seuadmin','$2y$10$HASHGERADO', 'admin', 1);
   ```
   - Use um gerador de senha bcrypt para criar o hash (exemplo: password_hash no PHP CLI)


## 🔐 Acesso Administrativo

**URL:** `/admin`

As credenciais são cadastradas no banco de dados (tabela `users`).
- **Usuário:** preencha durante o instalador ou insira manualmente
- **Senha:** cadastrada durante o instalador

> ⚠️ **Segurança:** Sempre use senhas fortes para o admin! Considere auditar os acessos no painel de auditoria.

## 🎨 Personalização

### Logo e Favicon
1. Acesse `/admin/media`
2. Faça upload do seu logo (PNG recomendado, até 2MB)
3. Faça upload do favicon (ICO 32x32px recomendado, até 100KB)
4. Os arquivos serão exibidos automaticamente no site

### Cores e Visual
Acesse `/admin/settings` para personalizar:
- Título e descrição do site
- Cores primária e secundária
- Texto do rodapé
- Preview em tempo real das mudanças

## 🛡️ Segurança

### Implementado
- Proteção contra SQL Injection (PDO prepared statements)
- Validação rigorosa de uploads
- Timeout de sessão administrativo (2 horas)
- Variáveis de ambiente separadas das de usuário admin
- Proteção de arquivos via .htaccess
- Sanitização de dados de entrada
- Log de auditoria de acesso/admin
- Rate limiting no login
- CSRF tokens globais

### Recomendações de Produção
1. **HTTPS obrigatório:** Configure SSL/TLS
2. **Firewall:** Limite acesso ao `/admin` por IP
3. **Backup:** Configure backup automático do banco
4. **Monitoramento:** Logs de acesso e erro
5. **Atualizações:** Mantenha PHP e MySQL atualizados

## 🧪 Verificação de Integridade

Após a instalação, acesse `/admin/check.php` (somente admin logado) para checar rapidamente:
- Escrita em `uploads/` e `assets/`
- Permissão e presença de `.env` e da flag `install/.installed`
- Conexão e latência do banco
- Existência das tabelas essenciais
- Extensões e versão do PHP/MySQL

---

**AvyaHub Central de Ajuda** - Sistema profissional de documentação e suporte com personalização completa.
