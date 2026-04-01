-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 10:28 AM
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
-- Database: `php_case_study`
--

-- --------------------------------------------------------

--
-- Table structure for table `movies_details`
--

CREATE TABLE `movies_details` (
  `ID` int(11) NOT NULL,
  `title` varchar(25) NOT NULL,
  `description` varchar(500) NOT NULL,
  `avg_rating` float NOT NULL,
  `release_year` year(4) NOT NULL,
  `director_name` varchar(50) NOT NULL,
  `synopsis` text DEFAULT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies_details`
--

INSERT INTO `movies_details` (`ID`, `title`, `description`, `avg_rating`, `release_year`, `director_name`, `synopsis`, `poster_path`, `created_at`) VALUES
(1, 'Dhurandhar', '', 3.5, '2025', 'Aditya Dhar', 'A gritty spy thriller following Jaskirat Singh (Ranveer Singh), an undercover Indian intelligence agent who infiltrates Karachi\'s criminal syndicates in Lyari as \"Hamza.\" He risks everything to dismantle a terror network targeting India, showcasing the lethal grit and silent sacrifices of unsung heroes operating deep within enemy territory.', 'poster_69cb7b03b44c1.jpg', '2026-03-31 07:42:59'),
(2, 'Harry Potter and the Phil', '', 5, '2001', 'Chris Columbus', 'After his eleventh birthday, an orphaned boy discovers he is a wizard and is invited to attend Hogwarts School of Witchcraft and Wizardry. He makes profound friendships while uncovering the mystery of a hidden stone that grants immortality.', 'poster_69cb806d1bf6a.webp', '2026-03-31 08:06:05'),
(3, 'Chhello Divas (Gujarati)', '', 4, '2015', 'Krishnadev Yagnik', 'This is a funny story about 8 college friends during their last year of school. It shows all the silly things they do, their jokes, and how they feel about finally growing up and leaving their friends behind.', 'poster_69cb8163bed99.webp', '2026-03-31 08:10:11'),
(4, '3 Idiots', '', 4, '2009', 'Rajkumar Hirani', 'Two friends embark on an audacious journey to find their long-lost college roommate, Rancho. Through a series of flashbacks, they remember how his audacious spirit challenged the high-pressure, rigid Indian education system and taught them to chase excellence, not success.', 'poster_69cb818d4c917.jpg', '2026-03-31 08:10:53'),
(5, 'Magadheera', '', 5, '2009', 'S. S. Rajamouli', 'A brave warrior dies while trying to save a princess he loves. He is reborn 400 years later in the modern world. Soon, he starts to remember his past life and must find the princess again to protect her from an old enemy who has also returned. It is an enthralling story of true love that never dies.', 'poster_69cb82136232f.jpg', '2026-03-31 08:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `review_details`
--

CREATE TABLE `review_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `review_text` varchar(500) NOT NULL,
  `rating` int(5) NOT NULL,
  `status` enum('pending','approved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_details`
--

INSERT INTO `review_details` (`id`, `user_id`, `movie_id`, `review_text`, `rating`, `status`, `created_at`) VALUES
(1, 5, 1, 'Dhurandhar is a cold, hard slap to the face of the spy genre. Aditya Dhar doesn\'t hold back. Ranveer Singh as Hamza is terrifyingly good. It’s a candid look at the isolation of undercover life in Lyari. No fluff, just pure, gritty adrenaline...', 4, 'approved', '2026-03-31 07:57:02'),
(2, 6, 1, 'Ranveer Singh is a total beast as Hamza in this spy thriller.', 3, 'approved', '2026-03-31 08:18:28'),
(3, 6, 2, 'Hogwarts looks absolutely beautiful in this 2001 classic. ✨', 5, 'approved', '2026-03-31 08:18:58'),
(4, 5, 5, 'This movie is absolutely gripping! It makes you feel so many big emotions—excitement during the fights, sadness for the lovers, and joy when they finally meet again. The story is very powerful and the music makes everything feel even more intense.', 5, 'approved', '2026-03-31 08:20:37'),
(5, 5, 4, 'This movie is a real emotional roller coaster! You will laugh out loud at the funny moments, but you might also cry at the sad parts', 4, 'approved', '2026-03-31 08:21:03'),
(6, 7, 2, 'This movie is pure magic! It makes you feel like you’re actually a student at Hogwarts. It’s a great start to the series and feels like home every time you watch it.', 5, 'approved', '2026-03-31 08:23:55'),
(7, 7, 3, 'Vicky bhai no swag j jordar chhe! Akhi movie ma ena funny dialogues ane comedy timing ek number chhe.', 4, 'approved', '2026-03-31 08:26:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_details`
--

CREATE TABLE `user_details` (
  `ID` int(11) NOT NULL,
  `name` varchar(25) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='this is for storing user data such as its Uname and pswd ';

--
-- Dumping data for table `user_details`
--

INSERT INTO `user_details` (`ID`, `name`, `password`, `role`) VALUES
(1, 'TANJIL', '$2y$10$.ZzVe6KxAOgkeSJ7NuiIIu4zl1uiBNKdrgx/B.5H/dc', 'admin'),
(4, 'admin', '$2y$10$eVgpV8BmurzhOroBREazP.znOlXZRSmQgXFHorkIfzsx0W55obay.', 'admin'),
(5, 'TANJJIL', '$2y$10$573Y4y5Kd/HZgsYQqVWDOujYAU8NZBbXxBiXT9TVu39K4Cj8QA1qm', 'user'),
(6, 'user', '$2y$10$qt0EsPwtCwI64ZwgjOYLeuLWhjVLlbMNUiS0JcdA7u65Lw8YtX77y', 'user'),
(7, 'ApexVibe', '$2y$10$diQso.zDfIo9pwE9e0SfbuQNbVrMJ5C7Hr1p.xMLRZA5uLecrgPvW', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `movies_details`
--
ALTER TABLE `movies_details`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `review_details`
--
ALTER TABLE `review_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `movies_details`
--
ALTER TABLE `movies_details`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `review_details`
--
ALTER TABLE `review_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_details`
--
ALTER TABLE `user_details`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `review_details`
--
ALTER TABLE `review_details`
  ADD CONSTRAINT `review_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_details` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_details_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies_details` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
