-- Testing flow columns on water_frequency_schedule
-- Run this script before using Testing -> Checking -> Approval -> Log flow.

ALTER TABLE `water_frequency_schedule`
    ADD COLUMN IF NOT EXISTS `tested_by` text DEFAULT NULL AFTER `testing_remark`,
    ADD COLUMN IF NOT EXISTS `tested_on` text DEFAULT NULL AFTER `tested_by`,
    ADD COLUMN IF NOT EXISTS `checking_status` text DEFAULT 'Pending' AFTER `tested_on`,
    ADD COLUMN IF NOT EXISTS `checking_remark` mediumtext DEFAULT NULL AFTER `checking_status`,
    ADD COLUMN IF NOT EXISTS `approval_status` text DEFAULT 'Pending' AFTER `checking_remark`,
    ADD COLUMN IF NOT EXISTS `approval_remark` mediumtext DEFAULT NULL AFTER `approval_status`,
    ADD COLUMN IF NOT EXISTS `approved_by` text DEFAULT NULL AFTER `approval_remark`,
    ADD COLUMN IF NOT EXISTS `approved_on` text DEFAULT NULL AFTER `approved_by`;

ALTER TABLE `water_frequency_schedule`
    ADD INDEX IF NOT EXISTS `idx_testing_status` (`testing_status`(20)),
    ADD INDEX IF NOT EXISTS `idx_checking_status` (`checking_status`(20)),
    ADD INDEX IF NOT EXISTS `idx_approval_status` (`approval_status`(20)),
    ADD INDEX IF NOT EXISTS `idx_sampling_no` (`sampling_no`(20));
