<div class="container py-3 py-md-4">
    <!-- Hero / Welcome Banner -->
    <div class="hero-banner mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-gold-subtle text-gold-dark mb-2 px-3 py-1 rounded-pill fw-semibold">
                    <i class="bi bi-stars me-1"></i> Authentic Ethiopian Coffee Experience
                </span>
                <h1 class="hero-title mb-2"><?= e($settings['menu_title']['value'] ?? 'Trattoria-Gusto') ?></h1>
                <p class="lead mb-0 text-white-50" style="font-size: 1rem;">
                    <?= e($settings['menu_subtitle']['value'] ?? 'Freshly brewed single-origin beans, traditional delicacies, and light meals.') ?>
                </p>
                <?php if (!empty($branch)): ?>
                    <div class="mt-3 d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill small">
                        <i class="bi bi-geo-alt-fill text-gold"></i>
                        <span>Serving you at <strong><?= e($branch['name']) ?></strong></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8 col-lg-6">
            <div class="search-wrapper position-relative">
                <i class="bi bi-search search-icon"></i>
                <input 
                    type="search" 
                    id="menuSearchInput" 
                    class="form-control search-input" 
                    placeholder="Search menu (e.g. Macchiato, Croissant, Club Sandwich)..."
                    value="<?= e($search ?? '') ?>"
                    autocomplete="off"
                >
                <button type="button" id="searchClearBtn" class="btn btn-sm text-muted position-absolute end-0 top-50 translate-middle-y me-2" style="display: <?= !empty($search) ? 'block' : 'none' ?>;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Parent Category Horizontal Scroll Navigation -->
    <div class="mb-3">
        <div class="category-nav-scroll d-flex gap-2">
            <?php
            $currentParent = isset($_GET['parent']) ? (int)$_GET['parent'] : 0;
            $currentChild  = isset($_GET['child']) ? (int)$_GET['child'] : 0;
            $branchParam   = !empty($branch) ? '&branch=' . urlencode($branch['code']) : '';
            ?>
            <a href="<?= base_url('menu') . ($branchParam ? '?' . ltrim($branchParam, '&') : '') ?>" 
               class="cat-pill <?= $currentParent === 0 ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span>All Items</span>
                <span class="cat-badge"><?= count($products['data'] ?? []) ?></span>
            </a>

            <?php foreach ($categories as $cat): ?>
                <?php if ((bool)$cat['is_visible']): ?>
                    <a href="<?= base_url('menu?parent=' . $cat['id'] . $branchParam) ?>" 
                       class="cat-pill <?= $currentParent === (int)$cat['id'] ? 'active' : '' ?>">
                        <span><?= e($cat['name']) ?></span>
                        <span class="cat-badge"><?= count($cat['children'] ?? []) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Child Category Sub-filters (shown if current parent has children) -->
    <?php if ($currentParent > 0): ?>
        <?php 
        $activeParentObj = null;
        foreach ($categories as $cat) {
            if ((int)$cat['id'] === $currentParent) {
                $activeParentObj = $cat;
                break;
            }
        }
        ?>
        <?php if ($activeParentObj && !empty($activeParentObj['children'])): ?>
            <div class="d-flex flex-wrap gap-2 mb-4 p-2 bg-white rounded-3 border border-1">
                <a href="<?= base_url('menu?parent=' . $currentParent . $branchParam) ?>" 
                   class="subcat-pill <?= $currentChild === 0 ? 'active' : '' ?>">
                    All in <?= e($activeParentObj['name']) ?>
                </a>
                <?php foreach ($activeParentObj['children'] as $ch): ?>
                    <?php if ((bool)$ch['is_visible']): ?>
                        <a href="<?= base_url('menu?parent=' . $currentParent . '&child=' . $ch['id'] . $branchParam) ?>" 
                           class="subcat-pill <?= $currentChild === (int)$ch['id'] ? 'active' : '' ?>">
                            <?= e($ch['name']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Product Grid -->
    <div class="row g-3 g-md-4" id="productGrid">
        <?php if (!empty($products['data'])): ?>
            <?php foreach ($products['data'] as $p): ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 product-card-col" 
                     data-name="<?= e(strtolower($p['name'])) ?>"
                     data-desc="<?= e(strtolower($p['local_description'] ?? '')) ?>"
                     data-code="<?= e(strtolower($p['article_code'])) ?>"
                     data-parent="<?= e(strtolower($p['parent_category_name'] ?? '')) ?>"
                     data-child="<?= e(strtolower($p['child_category_name'] ?? '')) ?>">
                    
                    <div class="product-card">
                        <!-- Image Container -->
                        <div class="product-image-container">
                            <?php if (!empty($p['image_path']) && file_exists(BASE_PATH . '/public/' . ltrim($p['image_path'], '/'))): ?>
                                <img src="<?= base_url($p['image_path']) ?>" alt="<?= e($p['name']) ?>" class="product-image" loading="lazy">
                            <?php else: ?>
                                <i class="bi bi-cup-hot product-placeholder-icon"></i>
                            <?php endif; ?>

                            <div class="product-badge-overlay">
                                <?php if (!empty($p['consignee_unit'])): ?>
                                    <span class="badge bg-espresso bg-opacity-75 text-white extra-small px-2 py-1 rounded-pill">
                                        <?= e($p['consignee_unit']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="product-card-body">
                            <div class="product-category-breadcrumb">
                                <?= e($p['parent_category_name'] ?? 'Menu') ?>
                                <?php if (!empty($p['child_category_name'])): ?>
                                    &middot; <?= e($p['child_category_name']) ?>
                                <?php endif; ?>
                            </div>

                            <h3 class="product-title"><?= e($p['name']) ?></h3>

                            <?php if (!empty($p['local_description'])): ?>
                                <p class="product-description"><?= e($p['local_description']) ?></p>
                            <?php else: ?>
                                <p class="product-description text-muted fst-italic">Prepared fresh with premium ingredients.</p>
                            <?php endif; ?>

                            <?php
                            $vatRate = (float)($settings['vat_rate']['value'] ?? '1.20');
                            $displayPrice = null;
                            if ($p['price'] !== null && (float)$p['price'] > 0) {
                                $displayPrice = (float)$p['price'];
                            } elseif (!empty($p['default_value']) && is_numeric($p['default_value']) && (float)$p['default_value'] > 0) {
                                $displayPrice = round((float)$p['default_value'] * $vatRate, 2);
                            }
                            ?>
                            <div class="product-footer">
                                <?php if ($showPrices && $displayPrice !== null): ?>
                                    <div>
                                        <span class="product-price"><?= number_format($displayPrice, 2) ?></span>
                                        <span class="product-price-currency"><?= e($p['price_currency'] ?? 'ETB') ?></span>
                                        <span class="text-muted extra-small d-block text-nowrap" style="font-size: 0.68rem;">incl. VAT</span>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 extra-small fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i> Available
                                    </span>
                                <?php endif; ?>

                                <span class="badge bg-light text-muted extra-small">
                                    #<?= e($p['article_code']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty State -->
    <div id="menuEmptyState" class="text-center py-5 my-5" style="display: <?= empty($products['data']) ? 'block' : 'none' ?>;">
        <div class="mb-3 text-muted" style="font-size: 3rem;">
            <i class="bi bi-cup"></i>
        </div>
        <h4 class="fw-bold text-espresso mb-2">No Menu Items Found</h4>
        <p class="text-muted mb-4">We couldn't find any products matching your selection.</p>
        <a href="<?= base_url('menu') . ($branchParam ? '?' . ltrim($branchParam, '&') : '') ?>" class="btn btn-gold rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> View Full Menu
        </a>
    </div>
</div>

