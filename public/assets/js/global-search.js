/**
 * Global Search JS
 * Handles AJAX search, debounce, keyboard navigation and rendering per wrapper.
 */
class GlobalSearch {
    constructor(wrapper) {
        this.$wrapper = wrapper || document.querySelector('.navbar-search-wrapper');
        this.$input = this.$wrapper?.querySelector('.search-input') || null;
        this.$container = this.$wrapper?.querySelector('.search-results-container') || null;
        this.$loading = this.$wrapper?.querySelector('.search-loading-state') || null;
        this.$empty = this.$wrapper?.querySelector('.search-empty-state') || null;
        this.$list = this.$wrapper?.querySelector('.search-results-list') || null;
        this.$filterBtn = this.$wrapper?.querySelector('.search-filter-btn') || null;
        this.$filterItems = this.$wrapper ? this.$wrapper.querySelectorAll('.search-filter-menu .dropdown-item') : [];
        this.$filterLabel = this.$wrapper?.querySelector('.filter-label') || null;
        this.currentFilters = ['all'];
        this.timeout = null;
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        this.selectedIndex = -1;
        this.ready = Boolean(this.$wrapper && this.$input && this.$container && this.$list);

        if (this.ready) {
            this.init();
        }
    }

    init() {
        this.$filterItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');

            if (!checkbox) {
                return;
            }

            item.addEventListener('click', (event) => {
                event.preventDefault();
                checkbox.checked = !checkbox.checked;

                const filter = item.getAttribute('data-filter');

                if (filter === 'all') {
                    this.currentFilters = ['all'];
                    this.$filterItems.forEach(filterItem => {
                        const filterCheckbox = filterItem.querySelector('input[type="checkbox"]');

                        if (!filterCheckbox) {
                            return;
                        }

                        if (filterItem.getAttribute('data-filter') !== 'all') {
                            filterCheckbox.checked = false;
                            filterItem.classList.remove('active');
                        } else {
                            filterCheckbox.checked = true;
                            filterItem.classList.add('active');
                        }
                    });
                } else {
                    const allItem = this.$wrapper.querySelector('.filter-all');
                    const allCheckbox = allItem?.querySelector('input[type="checkbox"]');

                    if (allCheckbox) {
                        allCheckbox.checked = false;
                    }

                    allItem?.classList.remove('active');

                    if (checkbox.checked) {
                        item.classList.add('active');

                        if (!this.currentFilters.includes(filter)) {
                            this.currentFilters.push(filter);
                        }
                    } else {
                        item.classList.remove('active');
                        this.currentFilters = this.currentFilters.filter(currentFilter => currentFilter !== filter);
                    }

                    this.currentFilters = this.currentFilters.filter(currentFilter => currentFilter !== 'all');

                    if (this.currentFilters.length === 0) {
                        this.currentFilters = ['all'];

                        if (allCheckbox) {
                            allCheckbox.checked = true;
                        }

                        allItem?.classList.add('active');
                    }
                }

                this.updateFilterLabel();

                const query = this.$input.value.trim();

                if (query.length >= 2) {
                    this.performSearch(query);
                }
            });
        });

        this.$input.addEventListener('input', (event) => {
            clearTimeout(this.timeout);
            const query = event.target.value.trim();

            if (query.length < 2) {
                this.hide();
                return;
            }

            this.timeout = setTimeout(() => this.performSearch(query), 300);
        });

        this.$input.addEventListener('keydown', (event) => {
            const items = this.$list.querySelectorAll('.search-result-item');

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.show();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.updateSelection(items);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection(items);
            } else if (event.key === 'Enter') {
                if (this.selectedIndex > -1 && items[this.selectedIndex]) {
                    event.preventDefault();
                    items[this.selectedIndex].click();
                }
            } else if (event.key === 'Escape') {
                this.hide();
            }
        });

        document.addEventListener('click', (event) => {
            if (!this.$wrapper.contains(event.target)) {
                this.hide();
            }
        });

        this.$input.addEventListener('focus', () => {
            if (this.$input.value.trim().length >= 2) {
                this.show();
            }
        });
    }

    updateFilterLabel() {
        if (!this.$filterLabel) {
            return;
        }

        if (this.currentFilters.includes('all')) {
            this.$filterLabel.textContent = 'Tudo';
        } else if (this.currentFilters.length === 1) {
            const item = this.$wrapper.querySelector(`.dropdown-item[data-filter="${this.currentFilters[0]}"]`);
            this.$filterLabel.textContent = item?.querySelector('.form-check-label')?.textContent.trim() || 'Filtro';
        } else {
            this.$filterLabel.textContent = `${this.currentFilters.length} Filtros`;
        }
    }

    async performSearch(query) {
        this.show();
        this.setLoading(true);

        try {
            const filters = this.currentFilters.join(',');
            const baseUrl = this.baseUrl.replace(/\/$/, '');
            const response = await fetch(`${baseUrl}/api/busca-global?q=${encodeURIComponent(query)}&filter=${filters}`);

            if (!response.ok) {
                throw new Error('Falha na busca');
            }

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

            if (items.length === 0) {
                continue;
            }

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

document.addEventListener('DOMContentLoaded', () => {
    window.globalSearchInstances = Array.from(document.querySelectorAll('.navbar-search-wrapper'))
        .map(wrapper => new GlobalSearch(wrapper))
        .filter(instance => instance.ready);

    window.globalSearch = window.globalSearchInstances[0] || null;
});
