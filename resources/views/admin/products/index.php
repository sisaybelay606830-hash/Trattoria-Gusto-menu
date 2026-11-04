<!-- Product Filters -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?= base_url('admin/products') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input 
                        type="text" 
                        name="q" 
                        class="form-control border-start-0" 
                        placeholder="Search product name or code..."
                        value="<?= e($search ?? '') ?>"
                    >
                </div>
            </div>

            <div class="col-md-3">
                <select name="parent" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Parent Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)($parentId ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="visible" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Visibility</option>
                    <option value="1" <?= ($visible ?? '') === '1' ? 'selected' : '' ?>>Public Only</option>
                    <option value="0" <?= ($visible ?? '') === '0' ? 'selected' : '' ?>>Hidden Only</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-gold btn-sm px-3 rounded-pill">Filter</button>
                <a href="<?= base_url('admin/products') ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Product Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold text-espresso mb-0">Products Catalog</h6>
            <span class="text-muted extra-small">Showing <?= count($products) ?> of <?= number_format($total) ?> total items</span>
        </div>
        <div>
            <a href="<?= base_url('admin/import') ?>" class="btn btn-outline-gold btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-repeat me-1"></i> Sync from Source
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">Img</th>
                    <th>Article Code</th>
                    <th>Product Name</th>
                    <th>Categories (Parent &rarr; Child)</th>
                    <th>Verified Price</th>
                    <th>Source Active</th>
                    <th>Menu Visible</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div class="rounded-3 overflow-hidden bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <?php if (!empty($p['image_path']) && file_exists(BASE_PATH . '/public/' . ltrim($p['image_path'], '/'))): ?>
                                        <img src="<?= base_url($p['image_path']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-cup-hot text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <code class="text-dark fw-semibold"><?= e($p['article_code']) ?></code>
                            </td>
                            <td>
                                <div class="fw-semibold text-espresso"><?= e($p['name']) ?></div>
                                <?php if (!empty($p['consignee_unit'])): ?>
                                    <span class="text-muted extra-small">Unit: <?= e($p['consignee_unit']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><span class="badge bg-light text-dark border"><?= e($p['parent_category_name'] ?? 'Uncategorized') ?></span></div>
                                <?php if (!empty($p['child_category_name'])): ?>
                                    <div class="mt-1"><span class="text-muted extra-small">&rarr; <?= e($p['child_category_name']) ?></span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $effPrice = null;
                                $vatApplied = (float)($vatRate ?? 1.20);
                                $isCustom = false;
                                if ($p['price'] !== null && (float)$p['price'] > 0) {
                                    $effPrice = (float)$p['price'];
                                    $isCustom = true;
                                } elseif (!empty($p['default_value']) && is_numeric($p['default_value']) && (float)$p['default_value'] > 0) {
                                    $effPrice = round((float)$p['default_value'] * $vatApplied, 2);
                                    $isCustom = false;
                                }
                                ?>
                                <?php if ($effPrice !== null): ?>
                                    <div class="fw-bold text-espresso"><?= number_format($effPrice, 2) ?> <?= e($p['price_currency'] ?? 'ETB') ?></div>
                                    <?php if (!empty($p['default_value'])): ?>
                                        <span class="text-muted extra-small d-block" style="font-size: 0.7rem;">
                                            Base: <?= number_format((float)$p['default_value'], 2) ?> &times; <?= $vatApplied ?>
                                            <?= $isCustom ? '<span class="badge bg-info text-dark ms-1" style="font-size:0.6rem">Manual</span>' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted extra-small fst-italic">Not verified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((bool)$p['is_active_source']): ?>
                                    <span class="badge bg-success-subtle text-success extra-small fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small fw-semibold">
                                        <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((bool)$p['is_visible']): ?>
                                    <span class="badge bg-success extra-small"><i class="bi bi-eye-fill"></i> Visible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger extra-small"><i class="bi bi-eye-slash-fill"></i> Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('admin/products/' . $p['id']) ?>" class="btn btn-sm btn-outline-gold rounded-pill px-3 py-1">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No products found matching your filter criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <span class="text-muted extra-small">Page <?= $page ?> of <?= $lastPage ?></span>
            <nav aria-label="Product navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link text-espresso" href="<?= base_url('admin/products?page=' . ($page - 1) . '&q=' . urlencode($search) . '&parent=' . $parentId . '&visible=' . $visible) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link <?= $i === $page ? 'bg-gold border-gold text-white' : 'text-espresso' ?>" href="<?= base_url('admin/products?page=' . $i . '&q=' . urlencode($search) . '&parent=' . $parentId . '&visible=' . $visible) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $lastPage): ?>
                        <li class="page-item">
                            <a class="page-link text-espresso" href="<?= base_url('admin/products?page=' . ($page + 1) . '&q=' . urlencode($search) . '&parent=' . $parentId . '&visible=' . $visible) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

