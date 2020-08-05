-- phpMyAdmin SQL Dump
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2020 at 09:59 AM

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toplist`
--

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `sku` int(11) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `paid` double NOT NULL,
  `currency` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `date_paid` bigint(20) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `premium_packages`
--

CREATE TABLE `premium_packages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL,
  `price` double NOT NULL,
  `level` int(11) NOT NULL DEFAULT '1',
  `features` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `premium_packages`
--

INSERT INTO `premium_packages` (`id`, `title`, `duration`, `price`, `level`, `features`) VALUES
(1, 'Bronze', 2592000, 9.99, 1, '[\r\n\"<b class=\\\"text-primary\\\">100</b> <span>Instant Votes</span>\",\r\n\"<b class=\\\"text-primary\\\">Highlighted</b> <span>Background</span>\",\r\n\"<b class=\\\"text-primary\\\">Double</b> <span>Votes</span>\"\r\n]'),
(2, 'Silver', 5184000, 19.99, 2, '[\r\n\"<b class=\\\"text-primary\\\">200</b> <span>Instant Votes</span>\",\r\n\"<b class=\\\"text-primary\\\">Highlighted</b> <span>Background</span>\",\r\n\"<b class=\\\"text-primary\\\">Double</b> <span>Votes</span>\"\r\n]'),
(3, 'Gold', 7776000, 29.99, 3, '[\r\n\"<b class=\\\"text-primary\\\">300</b> <span>Instant Votes</span>\",\r\n\"<b class=\\\"text-primary\\\">Highlighted</b> <span>Background</span>\",\r\n\"<b class=\\\"text-primary\\\">Double</b> <span>Votes</span>\"\r\n]'),
(4, 'Platinum', 15552000, 49.99, 5, '[\r\n\"<b class=\\\"text-primary\\\">500</b> <span>Instant Votes</span>\",\r\n\"<b class=\\\"text-primary\\\">Highlighted</b> <span>Background</span>\",\r\n\"<b class=\\\"text-primary\\\">Double</b> <span>Votes</span>\"\r\n]');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `body` mediumtext,
  `report_ip` varchar(255) DEFAULT NULL,
  `date_reported` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `revisions`
--

CREATE TABLE `revisions` (
  `id` int(11) NOT NULL,
  `revision` varchar(255) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE `servers` (
  `id` int(11) NOT NULL,
  `owner` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `revision` varchar(255) DEFAULT NULL,
  `server_ip` varchar(255) DEFAULT NULL,
  `server_port` int(11) NOT NULL DEFAULT '43594',
  `premium_level` int(11) NOT NULL DEFAULT '0',
  `premium_expires` bigint(20) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT '0',
  `ping` int(11) NOT NULL DEFAULT '-1',
  `last_ping` int(11) NOT NULL DEFAULT '-1',
  `votes` int(11) NOT NULL DEFAULT '0',
  `website` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `callback_url` varchar(255) DEFAULT NULL,
  `discord_link` varchar(255) DEFAULT NULL,
  `meta_info` varchar(255) DEFAULT NULL,
  `meta_tags` text,
  `description` mediumtext,
  `date_created` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `started` int(11) NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sponsor_packages`
--

CREATE TABLE `sponsor_packages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `price` double NOT NULL,
  `duration` bigint(20) NOT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'knight',
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `visible` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sponsor_packages`
--

INSERT INTO `sponsor_packages` (`id`, `title`, `price`, `duration`, `icon`, `highlight`, `visible`) VALUES
(1, 'Knight', 100, 2592000, 'chess-knight', 0, 1),
(2, 'Queen', 190, 5184000, 'chess-queen', 1, 1),
(3, 'King', 280, 7776000, 'chess-king', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `discriminator` varchar(255) NOT NULL DEFAULT '-1',
  `username` varchar(255) NOT NULL,
  `roles` text,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `join_date` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `server_id` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `incentive` varchar(255) DEFAULT NULL,
  `voted_on` bigint(20) NOT NULL DEFAULT '-1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `premium_packages`
--
ALTER TABLE `premium_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `revisions`
--
ALTER TABLE `revisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsor_packages`
--
ALTER TABLE `sponsor_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `premium_packages`
--
ALTER TABLE `premium_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revisions`
--
ALTER TABLE `revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsor_packages`
--
ALTER TABLE `sponsor_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
