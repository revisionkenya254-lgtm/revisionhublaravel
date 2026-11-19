-- Enable profile-driven admin taxonomy management for all higher-education branches.
-- Preserves category IDs, curriculum units, assignments, and linked products.
-- Safe to rerun on MySQL 8.

SET NAMES utf8mb4;
SET @now=NOW();

WITH RECURSIVE `taxonomy_tree` AS (
    SELECT `id`,`id` AS `root_id`,`taxonomy_profile` AS `root_profile`
    FROM `course_categories`
    WHERE `parent_id` IS NULL
      AND `deleted_at` IS NULL
      AND `taxonomy_profile` IN ('cdacc','tvet','certificate','diploma','undergraduate','professional')
    UNION ALL
    SELECT child.`id`,tree.`root_id`,tree.`root_profile`
    FROM `course_categories` child
    JOIN `taxonomy_tree` tree ON tree.`id`=child.`parent_id`
)
UPDATE `course_categories` category
JOIN `taxonomy_tree` tree ON tree.`id`=category.`id`
SET category.`taxonomy_profile`=tree.`root_profile`,category.`updated_at`=@now;

UPDATE `course_categories`
SET `node_type`='education_level',
    `status`=1,
    `taxonomy_metadata`=JSON_SET(
        COALESCE(`taxonomy_metadata`,JSON_OBJECT()),
        '$.admin_taxonomy_version',2,
        '$.hierarchy',CASE `taxonomy_profile`
            WHEN 'cdacc' THEN JSON_ARRAY('department','programme')
            WHEN 'professional' THEN JSON_ARRAY('awarding_body','programme')
            ELSE JSON_ARRAY('awarding_body','department','programme')
        END
    ),
    `updated_at`=@now
WHERE `parent_id` IS NULL
  AND `deleted_at` IS NULL
  AND `taxonomy_profile` IN ('cdacc','tvet','certificate','diploma','undergraduate','professional');

SET @taxonomy_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_19_000008_normalize_higher_education_admin_taxonomy',@taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration`='2026_11_19_000008_normalize_higher_education_admin_taxonomy'
);

SELECT root.`taxonomy_profile`,root.`id`,translation.`name`,
       JSON_UNQUOTE(JSON_EXTRACT(root.`taxonomy_metadata`,'$.hierarchy')) AS `admin_hierarchy`
FROM `course_categories` root
LEFT JOIN `course_category_translations` translation
  ON translation.`course_category_id`=root.`id` AND translation.`lang_code`='en'
WHERE root.`parent_id` IS NULL
  AND root.`deleted_at` IS NULL
  AND root.`taxonomy_profile` IN ('cdacc','tvet','certificate','diploma','undergraduate','professional')
ORDER BY root.`order`,root.`id`;
