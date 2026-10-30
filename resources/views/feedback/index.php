<div class="feedback-page py-4">
    <div class="container" style="max-width: 680px;">

        <!-- Header Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-espresso text-white text-center p-4">
            <div class="brand-crest mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                <i class="bi bi-chat-heart-fill text-gold"></i>
            </div>
            <h3 class="font-playfair text-gold fw-bold mb-1">Your Voice Matters to Us</h3>
            <p class="text-white-50 mb-0 small">
                Help Trattoria-Gusto elevate your coffee & dining experience. We read every single review!
            </p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger rounded-3 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please check the errors below:</strong>
                <ul class="mb-0 mt-2 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
            <form action="<?= base_url('feedback') ?>" method="POST" id="feedbackForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <?php if ($selectedBranch): ?>
                    <input type="hidden" name="branch_code" value="<?= e($selectedBranch['code']) ?>">
                <?php endif; ?>

                <!-- Branch & Table Selection -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-7">
                        <label class="form-label fw-bold text-espresso small">
                            <i class="bi bi-geo-alt-fill text-gold me-1"></i> Trattoria-Gusto Branch
                        </label>
                        <select name="branch_id" class="form-select rounded-3">
                            <option value="">— Select Branch (Optional) —</option>
                            <?php foreach ($branches as $b): ?>
                                <?php 
                                    $isSelected = ($selectedBranch && $selectedBranch['id'] === $b['id']) 
                                               || ((int)($old['branch_id'] ?? 0) === $b['id']);
                                ?>
                                <option value="<?= e($b['id']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= e($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label fw-bold text-espresso small">
                            <i class="bi bi-table text-gold me-1"></i> Table Number
                        </label>
                        <input type="text" name="table_number" class="form-control rounded-3" 
                               placeholder="e.g. 12 or Patio 4"
                               value="<?= e($old['table_number'] ?? $tableRef) ?>" maxlength="50">
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Overall Rating (Required) -->
                <div class="text-center mb-4">
                    <label class="form-label fw-bold text-espresso mb-1 d-block" style="font-size: 1.1rem;">
                        How was your overall experience today? <span class="text-danger">*</span>
                    </label>
                    <p class="text-muted small mb-2">Tap a star to rate</p>
                    
                    <div class="rating-stars d-inline-flex gap-2 p-2 rounded-pill bg-light" id="overallStars">
                        <?php $currentOverall = (int)($old['rating_overall'] ?? 5); ?>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="btn btn-star p-1 border-0 bg-transparent" data-rating="<?= $i ?>" data-target="rating_overall">
                                <i class="bi <?= $i <= $currentOverall ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>" style="font-size: 2rem;"></i>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating_overall" id="rating_overall" value="<?= $currentOverall ?>">
                    <div class="rating-label fw-semibold text-gold-dark mt-1 small" id="rating_overall_label">
                        <?= $currentOverall === 5 ? 'Excellent!' : ($currentOverall === 4 ? 'Very Good' : ($currentOverall === 3 ? 'Average' : 'Could be better')) ?>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Aspect Ratings -->
                <div class="mb-4">
                    <h6 class="fw-bold text-espresso mb-3">Rate Specific Aspects (Optional)</h6>

                    <!-- Coffee Rating -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cup-hot-fill text-gold"></i>
                            <span class="small fw-semibold text-espresso">Coffee & Beverages</span>
                        </div>
                        <div class="star-group d-flex gap-1" data-target="rating_coffee">
                            <input type="hidden" name="rating_coffee" id="rating_coffee" value="<?= e($old['rating_coffee'] ?? '') ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star text-muted star-btn" data-value="<?= $i ?>" style="cursor: pointer; font-size: 1.25rem;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Food Rating -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-egg-fried text-gold"></i>
                            <span class="small fw-semibold text-espresso">Food & Pastries</span>
                        </div>
                        <div class="star-group d-flex gap-1" data-target="rating_food">
                            <input type="hidden" name="rating_food" id="rating_food" value="<?= e($old['rating_food'] ?? '') ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star text-muted star-btn" data-value="<?= $i ?>" style="cursor: pointer; font-size: 1.25rem;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Service Rating -->
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-heart text-gold"></i>
                            <span class="small fw-semibold text-espresso">Staff & Hospitality</span>
                        </div>
                        <div class="star-group d-flex gap-1" data-target="rating_service">
                            <input type="hidden" name="rating_service" id="rating_service" value="<?= e($old['rating_service'] ?? '') ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star text-muted star-btn" data-value="<?= $i ?>" style="cursor: pointer; font-size: 1.25rem;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Comments -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-espresso small">
                        <i class="bi bi-pencil-square text-gold me-1"></i> Comments & Suggestions
                    </label>
                    <textarea name="comment" class="form-control rounded-3" rows="3" 
                              placeholder="Tell us what you loved or how we can make your next visit even better..."><?= e($old['comment'] ?? '') ?></textarea>
                </div>

                <!-- Contact Details (Optional) -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold text-muted small">Your Name (Optional)</label>
                        <input type="text" name="customer_name" class="form-control rounded-3" 
                               placeholder="e.g. Abebe" value="<?= e($old['customer_name'] ?? '') ?>" maxlength="150">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold text-muted small">Phone Number (Optional)</label>
                        <input type="tel" name="customer_phone" class="form-control rounded-3" 
                               placeholder="e.g. 0911..." value="<?= e($old['customer_phone'] ?? '') ?>" maxlength="25">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-gold py-3 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-send-fill me-2"></i> Submit Feedback
                    </button>
                    <a href="<?= base_url('menu' . ($selectedBranch ? '/' . urlencode($selectedBranch['code']) : '')) ?>" class="btn btn-outline-secondary py-2 rounded-pill small border-0">
                        Cancel & Return to Menu
                    </a>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
document += window.addEventListener('DOMContentLoaded', function() {
    // Labels for overall ratings
    const labels = {
        1: 'Poor — Needs urgent improvement',
        2: 'Fair — Room for improvement',
        3: 'Good — Met expectations',
        4: 'Very Good — Really enjoyed it!',
        5: 'Excellent — Trattoria-Gusto at its best!'
    };

    // Overall Star Rating interactive selector
    const overallButtons = document.querySelectorAll('#overallStars .btn-star');
    const overallInput   = document.getElementById('rating_overall');
    const overallLabel   = document.getElementById('rating_overall_label');

    function updateOverallStars(rating) {
        overallButtons.forEach(btn => {
            const r = parseInt(btn.dataset.rating, 10);
            const icon = btn.querySelector('i');
            if (r <= rating) {
                icon.className = 'bi bi-star-fill text-warning';
            } else {
                icon.className = 'bi bi-star text-muted';
            }
        });
        overallInput.value = rating;
        if (overallLabel && labels[rating]) {
            overallLabel.textContent = labels[rating];
        }
    }

    overallButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            updateOverallStars(parseInt(this.dataset.rating, 10));
        });
    });

    // Aspect Star Rating groups
    document.querySelectorAll('.star-group').forEach(group => {
        const targetId = group.dataset.target;
        const hiddenInput = document.getElementById(targetId);
        const stars = group.querySelectorAll('.star-btn');

        function setAspectStars(val) {
            stars.forEach(s => {
                const sv = parseInt(s.dataset.value, 10);
                if (sv <= val) {
                    s.className = 'bi bi-star-fill text-warning star-btn';
                } else {
                    s.className = 'bi bi-star text-muted star-btn';
                }
            });
            hiddenInput.value = val;
        }

        // Initialize if value present
        if (hiddenInput && hiddenInput.value) {
            setAspectStars(parseInt(hiddenInput.value, 10));
        }

        stars.forEach(s => {
            s.addEventListener('click', function() {
                setAspectStars(parseInt(this.dataset.value, 10));
            });
        });
    });
});
</script>

