<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="fw-bold text-espresso mb-0"><i class="bi bi-sliders me-2 text-gold"></i>Menu Configuration Settings</h6>
                <span class="text-muted extra-small">Control digital menu titles, branding logo, default branch, pricing display, and maintenance</span>
            </div>

            <div class="card-body p-4">
                <form action="<?= base_url('admin/settings') ?>" method="POST" enctype="multipart/form-data">
                    <?= \App\Helpers\Csrf::field() ?>

                    <!-- Brand Logo Setting -->
                    <div class="mb-4 p-3 bg-light rounded-3 border">
                        <label class="form-label small fw-bold text-espresso mb-2">
                            <i class="bi bi-image text-gold me-1"></i> Brand Logo / Crest
                        </label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="border rounded-circle bg-white d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 60px; height: 60px; flex-shrink: 0;">
                                <?php if (!empty($settings['custom_logo']['value']) && file_exists(BASE_PATH . '/public/' . ltrim($settings['custom_logo']['value'], '/'))): ?>
                                    <img src="<?= base_url($settings['custom_logo']['value']) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="bi bi-cup-hot-fill text-gold fs-3"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control form-control-sm" id="custom_logo" name="custom_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                <div class="form-text extra-small">Upload a custom logo to replace the default crest. Recommended: PNG or SVG with transparent background.</div>
                                <?php if (!empty($settings['custom_logo']['value'])): ?>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="remove_custom_logo" name="remove_custom_logo" value="1">
                                        <label class="form-check-label extra-small text-danger" for="remove_custom_logo">Remove custom logo and restore default Kaldis crest</label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="menu_title" class="form-label small fw-semibold text-espresso">Menu Title</label>
                        <input type="text" class="form-control" id="menu_title" name="menu_title" value="<?= e($settings['menu_title']['value'] ?? 'Kaldis Coffee') ?>" required>
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

                    <!-- Telegram Feedback Bot Integration -->
                    <div class="border-top pt-4 mt-4 mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telegram text-primary fs-5"></i>
                            <h6 class="fw-bold text-espresso mb-0">Telegram Feedback Alerts</h6>
                        </div>
                        <p class="text-muted extra-small mb-3">
                            Automatically forward new customer feedback & ratings directly into your managers' Telegram group or channel.
                        </p>

                        <div class="form-check form-switch mb-3 p-3 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" id="telegram_enabled" name="telegram_enabled" value="1" <?= ($settings['telegram_enabled']['value'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-espresso" for="telegram_enabled">
                                Enable Telegram Notifications
                            </label>
                            <div class="form-text extra-small text-muted ps-4">
                                Sends an instant Telegram notification whenever a customer submits feedback on the menu.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="telegram_bot_token" class="form-label small fw-semibold text-espresso">Telegram Bot Token</label>
                                <input type="text" class="form-control form-control-sm" id="telegram_bot_token" name="telegram_bot_token" 
                                       placeholder="e.g. 123456789:ABCdefGHIjklMNOpqrsTUVwxyz" 
                                       value="<?= e($settings['telegram_bot_token']['value'] ?? '') ?>">
                                <div class="form-text extra-small">Obtained from <code>@BotFather</code> on Telegram.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="telegram_chat_id" class="form-label small fw-semibold text-espresso">Group Chat ID</label>
                                <input type="text" class="form-control form-control-sm" id="telegram_chat_id" name="telegram_chat_id" 
                                       placeholder="e.g. -1001234567890 or @your_channel" 
                                       value="<?= e($settings['telegram_chat_id']['value'] ?? '') ?>">
                                <div class="form-text extra-small">The group or channel ID (usually starts with <code>-100</code>). Add your bot as Admin in the group.</div>
                            </div>
                        </div>
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

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-gold rounded-pill px-4 py-2 fw-semibold">
                            <i class="bi bi-check2 me-1"></i> Save Settings
                        </button>
                    </div>
                </form>

                <!-- Telegram Test Button Form -->
                <?php if (!empty($settings['telegram_bot_token']['value']) && !empty($settings['telegram_chat_id']['value'])): ?>
                    <form action="<?= base_url('admin/settings/telegram-test') ?>" method="POST" class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between">
                        <?= \App\Helpers\Csrf::field() ?>
                        <div>
                            <span class="small fw-semibold text-espresso">Test Telegram Connection</span>
                            <div class="extra-small text-muted">Sends a live verification ping to your Telegram group.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-send me-1"></i> Send Test Message
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

