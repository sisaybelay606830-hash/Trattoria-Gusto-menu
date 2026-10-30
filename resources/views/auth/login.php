<div class="container py-5 my-md-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-espresso p-4 text-center text-white border-0">
                    <div class="brand-crest mx-auto mb-3" style="width: 52px; height: 52px;">
                        <i class="bi bi-cup-hot-fill text-gold fs-4"></i>
                    </div>
                    <h4 class="fw-bold mb-1 font-heading text-white">Trattoria-Gusto Staff Portal</h4>
                    <p class="extra-small text-gold-light mb-0 text-uppercase tracking-wide">Digital Menu Management</p>
                </div>

                <div class="card-body p-4 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('admin/login') ?>" method="POST" novalidate>
                        <?= \App\Helpers\Csrf::field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-espresso">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input 
                                    type="email" 
                                    class="form-control border-start-0 ps-0" 
                                    id="email" 
                                    name="email" 
                                    required 
                                    autofocus
                                    placeholder="admin@kaldis.coffee"
                                    value="<?= e($_POST['email'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label small fw-semibold text-espresso">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input 
                                    type="password" 
                                    class="form-control border-start-0 ps-0" 
                                    id="password" 
                                    name="password" 
                                    required
                                    placeholder="••••••••••••"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold w-100 py-2 rounded-3 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Dashboard
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <a href="<?= base_url('menu') ?>" class="text-decoration-none small text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back to Public Menu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

