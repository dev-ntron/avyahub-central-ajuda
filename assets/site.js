// AvyaHub Central de Ajuda - JavaScript Robusto
let searchTimeout = null;
let selectedIndex = -1;
let searchCache = new Map();

// BUSCA AJAX EM TEMPO REAL
function performSearch(query) {
    const searchResults = document.querySelector('.search-results') || document.getElementById('searchResults');
    if (!searchResults) return;
    
    if (!query || query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }
    
    if (searchCache.has(query)) {
        displaySearchResults(searchCache.get(query), query);
        return;
    }
    
    searchResults.innerHTML = '<div class="search-result-item">🔍 Buscando...</div>';
    searchResults.style.display = 'block';
    
    const basePath = window.BASE_PATH || '';
    
    fetch(`${basePath}/api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            searchCache.set(query, data);
            displaySearchResults(data, query);
        })
        .catch(error => {
            console.error('Erro na busca:', error);
            searchResults.innerHTML = '<div class="search-result-item">❌ Erro na busca. Tente novamente.</div>';
        });
}

function displaySearchResults(results, query) {
    const searchResults = document.querySelector('.search-results') || document.getElementById('searchResults');
    if (!searchResults) return;
    
    if (!results || results.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">Nenhum resultado encontrado</div>';
        searchResults.style.display = 'block';
        return;
    }
    
    const basePath = window.BASE_PATH || '';
    let html = '';
    
    results.slice(0, 8).forEach((item, index) => {
        const url = `${basePath}/${item.slug}`;
        html += `
            <a href="${escapeHtml(url)}" class="search-result-item" data-index="${index}">
                <div class="search-result-title">${highlightText(escapeHtml(item.title), query)}</div>
                <div class="search-result-category">📁 ${escapeHtml(item.category_name || 'Geral')}</div>
                <div class="search-result-excerpt">${highlightText(escapeHtml(item.excerpt || ''), query)}</div>
            </a>
        `;
    });
    
    if (results.length > 8) {
        html += `<div class="search-result-item" style="text-align:center;color:var(--text-muted-light);font-style:italic;">+${results.length - 8} mais resultados...</div>`;
    }
    
    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
    selectedIndex = -1;
}

// DARK MODE PERSISTIDO
function toggleDarkMode() {
    const isDark = !document.body.classList.contains('dark');
    document.body.classList.toggle('dark', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    const toggle = document.querySelector('.dark-toggle');
    if (toggle) {
        toggle.textContent = isDark ? '☀️' : '🌙';
        toggle.title = isDark ? 'Alternar para modo claro' : 'Alternar para modo escuro';
    }
}

// NAVEGAÇÃO E SIDEBAR
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    const isOpen = sidebar?.classList.contains('open');
    
    if (isOpen) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
    } else {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function toggleCategory(element) {
    const category = element.closest('.nav-category');
    const articles = element.nextElementSibling;
    const arrow = element.querySelector('.icon') || element.querySelector('span:last-child');
    
    if (category) {
        category.classList.toggle('collapsed');
        
        if (arrow) {
            arrow.textContent = category.classList.contains('collapsed') ? '▶' : '▼';
        }
        
        // Persistir estado
        const slug = category.dataset.slug;
        if (slug) {
            const collapsed = category.classList.contains('collapsed');
            localStorage.setItem(`category-${slug}`, collapsed ? '1' : '0');
        }
    }
}

// UTILITÁRIOS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function highlightText(text, query) {
    if (!query || query.length < 2) return text;
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&')})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

function updateSelection(items) {
    items.forEach((item, index) => {
        item.classList.toggle('highlighted', index === selectedIndex);
    });
    
    if (selectedIndex >= 0 && items[selectedIndex]) {
        items[selectedIndex].scrollIntoView({ block: 'nearest' });
    }
}

function copyCurrentLink() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href)
            .then(() => showNotification('Link copiado!', 'success'))
            .catch(() => fallbackCopy());
    } else {
        fallbackCopy();
    }
    
    function fallbackCopy() {
        const textArea = document.createElement('textarea');
        textArea.value = window.location.href;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showNotification('Link copiado!', 'success');
        } catch (err) {
            showNotification('Erro ao copiar link', 'error');
        }
        document.body.removeChild(textArea);
    }
}

function showNotification(message, type = 'info') {
    // Remove notificação existente
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Criar nova
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed; top: 2rem; right: 2rem;
        background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#2563eb'};
        color: white; padding: 1rem 1.5rem; border-radius: 8px;
        box-shadow: 0 10px 15px rgba(0,0,0,0.1); z-index: 9999;
        animation: slideInRight 0.3s ease; font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// INICIALIZAÇÃO COMPLETA
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode inicial
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
        document.body.classList.add('dark');
        const toggle = document.querySelector('.dark-toggle');
        if (toggle) toggle.textContent = '☀️';
    }
    
    // Busca com debounce e navegação por teclado
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => performSearch(query), 250);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            const searchResults = document.querySelector('.search-results') || document.getElementById('searchResults');
            if (!searchResults || searchResults.style.display === 'none') return;
            
            const items = searchResults.querySelectorAll('.search-result-item');
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(items);
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        items[selectedIndex].click();
                    }
                    break;
                case 'Escape':
                    searchResults.style.display = 'none';
                    searchInput.blur();
                    break;
            }
        });
    }
    
    // Fechar busca ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-box')) {
            const searchResults = document.querySelector('.search-results') || document.getElementById('searchResults');
            if (searchResults) searchResults.style.display = 'none';
        }
    });
    
    // Restaurar estado das categorias
    document.querySelectorAll('.nav-category[data-slug]').forEach(category => {
        const slug = category.dataset.slug;
        const isCollapsed = localStorage.getItem(`category-${slug}`) === '1';
        if (isCollapsed) {
            category.classList.add('collapsed');
            const arrow = category.querySelector('.nav-category-title .icon, .nav-category-title span:last-child');
            if (arrow) arrow.textContent = '▶';
        }
    });
    
    // Botão voltar ao topo
    const backToTop = document.createElement('button');
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '↑';
    backToTop.title = 'Voltar ao topo';
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', () => {
        backToTop.classList.toggle('visible', window.pageYOffset > 300);
    });
    
    // Fechar sidebar com overlay
    const overlay = document.querySelector('.overlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
});

// ATALHOS DE TECLADO
document.addEventListener('keydown', function(e) {
    // Ctrl+/ para focar busca
    if (e.ctrlKey && e.key === '/') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Alt+D para dark mode
    if (e.altKey && e.key.toLowerCase() === 'd') {
        e.preventDefault();
        toggleDarkMode();
    }
    
    // Escape para fechar busca e sidebar
    if (e.key === 'Escape') {
        const searchResults = document.querySelector('.search-results') || document.getElementById('searchResults');
        if (searchResults) searchResults.style.display = 'none';
        
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');
        if (sidebar && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
});

// Adicionar CSS dinâmico
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    mark {
        background: rgba(37, 99, 235, 0.2);
        color: inherit;
        padding: 0.1rem 0.2rem;
        border-radius: 3px;
    }
`;
document.head.appendChild(style);