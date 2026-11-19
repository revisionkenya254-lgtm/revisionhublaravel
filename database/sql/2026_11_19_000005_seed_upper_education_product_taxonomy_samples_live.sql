-- RevisionHub upper-education sample taxonomy data.
-- Requires migrations 2026_11_18_000000 through 2026_11_18_000004.
-- Safe to run once. Existing matching nodes and units are updated in place.

SET NAMES utf8mb4;
SET @now = NOW();

-- Canonical roots and labels.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'cdacc','education_level','cdacc',NULL,8,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='cdacc' AND `deleted_at` IS NULL);
SET @cdacc_root = (SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='cdacc' AND `deleted_at` IS NULL ORDER BY `id` LIMIT 1);
UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='cdacc',`order`=8,`status`=1,`updated_at`=@now WHERE `id`=@cdacc_root;
INSERT INTO `course_category_translations` (`course_category_id`,`lang_code`,`name`,`created_at`,`updated_at`) VALUES (@cdacc_root,'en','CDACC',@now,@now)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);

-- Merge/archive a legacy "CDACC Courses" root when it exists.
UPDATE `course_categories` child
JOIN `course_categories` duplicate ON duplicate.`id`=child.`parent_id`
LEFT JOIN `course_category_translations` translation ON translation.`course_category_id`=duplicate.`id` AND translation.`lang_code`='en'
SET child.`parent_id`=@cdacc_root,child.`taxonomy_profile`='cdacc',child.`updated_at`=@now
WHERE duplicate.`parent_id` IS NULL AND duplicate.`id`<>@cdacc_root AND duplicate.`deleted_at` IS NULL
  AND (duplicate.`slug` IN ('cdacc-courses','cdacc-course') OR LOWER(TRIM(translation.`name`))='cdacc courses');
UPDATE `products` product
JOIN `course_categories` duplicate ON duplicate.`id`=product.`category_id`
LEFT JOIN `course_category_translations` translation ON translation.`course_category_id`=duplicate.`id` AND translation.`lang_code`='en'
SET product.`category_id`=@cdacc_root
WHERE duplicate.`parent_id` IS NULL AND duplicate.`id`<>@cdacc_root AND duplicate.`deleted_at` IS NULL
  AND (duplicate.`slug` IN ('cdacc-courses','cdacc-course') OR LOWER(TRIM(translation.`name`))='cdacc courses');
UPDATE `courses` course
JOIN `course_categories` duplicate ON duplicate.`id`=course.`category_id`
LEFT JOIN `course_category_translations` translation ON translation.`course_category_id`=duplicate.`id` AND translation.`lang_code`='en'
SET course.`category_id`=@cdacc_root
WHERE duplicate.`parent_id` IS NULL AND duplicate.`id`<>@cdacc_root AND duplicate.`deleted_at` IS NULL
  AND (duplicate.`slug` IN ('cdacc-courses','cdacc-course') OR LOWER(TRIM(translation.`name`))='cdacc courses');
UPDATE `curriculum_units` unit
JOIN `course_categories` duplicate ON duplicate.`id`=unit.`awarding_body_id`
LEFT JOIN `course_category_translations` translation ON translation.`course_category_id`=duplicate.`id` AND translation.`lang_code`='en'
SET unit.`awarding_body_id`=@cdacc_root,unit.`updated_at`=@now
WHERE duplicate.`parent_id` IS NULL AND duplicate.`id`<>@cdacc_root AND duplicate.`deleted_at` IS NULL
  AND (duplicate.`slug` IN ('cdacc-courses','cdacc-course') OR LOWER(TRIM(translation.`name`))='cdacc courses');
UPDATE `course_categories` duplicate
LEFT JOIN `course_category_translations` translation ON translation.`course_category_id`=duplicate.`id` AND translation.`lang_code`='en'
SET duplicate.`status`=0,duplicate.`node_type`='legacy',duplicate.`taxonomy_profile`=NULL,duplicate.`deleted_at`=@now,duplicate.`updated_at`=@now
WHERE duplicate.`parent_id` IS NULL AND duplicate.`id`<>@cdacc_root AND duplicate.`deleted_at` IS NULL
  AND (duplicate.`slug` IN ('cdacc-courses','cdacc-course') OR LOWER(TRIM(translation.`name`))='cdacc courses');

-- Create any missing higher-education roots, then normalize their labels.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'tvet','education_level','tvet',NULL,7,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='tvet' AND `deleted_at` IS NULL);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'certificate-courses','education_level','certificate',NULL,9,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='certificate-courses' AND `deleted_at` IS NULL);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'diploma-courses','education_level','diploma',NULL,10,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='diploma-courses' AND `deleted_at` IS NULL);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'undergraduate','education_level','undergraduate',NULL,11,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='undergraduate' AND `deleted_at` IS NULL);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT NULL,'professional-courses','education_level','professional',NULL,12,1,@now,@now
WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='professional-courses' AND `deleted_at` IS NULL);

UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='tvet',`order`=7,`status`=1,`updated_at`=@now WHERE `parent_id` IS NULL AND `slug`='tvet' AND `deleted_at` IS NULL;
UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='certificate',`order`=9,`status`=1,`updated_at`=@now WHERE `parent_id` IS NULL AND `slug`='certificate-courses' AND `deleted_at` IS NULL;
UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='diploma',`order`=10,`status`=1,`updated_at`=@now WHERE `parent_id` IS NULL AND `slug`='diploma-courses' AND `deleted_at` IS NULL;
UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='undergraduate',`order`=11,`status`=1,`updated_at`=@now WHERE `parent_id` IS NULL AND `slug`='undergraduate' AND `deleted_at` IS NULL;
UPDATE `course_categories` SET `node_type`='education_level',`taxonomy_profile`='professional',`order`=12,`status`=1,`updated_at`=@now WHERE `parent_id` IS NULL AND `slug`='professional-courses' AND `deleted_at` IS NULL;
SET @tvet_root=(SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='tvet' AND `deleted_at` IS NULL LIMIT 1);
SET @certificate_root=(SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='certificate-courses' AND `deleted_at` IS NULL LIMIT 1);
SET @diploma_root=(SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='diploma-courses' AND `deleted_at` IS NULL LIMIT 1);
SET @undergraduate_root=(SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='undergraduate' AND `deleted_at` IS NULL LIMIT 1);
SET @professional_root=(SELECT `id` FROM `course_categories` WHERE `parent_id` IS NULL AND `slug`='professional-courses' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` (`course_category_id`,`lang_code`,`name`,`created_at`,`updated_at`) VALUES
(@tvet_root,'en','TVET',@now,@now),(@certificate_root,'en','Certificate',@now,@now),(@diploma_root,'en','Diploma',@now,@now),
(@undergraduate_root,'en','Undergraduate',@now,@now),(@professional_root,'en','Professional',@now,@now)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);

-- TVET sample: Awarding Body -> Trade Area -> Programme/Level -> Module -> Unit Group -> Unit.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @tvet_root,'tveta-sample','awarding_body','tvet',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@tvet_root AND `slug`='tveta-sample' AND `deleted_at` IS NULL);
SET @tvet_owner=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@tvet_root AND `slug`='tveta-sample' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@tvet_owner,'en','Technical and Vocational Training Authority',@now,@now)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @tvet_owner,'electrical-electronics-trade-area','department','tvet',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@tvet_owner AND `slug`='electrical-electronics-trade-area' AND `deleted_at` IS NULL);
SET @tvet_department=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@tvet_owner AND `slug`='electrical-electronics-trade-area' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@tvet_department,'en','Electrical and Electronics Trade Area',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT @tvet_department,'electrical-installation-level-5','programme','tvet',JSON_OBJECT('qualification_level','Level 5'),0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@tvet_department AND `slug`='electrical-installation-level-5' AND `deleted_at` IS NULL);
SET @tvet_programme=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@tvet_department AND `slug`='electrical-installation-level-5' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@tvet_programme,'en','Electrical Installation - Level 5',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `curriculum_units` (`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`) VALUES
(@tvet_owner,'TVT-COM-101','Communication Skills','communication-skills','2026',1,@now,@now),
(@tvet_owner,'TVT-ELI-105','Electrical Installation Practice','electrical-installation-practice','2026',1,@now,@now)
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`),`name`=VALUES(`name`),`status`=1,`deleted_at`=NULL,`updated_at`=VALUES(`updated_at`);
SET @tvet_unit_1=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@tvet_owner AND `slug`='communication-skills' AND `curriculum_version`='2026');
SET @tvet_unit_2=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@tvet_owner AND `slug`='electrical-installation-practice' AND `curriculum_version`='2026');
INSERT INTO `programme_unit_assignments` (`programme_node_id`,`curriculum_unit_id`,`group_type`,`module`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`) VALUES
(@tvet_programme,@tvet_unit_1,'common','Module 1',1,0,1,@now,@now),(@tvet_programme,@tvet_unit_2,'major','Module 1',1,1,1,@now,@now)
ON DUPLICATE KEY UPDATE `group_type`=VALUES(`group_type`),`module`=VALUES(`module`),`status`=1,`updated_at`=VALUES(`updated_at`);

-- Certificate sample.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @certificate_root,'kenya-sample-technical-college-certificate','awarding_body','certificate',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@certificate_root AND `slug`='kenya-sample-technical-college-certificate' AND `deleted_at` IS NULL);
SET @certificate_owner=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@certificate_root AND `slug`='kenya-sample-technical-college-certificate' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@certificate_owner,'en','Kenya Sample Technical College',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @certificate_owner,'school-of-ict-computing-certificate','department','certificate',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@certificate_owner AND `slug`='school-of-ict-computing-certificate' AND `deleted_at` IS NULL);
SET @certificate_department=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@certificate_owner AND `slug`='school-of-ict-computing-certificate' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@certificate_department,'en','School of ICT and Computing',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT @certificate_department,'certificate-in-ict','programme','certificate',JSON_OBJECT('qualification_level','Certificate'),0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@certificate_department AND `slug`='certificate-in-ict' AND `deleted_at` IS NULL);
SET @certificate_programme=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@certificate_department AND `slug`='certificate-in-ict' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@certificate_programme,'en','Certificate in Information Communication Technology',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `curriculum_units` (`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`) VALUES
(@certificate_owner,'CICT-101','Communication Skills','communication-skills','2026',1,@now,@now),(@certificate_owner,'CICT-102','Computer Applications','computer-applications','2026',1,@now,@now)
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`),`name`=VALUES(`name`),`status`=1,`deleted_at`=NULL,`updated_at`=VALUES(`updated_at`);
SET @certificate_unit_1=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@certificate_owner AND `slug`='communication-skills' AND `curriculum_version`='2026');
SET @certificate_unit_2=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@certificate_owner AND `slug`='computer-applications' AND `curriculum_version`='2026');
INSERT INTO `programme_unit_assignments` (`programme_node_id`,`curriculum_unit_id`,`group_type`,`semester`,`module`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`) VALUES
(@certificate_programme,@certificate_unit_1,'common','Semester 1','Module 1',1,0,1,@now,@now),(@certificate_programme,@certificate_unit_2,'major','Semester 1','Module 1',1,1,1,@now,@now)
ON DUPLICATE KEY UPDATE `group_type`=VALUES(`group_type`),`semester`=VALUES(`semester`),`module`=VALUES(`module`),`status`=1,`updated_at`=VALUES(`updated_at`);

-- Diploma sample.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @diploma_root,'kenya-sample-technical-college-diploma','awarding_body','diploma',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@diploma_root AND `slug`='kenya-sample-technical-college-diploma' AND `deleted_at` IS NULL);
SET @diploma_owner=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@diploma_root AND `slug`='kenya-sample-technical-college-diploma' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@diploma_owner,'en','Kenya Sample Technical College',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @diploma_owner,'school-of-engineering-diploma','department','diploma',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@diploma_owner AND `slug`='school-of-engineering-diploma' AND `deleted_at` IS NULL);
SET @diploma_department=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@diploma_owner AND `slug`='school-of-engineering-diploma' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@diploma_department,'en','School of Engineering',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT @diploma_department,'diploma-in-electrical-engineering','programme','diploma',JSON_OBJECT('qualification_level','Diploma'),0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@diploma_department AND `slug`='diploma-in-electrical-engineering' AND `deleted_at` IS NULL);
SET @diploma_programme=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@diploma_department AND `slug`='diploma-in-electrical-engineering' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@diploma_programme,'en','Diploma in Electrical Engineering',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `curriculum_units` (`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`) VALUES
(@diploma_owner,'DEE-1101','Communication Skills','communication-skills','2026',1,@now,@now),(@diploma_owner,'DEE-1102','Electrical Circuit Theory','electrical-circuit-theory','2026',1,@now,@now)
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`),`name`=VALUES(`name`),`status`=1,`deleted_at`=NULL,`updated_at`=VALUES(`updated_at`);
SET @diploma_unit_1=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@diploma_owner AND `slug`='communication-skills' AND `curriculum_version`='2026');
SET @diploma_unit_2=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@diploma_owner AND `slug`='electrical-circuit-theory' AND `curriculum_version`='2026');
INSERT INTO `programme_unit_assignments` (`programme_node_id`,`curriculum_unit_id`,`group_type`,`study_year`,`semester`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`) VALUES
(@diploma_programme,@diploma_unit_1,'common','Year 1','Semester 1',1,0,1,@now,@now),(@diploma_programme,@diploma_unit_2,'major','Year 1','Semester 1',1,1,1,@now,@now)
ON DUPLICATE KEY UPDATE `group_type`=VALUES(`group_type`),`study_year`=VALUES(`study_year`),`semester`=VALUES(`semester`),`status`=1,`updated_at`=VALUES(`updated_at`);

-- Undergraduate sample.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @undergraduate_root,'revisionhub-sample-university','awarding_body','undergraduate',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@undergraduate_root AND `slug`='revisionhub-sample-university' AND `deleted_at` IS NULL);
SET @undergraduate_owner=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@undergraduate_root AND `slug`='revisionhub-sample-university' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@undergraduate_owner,'en','RevisionHub Sample University',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @undergraduate_owner,'faculty-of-computing-information-technology','department','undergraduate',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@undergraduate_owner AND `slug`='faculty-of-computing-information-technology' AND `deleted_at` IS NULL);
SET @undergraduate_department=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@undergraduate_owner AND `slug`='faculty-of-computing-information-technology' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@undergraduate_department,'en','Faculty of Computing and Information Technology',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT @undergraduate_department,'bsc-computer-science','programme','undergraduate',JSON_OBJECT('qualification_level','Bachelor Degree'),0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@undergraduate_department AND `slug`='bsc-computer-science' AND `deleted_at` IS NULL);
SET @undergraduate_programme=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@undergraduate_department AND `slug`='bsc-computer-science' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@undergraduate_programme,'en','Bachelor of Science in Computer Science',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `curriculum_units` (`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`) VALUES
(@undergraduate_owner,'UCU-100','Communication Skills','communication-skills','2026',1,@now,@now),(@undergraduate_owner,'CSC-101','Introduction to Programming','introduction-to-programming','2026',1,@now,@now)
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`),`name`=VALUES(`name`),`status`=1,`deleted_at`=NULL,`updated_at`=VALUES(`updated_at`);
SET @undergraduate_unit_1=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@undergraduate_owner AND `slug`='communication-skills' AND `curriculum_version`='2026');
SET @undergraduate_unit_2=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@undergraduate_owner AND `slug`='introduction-to-programming' AND `curriculum_version`='2026');
INSERT INTO `programme_unit_assignments` (`programme_node_id`,`curriculum_unit_id`,`group_type`,`study_year`,`semester`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`) VALUES
(@undergraduate_programme,@undergraduate_unit_1,'university_common','Year 1','Semester 1',1,0,1,@now,@now),(@undergraduate_programme,@undergraduate_unit_2,'core','Year 1','Semester 1',1,1,1,@now,@now)
ON DUPLICATE KEY UPDATE `group_type`=VALUES(`group_type`),`study_year`=VALUES(`study_year`),`semester`=VALUES(`semester`),`status`=1,`updated_at`=VALUES(`updated_at`);

-- Professional sample.
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @professional_root,'kasneb-sample','awarding_body','professional',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@professional_root AND `slug`='kasneb-sample' AND `deleted_at` IS NULL);
SET @professional_owner=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@professional_root AND `slug`='kasneb-sample' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@professional_owner,'en','KASNEB',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`order`,`status`,`created_at`,`updated_at`)
SELECT @professional_owner,'cpa-qualification','department','professional',0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@professional_owner AND `slug`='cpa-qualification' AND `deleted_at` IS NULL);
SET @professional_department=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@professional_owner AND `slug`='cpa-qualification' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@professional_department,'en','Certified Public Accountants Qualification',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `course_categories` (`parent_id`,`slug`,`node_type`,`taxonomy_profile`,`taxonomy_metadata`,`order`,`status`,`created_at`,`updated_at`)
SELECT @professional_department,'certified-public-accountants-cpa','programme','professional',JSON_OBJECT('qualification_level','Professional Qualification'),0,1,@now,@now WHERE NOT EXISTS (SELECT 1 FROM `course_categories` WHERE `parent_id`=@professional_department AND `slug`='certified-public-accountants-cpa' AND `deleted_at` IS NULL);
SET @professional_programme=(SELECT `id` FROM `course_categories` WHERE `parent_id`=@professional_department AND `slug`='certified-public-accountants-cpa' AND `deleted_at` IS NULL LIMIT 1);
INSERT INTO `course_category_translations` VALUES (NULL,@professional_programme,'en','Certified Public Accountants (CPA)',@now,@now) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`updated_at`=VALUES(`updated_at`);
INSERT INTO `curriculum_units` (`awarding_body_id`,`code`,`name`,`slug`,`curriculum_version`,`status`,`created_at`,`updated_at`) VALUES
(@professional_owner,'CPA-FA','Financial Accounting','financial-accounting','2026',1,@now,@now),(@professional_owner,'CPA-CS','Communication Skills','communication-skills','2026',1,@now,@now)
ON DUPLICATE KEY UPDATE `code`=VALUES(`code`),`name`=VALUES(`name`),`status`=1,`deleted_at`=NULL,`updated_at`=VALUES(`updated_at`);
SET @professional_unit_1=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@professional_owner AND `slug`='financial-accounting' AND `curriculum_version`='2026');
SET @professional_unit_2=(SELECT `id` FROM `curriculum_units` WHERE `awarding_body_id`=@professional_owner AND `slug`='communication-skills' AND `curriculum_version`='2026');
INSERT INTO `programme_unit_assignments` (`programme_node_id`,`curriculum_unit_id`,`group_type`,`stage`,`section`,`is_mandatory`,`display_order`,`status`,`created_at`,`updated_at`) VALUES
(@professional_programme,@professional_unit_1,'paper','Foundation Level','Section 1',1,0,1,@now,@now),(@professional_programme,@professional_unit_2,'paper','Foundation Level','Section 1',1,1,1,@now,@now)
ON DUPLICATE KEY UPDATE `group_type`=VALUES(`group_type`),`stage`=VALUES(`stage`),`section`=VALUES(`section`),`status`=1,`updated_at`=VALUES(`updated_at`);

SET @taxonomy_batch=(SELECT COALESCE(MAX(`batch`),0)+1 FROM `migrations`);
INSERT INTO `migrations` (`migration`,`batch`)
SELECT '2026_11_19_000005_seed_upper_education_product_taxonomy_samples',@taxonomy_batch
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration`='2026_11_19_000005_seed_upper_education_product_taxonomy_samples');

SELECT `taxonomy_profile`,COUNT(*) AS `active_assignments`
FROM `programme_unit_assignments` assignment
JOIN `course_categories` programme ON programme.`id`=assignment.`programme_node_id`
WHERE assignment.`status`=1 AND programme.`taxonomy_profile` IS NOT NULL
GROUP BY `taxonomy_profile`
ORDER BY `taxonomy_profile`;
