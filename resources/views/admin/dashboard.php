<!-- Quick Action Bar -->
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-espresso mb-1">Welcome back, <?= e($user['name']) ?>!</h4>
        <p class="text-muted small mb-0">System status and digital menu overview</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('admin/qrcodes') ?>" class="btn btn-gold btn-sm rounded-pill px-3">
            <i class="bi bi-qr-code me-1"></i> QR Codes
        </a>
        <a href="<?= base_url('admin/feedbacks') ?>" class="btn btn-outline-gold btn-sm rounded-pill px-3">
            <i class="bi bi-chat-heart me-1"></i> Feedback
        </a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted extra-small text-uppercase fw-bold">Total Products</span>
                <h3 class="fw-bold text-espresso mb-0 mt-1"><?= number_format($stats['total_products'] ?? 0) ?></h3>
                <span class="extra-small text-success fw-semibold"><i class="bi bi-check2"></i> In catalog</span>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted extra-small text-uppercase fw-bold">Active in Menu</span>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($stats['active_products'] ?? 0) ?></h3>
                <span class="extra-small text-muted"><?= number_format($stats['hidden_products'] ?? 0) ?> hidden</span>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-eye"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted extra-small text-uppercase fw-bold">Categories</span>
                <h3 class="fw-bold text-espresso mb-0 mt-1"><?= number_format($stats['parent_categories'] ?? 0) ?></h3>
                <span class="extra-small text-muted"><?= number_format($stats['child_categories'] ?? 0) ?> subcategories</span>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-diagram-3"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="stat-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted extra-small text-uppercase fw-bold">Last Sync</span>
                <h6 class="fw-bold text-espresso mb-0 mt-1 text-truncate" style="max-width: 140px;">
                    <?= !empty($stats['last_sync_at']) ? date('M j, H:i', strtotime($stats['last_sync_at'])) : 'Never' ?>
                </h6>
                <span class="badge <?= ($stats['last_sync_status'] ?? '') === 'completed' ? 'bg-success' : 'bg-secondary' ?> extra-small">
                    <?= e(strtoupper($stats['last_sync_status'] ?? 'None')) ?>
                </span>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-clock-history"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Import Logs -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-arrow-repeat me-2 text-gold"></i>Recent Import & Synchronization Runs</h6>
                <a href="<?= base_url('admin/import/logs') ?>" class="text-decoration-none small text-gold">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Inspected</th>
                                <th>Inserted</th>
                                <th>Updated</th>
                                <th>Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentLogs)): ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= date('M j, Y H:i', strtotime($log['started_at'])) ?></div>
                                            <span class="text-muted extra-small"><?= e($log['triggered_by']) ?><?= (bool)$log['is_dry_run'] ? ' (dry-run)' : '' ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php elseif ($log['status'] === 'failed'): ?>
                                                <span class="badge bg-danger" title="<?= e($log['error_summary'] ?? '') ?>">Failed</span>
                                            <?php elseif ($log['status'] === 'running'): ?>
                                                <span class="badge bg-warning text-dark">Running</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= e($log['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($log['records_inspected']) ?></td>
                                        <td class="text-success fw-semibold">+<?= number_format($log['records_inserted']) ?></td>
                                        <td class="text-info">~<?= number_format($log['records_updated']) ?></td>
                                        <td>
                                            <?php if ((int)$log['records_errored'] > 0): ?>
                                                <span class="text-danger fw-bold"><?= $log['records_errored'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No synchronization logs recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Branches & Audit Trail -->
    <div class="col-lg-5">
        <!-- Branches Quick View -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-shop me-2 text-gold"></i>Active Branches</h6>
                <a href="<?= base_url('admin/branches') ?>" class="text-decoration-none small text-gold">Manage</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php if (!empty($branches)): ?>
                        <?php foreach (array_slice($branches, 0, 4) as $b): ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                <div>
                                    <span class="fw-semibold text-espresso"><?= e($b['name']) ?></span>
                                    <div class="text-muted extra-small">Code: <code><?= e($b['code']) ?></code></div>
                                </div>
                                <a href="<?= base_url('menu?branch=' . urlencode($b['code'])) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2 extra-small">
                                    Menu
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-3">No active branches configured.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Recent Activity Audit -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-clock me-2 text-gold"></i>Recent Admin Activity</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush extra-small">
                    <?php if (!empty($recentAudit)): ?>
                        <?php foreach ($recentAudit as $audit): ?>
                            <li class="list-group-item py-2 px-3">
                                <div class="d-flex justify-content-between text-muted">
                                    <span class="fw-semibold text-espresso"><?= e($audit['user_name'] ?? 'System') ?></span>
                                    <span><?= date('M j, H:i', strtotime($audit['created_at'])) ?></span>
                                </div>
                                <div class="text-truncate mt-1">
                                    <span class="badge bg-light text-dark border"><?= e($audit['action']) ?></span>
                                    <span class="text-muted ms-1"><?= e($audit['entity_type'] ?? '') ?> #<?= e((string)$audit['entity_id']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted py-3">No admin actions recorded yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

