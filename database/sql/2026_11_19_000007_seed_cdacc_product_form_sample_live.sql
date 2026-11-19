-- CDACC sample data for instructor/admin product classification.
-- Path: CDACC -> Agriculture and Aquaculture Department
--       -> Agricultural Engineering Level 6 -> Module 1
--       -> Common Units / Major Units -> Unit.
-- Safe to rerun on MySQL 8 after the higher-education foundation migrations.

SET NAMES utf8mb4;
SET @now=NOW();

INSERT INTO `course_categories`
(`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`,`deleted_at`)
SELECT NULL,'cdacc','education_level','cdacc',NULL,8,1,@now,@now,NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories`
    WHERE `parent_id` IS NULL AND `slug`='cdacc' AND `deleted_at` IS NULL
);
SET @cdacc_root=(
    SELECT `id` FROM `course_categories`
    WHERE `parent_id` IS NULL AND `slug`='cdacc' AND `deleted_at` IS NULL
    ORDER BY `id` LIMIT 1
);
UPDATE `course_categories`
SET `node_type`='education_level',`taxonomy_profile`='cdacc',`order`=8,`status`=1,`updated_at`=@now
WHERE `id`=@cdacc_root;
UPDATE `course_category_translations`
SET `name`='CDACC',`updated_at`=@now
WHERE `course_category_id`=@cdacc_root AND `lang_code`='en';
INSERT INTO `course_category_translations`
(`course_category_id`,`lang_code`,`name`,`created_at`,`updated_at`)
SELECT @cdacc_root,'en','CDACC',@now,@now
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id`=@cdacc_root AND `lang_code`='en'
);

INSERT INTO `course_categories`
(`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`,`deleted_at`)
SELECT @cdacc_root,'agriculture-and-aquaculture-department','department','cdacc',NULL,0,1,@now,@now,NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories`
    WHERE `parent_id`=@cdacc_root AND `slug`='agriculture-and-aquaculture-department' AND `deleted_at` IS NULL
);
SET @cdacc_department=(
    SELECT `id` FROM `course_categories`
    WHERE `parent_id`=@cdacc_root AND `slug`='agriculture-and-aquaculture-department' AND `deleted_at` IS NULL
    ORDER BY `id` LIMIT 1
);
UPDATE `course_categories`
SET `node_type`='department',`taxonomy_profile`='cdacc',`order`=0,`status`=1,`updated_at`=@now
WHERE `id`=@cdacc_department;
UPDATE `course_category_translations`
SET `name`='Agriculture and Aquaculture Department',`updated_at`=@now
WHERE `course_category_id`=@cdacc_department AND `lang_code`='en';
INSERT INTO `course_category_translations`
(`course_category_id`,`lang_code`,`name`,`created_at`,`updated_at`)
SELECT @cdacc_department,'en','Agriculture and Aquaculture Department',@now,@now
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id`=@cdacc_department AND `lang_code`='en'
);

INSERT INTO `course_categories`
(`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`,`deleted_at`)
SELECT @cdacc_department,'agricultural-engineering-level-6','programme','cdacc',JSON_OBJECT('qualification_level','Level 6'),0,1,@now,@now,NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `course_categories`
    WHERE `parent_id`=@cdacc_department AND `slug`='agricultural-engineering-level-6' AND `deleted_at` IS NULL
);
SET @cdacc_programme=(
    SELECT `id` FROM `course_categories`
    WHERE `parent_id`=@cdacc_department AND `slug`='agricultural-engineering-level-6' AND `deleted_at` IS NULL
    ORDER BY `id` LIMIT 1
);
UPDATE `course_categories`
SET `node_type`='programme',`taxonomy_profile`='cdacc',
    `taxonomy_metadata`=JSON_OBJECT('qualification_level','Level 6'),`order`=0,`status`=1,`updated_at`=@now
WHERE `id`=@cdacc_programme;
UPDATE `course_category_translations`
SET `name`='Agricultural Engineering Level 6',`updated_at`=@now
WHERE `course_category_id`=@cdacc_programme AND `lang_code`='en';
INSERT INTO `course_category_translations`
(`course_category_id`,`lang_code`,`name`,`created_at`,`updated_at`)
SELECT @cdacc_programme,'en','Agricultural Engineering Level 6',@now,@now
WHERE NOT EXISTS (
    SELECT 1 FROM `course_category_translations`
    WHERE `course_category_id`=@cdacc_programme AND `lang_code`='en'
);

INSERT INTO `curriculum_units`
(`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`,`deleted_at`)
SELECT @cdacc_root,'CDACC-CU-001','Communication Skills','communication-skills','current',1,@now,@now,NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `curriculum_units`
    WHERE `awarding_body_id`=@cdacc_root AND `slug`='communication-skills' AND `curriculum_version`='current'
);
SET @cdacc_common_unit=(
    SELECT `id` FROM `curriculum_units`
    WHERE `awarding_body_id`=@cdacc_root AND `slug`='communication-skills' AND `curriculum_version`='current'
    ORDER BY `id` LIMIT 1
);
UPDATE `curriculum_units`
SET `code`='CDACC-CU-001',`name`='Communication Skills',`status`=1,`deleted_at`=NULL,`updated_at`=@now
WHERE `id`=@cdacc_common_unit;

INSERT INTO `curriculum_units`
(`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`,`deleted_at`)
SELECT @cdacc_root,'CDACC-AE-601','Apply Renewable Energy Technology in Agriculture',
       'apply-renewable-energy-technology-in-agriculture','current',1,@now,@now,NULL
WHERE NOT EXISTS (
    SELECT 1 FROM `curriculum_units`
    WHERE `awarding_body_id`=@cdacc_root
      AND `slug`='apply-renewable-energy-technology-in-agriculture'
      AND `curriculum_version`='current'
);
SET @cdacc_major_unit=(
    SELECT `id` FROM `curriculum_units`
    WHERE `awarding_body_id`=@cdacc_root
      AND `slug`='apply-renewable-energy-technology-in-agriculture'
      AND `curriculum_version`='current'
    ORDER BY `id` LIMIT 1
);
UPDATE `curriculum_units`
SET `code`='CDACC-AE-601',`name`='Apply Renewable Energy Technology in Agriculture',
    `status`=1,`deleted_at`=NULL,`updated_at`=@now
WHERE `id`=@cdacc_major_unit;

INSERT INTO `programme_unit_assignments`
(`programme_node_id`,`curriculum_unit_id`,`group_type`,`module`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`)
SELECT @cdacc_programme,@cdacc_common_unit,'common','Module 1',1,0,1,@now,@now
WHERE NOT EXISTS (
    SELECT 1 FROM `programme_unit_assignments`
    WHERE `programme_node_id`=@cdacc_programme AND `curriculum_unit_id`=@cdacc_common_unit
);
UPDATE `programme_unit_assignments`
SET `group_type`='common',`study_year`=NULL,`semester`=NULL,`module`='Module 1',
    `stage`=NULL,`section`=NULL,`is_mandatory`=1,`display_order`=0,`status`=1,`updated_at`=@now
WHERE `programme_node_id`=@cdacc_programme AND `curriculum_unit_id`=@cdacc_common_unit;

INSERT INTO `programme_unit_assignments`
(`programme_node_id`,`curriculum_unit_id`,`group_type`,`module`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`)
SELECT @cdacc_programme,@cdacc_major_unit,'major','Module 1',1,1,1,@now,@now
WHERE NOT EXISTS (
    SELECT 1 FROM `programme_unit_assignments`
    WHERE `programme_node_id`=@cdacc_programme AND `curriculum_unit_id`=@cdacc_major_unit
);
UPDATE `programme_unit_assignments`
SET `group_type`='major',`study_year`=NULL,`semester`=NULL,`module`='Module 1',
    `stage`=NULL,`section`=NULL,`is_mandatory`=1,`display_order`=1,`status`=1,`updated_at`=@now
WHERE `programme_node_id`=@cdacc_programme AND `curriculum_unit_id`=@cdacc_major_unit;

SET @taxonomy_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_19_000007_seed_cdacc_product_form_sample',@taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration`='2026_11_19_000007_seed_cdacc_product_form_sample'
);

SELECT department_translation.`name` AS `department`,programme_translation.`name` AS `programme`,
       assignment.`module`,assignment.`group_type`,unit.`code`,unit.`name` AS `unit`
FROM `programme_unit_assignments` assignment
JOIN `course_categories` programme ON programme.`id`=assignment.`programme_node_id`
JOIN `course_categories` department ON department.`id`=programme.`parent_id`
JOIN `course_category_translations` programme_translation
  ON programme_translation.`course_category_id`=programme.`id` AND programme_translation.`lang_code`='en'
JOIN `course_category_translations` department_translation
  ON department_translation.`course_category_id`=department.`id` AND department_translation.`lang_code`='en'
JOIN `curriculum_units` unit ON unit.`id`=assignment.`curriculum_unit_id`
WHERE programme.`id`=@cdacc_programme AND assignment.`status`=1 AND unit.`status`=1
ORDER BY assignment.`display_order`,assignment.`id`;
