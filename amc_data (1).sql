-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 05:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `amc_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditlog`
--

CREATE TABLE `auditlog` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Action` varchar(255) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `EquipmentID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `SerialNumber` varchar(100) NOT NULL,
  `Status` enum('Available','In Use','Maintenance','Decommissioned') NOT NULL,
  `PurchaseDate` date DEFAULT NULL,
  `LastServicedDate` date DEFAULT NULL,
  `CreatedBy` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`EquipmentID`, `Name`, `Description`, `SerialNumber`, `Status`, `PurchaseDate`, `LastServicedDate`, `CreatedBy`) VALUES
(250, 'microscope', '----na-----', '001', 'Available', '2025-02-05', '2025-04-25', '2301409E@student.tp.edu.sg'),
(251, 'flask', 'stores liquid', '002', 'In Use', '2025-02-14', '2025-02-28', '2301409E@student.tp.edu.sg'),
(253, 'Battery', 'to charge things', '005', 'In Use', '2024-12-31', '2025-04-03', 'Assistant@gmail.com'),
(254, 'syringe', '----- na -----', '006', 'Available', '2024-11-20', '2027-06-16', 'Assistant@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_usage`
--

CREATE TABLE `equipment_usage` (
  `UsageID` int(11) NOT NULL,
  `EquipmentID` int(11) NOT NULL,
  `ProjectID` int(11) NOT NULL,
  `BorrowedBy` int(11) NOT NULL,
  `BorrowedDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReturnedDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(5, 'yhead2021@gmail.com', 'ca1922ba5251b668ba3619966a999a483e13148b781308314cdc740b82e8861c', '2025-01-31 16:19:57', '2025-01-31 14:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `ProjectID` int(11) NOT NULL,
  `ProjectName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Active','Completed','On Hold') NOT NULL,
  `ProjectFunding` decimal(10,2) DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`ProjectID`, `ProjectName`, `Description`, `StartDate`, `EndDate`, `Status`, `ProjectFunding`, `CreatedBy`) VALUES
(1, 'aaaaa', 'aaaa', '2025-02-06', '2025-02-13', 'Active', 96.00, 1),
(3, 'dgfdg', 'dfgsfd', '2025-02-14', '2025-02-11', 'Completed', 1111.00, 1),
(4, 'njnjnj', 'jnjnjnjnj', '2025-01-03', '2025-01-31', 'Active', 87.00, 2),
(5, 'IJIJIJIJ', 'KJIINININ', '2025-02-06', '2025-02-07', 'Active', 67.00, 4),
(6, 'research ', 'research on X', '2025-01-31', '2025-02-27', 'Active', 1600000.00, 3);

-- --------------------------------------------------------

--
-- Table structure for table `researchers`
--

CREATE TABLE `researchers` (
  `ResearcherID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Department` varchar(50) DEFAULT NULL,
  `Specialization` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researchers`
--

INSERT INTO `researchers` (`ResearcherID`, `UserID`, `FullName`, `Email`, `PhoneNumber`, `Department`, `Specialization`) VALUES
(1, 1, 'wwwhuhuhu', 'Nohead2013@gmail.com', '97361111', '22', '33'),
(2, 2, 'gtc', 'yhead2021@gmail.com', '98767898', 'egg', 'swsx'),
(4, 4, 'kokkoik', 'davidzhai2012@gmail.com', '98765456', 'mkm', 'mimi'),
(10, 3, 'wdadada', '2301409E@student.tp.edu.sg', '12345678', 'dege', 'efesfsdf'),
(12, 5, 'egggbgfs', 'TEST@gmail.com', '34675435', 'hgd', 'wgbd'),
(13, 6, 'John', 'Researcher@gmail.com', '49285467', 'science', 'experiments'),
(14, 7, 'Mark', 'Assistant@gmail.com', '67543567', 'research', 'recording information');

-- --------------------------------------------------------

--
-- Table structure for table `researcher_project`
--

CREATE TABLE `researcher_project` (
  `ID` int(11) NOT NULL,
  `ResearcherID` int(11) NOT NULL,
  `ProjectID` int(11) NOT NULL,
  `Role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `researcher_project`
--

INSERT INTO `researcher_project` (`ID`, `ResearcherID`, `ProjectID`, `Role`) VALUES
(28, 1, 4, NULL),
(29, 1, 5, NULL),
(36, 1, 3, NULL),
(37, 2, 3, NULL),
(38, 4, 3, NULL),
(39, 1, 1, NULL),
(40, 4, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`) VALUES
(1, 'Admin'),
(3, 'Research Assistant'),
(2, 'Researcher');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `RoleID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `PasswordHash`, `CreatedAt`, `RoleID`) VALUES
(1, 'Nohead2013@gmail.com', '$2y$10$T9h9EbLojMpDSK4m8j4kcOIOxb6x76xKxJhziwm3WD7n.aPpW81uC', '2025-01-31 13:39:49', 2),
(2, 'yhead2021@gmail.com', '$2y$10$NZmZx37jQw6dCQ2VPr2.iux.8dj4dXNVI1l1sRTkC9q9ErmRKi472', '2025-01-31 14:12:03', 2),
(3, '2301409E@student.tp.edu.sg', '$2y$10$TXrGd6oLniMJXWHUEu88kOMEb933bA0ms7kpzEOQfQBXR7AVMA.ci', '2025-01-31 13:39:49', 1),
(4, 'davidzhai2012@gmail.com', '$2y$10$H7TonXmfbWokzgo5liHJxO6g8nHfEHQasMP9b4hm78BeaC7Xe7j6m', '2025-02-01 08:26:33', 3),
(5, 'TEST@gmail.coim', '$2y$10$JZhaSbMVvV3siWvacfwSr.harh2.pLB9avHfoNij47c8iTcHigQcO', '2025-02-02 15:11:51', 3),
(6, 'Researcher@gmail.com', '$2y$10$ZM8dioIPgr2VI6M1E1kjKeqeN1ORbnuJjGjgh./2HmuB2yZo.ODRe', '2025-02-17 04:05:30', 2),
(7, 'Assistant@gmail.com', '$2y$10$RsVnRVOSQJxy4M5TszxYyONSMIaW337JQusASZ67XeZWd.Uia51LC', '2025-02-17 04:10:09', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`EquipmentID`),
  ADD UNIQUE KEY `SerialNumber` (`SerialNumber`),
  ADD KEY `SerialNumber_2` (`SerialNumber`);

--
-- Indexes for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  ADD PRIMARY KEY (`UsageID`),
  ADD KEY `EquipmentID` (`EquipmentID`),
  ADD KEY `ProjectID` (`ProjectID`),
  ADD KEY `BorrowedBy` (`BorrowedBy`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`ProjectID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `researchers`
--
ALTER TABLE `researchers`
  ADD PRIMARY KEY (`ResearcherID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `researcher_project`
--
ALTER TABLE `researcher_project`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ResearcherID` (`ResearcherID`),
  ADD KEY `ProjectID` (`ProjectID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `fk_role` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditlog`
--
ALTER TABLE `auditlog`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `EquipmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  MODIFY `UsageID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `ProjectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `researchers`
--
ALTER TABLE `researchers`
  MODIFY `ResearcherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `researcher_project`
--
ALTER TABLE `researcher_project`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auditlog`
--
ALTER TABLE `auditlog`
  ADD CONSTRAINT `auditlog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_usage`
--
ALTER TABLE `equipment_usage`
  ADD CONSTRAINT `equipment_usage_ibfk_1` FOREIGN KEY (`EquipmentID`) REFERENCES `equipment` (`EquipmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_usage_ibfk_2` FOREIGN KEY (`ProjectID`) REFERENCES `projects` (`ProjectID`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_usage_ibfk_3` FOREIGN KEY (`BorrowedBy`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `researchers`
--
ALTER TABLE `researchers`
  ADD CONSTRAINT `researchers_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_project`
--
ALTER TABLE `researcher_project`
  ADD CONSTRAINT `researcher_project_ibfk_1` FOREIGN KEY (`ResearcherID`) REFERENCES `researchers` (`ResearcherID`) ON DELETE CASCADE,
  ADD CONSTRAINT `researcher_project_ibfk_2` FOREIGN KEY (`ProjectID`) REFERENCES `projects` (`ProjectID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
