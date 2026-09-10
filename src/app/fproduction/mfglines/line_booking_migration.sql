-- Migration Script: Add Actual Date Columns to line_booking table
-- Date: 2026-01-30
-- Purpose: Add actual_start_date, actual_start_time, actual_end_date, actual_end_time columns
--          to track actual production dates separately from tentative booking dates

-- Add actual date columns
ALTER TABLE `line_booking`
ADD COLUMN `actual_start_date` DATE NULL AFTER `booking_end_time`,
ADD COLUMN `actual_start_time` TIME NULL AFTER `actual_start_date`,
ADD COLUMN `actual_end_date` DATE NULL AFTER `actual_start_time`,
ADD COLUMN `actual_end_time` TIME NULL AFTER `actual_end_date`;

-- Add index for performance on actual dates
ALTER TABLE `line_booking`
ADD INDEX `idx_actual_dates` (`actual_start_date`, `actual_end_date`);

-- Optional: Create trigger to auto-complete booking when actual_end_date is set
-- Note: This can also be handled in application logic if preferred
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `auto_complete_booking_on_actual_end_date`
BEFORE UPDATE ON `line_booking`
FOR EACH ROW
BEGIN
    -- If actual_end_date is being set and status is not already Completed/Cancelled
    IF NEW.actual_end_date IS NOT NULL 
       AND (OLD.actual_end_date IS NULL OR OLD.actual_end_date = '')
       AND NEW.status NOT IN ('Completed', 'Cancelled') THEN
        SET NEW.status = 'Completed';
        SET NEW.updated_date = NOW();
    END IF;
END$$
DELIMITER ;

-- Verify columns were added
-- SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'line_booking' 
-- AND COLUMN_NAME LIKE 'actual%';
