-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 07:40 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `library_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `total_copies` int(11) NOT NULL DEFAULT '1',
  `available_copies` int(11) NOT NULL DEFAULT '1',
  `book_type` enum('physical','ebook') NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `book_copies`
--

CREATE TABLE `book_copies` (
  `copy_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `copy_number` varchar(20) NOT NULL,
  `status` enum('available','borrowed','lost','damaged') NOT NULL DEFAULT 'available',
  `location` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `borrowing_transactions`
--

CREATE TABLE `borrowing_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `copy_id` int(11) DEFAULT NULL,
  `transaction_type` enum('borrow','return','reserve') NOT NULL,
  `borrow_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('pending','active','returned','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ebook_access`
--

CREATE TABLE `ebook_access` (
  `access_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `access_code` varchar(100) DEFAULT NULL,
  `access_start` datetime NOT NULL,
  `access_end` datetime NOT NULL,
  `status` enum('active','expired','revoked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `fine_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` enum('overdue','damage','loss') NOT NULL,
  `status` enum('pending','paid','waived') NOT NULL DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('due_reminder','overdue','reservation','system') NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('Important','General','Events','Maintenance') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_announcements_type` (`type`),
  KEY `idx_announcements_created` (`created_at`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_members`
--

CREATE TABLE `reservation_members` (
  `member_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `student_id` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reservation_members`
--

INSERT INTO `reservation_members` (`member_id`, `reservation_id`, `name`, `student_id`, `created_at`) VALUES
(6, 6, 'asd', 'asd', '2025-05-13 10:41:24'),
(7, 6, 'asda', 'asd', '2025-05-13 10:41:24'),
(8, 6, 'asd', 'asd', '2025-05-13 10:41:25'),
(9, 6, 'asd', 'asd', '2025-05-13 10:41:25'),
(10, 6, 'asd', 'asd', '2025-05-13 10:41:25'),
(11, 7, 'asda', 'asd', '2025-05-13 10:43:40'),
(12, 7, 'asd', '12', '2025-05-13 10:43:40'),
(13, 7, '123', '123', '2025-05-13 10:43:40'),
(14, 7, '123', '123', '2025-05-13 10:43:40'),
(15, 7, '123', '123', '2025-05-13 10:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `is_available` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `room_type`, `capacity`, `description`, `is_available`) VALUES
(1, 'Study Room 101', 'Study Room', 10, 'Small study room suitable for group discussions', 1),
(2, 'Study Room 102', 'Study Room', 10, 'Small study room with whiteboard', 1),
(3, 'Computer Lab 201', 'Computer Lab', 10, 'Computer laboratory with internet access', 1),
(4, 'Library Conference Room', 'Conference Room', 10, 'Large conference room with projector', 1),
(5, 'Reading Lounge', 'Lounge', 10, 'Quiet reading area with comfortable seating', 1),
(6, 'Study Room 101', 'Study Room', 10, 'Small study room suitable for group discussions', 1),
(7, 'Study Room 102', 'Study Room', 10, 'Small study room with whiteboard', 1),
(8, 'Computer Lab 201', 'Computer Lab', 10, 'Computer laboratory with internet access', 1),
(9, 'Library Conference Room', 'Conference Room', 10, 'Large conference room with projector', 1),
(10, 'Reading Lounge', 'Lounge', 10, 'Quiet reading area with comfortable seating', 1);

-- --------------------------------------------------------

--
-- Table structure for table `room_reservations`
--

CREATE TABLE `room_reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `room_name` varchar(50) DEFAULT NULL,
  `purpose` text NOT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_assign` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `room_reservations`
--

INSERT INTO `room_reservations` (`reservation_id`, `user_id`, `room_id`, `room_name`, `purpose`, `reservation_date`, `start_time`, `end_time`, `status`, `admin_assign`, `created_at`) VALUES
(6, 2, NULL, NULL, 'group-study: test', '2025-05-15', '10:00:00', '11:00:00', 'rejected', 1, '2025-05-13 10:41:24'),
(7, 4, NULL, NULL, 'group-study: test', '2025-05-14', '01:00:00', '02:00:00', 'pending', 1, '2025-05-13 10:43:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` enum('student','faculty','admin') NOT NULL,
  `student_employee_id` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `student_employee_id`, `last_name`, `first_name`, `middle_name`, `email`, `password`, `course`, `year_level`, `section`, `profile_image`, `created_at`, `updated_at`) VALUES
(2, 'admin', '23-2316', 'gatdula', 'ronnie', 'p', 'tagumpay.lolito.cuevas@gmail.com', '$2y$10$AqeHRTdquP9AUgm/Ibs8hum1NdhaZ2X7MlV7HEw55SZp/nIJ.edyC', '', 0, '', NULL, '2025-05-12 06:56:10', '2025-05-12 06:56:10'),
(3, 'faculty', '23-2317', 'christine', 'angel', 'cuevas', 'christineangel@gmail.com', '$2y$10$sim0.D2ayj7m1yLvLuhLi.HlY0JPIQlNjIL/8gH9nQk1ZY/7hzCyS', '', 0, '', NULL, '2025-05-12 08:05:51', '2025-05-12 08:05:51'),
(4, 'student', '23-2315', 'Tagumpay', 'Lolito', 'cuevas', 'lolitotagumpay@gmail.com', '$2y$10$JdQlike68gcZjYVtvax1heD7p7GxhT/YZQsgch.6Rr7KxUlBZVzgy', 'Bachelor of Science in Information Technology', 2, 'SBIT-2B', NULL, '2025-05-12 08:23:55', '2025-05-12 08:23:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `idx_books_title` (`title`),
  ADD KEY `idx_books_author` (`author`),
  ADD KEY `idx_books_category` (`category`);

--
-- Indexes for table `book_copies`
--
ALTER TABLE `book_copies`
  ADD PRIMARY KEY (`copy_id`),
  ADD UNIQUE KEY `unique_copy` (`book_id`,`copy_number`);

--
-- Indexes for table `borrowing_transactions`
--
ALTER TABLE `borrowing_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `copy_id` (`copy_id`),
  ADD KEY `idx_transactions_status` (`status`),
  ADD KEY `idx_transactions_dates` (`borrow_date`,`due_date`,`return_date`);

--
-- Indexes for table `ebook_access`
--
ALTER TABLE `ebook_access`
  ADD PRIMARY KEY (`access_id`),
  ADD UNIQUE KEY `access_code` (`access_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`fine_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `reservation_members`
--
ALTER TABLE `reservation_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `idx_room_reservations_date` (`reservation_date`),
  ADD KEY `idx_room_reservations_user` (`user_id`),
  ADD KEY `idx_room_reservations_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_employee_id` (`student_employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `