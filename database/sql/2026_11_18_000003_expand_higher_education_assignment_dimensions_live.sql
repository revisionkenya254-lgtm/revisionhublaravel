-- Run this incremental script only if migrations 000000-000002 were already
-- applied manually before the multi-profile expansion was deployed.

ALTER TABLE `programme_unit_assignments`
    MODIFY `group_type` VARCHAR(40) NOT NULL DEFAULT 'major',
    ADD COLUMN `study_year` VARCHAR(40) NULL AFTER `group_type`,
    ADD COLUMN `module` VARCHAR(80) NULL AFTER `semester`,
    ADD COLUMN `stage` VARCHAR(80) NULL AFTER `module`,
    ADD COLUMN `section` VARCHAR(80) NULL AFTER `stage`,
    ADD INDEX `programme_unit_assignment_period_index`
        (`programme_node_id`, `study_year`, `semester`);

SET @taxonomy_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_18_000003_expand_higher_education_assignment_dimensions', @taxonomy_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_11_18_000003_expand_higher_education_assignment_dimensions'
);

SELECT `group_type`, `study_year`, `semester`, `module`, `stage`, `section`
FROM `programme_unit_assignments`
LIMIT 20;
