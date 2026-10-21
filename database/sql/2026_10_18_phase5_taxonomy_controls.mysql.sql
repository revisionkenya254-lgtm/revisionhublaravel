-- RevisionHub Kenya - Phase 5 taxonomy production controls
-- MySQL/MariaDB alternative for environments that cannot run artisan migrate.
-- Prerequisite: migrations through 2026_10_16_050000 must already be complete.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `taxonomy_revisions` (
    `id` TINYINT UNSIGNED NOT NULL,
    `revision` BIGINT UNSIGNED NOT NULL DEFAULT 1,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taxonomy_revisions` (`id`, `revision`, `updated_at`)
VALUES (1, 1, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

CREATE TABLE IF NOT EXISTS `taxonomy_change_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` BIGINT UNSIGNED NULL,
    `category_id` BIGINT UNSIGNED NULL,
    `batch_uuid` CHAR(36) NOT NULL,
    `action` VARCHAR(60) NOT NULL,
    `before` JSON NULL,
    `after` JSON NULL,
    `context` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `taxonomy_change_logs_batch_uuid_index` (`batch_uuid`),
    KEY `taxonomy_change_logs_action_index` (`action`),
    KEY `taxonomy_logs_category_created_index` (`category_id`, `created_at`),
    KEY `taxonomy_logs_admin_created_index` (`admin_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @taxonomy_phase5_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_18_000000_add_taxonomy_production_controls', @taxonomy_phase5_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_18_000000_add_taxonomy_production_controls'
);

COMMIT;
