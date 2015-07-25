-- phpMyAdmin SQL Dump
-- version 4.1.9
-- http://www.phpmyadmin.net
--
-- Host: 123.59.54.246
-- Generation Time: 2015-07-24 02:19:42
-- 服务器版本： 5.5.44-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wms`
--

-- --------------------------------------------------------

--
-- 表的结构 `stock_bill_in_container`
--

CREATE TABLE IF NOT EXISTS `stock_bill_in_container` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `refer_code` varchar(45) NOT NULL COMMENT '关联入库单号',
  `pro_code` varchar(45) NOT NULL,
  `batch` varchar(45) NOT NULL,
  `wh_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `qty` decimal(18,2) NOT NULL COMMENT '入库量',
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  `created_user` int(10) unsigned NOT NULL,
  `updated_user` int(10) unsigned NOT NULL,
  `is_deleted` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
