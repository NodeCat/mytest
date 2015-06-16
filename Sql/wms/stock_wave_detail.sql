/*
 Navicat Premium Data Transfer

 Source Server         : test.wms.dachuwang
 Source Server Type    : MySQL
 Source Server Version : 50543
 Source Host           : 123.59.54.246
 Source Database       : wms

 Target Server Type    : MySQL
 Target Server Version : 50543
 File Encoding         : utf-8

 Date: 06/16/2015 11:50:35 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `stock_wave_detail`
-- ----------------------------
DROP TABLE IF EXISTS `stock_wave_detail`;
CREATE TABLE `stock_wave_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '波次关联id',
  `bill_out_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联出库单id',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(32) NOT NULL DEFAULT '''''',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COMMENT='波次_出库单关联表';

SET FOREIGN_KEY_CHECKS = 1;
