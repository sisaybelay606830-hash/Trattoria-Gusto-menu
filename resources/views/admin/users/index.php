<div class="row g-4">
    <!-- User List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-espresso mb-0">System Users</h6>
                    <span class="text-muted extra-small">Staff accounts with dashboard access</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>User Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-espresso"><?= e($u['name']) ?></div>
                                    </td>
                                    <td>
                                        <code><?= e($u['email']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'administrator'): ?>
                                            <span class="badge bg-danger extra-small">Administrator</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark extra-small">Menu Manager</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((bool)$u['is_active']): ?>
                                            <span class="badge bg-success-subtle text-success extra-small">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary extra-small">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted extra-small">
                                            <?= !empty($u['last_login_at']) ? date('M j, Y H:i', strtotime($u['last_login_at'])) : 'Never' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-person-plus me-1 text-gold"></i> Create New User</h6>
            </div>
            <div class="card-body p-3">
                <form action="<?= base_url('admin/users') ?>" method="POST">
                    <?= \App\Helpers\Csrf::field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-espresso">Full Name</label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required placeholder="e.g. Ermias Girma">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-espresso">Email Address</label>
                        <input type="email" class="form-control form-control-sm" id="email" name="email" required placeholder="staff@trattoriagusto.com">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-espresso">Initial Password</label>
                        <input type="password" class="form-control form-control-sm" id="password" name="password" required minlength="8" placeholder="Minimum 8 characters">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label small fw-semibold text-espresso">System Role</label>
                        <select name="role" id="role" class="form-select form-select-sm">
                            <option value="menu_manager">Menu Manager (Products & QR)</option>
                            <option value="administrator">Administrator (Full Access)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-gold btn-sm w-100 rounded-pill py-2 fw-semibold">
                        <i class="bi bi-check2 me-1"></i> Create User Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

