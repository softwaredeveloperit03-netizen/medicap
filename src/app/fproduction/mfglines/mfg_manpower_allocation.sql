-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 31, 2026 at 12:55 PM
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
-- Database: `paperlessgmp_pritam`
--

-- --------------------------------------------------------

--
-- Table structure for table `mfg_manpower_allocation`
--

CREATE TABLE `mfg_manpower_allocation` (
  `id` int(11) NOT NULL,
  `work_order_no` varchar(50) NOT NULL,
  `emp_id` varchar(50) NOT NULL,
  `emp_name` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL COMMENT 'Supervisor, Operator, Helper, QA Person, QC Person, Other',
  `remarks` text DEFAULT NULL,
  `allocated_date` datetime NOT NULL,
  `allocated_by` varchar(50) NOT NULL COMMENT 'Employee ID of person who allocated',
  `removed_date` datetime DEFAULT NULL,
  `removed_by` varchar(50) DEFAULT NULL COMMENT 'Employee ID of person who removed',
  `plant_id` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mfg_manpower_allocation`
--
ALTER TABLE `mfg_manpower_allocation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_work_order_no` (`work_order_no`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_plant_id` (`plant_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mfg_manpower_allocation`
--
ALTER TABLE `mfg_manpower_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
