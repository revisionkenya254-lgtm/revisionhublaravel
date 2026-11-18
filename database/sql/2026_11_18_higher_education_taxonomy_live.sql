-- RevisionHub higher-education taxonomy deployment
-- Target: MySQL 8+ / MariaDB 10.5+
-- Take a database backup before running this file.
-- Run this file once, after deploying the matching application code.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------------------
-- 1. Profile-aware taxonomy columns
-- -------------------------------------------------------------------------

ALTER TABLE `course_categories`
    ADD COLUMN `taxonomy_profile` VARCHAR(40) NULL AFTER `node_type`,
    ADD COLUMN `taxonomy_metadata` JSON NULL AFTER `taxonomy_profile`,
    ADD INDEX `course_categories_profile_type_index`
        (`taxonomy_profile`, `node_type`, `status`);

-- -------------------------------------------------------------------------
-- 2. Canonical units, programme assignments, and product contexts
-- -------------------------------------------------------------------------

CREATE TABLE `curriculum_units` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `awarding_body_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(255) NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `curriculum_version` VARCHAR(80) NOT NULL DEFAULT 'current',
    `description` TEXT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `curriculum_units_identity_unique`
        (`awarding_body_id`, `slug`, `curriculum_version`),
    KEY `curriculum_units_catalog_index` (`awarding_body_id`, `status`, `name`),
    CONSTRAINT `curriculum_units_awarding_body_fk`
        FOREIGN KEY (`awarding_body_id`) REFERENCES `course_categories` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `programme_unit_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `programme_node_id` BIGINT UNSIGNED NOT NULL,
    `curriculum_unit_id` BIGINT UNSIGNED NOT NULL,
    `group_type` ENUM('common', 'major', 'elective') NOT NULL DEFAULT 'major',
    `semester` VARCHAR(80) NULL,
    `credit_hours` DECIMAL(5,2) NULL,
    `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `programme_unit_assignment_unique`
        (`programme_node_id`, `curriculum_unit_id`),
    KEY `programme_unit_assignment_tree_index`
        (`programme_node_id`, `group_type`, `status`, `display_order`),
    CONSTRAINT `programme_unit_assignment_programme_fk`
        FOREIGN KEY (`programme_node_id`) REFERENCES `course_categories` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `programme_unit_assignment_unit_fk`
        FOREIGN KEY (`curriculum_unit_id`) REFERENCES `curriculum_units` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_curriculum_contexts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `curriculum_unit_id` BIGINT UNSIGNED NOT NULL,
    `programme_unit_assignment_id` BIGINT UNSIGNED NULL,
    `resource_type` VARCHAR(40) NOT NULL DEFAULT 'past_paper',
    `assessment_series` VARCHAR(80) NULL,
    `assessment_year` SMALLINT UNSIGNED NULL,
    `paper_number` VARCHAR(40) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_curriculum_context_unique`
        (`product_id`, `curriculum_unit_id`, `programme_unit_assignment_id`),
    KEY `product_curriculum_context_lookup_index`
        (`curriculum_unit_id`, `assessment_year`, `assessment_series`),
    CONSTRAINT `product_curriculum_context_product_fk`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `product_curriculum_context_unit_fk`
        FOREIGN KEY (`curriculum_unit_id`) REFERENCES `curriculum_units` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `product_curriculum_context_assignment_fk`
        FOREIGN KEY (`programme_unit_assignment_id`) REFERENCES `programme_unit_assignments` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 3. CDACC example hierarchy
-- -------------------------------------------------------------------------

INSERT INTO `course_categories`
    (`parent_id`, `slug`, `node_type`, `taxonomy_profile`, `taxonomy_metadata`, `order`, `status`, `created_at`, `updated_at`)
SELECT NULL, 'cdacc', 'education_level', 'cdacc', NULL, 12, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug` = 'cdacc'
);

SET @cdacc_id = (
    SELECT `id` FROM `course_categories`
    WHERE `parent_id` IS NULL AND `slug` = 'cdacc'
    ORDER BY `id` LIMIT 1
);

UPDATE `course_categories`
SET `node_type` = 'education_level', `taxonomy_profile` = 'cdacc', `updated_at` = NOW()
WHERE `id` = @cdacc_id;

UPDATE `course_category_translations`
SET `name` = 'CDACC', `updated_at` = NOW()
WHERE `course_category_id` = @cdacc_id AND `lang_code` = 'en';

INSERT INTO `course_category_translations`
    (`course_category_id`, `lang_code`, `name`, `created_at`, `updated_at`)
SELECT @cdacc_id, 'en', 'CDACC', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id` = @cdacc_id AND `lang_code` = 'en'
);

INSERT INTO `course_categories`
    (`parent_id`, `slug`, `node_type`, `taxonomy_profile`, `taxonomy_metadata`, `order`, `status`, `created_at`, `updated_at`)
SELECT @cdacc_id, 'agriculture-and-aquaculture-department', 'department', 'cdacc', NULL, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories`
    WHERE `parent_id` = @cdacc_id AND `slug` = 'agriculture-and-aquaculture-department'
);

SET @department_id = (
    SELECT `id` FROM `course_categories`
    WHERE `parent_id` = @cdacc_id AND `slug` = 'agriculture-and-aquaculture-department'
    ORDER BY `id` LIMIT 1
);

UPDATE `course_categories`
SET `node_type` = 'department', `taxonomy_profile` = 'cdacc', `updated_at` = NOW()
WHERE `id` = @department_id;

UPDATE `course_category_translations`
SET `name` = 'Agriculture and Aquaculture Department', `updated_at` = NOW()
WHERE `course_category_id` = @department_id AND `lang_code` = 'en';

INSERT INTO `course_category_translations`
    (`course_category_id`, `lang_code`, `name`, `created_at`, `updated_at`)
SELECT @department_id, 'en', 'Agriculture and Aquaculture Department', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id` = @department_id AND `lang_code` = 'en'
);

INSERT INTO `course_categories`
    (`parent_id`, `slug`, `node_type`, `taxonomy_profile`, `taxonomy_metadata`, `order`, `status`, `created_at`, `updated_at`)
SELECT @department_id,
       'agricultural-engineering-level-6',
       'programme',
       'cdacc',
       JSON_OBJECT('qualification_level', 'Level 6'),
       0,
       1,
       NOW(),
       NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories`
    WHERE `parent_id` = @department_id AND `slug` = 'agricultural-engineering-level-6'
);

SET @programme_id = (
    SELECT `id` FROM `course_categories`
    WHERE `parent_id` = @department_id AND `slug` = 'agricultural-engineering-level-6'
    ORDER BY `id` LIMIT 1
);

UPDATE `course_categories`
SET `node_type` = 'programme',
    `taxonomy_profile` = 'cdacc',
    `taxonomy_metadata` = JSON_OBJECT('qualification_level', 'Level 6'),
    `updated_at` = NOW()
WHERE `id` = @programme_id;

UPDATE `course_category_translations`
SET `name` = 'Agricultural Engineering – Level 6', `updated_at` = NOW()
WHERE `course_category_id` = @programme_id AND `lang_code` = 'en';

INSERT INTO `course_category_translations`
    (`course_category_id`, `lang_code`, `name`, `created_at`, `updated_at`)
SELECT @programme_id, 'en', 'Agricultural Engineering – Level 6', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id` = @programme_id AND `lang_code` = 'en'
);

-- -------------------------------------------------------------------------
-- 4. Canonical CDACC units and assignments
-- -------------------------------------------------------------------------

INSERT INTO `curriculum_units`
    (`awarding_body_id`, `code`, `name`, `slug`, `curriculum_version`, `status`, `created_at`, `updated_at`)
VALUES
    (@cdacc_id, NULL, 'Communication Skills', 'communication-skills', 'current', 1, NOW(), NOW()),
    (@cdacc_id, NULL, 'Apply Renewable Energy Technology in Agriculture',
     'apply-renewable-energy-technology-in-agriculture', 'current', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `status` = 1,
    `updated_at` = NOW();

SET @communication_unit_id = (
    SELECT `id` FROM `curriculum_units`
    WHERE `awarding_body_id` = @cdacc_id
      AND `slug` = 'communication-skills'
      AND `curriculum_version` = 'current'
    LIMIT 1
);

SET @renewable_unit_id = (
    SELECT `id` FROM `curriculum_units`
    WHERE `awarding_body_id` = @cdacc_id
      AND `slug` = 'apply-renewable-energy-technology-in-agriculture'
      AND `curriculum_version` = 'current'
    LIMIT 1
);

INSERT INTO `programme_unit_assignments`
    (`programme_node_id`, `curriculum_unit_id`, `group_type`, `is_mandatory`, `display_order`, `status`, `created_at`, `updated_at`)
VALUES
    (@programme_id, @communication_unit_id, 'common', 1, 0, 1, NOW(), NOW()),
    (@programme_id, @renewable_unit_id, 'major', 1, 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `group_type` = VALUES(`group_type`),
    `is_mandatory` = VALUES(`is_mandatory`),
    `display_order` = VALUES(`display_order`),
    `status` = 1,
    `updated_at` = NOW();

-- -------------------------------------------------------------------------
-- 5. Tag existing upper-education roots and descendants with UI profiles
-- The descendant update is repeated to cover the existing hierarchy depth.
-- -------------------------------------------------------------------------

UPDATE `course_categories`
SET `taxonomy_profile` = CASE `slug`
    WHEN 'tvet' THEN 'tvet'
    WHEN 'certificate-courses' THEN 'certificate'
    WHEN 'diploma-courses' THEN 'diploma'
    WHEN 'undergraduate' THEN 'undergraduate'
    WHEN 'professional-courses' THEN 'professional'
    ELSE `taxonomy_profile`
END
WHERE `parent_id` IS NULL
  AND `slug` IN ('tvet', 'certificate-courses', 'diploma-courses', 'undergraduate', 'professional-courses');

UPDATE `course_categories` child
JOIN `course_categories` parent ON parent.`id` = child.`parent_id`
SET child.`taxonomy_profile` = parent.`taxonomy_profile`
WHERE parent.`taxonomy_profile` IN ('tvet', 'certificate', 'diploma', 'undergraduate', 'professional');

UPDATE `course_categories` child
JOIN `course_categories` parent ON parent.`id` = child.`parent_id`
SET child.`taxonomy_profile` = parent.`taxonomy_profile`
WHERE parent.`taxonomy_profile` IN ('tvet', 'certificate', 'diploma', 'undergraduate', 'professional');

UPDATE `course_categories` child
JOIN `course_categories` parent ON parent.`id` = child.`parent_id`
SET child.`taxonomy_profile` = parent.`taxonomy_profile`
WHERE parent.`taxonomy_profile` IN ('tvet', 'certificate', 'diploma', 'undergraduate', 'professional');

-- -------------------------------------------------------------------------
-- 6. Mark the equivalent Laravel migrations as applied
-- Do not add these rows if you plan to run php artisan migrate instead.
-- -------------------------------------------------------------------------

SET @taxonomy_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000000_add_higher_education_taxonomy_foundation', @taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000000_add_higher_education_taxonomy_foundation'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000001_seed_cdacc_taxonomy_example', @taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000001_seed_cdacc_taxonomy_example'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000002_tag_existing_higher_education_profiles', @taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000002_tag_existing_higher_education_profiles'
);

-- -------------------------------------------------------------------------
-- 7. Verification
-- -------------------------------------------------------------------------

SELECT `id`, `slug`, `node_type`, `taxonomy_profile`, `taxonomy_metadata`
FROM `course_categories`
WHERE `taxonomy_profile` IS NOT NULL
ORDER BY `taxonomy_profile`, `parent_id`, `order`, `id`;

SELECT unit.`id`, unit.`name`, unit.`slug`, unit.`curriculum_version`,
       assignment.`group_type`, assignment.`programme_node_id`
FROM `curriculum_units` unit
LEFT JOIN `programme_unit_assignments` assignment
    ON assignment.`curriculum_unit_id` = unit.`id`
WHERE unit.`awarding_body_id` = @cdacc_id
ORDER BY assignment.`group_type`, assignment.`display_order`, unit.`name`;

SELECT `migration`, `batch`
FROM `migrations`
WHERE `migration` LIKE '2026_11_18_00000%'
ORDER BY `migration`;
