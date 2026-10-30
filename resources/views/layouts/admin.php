<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Trattoria-Gusto Admin') ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-wrapper d-flex">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header p-3 d-flex align-items-center justify-content-between">
                <a href="<?= base_url('admin/dashboard') ?>" class="text-decoration-none d-flex align-items-center gap-2">
                    <div class="brand-crest" style="width: 36px; height: 36px;">
                        <i class="bi bi-cup-hot-fill text-gold"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-white fs-6 tracking-wide">TRATTORIA-GUSTO</span>
                        <span class="d-block text-gold extra-small fw-semibold">MENU ADMIN</span>
                    </div>
                </a>
                <button class="btn btn-sm btn-dark text-white d-md-none" id="sidebarCloseBtn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="sidebar-nav px-2 py-3">
                <div class="nav-section-title px-3 mb-1 text-uppercase extra-small text-muted fw-bold">Overview</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item">
                        <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/dashboard') ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                </ul>

                <div class="nav-section-title px-3 mb-1 text-uppercase extra-small text-muted fw-bold">Catalog & Operations</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item">
                        <a href="<?= base_url('admin/products') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/products') ? 'active' : '' ?>">
                            <i class="bi bi-cup-straw me-2"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/categories') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/categories') ? 'active' : '' ?>">
                            <i class="bi bi-diagram-3 me-2"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/branches') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/branches') ? 'active' : '' ?>">
                            <i class="bi bi-shop me-2"></i> Branches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/qrcodes') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/qrcodes') ? 'active' : '' ?>">
                            <i class="bi bi-qr-code me-2"></i> QR Codes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/feedbacks') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/feedbacks') ? 'active' : '' ?>">
                            <i class="bi bi-chat-heart me-2"></i> Customer Feedback
                        </a>
                    </li>
                </ul>

                <div class="nav-section-title px-3 mb-1 text-uppercase extra-small text-muted fw-bold">Data & Sync</div>
                <ul class="nav flex-column mb-3">
                    <li class="nav-item">
                        <a href="<?= base_url('admin/import') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/import') ? 'active' : '' ?>">
                            <i class="bi bi-arrow-repeat me-2"></i> Sync Products
                        </a>
                    </li>
                </ul>

                <div class="nav-section-title px-3 mb-1 text-uppercase extra-small text-muted fw-bold">System</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="<?= base_url('admin/settings') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/settings') ? 'active' : '' ?>">
                            <i class="bi bi-sliders me-2"></i> Menu Settings
                        </a>
                    </li>
                    <?php if (\App\Helpers\Session::userRole() === 'administrator'): ?>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/users') ?>" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin/users') ? 'active' : '' ?>">
                                <i class="bi bi-people me-2"></i> Users
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item mt-2">
                        <a href="<?= base_url('menu') ?>" target="_blank" class="nav-link text-gold-light">
                            <i class="bi bi-box-arrow-up-right me-2"></i> View Live Menu
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer p-3 mt-auto border-top border-secondary border-opacity-25">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="user-info text-truncate me-2">
                        <div class="text-white small fw-semibold text-truncate"><?= e(\App\Helpers\Session::get('user_name', 'Admin')) ?></div>
                        <div class="text-gold extra-small text-uppercase"><?= e(\App\Helpers\Session::userRole()) ?></div>
                    </div>
                    <a href="<?= base_url('admin/logout') ?>" class="btn btn-outline-danger btn-sm p-1 px-2" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="admin-content flex-grow-1 d-flex flex-column min-vh-100">
            <!-- Top Navbar -->
            <header class="admin-topbar p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggleBtn">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h5 class="mb-0 fw-bold text-espresso"><?= e($title ?? 'Dashboard') ?></h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('menu') ?>" target="_blank" class="btn btn-sm btn-outline-gold rounded-pill px-3">
                        <i class="bi bi-eye me-1"></i> Customer View
                    </a>
                </div>
            </header>

            <!-- Alerts Container -->
            <div class="admin-body-inner p-3 p-md-4 flex-grow-1">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div><?= e($success) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= e($error) ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>

            <footer class="admin-footer p-3 bg-white border-top text-center text-muted small">
                Trattoria-Gusto &middot; Internal Administration System &middot; &copy; <?= date('Y') ?>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle mobile sidebar
        const sidebarToggle = document.getElementById('sidebarToggleBtn');
        const sidebarClose  = document.getElementById('sidebarCloseBtn');
        const sidebar       = document.getElementById('adminSidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => sidebar.classList.add('show'));
        }
        if (sidebarClose && sidebar) {
            sidebarClose.addEventListener('click', () => sidebar.classList.remove('show'));
        }
    </script>
</body>
</html>

