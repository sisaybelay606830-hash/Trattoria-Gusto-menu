/**
 * Kaldis Coffee PLC — Digital Menu System
 * Client-Side Interactive Filtering, Search & Layout Reliability
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('menuSearchInput');
    const productCards = document.querySelectorAll('.product-card-col');
    const emptyState = document.getElementById('menuEmptyState');
    const searchClearBtn = document.getElementById('searchClearBtn');
    const activeResultsCount = document.getElementById('activeResultsCount');
    const header = document.querySelector('.kaldis-header');

    // 1. Header scroll effect for glassmorphism
    if (header) {
        const handleScroll = () => {
            if (window.scrollY > 20) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    }

    // 2. Center active category pill horizontally on mobile load
    const activePill = document.querySelector('.category-nav-scroll .cat-pill.active');
    if (activePill) {
        const scrollContainer = document.querySelector('.category-nav-scroll');
        if (scrollContainer) {
            const containerWidth = scrollContainer.offsetWidth;
            const pillLeft = activePill.offsetLeft;
            const pillWidth = activePill.offsetWidth;
            scrollContainer.scrollTo({
                left: pillLeft - (containerWidth / 2) + (pillWidth / 2),
                behavior: 'smooth'
            });
        }
    }

    // 3. Image error resilience: gracefully replace broken image with placeholder icon
    document.querySelectorAll('.product-image').forEach((img) => {
        img.addEventListener('error', function () {
            const container = this.closest('.product-image-container');
            if (container) {
                this.style.display = 'none';
                if (!container.querySelector('.product-placeholder-icon')) {
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-cup-hot product-placeholder-icon';
                    container.appendChild(icon);
                }
            }
        });
    });

    // 4. Real-time search filter with debouncing
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.toLowerCase().trim();

            if (searchClearBtn) {
                searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
            }

            debounceTimer = setTimeout(() => {
                filterProducts(query);
            }, 80);
        });

        // Keyboard accessibility: Escape clears the search input
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                searchInput.value = '';
                if (searchClearBtn) {
                    searchClearBtn.style.display = 'none';
                }
                filterProducts('');
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
