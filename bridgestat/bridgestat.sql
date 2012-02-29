-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 29, 2012 at 03:26 PM
-- Server version: 5.1.58
-- PHP Version: 5.3.6-13ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `logstat2`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

DROP TABLE IF EXISTS `access`;
CREATE TABLE IF NOT EXISTS `access` (
  `eppn` varchar(100) DEFAULT NULL,
  `eid` varchar(100) DEFAULT NULL,
  `role` enum('admin','viewer') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `entitytoname`
--

DROP TABLE IF EXISTS `entitytoname`;
CREATE TABLE IF NOT EXISTS `entitytoname` (
  `entityid` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `da` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `en` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  PRIMARY KEY (`entityid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;

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
  `sp` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `idp` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `userhash` varchar(255) COLLATE utf8_danish_ci NOT NULL,
  `consent` enum('found','remember','remembernot') COLLATE utf8_danish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci AUTO_INCREMENT=1693823 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
