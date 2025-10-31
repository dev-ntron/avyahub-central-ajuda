<?php
$page_title = 'Dashboard';

// Obter estatísticas
$stats = [];

// Total de categorias
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$stats['categories'] = $stmt->fetchColumn();

// Total de artigos
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$stats['articles'] = $stmt->fetchColumn();

// Artigos publicados
$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1");
$stats['published'] = $stmt->fetchColumn();

// Artigos recentes
$stmt = $pdo->query("SELECT a.*, c.name as category_name FROM articles a JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC LIMIT 5");
$recent_articles = $stmt->fetchAll();

ob_start();
?>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="background: #3182ce; color: white; padding: 1rem; border-radius: 8px; font-size: 1.5rem;">📁</div>
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #2d3748;"><?= $stats['categories'] ?></div>
                <div style="color: #718096;">Categorias</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="background: #38a169; color: white; padding: 1rem; border-radius: 8px; font-size: 1.5rem;">📝</div>
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #2d3748;"><?= $stats['articles'] ?></div>
                <div style="color: #718096;">Total de Artigos</div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="background: #d69e2e; color: white; padding: 1rem; border-radius: 8px; font-size: 1.5rem;">✅</div>
            <div>
                <div style="font-size: 2rem; font-weight: bold; color: #2d3748;"><?= $stats['published'] ?></div>
                <div style="color: #718096;">Artigos Publicados</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.2rem; font-weight: 600; color: #2d3748;">Artigos Recentes</h2>
        <a href="/admin/articles" class="btn btn-sm">Ver Todos</a>
    </div>
    
    <?php if ($recent_articles): ?>
    <div class="table-container" style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_articles as $article): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($article['title']) ?></strong>
                        <?php if ($article['excerpt']): ?>
                        <div style="font-size: 0.8rem; color: #718096; margin-top: 0.25rem;">
                            <?= htmlspecialchars(substr($article['excerpt'], 0, 100)) ?>...
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($article['category_name']) ?></td>
                    <td>
                        <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; 
                              background: <?= $article['is_published'] ? '#f0fff4; color: #2f855a' : '#fed7d7; color: #c53030' ?>;">
                            <?= $article['is_published'] ? 'Publicado' : 'Rascunho' ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="/<?= $article['slug'] ?>" class="btn btn-sm" style="background: #38a169;" target="_blank">Ver</a>
                            <a href="/admin/articles?edit=<?= $article['id'] ?>" class="btn btn-sm">Editar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center" style="padding: 2rem; color: #718096;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
        <p>Nenhum artigo encontrado. <a href="/admin/articles" style="color: #3182ce;">Criar primeiro artigo</a></p>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="font-size: 1.2rem; font-weight: 600; color: #2d3748; margin-bottom: 1rem;">Ações Rápidas</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
        <a href="/admin/articles?new=1" class="btn btn-success">➕ Novo Artigo</a>
        <a href="/admin/categories?new=1" class="btn btn-success">📁 Nova Categoria</a>
        <a href="/admin/media" class="btn">🖼️ Gerenciar Mídia</a>
        <a href="/admin/settings" class="btn">⚙️ Configurações</a>
        <a href="/" class="btn" target="_blank">🌍 Ver Site</a>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>