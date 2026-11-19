-- Convert the legacy CDACC school-shaped branch into the configured hierarchy.
-- CDACC -> Department -> Programme and Level.
-- IDs and all references are preserved. Safe to rerun on MySQL 8.

SET NAMES utf8mb4;
SET @now=NOW();
SET @cdacc_root=(
    SELECT `id` FROM `course_categories`
    WHERE `parent_id` IS NULL AND `taxonomy_profile`='cdacc' AND `deleted_at` IS NULL
    ORDER BY `id` LIMIT 1
);

UPDATE `course_categories`
SET `node_type`='department',`taxonomy_profile`='cdacc',`updated_at`=@now
WHERE `parent_id`=@cdacc_root AND `deleted_at` IS NULL;

UPDATE `course_categories` programme
JOIN `course_categories` department ON department.`id`=programme.`parent_id`
SET programme.`node_type`='programme',programme.`taxonomy_profile`='cdacc',programme.`updated_at`=@now
WHERE department.`parent_id`=@cdacc_root
  AND department.`node_type`='department'
  AND department.`deleted_at` IS NULL
  AND programme.`deleted_at` IS NULL;

UPDATE `course_categories`
SET `taxonomy_metadata`=JSON_SET(
        COALESCE(`taxonomy_metadata`,JSON_OBJECT()),
        '$.admin_taxonomy_version',3,
        '$.legacy_depth_normalized',TRUE,
        '$.hierarchy',JSON_ARRAY('department','programme')
    ),
    `updated_at`=@now
WHERE `id`=@cdacc_root;

SET @taxonomy_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_19_000009_normalize_legacy_cdacc_node_types',@taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration`='2026_11_19_000009_normalize_legacy_cdacc_node_types'
);

SELECT child.`id`,translation.`name`,child.`slug`,child.`node_type`,COUNT(grandchild.`id`) AS `children`
FROM `course_categories` child
LEFT JOIN `course_category_translations` translation
  ON translation.`course_category_id`=child.`id` AND translation.`lang_code`='en'
LEFT JOIN `course_categories` grandchild
  ON grandchild.`parent_id`=child.`id` AND grandchild.`deleted_at` IS NULL
WHERE child.`parent_id`=@cdacc_root AND child.`deleted_at` IS NULL
GROUP BY child.`id`,translation.`name`,child.`slug`,child.`node_type`
ORDER BY child.`order`,child.`id`;
