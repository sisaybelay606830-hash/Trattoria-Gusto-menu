<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-sliders me-2 text-gold"></i>Menu Configuration Settings</h6>
                <span class="text-muted extra-small">Control digital menu titles, default branch, pricing display, and maintenance</span>
            </div>

            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings') ?>" method="POST">
                    <?= \App\Helpers\Csrf::field() ?>

                    <div class="mb-3">
                        <label for="menu_title" class="form-label small fw-semibold text-espresso">Menu Title</label>
                        <input type="text" class="form-control" id="menu_title" name="menu_title" value="<?= e($settings['menu_title']['value'] ?? 'Trattoria-Gusto') ?>" required>
                        <div class="form-text extra-small">Headline shown on top of the customer menu.</div>
                    </div>

                    <div class="mb-3">
                        <label for="menu_subtitle" class="form-label small fw-semibold text-espresso">Menu Subtitle</label>
                        <input type="text" class="form-control" id="menu_subtitle" name="menu_subtitle" value="<?= e($settings['menu_subtitle']['value'] ?? 'Digital Menu') ?>">
                        <div class="form-text extra-small">Subheading text displayed under the main title.</div>
                    </div>

                    <div class="mb-3">
                        <label for="default_branch_code" class="form-label small fw-semibold text-espresso">Default Branch</label>
                        <select name="default_branch_code" id="default_branch_code" class="form-select">
                            <option value="">-- No Default (Show All Products) --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= e($b['code']) ?>" <?= ($settings['default_branch_code']['value'] ?? '') === $b['code'] ? 'selected' : '' ?>>
                                    <?= e($b['name']) ?> (<?= e($b['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text extra-small">Selected branch when a customer opens the general menu URL without a branch parameter.</div>
                    </div>

                    <div class="mb-3">
                        <label for="fallback_category" class="form-label small fw-semibold text-espresso">Fallback Category Name</label>
                        <input type="text" class="form-control" id="fallback_category" name="fallback_category" value="<?= e($settings['fallback_category']['value'] ?? 'Uncategorized') ?>" required>
                        <div class="form-text extra-small">Assigned to imported products missing parent or child preference categories in the source data.</div>
                    </div>

                    <div class="mb-3">
                        <label for="items_per_page" class="form-label small fw-semibold text-espresso">Admin Items Per Page</label>
                        <input type="number" min="10" max="100" class="form-control" id="items_per_page" name="items_per_page" value="<?= e($settings['items_per_page']['value'] ?? '24') ?>" required>
                        <div class="form-text extra-small">Number of products listed per page in the admin table.</div>
                    </div>

                    <div class="mb-3">
                        <label for="vat_rate" class="form-label small fw-semibold text-espresso">VAT Multiplier (e.g. 1.2 for 20% VAT)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-percent"></i></span>
                            <input type="number" step="0.01" min="1.0" max="3.0" class="form-control" id="vat_rate" name="vat_rate" value="<?= e($settings['vat_rate']['value'] ?? '1.20') ?>" required>
                        </div>
                        <div class="form-text extra-small">Calculates customer selling price by multiplying the base <code>default_value</code> from CNET ERP &times; this VAT factor (e.g. <code>default_value &times; 1.20</code>). Change this value anytime VAT adjustments are required.</div>
                    </div>

                    <div class="border-top pt-3 mt-4 mb-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="show_prices" name="show_prices" value="1" <?= ($settings['show_prices']['value'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-espresso" for="show_prices">
                                Show Verified Prices on Public Menu
                            </label>
                            <div class="form-text extra-small">When enabled, verified prices are visible to customers. Only verified prices set in the admin are shown; unverified raw source values are never shown.</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode']['value'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-espresso" for="maintenance_mode">
                                Maintenance Mode
                            </label>
                            <div class="form-text extra-small text-danger">When enabled, public customers will see a polite "Updating Menu" maintenance screen.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold rounded-pill px-4 py-2 fw-semibold">
                        <i class="bi bi-check2 me-1"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

