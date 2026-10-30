/**
 * Trattoria-Gusto — Digital Menu System
 * Client-Side Interactive Filtering & Search
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('menuSearchInput');
    const productGrid = document.getElementById('productGrid');
    const productCards = document.querySelectorAll('.product-card-col');
    const emptyState = document.getElementById('menuEmptyState');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const activeResultsCount = document.getElementById('activeResultsCount');

    // Real-time search filter
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            filterProducts(query);

            if (searchClearBtn) {
                searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
            }
        });

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchClearBtn.style.display = 'none';
                filterProducts('');
                searchInput.focus();
            });
        }
    }

    function filterProducts(query) {
        let visibleCount = 0;

        productCards.forEach((col) => {
            const name = (col.getAttribute('data-name') || '').toLowerCase();
            const desc = (col.getAttribute('data-desc') || '').toLowerCase();
            const code = (col.getAttribute('data-code') || '').toLowerCase();
            const parent = (col.getAttribute('data-parent') || '').toLowerCase();
            const child = (col.getAttribute('data-child') || '').toLowerCase();

            const matches = !query ||
                name.includes(query) ||
                desc.includes(query) ||
                code.includes(query) ||
                parent.includes(query) ||
                child.includes(query);

            if (matches) {
                col.style.display = '';
                visibleCount++;
            } else {
                col.style.display = 'none';
            }
        });

        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (activeResultsCount) {
            activeResultsCount.textContent = visibleCount;
        }
    }
});

