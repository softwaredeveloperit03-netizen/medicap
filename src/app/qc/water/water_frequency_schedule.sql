-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 26, 2026 at 11:51 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paperlessgmp_cyclone`
--

-- --------------------------------------------------------

--
-- Table structure for table `water_frequency_schedule`
--

CREATE TABLE `water_frequency_schedule` (
  `id` int(11) NOT NULL,
  `due_type` text DEFAULT NULL,
  `frequency` text DEFAULT NULL,
  `water_point_id` text DEFAULT NULL,
  `due_date` text DEFAULT NULL,
  `checklist` mediumtext DEFAULT NULL,
  `preventive_date` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `entry_by` text DEFAULT NULL,
  `entry_date` text DEFAULT NULL,
  `check_by` text DEFAULT NULL,
  `check_on` text DEFAULT NULL,
  `approve_by` text DEFAULT NULL,
  `approve_on` text DEFAULT NULL,
  `intimation_status` text DEFAULT NULL,
  `intimation_data` mediumtext DEFAULT NULL,
  `sampling_status` text DEFAULT 'Pending',
  `sampling_requested_by` text DEFAULT NULL,
  `sampling_requested_on` text DEFAULT NULL,
  `sampling_allocated_to` text DEFAULT NULL,
  `sampling_allocated_on` text DEFAULT NULL,
  `sampling_no` text DEFAULT NULL,
  `bottle_list` mediumtext DEFAULT NULL,
  `sampling_tests` mediumtext DEFAULT NULL,
  `chemical_qty` text DEFAULT NULL,
  `microbiology_qty` text DEFAULT NULL,
  `sampling_unit` text DEFAULT NULL,
  `sampled_by` text DEFAULT NULL,
  `sampled_on` text DEFAULT NULL,
  `testing_status` text DEFAULT NULL,
  `testing_remark` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `water_frequency_schedule`
--

INSERT INTO `water_frequency_schedule` (`id`, `due_type`, `frequency`, `water_point_id`, `due_date`, `checklist`, `preventive_date`, `remark`, `status`, `entry_by`, `entry_date`, `check_by`, `check_on`, `approve_by`, `approve_on`, `intimation_status`, `intimation_data`) VALUES
(105, 'Frequency', 'Monthly', '4', '2026-03-26', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(106, 'Frequency', 'Monthly', '4', '2026-04-25', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(107, 'Frequency', 'Monthly', '4', '2026-05-25', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(108, 'Frequency', 'Monthly', '4', '2026-06-24', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(109, 'Frequency', 'Monthly', '4', '2026-07-24', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(110, 'Frequency', 'Monthly', '4', '2026-08-23', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(111, 'Frequency', 'Monthly', '4', '2026-09-22', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(112, 'Frequency', 'Monthly', '4', '2026-10-22', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(113, 'Frequency', 'Monthly', '4', '2026-11-21', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(114, 'Frequency', 'Monthly', '4', '2026-12-21', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(115, 'Frequency', 'Monthly', '4', '2027-01-20', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(116, 'Frequency', 'Monthly', '4', '2027-02-19', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(117, 'Frequency', 'Monthly', '4', '2027-03-21', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(118, 'Frequency', 'Monthly', '4', '2027-04-20', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(119, 'Frequency', 'Monthly', '4', '2027-05-20', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(120, 'Frequency', 'Monthly', '4', '2027-06-19', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(121, 'Frequency', 'Monthly', '4', '2027-07-19', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(122, 'Frequency', 'Monthly', '4', '2027-08-18', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(123, 'Frequency', 'Monthly', '4', '2027-09-17', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(124, 'Frequency', 'Monthly', '4', '2027-10-17', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(125, 'Frequency', 'Monthly', '4', '2027-11-16', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(126, 'Frequency', 'Monthly', '4', '2027-12-16', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(127, 'Frequency', 'Monthly', '4', '2028-01-15', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL),
(128, 'Frequency', 'Monthly', '4', '2028-02-14', NULL, NULL, NULL, 'Pending', 'APPL002', '2026-03-25 19:43:41', NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `water_frequency_schedule`
--
ALTER TABLE `water_frequency_schedule`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `water_frequency_schedule`
--
ALTER TABLE `water_frequency_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
