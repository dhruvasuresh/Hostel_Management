-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2024 at 02:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hostel_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_check_room_availability` (IN `p_room_no` VARCHAR(10))   BEGIN
    SELECT capacity, occupancy, 
           CASE 
               WHEN room_type = 'Single' THEN 60000
               WHEN room_type = 'Double' THEN 50000
               WHEN room_type = 'Triple' THEN 40000
           END as fees
    FROM room 
    WHERE room_no = p_room_no COLLATE utf8mb4_general_ci 
    AND occupancy < capacity;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_allotment` (IN `p_student_id` INT, IN `p_room_no` VARCHAR(10) CHARSET utf8mb4 COLLATE utf8mb4_general_ci, IN `p_allotment_date` DATE, IN `p_end_date` DATE, IN `p_room_fees` DECIMAL(10,2))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error occurred while creating allotment';
    END;

    START TRANSACTION;
    
    -- Create allotment
    INSERT INTO allotment (
        student_id, room_no, allotment_date, 
        end_date, status
    ) VALUES (
        p_student_id, 
        p_room_no,
        p_allotment_date, 
        p_end_date, 
        'Active' COLLATE utf8mb4_general_ci
    );

    -- Update room occupancy
    UPDATE room 
    SET occupancy = occupancy + 1,
        status = CASE 
            WHEN occupancy + 1 >= capacity THEN 'Full' COLLATE utf8mb4_general_ci
            ELSE 'Available' COLLATE utf8mb4_general_ci
        END
    WHERE room_no = p_room_no COLLATE utf8mb4_general_ci;

    -- Create fee record
    INSERT INTO fee (
        student_id, amount, due_date, status, created_at
    ) VALUES (
        p_student_id, 
        p_room_fees, 
        p_allotment_date, 
        'Pending' COLLATE utf8mb4_general_ci, 
        NOW()
    );

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_available_rooms` ()   BEGIN
    SELECT r.*, 
           CASE 
               WHEN r.room_type = 'Single' THEN 60000
               WHEN r.room_type = 'Double' THEN 50000
               WHEN r.room_type = 'Triple' THEN 40000
           END as room_fees
    FROM room r
    WHERE r.occupancy < r.capacity
    ORDER BY r.room_no;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_available_students` ()   BEGIN
    SELECT s.* FROM student s
    LEFT JOIN allotment a ON s.student_id = a.student_id AND a.status = 'Active' COLLATE utf8mb4_general_ci
    WHERE a.allotment_id IS NULL
    ORDER BY s.first_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_fees` (IN `p_status` VARCHAR(20))   BEGIN
    SELECT f.*, 
           s.first_name, s.last_name,
           r.room_no, r.room_type,
           CASE 
               WHEN r.room_type = 'Single' THEN '60000'
               WHEN r.room_type = 'Double' THEN '50000'
               WHEN r.room_type = 'Triple' THEN '40000'
           END as room_fees
    FROM fee f
    JOIN student s ON f.student_id = s.student_id
    LEFT JOIN allotment a ON s.student_id = a.student_id 
        AND a.status = 'Active' COLLATE utf8mb4_unicode_ci
    LEFT JOIN room r ON a.room_no = r.room_no
    WHERE (p_status = '' OR f.status COLLATE utf8mb4_unicode_ci = p_status)
    ORDER BY f.created_at DESC;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_room_fee` (`room_type_param` VARCHAR(10)) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    RETURN CASE 
        WHEN room_type_param = 'Single' THEN 60000
        WHEN room_type_param = 'Double' THEN 50000
        WHEN room_type_param = 'Triple' THEN 40000
        ELSE 0
    END;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `get_total_fees_by_status` (`p_status` VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci) RETURNS DECIMAL(10,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE total DECIMAL(10,2);
    
    SELECT COALESCE(SUM(amount), 0)
    INTO total
    FROM fee
    WHERE status COLLATE utf8mb4_unicode_ci = p_status;
    
    RETURN total;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_no` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `first_name`, `last_name`, `email`, `phone_no`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@hostel.com', '1234567890', '2024-11-01 09:21:35');

-- --------------------------------------------------------

--
-- Table structure for table `allotment`
--

CREATE TABLE `allotment` (
  `allotment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `room_no` varchar(10) NOT NULL,
  `allotment_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allotment`
--

INSERT INTO `allotment` (`allotment_id`, `student_id`, `room_no`, `allotment_date`, `end_date`, `status`, `created_at`) VALUES
(8, 2, '101', '2024-11-01', '2025-05-01', 'Active', '2024-11-01 17:54:45'),
(9, 1, '102', '2024-11-02', '2025-05-02', 'Active', '2024-11-02 10:47:43'),
(14, 3, '201', '2024-11-02', '2025-05-02', 'Active', '2024-11-02 17:43:05'),
(15, 4, '201', '2024-11-02', '2025-05-02', 'Active', '2024-11-02 17:47:13'),
(22, 5, '301', '2024-11-03', '2025-05-03', 'Active', '2024-11-03 07:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

CREATE TABLE `complaint` (
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `logged_on` date NOT NULL,
  `resolved_on` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint`
--

INSERT INTO `complaint` (`complaint_id`, `student_id`, `staff_id`, `type`, `description`, `logged_on`, `resolved_on`, `status`, `created_at`) VALUES
(2, 1, NULL, 'Others', 'Room not yet allotted ', '2024-11-02', '2024-11-02', 'Resolved', '2024-11-02 08:06:47'),
(3, 1, 1, 'Maintenance', 'Fan not working in room 101\r\n', '2024-11-02', NULL, 'In Progress', '2024-11-02 11:08:18');

-- --------------------------------------------------------

--
-- Table structure for table `fee`
--

CREATE TABLE `fee` (
  `fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_on` date DEFAULT NULL,
  `status` enum('Pending','Paid','Overdue') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee`
--

INSERT INTO `fee` (`fee_id`, `student_id`, `amount`, `due_date`, `paid_on`, `status`, `created_at`) VALUES
(1, 1, 60000.00, '2024-11-01', NULL, '', '2024-11-01 16:31:41'),
(2, 2, 40000.00, '2024-11-01', '2024-11-01', 'Paid', '2024-11-01 16:32:59'),
(3, 1, 50000.00, '2024-11-01', NULL, '', '2024-11-01 17:35:59'),
(4, 2, 40000.00, '2024-11-01', NULL, '', '2024-11-01 17:39:29'),
(5, 2, 60000.00, '2024-11-01', '2024-11-01', 'Paid', '2024-11-01 17:54:45'),
(6, 1, 60000.00, '2024-11-02', '2024-11-02', 'Paid', '2024-11-02 10:47:43'),
(7, 3, 50000.00, '2024-11-02', NULL, '', '2024-11-02 11:51:30'),
(8, 3, 50000.00, '2024-11-02', NULL, '', '2024-11-02 14:36:37'),
(11, 3, 50000.00, '2024-11-02', '2024-11-02', 'Paid', '2024-11-02 17:43:05'),
(12, 4, 50000.00, '2024-11-02', '2024-11-02', 'Paid', '2024-11-02 17:47:13'),
(13, 5, 40000.00, '2024-11-03', NULL, 'Pending', '2024-11-03 07:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `room_no` varchar(10) NOT NULL,
  `room_type` enum('Single','Double','Triple') NOT NULL,
  `capacity` int(11) NOT NULL,
  `occupancy` int(11) DEFAULT 0,
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `fees` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`room_no`, `room_type`, `capacity`, `occupancy`, `status`, `created_at`, `fees`) VALUES
('101', 'Single', 1, 1, 'Full', '2024-11-01 10:55:24', 60000.00),
('102', 'Single', 1, 1, 'Full', '2024-11-02 10:47:33', 0.00),
('201', 'Double', 2, 2, 'Full', '2024-11-01 11:03:02', 50000.00),
('301', 'Triple', 3, 1, 'Available', '2024-11-01 11:03:17', 40000.00);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `position` varchar(50) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `phone_no` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `middle_name`, `last_name`, `position`, `salary`, `phone_no`, `email`, `address`, `city`, `state`, `postal_code`, `created_at`) VALUES
(1, 'Henry ', 'Lopez', NULL, 'Security', 35000.00, '9385454521', 'henry_L@gmai.com', 'no 5 a layout yeshwanthapur ', 'Bengaluru', 'Karnataka', '560073', '2024-11-01 11:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `phone_no` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `age`, `phone_no`, `address`, `city`, `state`, `postal_code`, `created_at`) VALUES
(1, 'Skanda', 'Gowda', 'E V', '2004-12-30', 19, '9380919061', 'no 3 siddedahalli nagasandra post 560073', 'Bengaluru', 'Karnataka', '560073', '2024-11-01 10:35:03'),
(2, 'dhruva', 'S', NULL, '2024-11-01', 19, '8557741236', 'okay w z fwfw fdfiw', 'Bengaluru', 'Karnataka', '560073', '2024-11-01 15:25:14'),
(3, 'Harshith ', NULL, 'G', '2004-12-28', 19, '8855874521', 'no 7 somenagar , somehalli', 'Mysore', 'Karnataka', '560162', '2024-11-02 11:49:57'),
(4, 'Gotham ', 'h', NULL, '2024-11-06', 1, '8557741236', 'okay w z fwfw fdfiw', 'Bengaluru', 'Karnataka', '560073', '2024-11-02 17:46:11'),
(5, 'Garv', 'H', NULL, '2007-07-18', 17, '8855226699', 'no 8 somewhere in Rajasthan ', 'Jaipur', 'Rajasthan', '520111', '2024-11-03 07:50:19');

--
-- Triggers `student`
--
DELIMITER $$
CREATE TRIGGER `before_student_insert` BEFORE INSERT ON `student` FOR EACH ROW BEGIN
    SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.date_of_birth, CURDATE());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_student_update` BEFORE UPDATE ON `student` FOR EACH ROW BEGIN
    SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.date_of_birth, CURDATE());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `user_id`, `created_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$eYa5lE/RbxEWSVt9z4DAueOGl5f1t3Vlz3spf.GDh1haBspcw5MCC', 'admin', 1, '2024-11-01 09:21:35', 1),
(2, 'skanda', '$2y$10$xP4naVmm41f9Eqo/6yn4dehnAVR.AG/P3zuBXleyvgG8sV.51Y65.', 'student', 1, '2024-11-01 10:35:03', 1),
(3, 'dhruva', '$2y$10$g/aa3SOGFz7H07D/q1xiE.baccuz4JlJQxvb7eRVKYfpx4wQXyazi', 'student', 2, '2024-11-01 15:25:14', 1),
(4, 'harshith ', '$2y$10$yYvI55EWvH5zn/E6h5lafu.6A6Tmwo3pO3Jk3OJi9v7YsiMoby9cG', 'student', 3, '2024-11-02 11:49:57', 1),
(5, 'gotham ', '$2y$10$T/ZUkVXvnR1wiHGX.GzcDOy1ze9pqqPhx7NLV7G2C9XD0M7cwM3pm', 'student', 4, '2024-11-02 17:46:11', 1),
(6, 'garv', '$2y$10$dTXPvIwLMgQ6sfg30QfbL.5ZH2/d0grIrt1GNobm3NXk7C3/VG.zW', 'student', 5, '2024-11-03 07:50:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `visitor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_no` varchar(15) NOT NULL,
  `relation` varchar(50) NOT NULL,
  `visit_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor`
--

INSERT INTO `visitor` (`visitor_id`, `student_id`, `first_name`, `middle_name`, `last_name`, `phone_no`, `relation`, `visit_date`, `created_at`, `status`, `updated_at`) VALUES
(1, 1, 'jdsnjsdn', 'sdfs', 'sfd', '852145236', 'Guardian', '2024-11-09', '2024-11-01 16:40:35', 'Rejected', '2024-11-01 16:53:34'),
(2, 1, 'sddfsd', 'sdsd', 'sdd', '9852244456', 'Guardian', '2024-11-12', '2024-11-01 16:48:02', 'Rejected', '2024-11-01 16:53:32'),
(3, 1, 'jjn', NULL, 'saadsa', '8885552222', 'Other', '2024-11-27', '2024-11-01 16:48:35', 'Approved', '2024-11-01 16:53:30'),
(4, 1, 'John  ', NULL, 'Doe', '9876543210', 'Guardian', '2024-11-15', '2024-11-02 11:25:13', 'Rejected', '2024-11-02 15:42:18');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_room_occupancy`
-- (See below for the actual view)
--
CREATE TABLE `vw_room_occupancy` (
`room_no` varchar(10)
,`room_type` enum('Single','Double','Triple')
,`capacity` int(11)
,`occupancy` int(11)
,`status` enum('Available','Full','Maintenance')
,`occupants` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_details` (
`student_id` int(11)
,`first_name` varchar(50)
,`middle_name` varchar(50)
,`last_name` varchar(50)
,`date_of_birth` date
,`age` int(11)
,`phone_no` varchar(15)
,`address` varchar(255)
,`city` varchar(50)
,`state` varchar(50)
,`postal_code` varchar(10)
,`created_at` timestamp
,`room_no` varchar(10)
,`room_type` enum('Single','Double','Triple')
,`pending_fees` decimal(10,2)
,`active_complaints` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_room_occupancy`
--
DROP TABLE IF EXISTS `vw_room_occupancy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_room_occupancy`  AS SELECT `r`.`room_no` AS `room_no`, `r`.`room_type` AS `room_type`, `r`.`capacity` AS `capacity`, `r`.`occupancy` AS `occupancy`, `r`.`status` AS `status`, group_concat(concat(`s`.`first_name`,' ',`s`.`last_name`) separator ',') AS `occupants` FROM ((`room` `r` left join `allotment` `a` on(`r`.`room_no` = `a`.`room_no` and `a`.`status` = 'Active')) left join `student` `s` on(`a`.`student_id` = `s`.`student_id`)) GROUP BY `r`.`room_no` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_details`
--
DROP TABLE IF EXISTS `vw_student_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_details`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`first_name` AS `first_name`, `s`.`middle_name` AS `middle_name`, `s`.`last_name` AS `last_name`, `s`.`date_of_birth` AS `date_of_birth`, `s`.`age` AS `age`, `s`.`phone_no` AS `phone_no`, `s`.`address` AS `address`, `s`.`city` AS `city`, `s`.`state` AS `state`, `s`.`postal_code` AS `postal_code`, `s`.`created_at` AS `created_at`, `r`.`room_no` AS `room_no`, `r`.`room_type` AS `room_type`, `f`.`amount` AS `pending_fees`, count(`c`.`complaint_id`) AS `active_complaints` FROM ((((`student` `s` left join `allotment` `a` on(`s`.`student_id` = `a`.`student_id` and `a`.`status` = 'Active')) left join `room` `r` on(`a`.`room_no` = `r`.`room_no`)) left join `fee` `f` on(`s`.`student_id` = `f`.`student_id` and `f`.`status` = 'Pending')) left join `complaint` `c` on(`s`.`student_id` = `c`.`student_id` and `c`.`status` <> 'Resolved')) GROUP BY `s`.`student_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `allotment`
--
ALTER TABLE `allotment`
  ADD PRIMARY KEY (`allotment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `room_no` (`room_no`),
  ADD KEY `idx_allotment_status` (`status`);

--
-- Indexes for table `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `idx_complaint_status` (`status`);

--
-- Indexes for table `fee`
--
ALTER TABLE `fee`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_fee_status` (`status`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`room_no`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_staff_name` (`first_name`,`last_name`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `idx_student_name` (`first_name`,`last_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `username_unique` (`username`),
  ADD UNIQUE KEY `username_idx` (`username`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `username_index` (`username`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`visitor_id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `allotment`
--
ALTER TABLE `allotment`
  MODIFY `allotment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `complaint`
--
ALTER TABLE `complaint`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fee`
--
ALTER TABLE `fee`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `visitor`
--
ALTER TABLE `visitor`
  MODIFY `visitor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allotment`
--
ALTER TABLE `allotment`
  ADD CONSTRAINT `allotment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allotment_ibfk_2` FOREIGN KEY (`room_no`) REFERENCES `room` (`room_no`) ON DELETE CASCADE;

--
-- Constraints for table `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `fee`
--
ALTER TABLE `fee`
  ADD CONSTRAINT `fee_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `visitor`
--
ALTER TABLE `visitor`
  ADD CONSTRAINT `visitor_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
