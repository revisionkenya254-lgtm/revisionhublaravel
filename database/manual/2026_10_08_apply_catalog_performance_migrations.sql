-- RevisionHub live deployment: catalog taxonomy and API performance migrations
-- Covers Laravel migrations:
--   2026_10_07_000000_add_taxonomy_fields_to_courses_table
--   2026_10_07_000001_deactivate_removed_professional_course_categories
--   2026_10_07_000002_add_catalog_content_lookup_index_to_products
--   2026_10_08_000000_add_api_catalog_query_indexes
--
-- MySQL 8.x / MariaDB compatible. Safe to run more than once.
-- Select the live RevisionHub database before running this script.

SET @revisionhub_schema := DATABASE();
SET @revisionhub_batch := COALESCE((SELECT MAX(`batch`) + 1 FROM `migrations`), 1);

-- --------------------------------------------------------------------------
-- 1. Add normalized taxonomy fields to courses.
-- --------------------------------------------------------------------------

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name = 'education_level';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `education_level` VARCHAR(255) NULL AFTER `category_id`',
    'SELECT ''courses.education_level already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name = 'class_grade';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `class_grade` VARCHAR(255) NULL AFTER `education_level`',
    'SELECT ''courses.class_grade already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name = 'subject';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `subject` VARCHAR(255) NULL AFTER `class_grade`',
    'SELECT ''courses.subject already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name = 'exam_category';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `exam_category` VARCHAR(255) NULL AFTER `subject`',
    'SELECT ''courses.exam_category already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name = 'academic_year';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `courses` ADD COLUMN `academic_year` SMALLINT UNSIGNED NULL AFTER `exam_category`',
    'SELECT ''courses.academic_year already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_taxonomy_columns_ready
FROM information_schema.columns
WHERE table_schema = @revisionhub_schema
  AND table_name = 'courses'
  AND column_name IN ('education_level', 'class_grade', 'subject', 'exam_category', 'academic_year');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_07_000000_add_taxonomy_fields_to_courses_table', @revisionhub_batch
WHERE @revisionhub_taxonomy_columns_ready = 5
  AND NOT EXISTS (
      SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_10_07_000000_add_taxonomy_fields_to_courses_table'
  );

-- --------------------------------------------------------------------------
-- 2. Deactivate removed professional-course categories.
-- --------------------------------------------------------------------------

UPDATE `course_categories` AS child
INNER JOIN `course_categories` AS root
    ON root.`id` = child.`parent_id`
SET child.`status` = 0,
    child.`updated_at` = UTC_TIMESTAMP()
WHERE root.`slug` = 'professional-courses'
  AND root.`parent_id` IS NULL
  AND child.`slug` IN ('kasneb', 'cpa', 'cs', 'cifa', 'ccp', 'cams')
  AND child.`status` <> 0;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_07_000001_deactivate_removed_professional_course_categories', @revisionhub_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_10_07_000001_deactivate_removed_professional_course_categories'
);

-- --------------------------------------------------------------------------
-- 3. Add the category-content lookup index.
-- --------------------------------------------------------------------------

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = 'products_catalog_content_lookup_index';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `products` ADD INDEX `products_catalog_content_lookup_index` (`status`, `is_approved`, `deleted_at`, `category_id`)',
    'SELECT ''products_catalog_content_lookup_index already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = 'products_catalog_content_lookup_index';
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_07_000002_add_catalog_content_lookup_index_to_products', @revisionhub_batch
WHERE @revisionhub_exists > 0
  AND NOT EXISTS (
      SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_10_07_000002_add_catalog_content_lookup_index_to_products'
  );

-- --------------------------------------------------------------------------
-- 4. Add bounded catalog-listing and translation lookup indexes.
-- --------------------------------------------------------------------------

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = 'products_api_catalog_listing_index';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `products` ADD INDEX `products_api_catalog_listing_index` (`status`, `is_approved`, `deleted_at`, `created_at`, `id`)',
    'SELECT ''products_api_catalog_listing_index already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'products'
  AND index_name = 'products_api_catalog_type_listing_index';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `products` ADD INDEX `products_api_catalog_type_listing_index` (`status`, `is_approved`, `type`, `deleted_at`, `created_at`, `id`)',
    'SELECT ''products_api_catalog_type_listing_index already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(*) INTO @revisionhub_exists
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND table_name = 'course_category_translations'
  AND index_name = 'course_category_translations_lookup_index';
SET @revisionhub_sql := IF(
    @revisionhub_exists = 0,
    'ALTER TABLE `course_category_translations` ADD INDEX `course_category_translations_lookup_index` (`course_category_id`, `lang_code`)',
    'SELECT ''course_category_translations_lookup_index already exists'' AS message'
);
PREPARE revisionhub_statement FROM @revisionhub_sql;
EXECUTE revisionhub_statement;
DEALLOCATE PREPARE revisionhub_statement;

SELECT COUNT(DISTINCT index_name) INTO @revisionhub_api_indexes_ready
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND (
      (table_name = 'products' AND index_name IN (
          'products_api_catalog_listing_index',
          'products_api_catalog_type_listing_index'
      ))
      OR
      (table_name = 'course_category_translations'
          AND index_name = 'course_category_translations_lookup_index')
  );

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_08_000000_add_api_catalog_query_indexes', @revisionhub_batch
WHERE @revisionhub_api_indexes_ready = 3
  AND NOT EXISTS (
      SELECT 1 FROM `migrations`
      WHERE `migration` = '2026_10_08_000000_add_api_catalog_query_indexes'
  );

-- --------------------------------------------------------------------------
-- Verification summary.
-- --------------------------------------------------------------------------

SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` IN (
    '2026_10_07_000000_add_taxonomy_fields_to_courses_table',
    '2026_10_07_000001_deactivate_removed_professional_course_categories',
    '2026_10_07_000002_add_catalog_content_lookup_index_to_products',
    '2026_10_08_000000_add_api_catalog_query_indexes'
)
ORDER BY `migration`;

SELECT table_name, index_name,
       GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ', ') AS indexed_columns
FROM information_schema.statistics
WHERE table_schema = @revisionhub_schema
  AND index_name IN (
      'products_catalog_content_lookup_index',
      'products_api_catalog_listing_index',
      'products_api_catalog_type_listing_index',
      'course_category_translations_lookup_index'
  )
GROUP BY table_name, index_name
ORDER BY table_name, index_name;
