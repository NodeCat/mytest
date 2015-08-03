-- phpMyAdmin SQL Dump
-- version 4.1.9
-- http://www.phpmyadmin.net
--
-- Host: 123.59.54.246
-- Generation Time: 2015-08-03 05:42:10
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
-- 表的结构 `tms_delivery_fee`
--

CREATE TABLE IF NOT EXISTS `tms_delivery_fee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_platform` int(11) NOT NULL DEFAULT '0' COMMENT '运力平台',
  `car_type` int(11) NOT NULL DEFAULT '0' COMMENT '车型',
  `min_mile` int(11) NOT NULL DEFAULT '0' COMMENT '最小公里',
  `max_mile` int(11) NOT NULL DEFAULT '0' COMMENT '最大公里',
  `price` int(11) NOT NULL DEFAULT '0' COMMENT '运费价格',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='运费表' ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
