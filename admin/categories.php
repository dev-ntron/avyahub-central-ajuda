<?php
$page_title = 'Categorias';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = trim($_POST['name']);
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
                $description = trim($_POST['description']);
                
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $slug, $description])) {
                    $_SESSION['success'] = 'Categoria criada com sucesso!';
                } else {
                    $_SESSION['error'] = 'Erro ao criar categoria.';
                }
                break;
                
            case 'update':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
                $description = trim($_POST['description']);
                
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                if ($stmt->execute([$name, $slug, $description, $id])) {
                    $_SESSION['success'] = 'Categoria atualizada com sucesso!';
                } else {
                    $_SESSION['error'] = 'Erro ao atualizar categoria.';
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Verificar se existem artigos nesta categoria
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
                $stmt->execute([$id]);
                $article_count = $stmt->fetchColumn();
                
                if ($article_count > 0) {
                    $_SESSION['error'] = 'Não é possível excluir uma categoria que possui artigos.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $_SESSION['success'] = 'Categoria excluída com sucesso!';
                    } else {
                        $_SESSION['error'] = 'Erro ao excluir categoria.';
                    }
                }
                break;
        }
        
        header('Location: /admin/categories');
        exit;
    }
}

// Obter categorias
$stmt = $pdo->query("
    SELECT c.*, COUNT(a.id) as article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id 
    GROUP BY c.id 
    ORDER BY c.order_position ASC, c.name ASC
");
$categories = $stmt->fetchAll();

// Categoria para edição
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_category = $stmt->fetch();
}

ob_start();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 600; color: #2d3748; margin-bottom: 0.5rem;">Gerenciar Categorias</h2>
        <p style="color: #718096;">Organize o conteúdo da sua central de ajuda em categorias.</p>
    </div>
    <button onclick="toggleModal('newCategoryModal')" class="btn btn-success">➕ Nova Categoria</button>
</div>

<?php if ($categories): ?>
<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Descrição</th>
                    <th>Artigos</th>
                    <th>Criado em</th>
                    <th style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                    <td><code style="background: #f7fafc; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($category['slug']) ?></code></td>
                    <td>
                        <?php if ($category['description']): ?>
                            <?= htmlspecialchars(substr($category['description'], 0, 100)) ?>
                            <?= strlen($category['description']) > 100 ? '...' : '' ?>
                        <?php else: ?>
                            <span style="color: #a0aec0; font-style: italic;">Sem descrição</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background: #edf2f7; color: #2d3748; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                            <?= $category['article_count'] ?> artigo<?= $category['article_count'] != 1 ? 's' : '' ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($category['created_at'])) ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <button onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)" class="btn btn-sm">✏️</button>
                            <?php if ($category['article_count'] == 0): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                            <?php endif; ?>
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
    <div style="font-size: 4rem; margin-bottom: 1rem;">📁</div>
    <h3 style="margin-bottom: 1rem;">Nenhuma categoria encontrada</h3>
    <p style="color: #718096; margin-bottom: 2rem;">Crie sua primeira categoria para organizar o conteúdo.</p>
    <button onclick="toggleModal('newCategoryModal')" class="btn btn-success">Criar primeira categoria</button>
</div>
<?php endif; ?>

<!-- Modal Nova Categoria -->
<div id="newCategoryModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.3rem; color: #2d3748;">Nova Categoria</h3>
            <button onclick="toggleModal('newCategoryModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="name">Nome da Categoria</label>
                <input type="text" id="name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Descrição</label>
                <textarea id="description" name="description" class="form-textarea" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="toggleModal('newCategoryModal')" class="btn" style="background: #e2e8f0; color: #2d3748;">Cancelar</button>
                <button type="submit" class="btn btn-success">Criar Categoria</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div id="editCategoryModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 2rem; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; font-size: 1.3rem; color: #2d3748;">Editar Categoria</h3>
            <button onclick="toggleModal('editCategoryModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <form method="POST" id="editCategoryForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label" for="edit_name">Nome da Categoria</label>
                <input type="text" id="edit_name" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit_slug">Slug (URL)</label>
                <input type="text" id="edit_slug" name="slug" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit_description">Descrição</label>
                <textarea id="edit_description" name="description" class="form-textarea" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="toggleModal('editCategoryModal')" class="btn" style="background: #e2e8f0; color: #2d3748;">Cancelar</button>
                <button type="submit" class="btn btn-success">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
    } else {
        modal.style.display = 'none';
    }
}

function editCategory(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_slug').value = category.slug;
    document.getElementById('edit_description').value = category.description || '';
    toggleModal('editCategoryModal');
}

// Fechar modal ao clicar fora
document.addEventListener('click', function(e) {
    if (e.target.style.background === 'rgba(0, 0, 0, 0.5)') {
        e.target.style.display = 'none';
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>