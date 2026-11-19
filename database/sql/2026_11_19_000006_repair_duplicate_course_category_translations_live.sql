-- Repair repeated education-level options caused by duplicate translations.
-- Safe to rerun on MySQL 8.

SET NAMES utf8mb4;

DELETE duplicate_translation
FROM `course_category_translations` duplicate_translation
JOIN `course_category_translations` keeper
  ON keeper.`course_category_id`=duplicate_translation.`course_category_id`
 AND keeper.`lang_code`=duplicate_translation.`lang_code`
 AND keeper.`id`<duplicate_translation.`id`;

SET @translation_unique_exists=(
    SELECT COUNT(*) FROM `information_schema`.`statistics`
    WHERE `table_schema`=DATABASE()
      AND `table_name`='course_category_translations'
      AND `index_name`='course_category_translations_category_language_unique'
);
SET @translation_unique_sql=IF(
    @translation_unique_exists=0,
    'ALTER TABLE `course_category_translations` ADD UNIQUE KEY `course_category_translations_category_language_unique` (`course_category_id`,`lang_code`)',
    'SELECT 1'
);
PREPARE translation_unique_statement FROM @translation_unique_sql;
EXECUTE translation_unique_statement;
DEALLOCATE PREPARE translation_unique_statement;

SET @taxonomy_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_19_000006_enforce_unique_course_category_translations',@taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration`='2026_11_19_000006_enforce_unique_course_category_translations'
);

SELECT category.`id`,category.`slug`,translation.`lang_code`,COUNT(*) AS `translation_rows`
FROM `course_categories` category
JOIN `course_category_translations` translation ON translation.`course_category_id`=category.`id`
WHERE category.`parent_id` IS NULL AND category.`deleted_at` IS NULL
GROUP BY category.`id`,category.`slug`,translation.`lang_code`
ORDER BY category.`order`,category.`id`,translation.`lang_code`;
