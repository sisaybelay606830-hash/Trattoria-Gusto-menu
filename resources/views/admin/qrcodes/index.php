<div class="row g-4">
    <!-- QR Code List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-espresso mb-0">Generated QR Codes</h6>
                    <span class="text-muted extra-small">Print or download QR codes for physical table displays & counter tent cards</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">QR</th>
                            <th>Label / Type</th>
                            <th>Public URL Destination</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($qrcodes)): ?>
                            <?php foreach ($qrcodes as $qr): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $preview = $service->getBase64Preview((int)$qr['id']);
                                            $customLogo = (new \App\Repositories\SettingsRepository())->get('custom_logo', '');
                                            $hasCustomLogo = !empty($customLogo) && file_exists(BASE_PATH . '/public/' . ltrim($customLogo, '/'));
                                        ?>
                                        <?php if ($preview): ?>
                                            <div class="position-relative d-inline-block" style="width: 56px; height: 56px;">
                                                <img src="<?= $preview ?>" alt="QR" class="rounded border p-1 bg-white w-100 h-100">
                                                <div class="position-absolute top-50 start-50 translate-middle bg-white rounded-circle shadow-sm border border-gold d-flex align-items-center justify-content-center overflow-hidden" style="width: 18px; height: 18px; padding: 1px;">
                                                    <?php if ($hasCustomLogo): ?>
                                                        <img src="<?= base_url($customLogo) ?>" alt="logo" style="width: 100%; height: 100%; object-fit: contain;">
                                                    <?php else: ?>
                                                        <i class="bi bi-cup-hot-fill text-gold" style="font-size: 0.55rem;"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded border p-1 bg-light d-flex align-items-center justify-content-center text-muted" style="width: 54px; height: 54px;">
                                                <i class="bi bi-qr-code"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-espresso"><?= e($qr['label']) ?></div>
                                        <span class="badge bg-light text-dark border extra-small">
                                            <?= e(strtoupper($qr['type'])) ?>
                                            <?php if (!empty($qr['branch_name'])): ?>
                                                &middot; <?= e($qr['branch_name']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;">
                                            <a href="<?= e($qr['public_url']) ?>" target="_blank" class="text-decoration-none text-gold small">
                                                <i class="bi bi-link-45deg"></i> <?= e($qr['public_url']) ?>
                                            </a>
                                        </div>
                                        <span class="text-muted extra-small">Created <?= date('M j, Y', strtotime($qr['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <?php if ((bool)$qr['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Deactivated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="<?= base_url('admin/qrcodes/' . $qr['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 extra-small" title="Print Label">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                            <a href="<?= base_url('admin/qrcodes/' . $qr['id'] . '/download') ?>" class="btn btn-sm btn-outline-gold rounded-pill px-2 py-1 extra-small" title="Download PNG">
                                                <i class="bi bi-download"></i> PNG
                                            </a>
                                            <?php if ((bool)$qr['is_active']): ?>
                                                <form action="<?= base_url('admin/qrcodes/' . $qr['id'] . '/deactivate') ?>" method="POST" class="d-inline" onsubmit="return confirm('Deactivate this QR code?');">
                                                    <?= \App\Helpers\Csrf::field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 extra-small" title="Deactivate">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 extra-small" 
                                                    title="Edit QR Code"
                                                    onclick='editQrModal(<?= json_encode($qr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="<?= base_url('admin/qrcodes/' . $qr['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete QR code \'<?= e($qr['label']) ?>\'? This will remove its image file as well.');">
                                                <?= \App\Helpers\Csrf::field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 extra-small" title="Delete QR Code">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No QR codes generated yet. Generate your first QR code using the form.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Generate QR Code Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-qr-code-scan me-1 text-gold"></i> Generate QR Code</h6>
            </div>
            <div class="card-body p-3">
                <form action="<?= base_url('admin/qrcodes/generate') ?>" method="POST">
                    <?= \App\Helpers\Csrf::field() ?>

                    <div class="mb-3">
                        <label for="type" class="form-label small fw-semibold text-espresso">QR Code Purpose / Type</label>
                        <select name="type" id="typeSelect" class="form-select form-select-sm" onchange="toggleQrFields(this.value)">
                            <option value="feedback">Customer Feedback QR (Rate Experience)</option>
                            <option value="general">General Digital Menu</option>
                            <option value="branch">Branch-Specific Menu</option>
                            <option value="table">Table-Specific Menu</option>
                        </select>
                    </div>

                    <div class="mb-3" id="branchFieldGroup">
                        <label for="branch_id" class="form-label small fw-semibold text-espresso">Select Branch</label>
                        <select name="branch_id" id="branchSelect" class="form-select form-select-sm">
                            <option value="">-- Choose Branch (Optional for Feedback) --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="tableFieldGroup">
                        <label for="table_ref" class="form-label small fw-semibold text-espresso">Table Number / Reference (Optional)</label>
                        <input type="text" class="form-control form-control-sm" id="table_ref" name="table_ref" placeholder="e.g. Table 04 or Patio 2">
                    </div>

                    <div class="mb-3">
                        <label for="label" class="form-label small fw-semibold text-espresso">Custom Label (Optional)</label>
                        <input type="text" class="form-control form-control-sm" id="label" name="label" placeholder="e.g. Bole Branch Table 4 Feedback">
                    </div>

                    <div class="alert alert-info py-2 px-3 extra-small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> QR codes are generated locally at high resolution (ECC-H) for reliable scanning on mobile phones.
                    </div>

                    <button type="submit" class="btn btn-gold btn-sm w-100 rounded-pill py-2 fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i> Generate QR Code
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit QR Code Modal -->
<div class="modal fade" id="editQrModal" tabindex="-1" aria-labelledby="editQrModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-espresso" id="editQrModalLabel">
                    <i class="bi bi-pencil-square text-gold me-1"></i> Edit QR Code
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editQrForm" method="POST" action="">
                <?= \App\Helpers\Csrf::field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_label" class="form-label small fw-semibold text-espresso">Label Name</label>
                        <input type="text" class="form-control form-control-sm" id="edit_label" name="label" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_branch_id" class="form-label small fw-semibold text-espresso">Assigned Branch</label>
                        <select name="branch_id" id="edit_branch_id" class="form-select form-select-sm">
                            <option value="">-- No Specific Branch (General) --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_table_ref" class="form-label small fw-semibold text-espresso">Table Number / Reference</label>
                        <input type="text" class="form-control form-control-sm" id="edit_table_ref" name="table_ref" placeholder="e.g. Table 04">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label class="form-check-label small fw-semibold" for="edit_is_active">QR Code is Active</label>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i> Public URL: <span id="edit_public_url" class="text-break fw-semibold"></span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-gold rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check2 me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQrFields(type) {
    const branchGrp = document.getElementById('branchFieldGroup');
    const tableGrp  = document.getElementById('tableFieldGroup');

    if (type === 'general') {
        if (branchGrp) branchGrp.style.display = 'none';
        if (tableGrp)  tableGrp.style.display = 'none';
    } else if (type === 'branch') {
        if (branchGrp) branchGrp.style.display = 'block';
        if (tableGrp)  tableGrp.style.display = 'none';
    } else if (type === 'table') {
        if (branchGrp) branchGrp.style.display = 'block';
        if (tableGrp)  tableGrp.style.display = 'block';
    } else if (type === 'feedback') {
        if (branchGrp) branchGrp.style.display = 'block';
        if (tableGrp)  tableGrp.style.display = 'block';
    }
}

function editQrModal(qr) {
    const form = document.getElementById('editQrForm');
    form.action = '<?= base_url('admin/qrcodes') ?>/' + qr.id + '/edit';
    document.getElementById('edit_label').value = qr.label || '';
    document.getElementById('edit_branch_id').value = qr.branch_id || '';
    document.getElementById('edit_table_ref').value = qr.table_ref || '';
    document.getElementById('edit_is_active').checked = parseInt(qr.is_active, 10) === 1;
    document.getElementById('edit_public_url').textContent = qr.public_url || '';

    const modal = new bootstrap.Modal(document.getElementById('editQrModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('typeSelect');
    if (sel) toggleQrFields(sel.value);
});
</script>

