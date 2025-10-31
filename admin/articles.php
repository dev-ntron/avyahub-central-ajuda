<?php
$page_title = 'Artigos';

// Usar createDatabaseConnection() se não existir $pdo
if (!isset($pdo)) {
    try {
        $pdo = createDatabaseConnection();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro de conexão com banco de dados';
        header('Location: ' . BASE_PATH . '/admin');
        exit;
    }
}

// CSRF helper functions (assumindo que estão no auth.php ou config.php)
if (!function_exists('get_csrf_token')) {
    function get_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Obter categorias para o select
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $_SESSION['error'] = 'Erro ao obter categorias';
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $_SESSION['error'] = 'Token de segurança inválido';
        header('Location: ' . BASE_PATH . '/admin/articles');
        exit;
    }
    
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $title = trim($_POST['title']);
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['title'])));
                    $category_id = (int)$_POST['category_id'];
                    $content = $_POST['content'];
                    $excerpt = trim($_POST['excerpt']);
                    $is_published = isset($_POST['is_published']) ? 1 : 0;
                    
                    if (empty($title) || $category_id <= 0) {
                        $_SESSION['error'] = 'Título e categoria são obrigatórios';
                        break;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO articles (title, slug, category_id, content, excerpt, is_published) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$title, $slug, $category_id, $content, $excerpt, $is_published])) {
                        $_SESSION['success'] = 'Artigo criado com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao criar artigo.';
                    }
                    break;
                    
                case 'update':
                    $id = (int)$_POST['id'];
                    $title = trim($_POST['title']);
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
                    $category_id = (int)$_POST['category_id'];
                    $content = $_POST['content'];
                    $excerpt = trim($_POST['excerpt']);
                    $is_published = isset($_POST['is_published']) ? 1 : 0;
                    
                    if (empty($title) || empty($slug) || $category_id <= 0) {
                        $_SESSION['error'] = 'Título, slug e categoria são obrigatórios';
                        break;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE articles SET title = ?, slug = ?, category_id = ?, content = ?, excerpt = ?, is_published = ? WHERE id = ?");
                    if ($stmt->execute([$title, $slug, $category_id, $content, $excerpt, $is_published, $id])) {
                        $_SESSION['success'] = 'Artigo atualizado com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao atualizar artigo.';
                    }
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    
                    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $_SESSION['success'] = 'Artigo excluído com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao excluir artigo.';
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro interno: ' . $e->getMessage();
        }
        
        header('Location: ' . BASE_PATH . '/admin/articles');
        exit;
    }
}

// Artigo para edição
$edit_article = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $edit_article = $stmt->fetch();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Erro ao obter artigo para edição';
    }
}

// Listar artigos
$where = '';
$params = [];

if (isset($_GET['category']) && $_GET['category'] !== '') {
    $where = 'WHERE a.category_id = ?';
    $params[] = (int)$_GET['category'];
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        $where
        ORDER BY a.created_at DESC
    ");
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (Exception $e) {
    $articles = [];
    $_SESSION['error'] = 'Erro ao obter artigos';
}

$csrf_token = get_csrf_token();

ob_start();
?>

<?php if (isset($_GET['new']) || isset($_GET['edit'])): ?>
<!-- Formulário de Artigo -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
        <h2 style="margin: 0; font-size: 1.5rem; color: #2d3748;">
            <?= isset($_GET['edit']) ? 'Editar Artigo' : 'Novo Artigo' ?>
        </h2>
        <a href="<?= url('/admin/articles') ?>" class="btn" style="background: #e2e8f0; color: #2d3748;">← Voltar</a>
    </div>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="<?= isset($_GET['edit']) ? 'update' : 'create' ?>">
        <?php if (isset($_GET['edit'])): ?>
        <input type="hidden" name="id" value="<?= $edit_article['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">
            <div>
                <div class="form-group">
                    <label class="form-label" for="title">Título do Artigo</label>
                    <input type="text" id="title" name="title" class="form-input" 
                           value="<?= $edit_article ? htmlspecialchars($edit_article['title']) : '' ?>" required>
                </div>
                
                <?php if (isset($_GET['edit'])): ?>
                <div class="form-group">
                    <label class="form-label" for="slug">Slug (URL)</label>
                    <input type="text" id="slug" name="slug" class="form-input" 
                           value="<?= htmlspecialchars($edit_article['slug']) ?>" required>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="excerpt">Resumo/Descrição</label>
                    <textarea id="excerpt" name="excerpt" class="form-textarea" rows="3" 
                              placeholder="Breve descrição do artigo (opcional)"><?= $edit_article ? htmlspecialchars($edit_article['excerpt']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="content">Conteúdo</label>
                    <textarea id="content" name="content" class="form-textarea"><?= $edit_article ? $edit_article['content'] : '' ?></textarea>
                </div>
            </div>
            
            <div>
                <div class="card" style="margin: 0;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem;">Configurações</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="category_id">Categoria</label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= $edit_article && $edit_article['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_published" 
                                   <?= !$edit_article || $edit_article['is_published'] ? 'checked' : '' ?>>
                            <span>Publicar artigo</span>
                        </label>
                        <small style="color: #718096;">Artigos não publicados não aparecerão no site público.</small>
                    </div>
                    
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 1rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-success" style="width: 100%; margin-bottom: 0.5rem;">
                            <?= isset($_GET['edit']) ? 'Atualizar Artigo' : 'Criar Artigo' ?>
                        </button>
                        
                        <?php if (isset($_GET['edit']) && $edit_article): ?>
                        <a href="<?= url('/' . $edit_article['slug']) ?>" class="btn" target="_blank" 
                           style="width: 100%; text-align: center; display: block; margin-bottom: 0.5rem; background: #38a169;">
                            Ver no Site
                        </a>
                        
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir este artigo?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $edit_article['id'] ?>">
                            <button type="submit" class="btn btn-danger" style="width: 100%;">
                                Excluir Artigo
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php else: ?>
<!-- Lista de Artigos -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem;">Gerenciar Artigos</h2>
        <p style="color: #718096;">Crie e edite o conteúdo da sua central de ajuda.</p>
    </div>
    <a href="<?= url('/admin/articles?new=1') ?>" class="btn btn-success">➕ Novo Artigo</a>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 1rem; align-items: center;">
        <strong>Filtrar por:</strong>
        <select onchange="window.location.href='<?= url('/admin/articles?category=') ?>' + this.value" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            <option value="">Todas as categorias</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <?php if (isset($_GET['category'])): ?>
        <a href="<?= url('/admin/articles') ?>" style="color: #e53e3e; text-decoration: none;">✕ Limpar filtros</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($articles): ?>
<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Atualizado em</th>
                    <th style="width: 150px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <div>
                            <strong><?= htmlspecialchars($article['title']) ?></strong>
                            <div style="font-size: 0.8rem; color: #718096; margin-top: 0.25rem;">
                                <code>/<?= htmlspecialchars($article['slug']) ?></code>
                            </div>
                            <?php if ($article['excerpt']): ?>
                            <div style="font-size: 0.8rem; color: #718096; margin-top: 0.25rem;">
                                <?= htmlspecialchars(substr($article['excerpt'], 0, 100)) ?>...
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($article['category_name'] ?? 'Sem categoria') ?></td>
                    <td>
                        <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; 
                              background: <?= $article['is_published'] ? '#f0fff4; color: #2f855a' : '#fed7d7; color: #c53030' ?>;">
                            <?= $article['is_published'] ? 'Publicado' : 'Rascunho' ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($article['updated_at'])) ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <?php if ($article['is_published']): ?>
                            <a href="<?= url('/' . $article['slug']) ?>" class="btn btn-sm" style="background: #38a169;" target="_blank">👁️</a>
                            <?php endif; ?>
                            <a href="<?= url('/admin/articles?edit=' . $article['id']) ?>" class="btn btn-sm">✏️</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este artigo?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card text-center" style="padding: 3rem;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
    <h3 style="margin-bottom: 1rem;">Nenhum artigo encontrado</h3>
    <p style="color: #718096; margin-bottom: 2rem;">
        <?php if (isset($_GET['category'])): ?>
            Nenhum artigo encontrado nesta categoria.
        <?php else: ?>
            Crie seu primeiro artigo para começar a documentação.
        <?php endif; ?>
    </p>
    <a href="<?= url('/admin/articles?new=1') ?>" class="btn btn-success">Criar primeiro artigo</a>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
include 'layout.php';
?>