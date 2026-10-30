-- ============================================================
-- Trattoria Gusto Digital Menu — Local Database Schema
-- Migration 001: Initial schema
-- Run with: mysql -u root trattoriagusto_menu < database/migrations/001_initial_schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------
-- parent_categories
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parent_categories` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `source_id`             INT          NULL COMMENT 'parent_preference_id from source',
    `name`                  VARCHAR(200) NOT NULL,
    `name_normalized`       VARCHAR(200) NOT NULL COMMENT 'lowercase trimmed for dedup',
    `is_visible`            TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'admin hide/show',
    `sort_order`            SMALLINT     NOT NULL DEFAULT 0,
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_parent_source_id` (`source_id`),
    KEY `idx_visible` (`is_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- child_categories
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `child_categories` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_category_id`    INT UNSIGNED NOT NULL,
    `source_id`             INT          NULL COMMENT 'child_preference_id from source',
    `name`                  VARCHAR(200) NOT NULL,
    `name_normalized`       VARCHAR(200) NOT NULL,
    `is_visible`            TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`            SMALLINT     NOT NULL DEFAULT 0,
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_child_source_id` (`source_id`),
    KEY `idx_parent` (`parent_category_id`),
    KEY `idx_visible` (`is_visible`),
    CONSTRAINT `fk_child_parent` FOREIGN KEY (`parent_category_id`)
        REFERENCES `parent_categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- products
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_code`          VARCHAR(100) NOT NULL COMMENT 'External unique key from source',
    `name`                  VARCHAR(300) NOT NULL,
    `gsl_type_id`           INT          NULL,
    `gsl_type`              VARCHAR(200) NULL,
    `consignee_unit`        VARCHAR(100) NULL COMMENT 'Unit of measure from source',
    `default_value`         VARCHAR(200) NULL COMMENT 'Raw source value - meaning unverified; do NOT treat as price',
    `is_active_source`      TINYINT(1)   NOT NULL DEFAULT 1  COMMENT 'is_active from source DB',
    -- Source category references (preserved for traceability)
    `src_parent_pref_id`    INT          NULL,
    `src_parent_pref`       VARCHAR(200) NULL,
    `src_child_pref_id`     INT          NULL,
    `src_child_pref`        VARCHAR(200) NULL,
    -- Local category FK (resolved during import)
    `parent_category_id`    INT UNSIGNED NULL,
    `child_category_id`     INT UNSIGNED NULL,
    -- Admin-managed fields (preserved across syncs)
    `local_description`     TEXT         NULL,
    `image_path`            VARCHAR(500) NULL COMMENT 'Relative path under public/uploads/products/',
    `is_visible`            TINYINT(1)   NOT NULL DEFAULT 1  COMMENT 'Admin hide/show from public menu',
    `price`                 DECIMAL(10,2) NULL COMMENT 'Manual price if confirmed; NULL until verified',
    `price_currency`        VARCHAR(10)  NULL DEFAULT 'ETB',
    -- Sync metadata
    `synced_at`             DATETIME     NULL COMMENT 'Last successful source sync timestamp',
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_article_code` (`article_code`),
    KEY `idx_parent_cat` (`parent_category_id`),
    KEY `idx_child_cat` (`child_category_id`),
    KEY `idx_active_visible` (`is_active_source`, `is_visible`),
    KEY `idx_name` (`name`(100)),
    CONSTRAINT `fk_prod_parent` FOREIGN KEY (`parent_category_id`)
        REFERENCES `parent_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_prod_child` FOREIGN KEY (`child_category_id`)
        REFERENCES `child_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- branches
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `branches` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`          VARCHAR(50)  NOT NULL COMMENT 'URL-safe branch identifier',
    `name`          VARCHAR(200) NOT NULL,
    `address`       VARCHAR(500) NULL,
    `phone`         VARCHAR(50)  NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_branch_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- branch_products  (branch-level overrides)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `branch_products` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id`     INT UNSIGNED NOT NULL,
    `product_id`    INT UNSIGNED NOT NULL,
    `is_available`  TINYINT(1)   NOT NULL DEFAULT 1,
    `price_override` DECIMAL(10,2) NULL,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_branch_product` (`branch_id`, `product_id`),
    CONSTRAINT `fk_bp_branch`  FOREIGN KEY (`branch_id`)  REFERENCES `branches`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_bp_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- qr_codes
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_codes` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `label`         VARCHAR(200) NOT NULL,
    `type`          ENUM('general','branch','table') NOT NULL DEFAULT 'general',
    `branch_id`     INT UNSIGNED NULL,
    `table_ref`     VARCHAR(50)  NULL,
    `public_url`    VARCHAR(1000) NOT NULL,
    `file_path`     VARCHAR(500) NULL COMMENT 'PNG file relative to storage/qrcodes/',
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_branch` (`branch_id`),
    CONSTRAINT `fk_qr_branch` FOREIGN KEY (`branch_id`)
        REFERENCES `branches`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- users
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`              VARCHAR(200) NOT NULL,
    `email`             VARCHAR(320) NOT NULL,
    `password_hash`     VARCHAR(255) NOT NULL,
    `role`              ENUM('administrator','menu_manager') NOT NULL DEFAULT 'menu_manager',
    `is_active`         TINYINT(1)   NOT NULL DEFAULT 1,
    `last_login_at`     DATETIME     NULL,
    `failed_attempts`   TINYINT      NOT NULL DEFAULT 0,
    `locked_until`      DATETIME     NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- settings  (key-value store for admin-configurable options)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `key`           VARCHAR(100) NOT NULL PRIMARY KEY,
    `value`         TEXT         NULL,
    `description`   VARCHAR(500) NULL,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- import_logs
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `import_logs` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `started_at`        DATETIME     NOT NULL,
    `completed_at`      DATETIME     NULL,
    `status`            ENUM('running','completed','failed','dry_run') NOT NULL DEFAULT 'running',
    `is_dry_run`        TINYINT(1)   NOT NULL DEFAULT 0,
    `triggered_by`      VARCHAR(100) NOT NULL DEFAULT 'manual' COMMENT 'manual|cli|scheduler',
    `user_id`           INT UNSIGNED NULL,
    `records_inspected` INT          NOT NULL DEFAULT 0,
    `records_inserted`  INT          NOT NULL DEFAULT 0,
    `records_updated`   INT          NOT NULL DEFAULT 0,
    `records_skipped`   INT          NOT NULL DEFAULT 0,
    `records_errored`   INT          NOT NULL DEFAULT 0,
    `error_summary`     TEXT         NULL,
    `notes`             TEXT         NULL,
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- audit_logs  (admin action audit trail)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NULL,
    `action`        VARCHAR(100) NOT NULL,
    `entity_type`   VARCHAR(100) NULL,
    `entity_id`     INT UNSIGNED NULL,
    `details`       TEXT         NULL COMMENT 'JSON-encoded change details',
    `ip_address`    VARCHAR(45)  NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user`   (`user_id`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created`(`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- Default settings
-- ----------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key`, `value`, `description`) VALUES
('menu_title',          'Trattoria Gusto',              'Public menu page title'),
('menu_subtitle',       'Digital Menu',               'Public menu subtitle'),
('default_branch_code', '',                           'Default branch shown on menu (empty = all)'),
('fallback_category',   'Uncategorized',              'Category name for products with missing preferences'),
('show_prices',         '1',                          '1 = show prices on public menu'),
('vat_rate',            '1.20',                       'VAT/tax multiplier applied to default_value (e.g. 1.20 for 20% VAT)'),
('items_per_page',      '24',                         'Products per page on admin list'),
('maintenance_mode',    '0',                          '1 = show maintenance message to customers');

SET FOREIGN_KEY_CHECKS = 1;

