<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Print QR Label — Trattoria-Gusto') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4eee1;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #23160e;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .print-card {
            background: #ffffff;
            width: 380px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(35, 22, 14, 0.15);
            padding: 2.5rem 2rem;
            text-align: center;
            border: 2px solid #c69214;
            position: relative;
        }

        .crest {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #23160e 0%, #4a2818 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 12px rgba(35, 22, 14, 0.25);
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: #23160e;
            line-height: 1.1;
        }

        .brand-tag {
            font-size: 0.72rem;
            font-weight: 700;
            color: #c69214;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .qr-wrapper {
            background: #ffffff;
            padding: 14px;
            border-radius: 16px;
            display: inline-block;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ded2;
            margin-bottom: 1.25rem;
        }

        .qr-image {
            width: 200px;
            height: 200px;
            display: block;
        }

        .instruction-badge {
            background: #fbf5e6;
            color: #8f6507;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.45rem 1rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 0.85rem;
        }

        .branch-badge {
            font-weight: 600;
            font-size: 0.95rem;
            color: #23160e;
            margin-bottom: 0.35rem;
        }

        .url-text {
            font-size: 0.75rem;
            color: #726760;
            word-break: break-all;
        }

        .no-print {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-card {
                box-shadow: none;
                border: 2px solid #000000;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="print-card">
            <div class="crest">
                <i class="bi bi-cup-hot-fill text-warning fs-3"></i>
            </div>
            <div class="brand-title">Trattoria-Gusto</div>
            <div class="brand-tag">TRADITION & EXCELLENCE  &middot; ETHIOPIA</div>

            <div class="qr-wrapper">
                <?php if (!empty($previewBase64)): ?>
                    <img src="<?= $previewBase64 ?>" alt="QR Code" class="qr-image">
                <?php else: ?>
                    <p class="text-danger small mb-0">QR Code image unavailable.</p>
                <?php endif; ?>
            </div>

            <div>
                <div class="instruction-badge">
                    <?php if (($qr['type'] ?? '') === 'feedback'): ?>
                        <i class="bi bi-star-fill text-warning"></i> SCAN TO RATE & GIVE FEEDBACK
                    <?php else: ?>
                        <i class="bi bi-camera-fill"></i> SCAN TO VIEW DIGITAL MENU
                    <?php endif; ?>
                </div>
                <?php if (!empty($qr['branch_name'])): ?>
                    <div class="branch-badge">
                        <i class="bi bi-geo-alt-fill text-warning"></i> <?= e($qr['branch_name']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($qr['table_ref'])): ?>
                    <div class="fw-bold small text-muted mb-1">
                        Table: <?= e($qr['table_ref']) ?>
                    </div>
                <?php endif; ?>
                <div class="url-text"><?= e($qr['public_url']) ?></div>
            </div>
        </div>

        <div class="no-print text-center">
            <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
                <i class="bi bi-printer me-1"></i> Print Label
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill px-3">
                Close
            </button>
        </div>
    </div>
</body>
</html>

