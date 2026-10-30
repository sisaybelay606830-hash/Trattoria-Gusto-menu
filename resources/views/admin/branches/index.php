<div class="row g-4">
    <!-- Branch List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-espresso mb-0">Trattoria-Gusto Branches</h6>
                    <span class="text-muted extra-small">Manage locations and branch-specific QR links</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Branch Name</th>
                            <th>Code</th>
                            <th>Contact / Address</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($branches)): ?>
                            <?php foreach ($branches as $b): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-espresso"><?= e($b['name']) ?></div>
                                        <?php if (!empty($b['address'])): ?>
                                            <span class="text-muted extra-small text-truncate d-block" style="max-width: 250px;">
                                                <i class="bi bi-geo-alt"></i> <?= e($b['address']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="text-espresso fw-bold"><?= e($b['code']) ?></code>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= e($b['phone'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <?php if ((bool)$b['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= base_url('menu?branch=' . urlencode($b['code'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 extra-small" title="Preview Branch Menu">
                                                <i class="bi bi-box-arrow-up-right"></i> Menu
                                            </a>
                                            <form action="<?= base_url('admin/qrcodes/generate') ?>" method="POST" class="d-inline">
                                                <?= \App\Helpers\Csrf::field() ?>
                                                <input type="hidden" name="type" value="branch">
                                                <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                                                <input type="hidden" name="label" value="Trattoria-Gusto - <?= e($b['name']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-gold rounded-pill px-2 py-1 extra-small" title="Generate QR Code">
                                                    <i class="bi bi-qr-code"></i> QR
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No branches found. Add your first branch using the form on the right.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Branch Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-plus-circle me-1 text-gold"></i> Add New Branch</h6>
            </div>
            <div class="card-body p-3">
                <form action="<?= base_url('admin/branches') ?>" method="POST">
                    <?= \App\Helpers\Csrf::field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-espresso">Branch Name</label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required placeholder="e.g. Trattoria-Gusto Bole Medhanialem">
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label small fw-semibold text-espresso">Branch Code (URL-Safe)</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="code" name="code" required placeholder="e.g. BOLE_MED">
                        <div class="form-text extra-small">Used in QR URLs: <code>/menu?branch=CODE</code></div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label small fw-semibold text-espresso">Address / Location</label>
                        <textarea class="form-control form-control-sm" id="address" name="address" rows="2" placeholder="e.g. Cameroon St, Bole, Addis Ababa"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label small fw-semibold text-espresso">Phone Number</label>
                        <input type="text" class="form-control form-control-sm" id="phone" name="phone" placeholder="e.g. +251 116 61 77 00">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label small fw-semibold" for="is_active">Branch is Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-sm w-100 rounded-pill py-2 fw-semibold">
                        <i class="bi bi-check2 me-1"></i> Save Branch
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

