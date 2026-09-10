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
-- Table structure for table `water_point`
--

CREATE TABLE `water_point` (
  `id` int(11) NOT NULL,
  `plant_id` text DEFAULT NULL,
  `point_no` text DEFAULT NULL,
  `water_for` text DEFAULT NULL,
  `point_type` text DEFAULT NULL,
  `point_name` text DEFAULT NULL,
  `water_type` text DEFAULT NULL,
  `frequency` text DEFAULT NULL,
  `day` text DEFAULT NULL,
  `testing_type` text DEFAULT NULL,
  `tests` text DEFAULT NULL,
  `department` text DEFAULT NULL,
  `testing_remark` text DEFAULT NULL,
  `section` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `entry_by` text DEFAULT NULL,
  `entry_date` text DEFAULT NULL,
  `approve_by` text DEFAULT NULL,
  `approve_date` text DEFAULT NULL,
  `user_no` text DEFAULT NULL,
  `allocation` text DEFAULT NULL,
  `specification_no` text DEFAULT NULL,
  `allocated_to` text DEFAULT NULL,
  `allocated_on` text DEFAULT NULL,
  `microbiology_qty` text DEFAULT NULL,
  `chemical_qty` text DEFAULT NULL,
  `unit` text DEFAULT NULL,
  `sampling_no` text DEFAULT NULL,
  `sampling_by` text DEFAULT NULL,
  `sampling_date` text DEFAULT NULL,
  `sampling_status` text DEFAULT NULL,
  `is_RDS` text DEFAULT NULL,
  `testing_person` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `water_point`
--

INSERT INTO `water_point` (`id`, `plant_id`, `point_no`, `water_for`, `point_type`, `point_name`, `water_type`, `frequency`, `day`, `testing_type`, `tests`, `department`, `testing_remark`, `section`, `status`, `entry_by`, `entry_date`, `approve_by`, `approve_date`, `user_no`, `allocation`, `specification_no`, `allocated_to`, `allocated_on`, `microbiology_qty`, `chemical_qty`, `unit`, `sampling_no`, `sampling_by`, `sampling_date`, `sampling_status`, `is_RDS`, `testing_person`) VALUES
(4, '142', 'W00004', '', '', 'First Floor Water Point', 'Potable Water', '[{\"id\":4,\"particular\":\"Monthly\",\"checked\":true,\"last_insp_date\":\"2026-02-24\",\"last_prevent_date\":\"\"}]', '', 'Chemical', NULL, 'Quality Control', NULL, 'Sampling', NULL, 'APPL002', '2026-03-25 19:41:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '142', 'W00001', '', '', 'Raw Water Inlet Point', 'Raw Water', '[{\"id\":1,\"particular\":\"Daily\",\"checked\":true,\"last_insp_date\":\"\",\"last_prevent_date\":\"\"}]', '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:34:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, '142', 'W00006', '', '', 'Borewell / Municipal Supply Point', 'Raw Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:34:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '142', 'W00007', '', '', 'Pre-Treatment Feed Water Point', 'Raw Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:35:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, '142', 'W00008', '', '', 'Sand Filter Outlet Point', 'Raw Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:36:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, '142', 'W00009', '', '', 'Activated Carbon Filter Outlet Point', 'Raw Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:36:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '142', 'W00001', '', '', 'RO Feed Water Point', 'RO Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:37:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '142', 'W00011', '', '', 'RO Permeate Point (RO-I Outlet)', 'RO Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:37:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '142', 'W00012', '', '', 'RO-II Outlet Point (if double pass)', 'RO Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:37:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, '142', 'W00013', '', '', 'RO Reject Water Point', 'RO Water', NULL, '', 'Chemical', NULL, 'Quality Control', NULL, '', NULL, 'APPL007', '2026-03-26 08:38:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `water_point`
--
ALTER TABLE `water_point`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `water_point`
--
ALTER TABLE `water_point`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
