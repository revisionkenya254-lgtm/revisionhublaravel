-- Run after deploying the instructor/admin curriculum-context workflow.
-- This is the only new live SQL required when 000000-000003 are already applied.

ALTER TABLE `products`
    ADD COLUMN `approval_note` TEXT NULL AFTER `is_approved`,
    ADD COLUMN `taxonomy_verified_at` TIMESTAMP NULL AFTER `approval_note`,
    ADD COLUMN `taxonomy_verified_by` BIGINT UNSIGNED NULL AFTER `taxonomy_verified_at`,
    ADD INDEX `products_taxonomy_verified_by_index` (`taxonomy_verified_by`);

SET @taxonomy_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000004_add_product_approval_context_fields', @taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000004_add_product_approval_context_fields'
);

SELECT `approval_note`, `taxonomy_verified_at`, `taxonomy_verified_by`
FROM `products`
LIMIT 5;
