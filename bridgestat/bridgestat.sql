-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 02, 2012 at 12:35 PM
-- Server version: 5.1.58
-- PHP Version: 5.3.6-13ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `logstat`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

DROP TABLE IF EXISTS `access`;
CREATE TABLE IF NOT EXISTS `access` (
  `eppn` varchar(100) DEFAULT NULL,
  `eid` int(10) DEFAULT NULL,
  `role` enum('admin','viewer') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `entities`
--

DROP TABLE IF EXISTS `entities`;
CREATE TABLE IF NOT EXISTS `entities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entityid` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `entity_type` enum('sp','idp') COLLATE utf8_danish_ci NOT NULL,
  `da` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  `en` varchar(255) COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entityid` (`entityid`,`entity_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=403 ;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `server` varchar(8) COLLATE utf8_danish_ci NOT NULL,
  `hash_id` varchar(10) COLLATE utf8_danish_ci NOT NULL,
  `type` enum('saml20-sp-SSO','saml20-idp-SSO-first','saml20-idp-SSO','saml20-idp-SLO','saml20-sp-SLO') COLLATE utf8_danish_ci NOT NULL,
  `sp_id` int(10) unsigned NOT NULL,
  `idp_id` int(10) unsigned NOT NULL,
  `userhash` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `consent` enum('found','remember','remembernot') COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `sp` (`sp_id`),
  KEY `idp` (`idp_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=8415884 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_ibfk_1` FOREIGN KEY (`sp_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `log_ibfk_2` FOREIGN KEY (`idp_id`) REFERENCES `entities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
