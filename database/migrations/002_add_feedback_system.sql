-- ============================================================
-- Migration 002: Customer Feedback System
-- ============================================================

SET NAMES utf8mb4;

-- 1. Create feedbacks table
CREATE TABLE IF NOT EXISTS `feedbacks` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `branch_id`       INT UNSIGNED NULL,
    `table_number`    VARCHAR(50)  NULL,
    `rating_overall`  TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1 to 5 stars',
    `rating_coffee`   TINYINT UNSIGNED NULL COMMENT '1 to 5 stars',
    `rating_food`     TINYINT UNSIGNED NULL COMMENT '1 to 5 stars',
    `rating_service`  TINYINT UNSIGNED NULL COMMENT '1 to 5 stars',
    `comment`         TEXT         NULL,
    `customer_name`   VARCHAR(200) NULL,
    `customer_phone`  VARCHAR(50)  NULL,
    `is_reviewed`     TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fb_branch` (`branch_id`),
    KEY `idx_fb_rating` (`rating_overall`),
    KEY `idx_fb_created` (`created_at`),
    CONSTRAINT `fk_fb_branch` FOREIGN KEY (`branch_id`)
        REFERENCES `branches`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Expand qr_codes type enum to support 'feedback'
ALTER TABLE `qr_codes`
    MODIFY COLUMN `type` ENUM('general', 'branch', 'table', 'feedback') NOT NULL DEFAULT 'general';

-- 3. Ensure default 4 Kilo branch exists
INSERT IGNORE INTO `branches` (`code`, `name`, `address`, `phone`, `is_active`)
VALUES ('4_KILO', 'Kaldis 4 Kilo', '4 Kilo, Near Addis Ababa University, Addis Ababa', '+251 111 23 45 67', 1);


