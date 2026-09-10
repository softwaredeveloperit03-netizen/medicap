-- New table for testing audit trail
-- One row per stage action (Testing/Checking/Approval).

CREATE TABLE IF NOT EXISTS `water_testing_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `sampling_no` text DEFAULT NULL,
  `stage_name` text DEFAULT NULL,
  `action_name` text DEFAULT NULL,
  `action_by` text DEFAULT NULL,
  `action_on` text DEFAULT NULL,
  `remark` mediumtext DEFAULT NULL,
  `payload_json` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_action_on` (`action_on`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
