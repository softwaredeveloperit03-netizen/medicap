-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 30, 2026 at 08:23 PM
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
-- Table structure for table `manufacturing_process`
--

CREATE TABLE `manufacturing_process` (
  `id` int(11) NOT NULL,
  `product_code` text DEFAULT NULL,
  `plant_id` text DEFAULT NULL,
  `user_no` text DEFAULT 'gmpdemo1',
  `dosage_form` text DEFAULT NULL,
  `process_type` text DEFAULT NULL,
  `stage` text DEFAULT NULL,
  `step` text DEFAULT NULL,
  `status` text DEFAULT 'pending',
  `entry_by` text DEFAULT NULL,
  `entry_date` text DEFAULT NULL,
  `ipqc_test` text DEFAULT NULL,
  `inprocess_checks` text DEFAULT NULL,
  `line_clearance` text DEFAULT NULL,
  `ProcessTitle` text DEFAULT NULL,
  `DocumentTitle` text DEFAULT '0',
  `DocumentNo` text DEFAULT NULL,
  `Forms` longtext DEFAULT NULL,
  `SubTitle` text DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `for_department` text DEFAULT NULL,
  `batch_lot` text DEFAULT NULL,
  `effective_date` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `manufacturing_process`
--

INSERT INTO `manufacturing_process` (`id`, `product_code`, `plant_id`, `user_no`, `dosage_form`, `process_type`, `stage`, `step`, `status`, `entry_by`, `entry_date`, `ipqc_test`, `inprocess_checks`, `line_clearance`, `ProcessTitle`, `DocumentTitle`, `DocumentNo`, `Forms`, `SubTitle`, `Description`, `for_department`, `batch_lot`, `effective_date`) VALUES
(1, 'Test GEneRic001', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'fructose 1,6 Diphosphate Trisodium Hydrate', 'MBMR/FDP-001-00', 'null', NULL, NULL, NULL, NULL, NULL),
(2, 'GN401', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'amoxilin Test BMR', 'Test BMR001', 'null', NULL, NULL, NULL, NULL, NULL),
(3, 'GN401', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'PI Title', 'PINO', 'null', NULL, NULL, NULL, NULL, NULL),
(4, '', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, '', '', 'null', NULL, NULL, NULL, NULL, NULL),
(5, '231', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'Tenocxicam lyophilizate solution for injection', 'MBMR-/TNC-001', 'null', NULL, NULL, NULL, NULL, NULL),
(6, 'GN00020', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'Aprotin', 'BMR01', 'null', NULL, NULL, NULL, NULL, NULL),
(7, '231', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'Arginine hydrochloride, Levocarnitine solution for infusions100ml.', 'MBMR-/TNC-001', 'null', NULL, NULL, NULL, NULL, NULL),
(8, 'GN', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'BMR TITLE ', 'MBMR/FDP-001-0012', 'null', NULL, NULL, NULL, NULL, NULL),
(9, 'GN00019', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'Tenoxicam lyophilizate for solution for injections 20 mg ', 'MBMR-/TNC-001', 'null', NULL, NULL, NULL, NULL, NULL),
(10, '231', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'Arginine hydrochloride, Levocarnitine solution for infusions100ml.', 'MBMR/ARG-001', 'null', NULL, NULL, NULL, NULL, NULL),
(11, 'GN00018', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'BMR-AD', '242424', 'null', NULL, NULL, NULL, NULL, NULL),
(12, 'Tart Dilution 200', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'BMR-AD34', '242424', 'null', NULL, NULL, NULL, NULL, NULL),
(13, 'GN00013', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'fructose 1,6 Diphosphate trisodium hydrate ', 'MBMR-/TNC-001', 'null', NULL, NULL, NULL, NULL, NULL),
(14, 'GN00012', '77', 'gmpdemo1', ' ', ' ', ' ', ' ', 'pending', ' ', ' ', ' ', ' ', ' ', NULL, 'BMR TITLE ', 'PINO', 'null', NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `manufacturing_process`
--
ALTER TABLE `manufacturing_process`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `manufacturing_process`
--
ALTER TABLE `manufacturing_process`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
