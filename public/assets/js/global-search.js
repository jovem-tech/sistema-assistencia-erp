/**
 * Global Search JS
 * Handles AJAX search, debounce, keyboard navigation and rendering.
 */
class GlobalSearch {
    constructor() {
        this.$wrapper = document.querySelector('.navbar-search-wrapper');
        this.$input = document.querySelector('.search-input');
        this.$container = document.querySelector('.search-results-container');
        this.$loading = document.querySelector('.search-loading-state');
        this.$empty = document.querySelector('.search-empty-state');
        this.$list = document.querySelector('.search-results-list');
        this.$filterBtn = document.querySelector('.search-filter-btn');
        this.$filterItems = document.querySelectorAll('.search-filter-menu .dropdown-item');
        this.$filterLabel = document.querySelector('.filter-label');
        this.currentFilters = ['all'];
        this.timeout = null;
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        this.selectedIndex = -1;

        if (this.$input) {
            this.init();
        }
    }

    init() {
        // Evento de troca de filtro (Multi-seleção)
        this.$filterItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            
            item.addEventListener('click', (e) => {
                // Impede que o clique no label/checkbox dispare o evento duas vezes
                if (e.target.tagName !== 'INPUT') {
                    checkbox.checked = !checkbox.checked;
                }
                
                const filter = item.getAttribute('data-filter');
                
                if (filter === 'all') {
                    // Se selecionou "Tudo", desmarca os outros
                    this.currentFilters = ['all'];
                    this.$filterItems.forEach(i => {
                        const cb = i.querySelector('input[type="checkbox"]');
                        if (i.getAttribute('data-filter') !== 'all') {
                            cb.checked = false;
                            i.classList.remove('active');
                        } else {
                            cb.checked = true;
                            i.classList.add('active');
                        }
                    });
                } else {
                    // Se selecionou outro, desmarca "Tudo"
                    const allItem = document.querySelector('.filter-all');
                    const allCb = allItem.querySelector('input[type="checkbox"]');
                    allCb.checked = false;
                    allItem.classList.remove('active');
                    
                    if (checkbox.checked) {
                        item.classList.add('active');
                        if (!this.currentFilters.includes(filter)) {
                            this.currentFilters.push(filter);
                        }
                    } else {
                        item.classList.remove('active');
                        this.currentFilters = this.currentFilters.filter(f => f !== filter);
                    }
                    
                    // Remove 'all' se houver outros selecionados
                    this.currentFilters = this.currentFilters.filter(f => f !== 'all');
                    
                    // Se não sobrou nada, volta para 'all'
                    if (this.currentFilters.length === 0) {
                        this.currentFilters = ['all'];
                        allCb.checked = true;
                        allItem.classList.add('active');
                    }
                }
                
                this.updateFilterLabel();
                
                // Se já houver termo, refaz a busca
                const query = this.$input.value.trim();
                if (query.length >= 2) {
                    this.performSearch(query);
                }
            });
        });

        // Evento de input com debounce
        this.$input.addEventListener('input', (e) => {
            clearTimeout(this.timeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                this.hide();
                return;
            }

            this.timeout = setTimeout(() => this.performSearch(query), 300);
        });

        // Eventos de teclado (Navegação)
        this.$input.addEventListener('keydown', (e) => {
            const items = this.$list.querySelectorAll('.search-result-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.show();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection(items);
            } else if (e.key === 'Enter') {
                if (this.selectedIndex > -1 && items[this.selectedIndex]) {
                    e.preventDefault();
                    items[this.selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                this.hide();
            }
        });

        // Clique fora para fechar
        document.addEventListener('click', (e) => {
            if (!this.$wrapper.contains(e.target)) {
                this.hide();
            }
        });

        // Mostrar ao focar se tiver texto
        this.$input.addEventListener('focus', () => {
            if (this.$input.value.trim().length >= 2) {
                this.show();
            }
        });
    }

    updateFilterLabel() {
        if (this.currentFilters.includes('all')) {
            this.$filterLabel.textContent = 'Tudo';
        } else if (this.currentFilters.length === 1) {
            const item = document.querySelector(`.dropdown-item[data-filter="${this.currentFilters[0]}"]`);
            this.$filterLabel.textContent = item.querySelector('.form-check-label').textContent.trim();
        } else {
            this.$filterLabel.textContent = `${this.currentFilters.length} Filtros`;
        }
    }

    async performSearch(query) {
        this.show();
        this.setLoading(true);

        try {
            const filters = this.currentFilters.join(',');
            const response = await fetch(`${this.baseUrl}/api/busca-global?q=${encodeURIComponent(query)}&filter=${filters}`);
            if (!response.ok) throw new Error('Falha na busca');
            
            const results = await response.json();
            this.renderResults(results);
        } catch (error) {
            console.error('Erro na busca global:', error);
            this.renderEmpty('Ocorreu um erro ao buscar.');
        } finally {
            this.setLoading(false);
        }
    }

    updateSelection(items) {
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    renderResults(groupedResults) {
        this.$list.innerHTML = '';
        this.selectedIndex = -1;

        const hasResults = Object.keys(groupedResults).length > 0;

        if (!hasResults) {
            this.renderEmpty('Nenhum resultado encontrado.');
            return;
        }

        this.$empty.classList.add('d-none');
        this.$list.classList.remove('d-none');

        for (const group in groupedResults) {
            const items = groupedResults[group];
            if (items.length === 0) continue;

            const groupHeader = document.createElement('div');
            groupHeader.className = 'search-group-title';
            groupHeader.textContent = group;
            this.$list.appendChild(groupHeader);

            items.forEach(item => {
                const link = document.createElement('a');
                link.href = item.url;
                link.className = 'search-result-item';
                link.innerHTML = `
                    <div class="result-icon">
                        <i class="bi ${item.icon || 'bi-hash'}"></i>
                    </div>
                    <div class="result-info">
                        <span class="result-title">${item.title}</span>
                        <span class="result-subtitle">${item.subtitle}</span>
                    </div>
                    ${item.badge ? `<span class="result-badge">${item.badge}</span>` : ''}
                `;
                this.$list.appendChild(link);
            });
        }
    }

    renderEmpty(message) {
        this.$list.classList.add('d-none');
        this.$empty.classList.remove('d-none');
        this.$empty.querySelector('p').textContent = message || 'Nenhum resultado encontrado.';
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.$loading.classList.remove('d-none');
            this.$list.classList.add('d-none');
            this.$empty.classList.add('d-none');
        } else {
            this.$loading.classList.add('d-none');
        }
    }

    show() {
        this.$container.classList.add('active');
    }

    hide() {
        this.$container.classList.remove('active');
    }
}

// Inicializa a busca global quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.globalSearch = new GlobalSearch();
});
