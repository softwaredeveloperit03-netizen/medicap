-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 30, 2026 at 11:57 AM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aurenyxgmp_zuma`
--

-- --------------------------------------------------------

--
-- Table structure for table `line_booking`
--

CREATE TABLE `line_booking` (
  `id` int(11) NOT NULL,
  `workorder_no` varchar(100) NOT NULL,
  `linemaster_id` int(11) NOT NULL,
  `line_no` varchar(50) DEFAULT NULL,
  `product_code` varchar(100) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `booking_start_date` date DEFAULT NULL,
  `booking_start_time` time DEFAULT NULL,
  `booking_end_date` date DEFAULT NULL,
  `booking_end_time` time DEFAULT NULL,
  `responsible_person` varchar(255) DEFAULT NULL,
  `selected_equipments` text DEFAULT NULL COMMENT 'JSON array of selected equipment',
  `capacity_required` varchar(50) DEFAULT NULL,
  `no_of_hours_required` decimal(10,2) DEFAULT 0.00,
  `status` enum('Booked','In Progress','Parked','Completed','Cancelled') DEFAULT 'Booked',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `entry_by` varchar(100) DEFAULT NULL,
  `entry_date` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `plant_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `line_booking`
--

INSERT INTO `line_booking` (`id`, `workorder_no`, `linemaster_id`, `line_no`, `product_code`, `product_name`, `booking_start_date`, `booking_start_time`, `booking_end_date`, `booking_end_time`, `responsible_person`, `selected_equipments`, `capacity_required`, `no_of_hours_required`, `status`, `cancellation_reason`, `cancelled_by`, `cancelled_date`, `entry_by`, `entry_date`, `updated_by`, `updated_date`, `plant_id`) VALUES
(6, 'BO005', 17, '3', 'P0037', 'LOREAL SHAMPOO', '2026-01-22', '14:40:00', '2026-01-25', '14:40:00', 'Vivek Patil - (VP002)', '[{\"id\":\"29\",\"linemaster_id\":\"17\",\"equipment_name\":\"Batch Manufacturing Vessel\",\"equipment_code\":\"AEQP-037\",\"capacity\":\"250\",\"from_range\":\"10\",\"to_range\":\"250\",\"unit\":\"KG\"},{\"id\":\"30\",\"linemaster_id\":\"17\",\"equipment_name\":\"Homogenizer with Agitator\",\"equipment_code\":\"AEQP-036\",\"capacity\":\"500\",\"from_range\":\"5\",\"to_range\":\"500\",\"unit\":\"KG\"},{\"id\":\"31\",\"linemaster_id\":\"17\",\"equipment_name\":\"Liquid Mixing Vessel\",\"equipment_code\":\"AEQP-016\",\"capacity\":\"1000\",\"from_range\":\"10\",\"to_range\":\"1000\",\"unit\":\"LTR\"}]', '100', 0.00, 'Booked', NULL, NULL, NULL, 'master', '2026-01-22 16:33:50', NULL, NULL, '181'),
(7, 'BO002', 17, '3', 'P0037', 'LOREAL SHAMPOO', '2026-01-23', '12:31:00', '2026-01-25', '12:31:00', 'Vivek Patil - (VP002)', '[{\"id\":\"29\",\"linemaster_id\":\"17\",\"equipment_name\":\"Batch Manufacturing Vessel\",\"equipment_code\":\"AEQP-037\",\"capacity\":\"250\",\"from_range\":\"10\",\"to_range\":\"250\",\"unit\":\"KG\"},{\"id\":\"30\",\"linemaster_id\":\"17\",\"equipment_name\":\"Homogenizer with Agitator\",\"equipment_code\":\"AEQP-036\",\"capacity\":\"500\",\"from_range\":\"5\",\"to_range\":\"500\",\"unit\":\"KG\"},{\"id\":\"31\",\"linemaster_id\":\"17\",\"equipment_name\":\"Liquid Mixing Vessel\",\"equipment_code\":\"AEQP-016\",\"capacity\":\"1000\",\"from_range\":\"10\",\"to_range\":\"1000\",\"unit\":\"LTR\"}]', '100', 0.00, 'Parked', NULL, NULL, NULL, 'master', '2026-01-22 18:02:25', 'master', '2026-01-23 11:58:22', '181'),
(8, 'BO004', 17, '3', 'P0037', 'LOREAL SHAMPOO', '2026-01-22', '18:43:00', '2026-01-26', '20:43:00', 'Vivek Patil - (VP002)', '[{\"id\":\"29\",\"linemaster_id\":\"17\",\"equipment_name\":\"Batch Manufacturing Vessel\",\"equipment_code\":\"AEQP-037\",\"capacity\":\"250\",\"from_range\":\"10\",\"to_range\":\"250\",\"unit\":\"KG\"},{\"id\":\"30\",\"linemaster_id\":\"17\",\"equipment_name\":\"Homogenizer with Agitator\",\"equipment_code\":\"AEQP-036\",\"capacity\":\"500\",\"from_range\":\"5\",\"to_range\":\"500\",\"unit\":\"KG\"},{\"id\":\"31\",\"linemaster_id\":\"17\",\"equipment_name\":\"Liquid Mixing Vessel\",\"equipment_code\":\"AEQP-016\",\"capacity\":\"1000\",\"from_range\":\"10\",\"to_range\":\"1000\",\"unit\":\"LTR\"}]', '100', 0.00, 'Booked', NULL, NULL, NULL, 'master', '2026-01-23 11:28:37', NULL, NULL, '181'),
(9, 'BO316', 17, '3', 'P0037', 'LOREAL SHAMPOO', '2026-01-23', '16:12:00', '2026-01-27', '16:12:00', 'Vivek Patil - (VP002)', '[{\"id\":\"29\",\"linemaster_id\":\"17\",\"equipment_name\":\"Batch Manufacturing Vessel\",\"equipment_code\":\"AEQP-037\",\"capacity\":\"250\",\"from_range\":\"10\",\"to_range\":\"250\",\"unit\":\"KG\"},{\"id\":\"30\",\"linemaster_id\":\"17\",\"equipment_name\":\"Homogenizer with Agitator\",\"equipment_code\":\"AEQP-036\",\"capacity\":\"500\",\"from_range\":\"5\",\"to_range\":\"500\",\"unit\":\"KG\"},{\"id\":\"31\",\"linemaster_id\":\"17\",\"equipment_name\":\"Liquid Mixing Vessel\",\"equipment_code\":\"AEQP-016\",\"capacity\":\"1000\",\"from_range\":\"10\",\"to_range\":\"1000\",\"unit\":\"LTR\"}]', '100', 0.00, 'Booked', NULL, NULL, NULL, 'master', '2026-01-23 16:58:20', NULL, NULL, '181');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `line_booking`
--
ALTER TABLE `line_booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workorder_no` (`workorder_no`),
  ADD KEY `idx_linemaster_id` (`linemaster_id`),
  ADD KEY `idx_line_no` (`line_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_booking_dates` (`booking_start_date`,`booking_end_date`),
  ADD KEY `idx_booking_date_range` (`booking_start_date`,`booking_end_date`,`status`),
  ADD KEY `idx_product_code` (`product_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `line_booking`
--
ALTER TABLE `line_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `line_booking`
--
ALTER TABLE `line_booking`
  ADD CONSTRAINT `fk_line_booking_linemaster` FOREIGN KEY (`linemaster_id`) REFERENCES `linemaster` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
