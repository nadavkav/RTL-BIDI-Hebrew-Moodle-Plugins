-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 25, 2012 at 12:10 PM
-- Server version: 5.1.56
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ebook_moodle`
--

-- --------------------------------------------------------

--
-- Table structure for table `mdl_course_format_ebook`
--

CREATE TABLE IF NOT EXISTS `mdl_course_format_ebook` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `courseid` bigint(10) unsigned NOT NULL,
  `chapter` int(10) unsigned NOT NULL,
  `page` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `section` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_courformeboo_csi_ix` (`courseid`,`chapter`,`page`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Extra configuration for ebook format courses' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
