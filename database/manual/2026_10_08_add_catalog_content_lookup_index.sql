-- RevisionHub catalog category API optimization
-- Compatible with MySQL/MariaDB and safe to run more than once from phpMyAdmin.

SET @revisionhub_schema := DATABASE();
SET @revisionhub_index_name := 'products_catalog_content_lookup_index';
SET @revisionhub_migration := '2026_10_07_000002_add_catalog_content_lookup_index_to_products';

SELECT COUNT(*) INTO @revisionhub_index_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = @revisionhub_index_name;

SET @revisionhub_create_index_sql := IF(
    @revisionhub_index_exists = 0,
    'ALTER TABLE `products` ADD INDEX `products_catalog_content_lookup_index` (`status`, `is_approved`, `deleted_at`, `category_id`)',
    'SELECT ''products_catalog_content_lookup_index already exists'' AS message'
);

PREPARE revisionhub_statement FROM @revisionhub_create_index_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

-- Verify creation before marking the matching Laravel migration as complete.
SELECT COUNT(*) INTO @revisionhub_index_exists_after
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = @revisionhub_index_name;

SET @revisionhub_next_batch := COALESCE(
    (SELECT MAX(batch) + 1 FROM `migrations`),
    1
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT @revisionhub_migration, @revisionhub_next_batch
WHERE @revisionhub_index_exists_after > 0
  AND NOT EXISTS (
      SELECT 1
      FROM `migrations`
      WHERE `migration` = @revisionhub_migration
  );

SELECT
    IF(
        @revisionhub_index_exists_after > 0,
        'Catalog lookup index is installed and the Laravel migration is recorded.',
        'Catalog lookup index was not installed.'
    ) AS result;
