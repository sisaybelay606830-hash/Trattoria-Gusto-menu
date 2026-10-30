-- ============================================================
-- Trattoria-Gusto-menu — CNET Synced Data Staging Database
-- Schema for `trattoriagusto_cnet_synced_data`.`article_import`
--
-- Generated from CNET ERP Source View:
-- [trattoriagusto].[article].[vw_ArticleView]
-- Filter: [gslTypeId] = 13 AND [isActive] = 1 AND [articleCode] IS NOT NULL
-- ============================================================

CREATE DATABASE IF NOT EXISTS `trattoriabp_cnet_synced_data`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `trattoriabp_cnet_synced_data`;

DROP TABLE IF EXISTS `article_import`;

CREATE TABLE `article_import` (
    `article_code`           VARCHAR(50)    NULL COMMENT 'CAST([articleCode] AS VARCHAR(50))',
    `name`                   VARCHAR(255)   NULL COMMENT 'CAST([name] AS VARCHAR(255))',
    `gsl_type_id`            INT            NULL COMMENT 'CAST([gslTypeId] AS INT)',
    `gsl_type`               VARCHAR(250)   NULL COMMENT 'CAST([gslType] AS VARCHAR(250))',
    `is_active`              TINYINT(1)     NULL COMMENT 'CAST([isActive] AS INT)',
    `child_preference_id`    INT            NULL COMMENT 'CAST([childPreferenceId] AS INT)',
    `child_preference`       VARCHAR(100)   NULL COMMENT 'CAST(LTRIM(RTRIM([childPreference])) AS VARCHAR(100))',
    `parent_preference_id`   INT            NULL COMMENT 'CAST([parentPreferenceId] AS INT)',
    `parent_preference`      VARCHAR(100)   NULL COMMENT 'CAST(LTRIM(RTRIM([parentPreference])) AS VARCHAR(100))',
    `default_value`          DECIMAL(10, 2) NULL COMMENT 'CAST([defaultValue] AS DECIMAL(10, 2))',
    `consignee_unit`         INT            NULL COMMENT 'CAST([consigneeUnit] AS INT)',
    KEY `idx_article_code` (`article_code`),
    KEY `idx_child_preference` (`child_preference`),
    KEY `idx_parent_preference` (`parent_preference`),
    KEY `idx_gsl_type_active` (`gsl_type_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

