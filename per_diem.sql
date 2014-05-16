-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 09, 2014 at 03:02 PM
-- Server version: 5.5.24-log
-- PHP Version: 5.4.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `utilities`
--

-- --------------------------------------------------------

--
-- Table structure for table `per_diem`
--

CREATE TABLE IF NOT EXISTS `per_diem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `weeknumber` int(11) NOT NULL,
  `total_consumption` int(11) NOT NULL,
  `total_generation` int(11) NOT NULL,
  `standby_usage` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`,`year`,`month`,`weeknumber`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

--
-- Dumping data for table `per_diem`
--

INSERT INTO `per_diem` (`id`, `date`, `year`, `month`, `weeknumber`, `total_consumption`, `total_generation`, `standby_usage`) VALUES
(1, '2014-03-22', 2014, 3, 5959, 10346, 1872, 218),
(2, '2014-03-23', 2014, 3, 5959, 12513, 2979, 92),
(3, '2014-03-24', 2014, 3, 5960, 6855, 2064, 92),
(4, '2014-03-25', 2014, 3, 5960, 8954, 2855, 141),
(5, '2014-03-26', 2014, 3, 5960, 10424, 2063, 80),
(6, '2014-03-27', 2014, 3, 5960, 5933, 1859, 75),
(7, '2014-03-28', 2014, 3, 5960, 5778, 3404, 80),
(8, '2014-03-29', 2014, 3, 5960, 8645, 3035, 81),
(9, '2014-03-30', 2014, 3, 5960, 9420, 1788, 143),
(10, '2014-03-31', 2014, 3, 5961, 7305, 2683, 83),
(11, '2014-04-01', 2014, 4, 5961, 6117, 3371, 151),
(12, '2014-04-02', 2014, 4, 5961, 9803, 3260, 153),
(13, '2014-04-03', 2014, 4, 5961, 6141, 1842, 96),
(14, '2014-04-04', 2014, 4, 5961, 7684, 820, 97),
(15, '2014-04-05', 2014, 4, 5961, 12120, 3719, 97),
(16, '2014-04-06', 2014, 4, 5961, 18220, 1659, 101),
(17, '2014-04-07', 2014, 4, 5962, 7434, 2962, 105),
(18, '2014-04-08', 2014, 4, 5962, 7120, 2159, 154),
(19, '2014-04-09', 2014, 4, 5962, 10029, 3622, 92),
(20, '2014-04-10', 2014, 4, 5962, 6828, 3943, 107),
(21, '2014-04-11', 2014, 4, 5962, 6611, 2378, 93),
(22, '2014-04-12', 2014, 4, 5962, 10862, 2648, 92),
(23, '2014-04-13', 2014, 4, 5962, 11294, 3083, 85),
(24, '2014-04-14', 2014, 4, 5963, 8849, 3647, 97),
(25, '2014-04-15', 2014, 4, 5963, 7262, 3162, 146),
(26, '2014-04-16', 2014, 4, 5963, 7905, 4241, 86),
(27, '2014-04-17', 2014, 4, 5963, 5127, 3564, 90),
(28, '2014-04-18', 2014, 4, 5963, 2141, 1247, 85);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
