<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#23160e">
    <title><?= e($title ?? 'Trattoria-Gusto-menu — Digital Menu') ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="trattoria-gusto-app">
    <!-- Header / Brand Navigation -->
    <header class="trattoria-gusto-header sticky-top">
        <div class="container py-2">
            <div class="d-flex align-items-center justify-content-between">
                <a href="<?= base_url('menu') ?>" class="text-decoration-none d-flex align-items-center gap-2">
                    <div class="brand-crest">
                        <i class="bi bi-cup-hot-fill text-gold"></i>
                    </div>
                    <div>
                        <span class="brand-title">TRATTORIA-GUSTO</span>
                        <span class="brand-tagline d-block">TRADITION & EXCELLENCE</span>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($branch)): ?>
                        <div class="badge bg-gold-subtle text-gold-dark d-flex align-items-center gap-1 px-3 py-2 rounded-pill">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span class="fw-semibold"><?= e($branch['name']) ?></span>
                        </div>
                    <?php endif; ?>
                    <a href="<?= base_url('feedback' . (!empty($branch) ? '?branch=' . urlencode($branch['code']) : '')) ?>" class="btn btn-outline-gold btn-sm rounded-pill px-3" title="Rate Us & Feedback">
                        <i class="bi bi-chat-heart me-1"></i> Feedback
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="trattoria-gusto-main">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="trattoria-gusto-footer mt-5 py-4 text-center">
        <div class="container">
            <div class="mb-3">
                <a href="<?= base_url('feedback' . (!empty($branch) ? '?branch=' . urlencode($branch['code']) : '')) ?>" class="btn btn-sm btn-gold rounded-pill px-4 shadow-sm">
                    <i class="bi bi-star-fill me-1"></i> How was your experience? Leave Feedback
                </a>
            </div>
            <div class="brand-crest mx-auto mb-2" style="width: 36px; height: 36px;">
                <i class="bi bi-cup-hot-fill text-gold" style="font-size: 1.1rem;"></i>
            </div>
            <p class="mb-1 fw-semibold text-espresso">Trattoria-Gusto</p>
            <p class="text-muted small mb-2">The Birthplace of Coffee &middot; Crafted with Passion in Ethiopia</p>
            <p class="text-muted extra-small mb-0">&copy; <?= date('Y') ?> Trattoria-Gusto. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= base_url('assets/js/menu.js') ?>"></script>
</body>
</html>

