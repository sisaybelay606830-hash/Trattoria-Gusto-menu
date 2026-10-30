<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Trattoria-Gusto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="trattoria-gusto-app d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center p-4">
        <h1 class="display-1 fw-bold text-gold">404</h1>
        <h2 class="fw-bold text-espresso mb-3">Page Not Found</h2>
        <p class="text-muted mb-4">The menu page or resource you are looking for does not exist.</p>
        <a href="<?= base_url('menu') ?>" class="btn btn-gold rounded-pill px-4">Back to Menu</a>
    </div>
</body>
</html>

