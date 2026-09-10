-- Migration Script: Enhance workorder_stage_dates table
-- Add support for start dates, time fields, status, and duration

-- Step 1: Add new columns for start dates and times
ALTER TABLE `workorder_stage_dates`
ADD COLUMN IF NOT EXISTS `tentative_start_date` DATE NULL AFTER `stage`,
ADD COLUMN IF NOT EXISTS `tentative_start_time` TIME NULL AFTER `tentative_start_date`,
ADD COLUMN IF NOT EXISTS `actual_start_date` DATE NULL AFTER `tentative_start_time`,
ADD COLUMN IF NOT EXISTS `actual_start_time` TIME NULL AFTER `actual_start_date`,
ADD COLUMN IF NOT EXISTS `tentative_completion_time` TIME NULL AFTER `tentative_completion_date`,
ADD COLUMN IF NOT EXISTS `actual_completion_time` TIME NULL AFTER `actual_completion_date`,
ADD COLUMN IF NOT EXISTS `stage_status` ENUM('Not Started', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Not Started' AFTER `actual_completion_time`,
ADD COLUMN IF NOT EXISTS `duration_hours` DECIMAL(10,2) NULL AFTER `stage_status`;

-- Step 2: Add index for better query performance
ALTER TABLE `workorder_stage_dates`
ADD INDEX IF NOT EXISTS `idx_workorder_stage` (`workorder_no`, `stage`);

-- Step 3: Update existing records to set status based on dates
UPDATE `workorder_stage_dates`
SET `stage_status` = CASE
    WHEN `actual_completion_date` IS NOT NULL THEN 'Completed'
    WHEN `actual_start_date` IS NOT NULL THEN 'In Progress'
    ELSE 'Not Started'
END
WHERE `stage_status` IS NULL OR `stage_status` = 'Not Started';

-- Step 4: Calculate duration for existing records with both start and end dates
UPDATE `workorder_stage_dates`
SET `duration_hours` = TIMESTAMPDIFF(HOUR, 
    CONCAT(COALESCE(`actual_start_date`, `tentative_start_date`), ' ', COALESCE(`actual_start_time`, `tentative_start_time`, '00:00:00')),
    CONCAT(COALESCE(`actual_completion_date`, `tentative_completion_date`), ' ', COALESCE(`actual_completion_time`, `tentative_completion_time`, '00:00:00'))
)
WHERE (`actual_start_date` IS NOT NULL OR `tentative_start_date` IS NOT NULL)
AND (`actual_completion_date` IS NOT NULL OR `tentative_completion_date` IS NOT NULL)
AND `duration_hours` IS NULL;
