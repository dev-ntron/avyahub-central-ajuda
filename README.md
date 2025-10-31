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
- ✅ Verificações de integridade do sistema

### Funcionalidades Técnicas
- ✅ PHP puro com configuração por .env
- ✅ Banco MySQL com estrutura otimizada
- ✅ Sistema de busca com indexação
- ✅ Cache e otimizações de performance
- ✅ Upload seguro de arquivos
- ✅ Proteção contra XSS e SQL Injection
- ✅ Timeout de sessão administrativo
- ✅ Suporte a BASE_PATH (subpastas, subdomínios)
- ✅ CSRF tokens em todos os formulários
- ✅ Sistema de migrations automáticas
- ✅ Log de auditoria completo
- ✅ Gerenciamento de mídia organizado

## 📎 Suporte a Instalação Flexível

O sistema funciona perfeitamente em:
- **Domínio raiz:** `https://seusite.com`
- **Subpasta:** `https://seusite.com/ajuda/`
- **Subdomínio:** `https://help.seusite.com` 

Tudo configurado automaticamente pelo instalador!

## 📋 Requisitos

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache com mod_rewrite
- Extensões PHP: PDO, GD, JSON, mbstring

## 🛸 Instalação Rápida (Recomendado)

### 1. Upload dos Arquivos
```bash
git clone https://github.com/dev-ntron/avyahub-central-ajuda.git
cd avyahub-central-ajuda
# Faça upload dos arquivos para seu servidor
```

### 2. Instalador Web
1. Acesse: `https://seudominio.com/install/`
2. Siga o assistente em 3 etapas:
   - ✅ Requisitos do ambiente
   - ⚙️ Configuração (.env + banco de dados + admin)
   - 🏁 Finalização
3. Usuário admin é criado direto no banco de dados (tabela users)

### 3. Migrations Completas
Após o instalador, execute as migrations completas:
```
https://seudominio.com/install/migrations_complete.php
```

### 4. Remoção da Pasta Install
```bash
# Importante: remova após instalar por segurança
rm -rf install/
```

### 5. Acessos
- **Frontend:** `https://seudominio.com`
- **Admin:** `https://seudominio.com/admin`

## 🛠️ Instalação Manual (Alternativa)

### 1. Clone e Configuração
```bash
git clone https://github.com/dev-ntron/avyahub-central-ajuda.git
cd avyahub-central-ajuda
cp .env.example .env
nano .env
```

### 2. Configure o .env
```env
# Banco de dados
DB_HOST=localhost
DB_NAME=avyahub_help
DB_USER=seu_usuario
DB_PASS=sua_senha

# BASE_PATH para subpastas (obrigatório)
BASE_PATH=/ajuda

# Ambiente
APP_ENV=production
APP_DEBUG=false
```

### 3. Banco de Dados
```bash
# Importar estrutura
mysql -u root -p < install/install.sql

# Executar migrations
php -r "require 'install/migrations_complete.php'; runCompleteMigrations();"
```

### 4. Permissões
```bash
chmod 755 uploads/
chmod 755 assets/
chmod 640 .env
```

### 5. Criar Usuário Admin
```sql
INSERT INTO users (name, username, password_hash, role, is_active) 
VALUES ('Administrador','admin','$2y$10$HASHGERADO', 'admin', 1);
```

## 🔐 Acesso Administrativo

**URL:** `/admin`

As credenciais são cadastradas no banco de dados (tabela `users`):
- **Usuário:** definido durante instalação
- **Senha:** definida durante instalação

> ⚠️ **Segurança:** Sempre use senhas fortes! O sistema possui auditoria completa de acessos.

## 🎨 Personalização

### Logo e Favicon
1. Acesse `/admin/media`
2. Upload do logo (PNG recomendado, até 2MB)
3. Upload do favicon (ICO 32x32px, até 100KB)
4. Arquivos ficam disponíveis automaticamente

### Cores e Visual
Acesse `/admin/settings` para personalizar:
- Título e descrição do site
- Cores primária e secundária
- Texto do rodapé
- Preview em tempo real

## 🕰️ Verificação de Integridade

Acesse `/admin/check` para verificar:
- ✅ Escrita em `uploads/` e `assets/`
- ✅ .env e flag de instalação
- ✅ Conexão e latência do banco
- ✅ Tabelas essenciais
- ✅ Extensões e versão PHP/MySQL
- ✅ Configurações BASE_PATH

## 🔒 Segurança

### Implementado
- ✅ Proteção contra SQL Injection (PDO prepared statements)
- ✅ CSRF tokens em todos os formulários
- ✅ Rate limiting de login (10 tentativas/10min)
- ✅ Validação rigorosa de uploads
- ✅ Timeout de sessão administrativo (2 horas)
- ✅ Log de auditoria completo
- ✅ Headers de segurança
- ✅ Sanitização de dados

### Recomendações de Produção
1. **HTTPS obrigatório:** Configure SSL/TLS
2. **Firewall:** Limite acesso ao `/admin` por IP
3. **Backup:** Configure backup automático do banco
4. **Monitoramento:** Logs de acesso e erro
5. **Atualizações:** Mantenha PHP e MySQL atualizados
6. **Remoção:** Delete `/install` após instalação

## 📊 Estrutura de Tabelas

### Tabelas Principais
- `categories` - Categorias dos artigos
- `articles` - Artigos com conteúdo HTML
- `site_settings` - Configurações gerais
- `search_index` - Índice de busca otimizado

### Tabelas de Segurança
- `users` - Usuários administradores
- `login_attempts` - Rate limiting de login
- `audit_log` - Log de auditoria completo
- `media_files` - Gerenciamento de arquivos

## 🐛 Solução de Problemas

### Problemas Comuns
| Problema | Solução |
|----------|----------|
| Erro 500 | Verifique permissões e .env |
| Links quebrados | Configure BASE_PATH no .env |
| Upload falha | Verifique permissões da pasta uploads/ |
| Login não funciona | Execute migrations completas |
| CSS não carrega | Verifique .htaccess e mod_rewrite |

### Logs de Debug
```bash
# Ver logs de erro do PHP
tail -f /var/log/apache2/error.log

# Ver logs de auditoria
SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 50;
```

## 🗺️ Roadmap Futuro

### 🟢 Próximas Funcionalidades
- API REST para integração externa
- Sistema de comentários nos artigos
- Multi-idioma/internacionalização
- Sistema de temas customizáveis
- Analytics e métricas avançadas
- Webhooks para notificações

### 🗺️ Melhorias Técnicas
- Docker setup completo
- CI/CD com GitHub Actions
- Testes automatizados (PHPUnit)
- Cache avançado (Redis/Memcached)
- Queue system para tarefas pesadas
- Backup automático integrado

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📋 Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações.

## 📧 Suporte

Para suporte técnico:
- 🐛 Issues: [GitHub Issues](https://github.com/dev-ntron/avyahub-central-ajuda/issues)
- 📚 Documentação: Disponível no próprio sistema
- ✅ Verificações: Use `/admin/check` para diagnósticos

---

**AvyaHub Central de Ajuda** - Sistema profissional de documentação e suporte com personalização completa e segurança avançada.