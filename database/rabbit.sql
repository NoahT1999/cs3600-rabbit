-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2025 at 01:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rabbit`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `effective_date` date NOT NULL,
  `length` int(11) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget`
--

INSERT INTO `budget` (`id`, `name`, `effective_date`, `length`) VALUES
(14, 'Budget 1', '2026-01-01', 5),
(15, 'Budget 2', '2026-01-01', 5),
(16, 'testing 72', '2026-01-01', 5),
(23, 'test', '2025-11-21', 3);

-- --------------------------------------------------------

--
-- Table structure for table `budget_access`
--

CREATE TABLE `budget_access` (
  `user_id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_access`
--

INSERT INTO `budget_access` (`user_id`, `budget_id`) VALUES
(20, 14),
(20, 16),
(20, 23),
(28, 15);

-- --------------------------------------------------------

--
-- Table structure for table `budget_equipment`
--

CREATE TABLE `budget_equipment` (
  `budget_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_equipment`
--

INSERT INTO `budget_equipment` (`budget_id`, `equipment_id`, `year`, `cost`) VALUES
(14, 1, 1, 321.00),
(14, 2, 1, 0.00),
(14, 2, 2, 0.00),
(14, 2, 3, 123.00),
(14, 2, 4, 0.00),
(14, 2, 5, 0.00),
(14, 3, 1, 0.00),
(14, 3, 2, 0.00),
(14, 3, 3, 0.00),
(14, 3, 4, 0.00),
(14, 3, 5, 0.00),
(14, 4, 1, 0.00),
(14, 4, 2, 0.00),
(14, 4, 3, 46.52),
(14, 4, 4, 0.00),
(14, 4, 5, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_other_costs`
--

CREATE TABLE `budget_other_costs` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `materials_and_supplies` decimal(10,2) DEFAULT 0.00,
  `small_equipment` decimal(10,2) DEFAULT 0.00,
  `publication` decimal(10,2) DEFAULT 0.00,
  `computer_services` decimal(10,2) DEFAULT 0.00,
  `software` decimal(10,2) DEFAULT 0.00,
  `facility_fees` decimal(10,2) DEFAULT 0.00,
  `conference_registration` decimal(10,2) DEFAULT 0.00,
  `other` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_other_costs`
--

INSERT INTO `budget_other_costs` (`id`, `year`, `materials_and_supplies`, `small_equipment`, `publication`, `computer_services`, `software`, `facility_fees`, `conference_registration`, `other`) VALUES
(14, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.73, 12312345.00),
(14, 2, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(14, 3, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(14, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(14, 5, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(15, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(15, 2, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(15, 3, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(15, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(15, 5, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(16, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(16, 2, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(16, 3, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(16, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(16, 5, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(23, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(23, 2, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(23, 3, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(23, 4, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(23, 5, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_personnel`
--

CREATE TABLE `budget_personnel` (
  `budget_id` int(11) NOT NULL,
  `personnel_type` enum('staff','student') NOT NULL,
  `personnel_id` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_personnel`
--

INSERT INTO `budget_personnel` (`budget_id`, `personnel_type`, `personnel_id`) VALUES
(14, 'staff', '1'),
(14, 'staff', '344568');

-- --------------------------------------------------------

--
-- Table structure for table `budget_personnel_effort`
--

CREATE TABLE `budget_personnel_effort` (
  `budget_id` int(11) NOT NULL,
  `personnel_type` varchar(10) NOT NULL,
  `personnel_id` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `effort_percent` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_personnel_effort`
--

INSERT INTO `budget_personnel_effort` (`budget_id`, `personnel_type`, `personnel_id`, `year`, `effort_percent`) VALUES
(14, 'staff', '1', 1, 0.00),
(14, 'staff', '1', 2, 0.00),
(14, 'staff', '1', 3, 0.00),
(14, 'staff', '1', 4, 0.00),
(14, 'staff', '1', 5, 0.00),
(14, 'staff', '344568', 1, 3.00),
(14, 'staff', '344568', 2, 0.00),
(14, 'staff', '344568', 3, 0.00),
(14, 'staff', '344568', 4, 0.00),
(14, 'staff', '344568', 5, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_personnel_growth`
--

CREATE TABLE `budget_personnel_growth` (
  `budget_id` int(11) NOT NULL,
  `personnel_type` varchar(10) NOT NULL,
  `personnel_id` varchar(20) NOT NULL,
  `growth_rate_percent` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domestic_travel_per_diem`
--

CREATE TABLE `domestic_travel_per_diem` (
  `id` int(11) NOT NULL,
  `state` varchar(2) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `county` varchar(255) NOT NULL,
  `season_start` date DEFAULT NULL,
  `season_end` date DEFAULT NULL,
  `lodging` int(11) DEFAULT NULL,
  `mie` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `domestic_travel_per_diem`
--

INSERT INTO `domestic_travel_per_diem` (`id`, `state`, `destination`, `county`, `season_start`, `season_end`, `lodging`, `mie`) VALUES
(1, 'WA', 'Spokane', 'Spokane', NULL, NULL, 100, 200),
(2, 'WA', 'Seattle', 'King', '1000-10-01', '1000-05-31', 188, 92),
(3, 'WA', 'Seattle', 'King', '1000-06-01', '1000-09-30', 248, 92),
(4, 'ID', 'Coeur dAlene', 'Kootenai', NULL, NULL, 200, 500),
(5, 'WA', 'Tacoma', 'King', NULL, NULL, 48, 92);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `description`) VALUES
(1, 'Flux Capacitor', 'The flux capacitor is too complex to comprehend'),
(2, 'Shovel', 'What needs to be said about the shovel?'),
(3, 'Raspberry Pi', NULL),
(4, 'Sandwich Cutter', 'Cuts sandwiches into little cirlces.');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`) VALUES
(20, 'user', '$2y$10$9vFg4tJU8CtvIE9xF90vjuPza0rcttYzrL0ir2gOz/fSf56V6T97a'),
(28, 'user2', '$2y$10$dCTZX5yeJp9jLxLS88.C9.7/ZyW28P36ZP/9ht717uwc9ArGAuxfW');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` varchar(20) NOT NULL,
  `first_name` varchar(45) NOT NULL,
  `last_name` varchar(45) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `first_name`, `last_name`, `salary`) VALUES
('1', 'joe', 'rog', 50000.00),
('123456', 'jane', 'dope', 5000.00),
('344568', 'g', 'g', 50000.00),
('961441', 'rummy', 'rum', 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` varchar(20) NOT NULL,
  `first_name` varchar(45) NOT NULL,
  `last_name` varchar(45) NOT NULL,
  `level` varchar(20) DEFAULT NULL,
  `tuition` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `first_name`, `last_name`, `level`, `tuition`) VALUES
('124455', 'john', 'doe', 'graduate', 125000.00),
('v000000004', 'james', 'garfield', NULL, NULL),
('v00000001', 'steve', 'harvey', NULL, NULL),
('v00000002', 'john', 'kennedy', NULL, NULL),
('v00000003', 'george', 'patton', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_access`
--
ALTER TABLE `budget_access`
  ADD PRIMARY KEY (`user_id`,`budget_id`),
  ADD KEY `budget_access_ibfk_2` (`budget_id`);

--
-- Indexes for table `budget_equipment`
--
ALTER TABLE `budget_equipment`
  ADD PRIMARY KEY (`budget_id`,`equipment_id`,`year`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `budget_other_costs`
--
ALTER TABLE `budget_other_costs`
  ADD PRIMARY KEY (`id`,`year`);

--
-- Indexes for table `budget_personnel`
--
ALTER TABLE `budget_personnel`
  ADD PRIMARY KEY (`budget_id`,`personnel_type`,`personnel_id`);

--
-- Indexes for table `budget_personnel_effort`
--
ALTER TABLE `budget_personnel_effort`
  ADD PRIMARY KEY (`budget_id`,`personnel_type`,`personnel_id`,`year`);

--
-- Indexes for table `budget_personnel_growth`
--
ALTER TABLE `budget_personnel_growth`
  ADD PRIMARY KEY (`budget_id`,`personnel_type`,`personnel_id`);

--
-- Indexes for table `domestic_travel_per_diem`
--
ALTER TABLE `domestic_travel_per_diem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_UNIQUE` (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `domestic_travel_per_diem`
--
ALTER TABLE `domestic_travel_per_diem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_access`
--
ALTER TABLE `budget_access`
  ADD CONSTRAINT `budget_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `budget_access_ibfk_2` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `budget_equipment`
--
ALTER TABLE `budget_equipment`
  ADD CONSTRAINT `budget_equipment_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_equipment_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_other_costs`
--
ALTER TABLE `budget_other_costs`
  ADD CONSTRAINT `budget_other_costs_ibfk_1` FOREIGN KEY (`id`) REFERENCES `budget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `budget_personnel`
--
ALTER TABLE `budget_personnel`
  ADD CONSTRAINT `budget_personnel_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_personnel_effort`
--
ALTER TABLE `budget_personnel_effort`
  ADD CONSTRAINT `bpe_budget_fk` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `budget_personnel_growth`
--
ALTER TABLE `budget_personnel_growth`
  ADD CONSTRAINT `bpg_budget_fk` FOREIGN KEY (`budget_id`) REFERENCES `budget` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
