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

 Date: 06/16/2015 11:50:21 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `stock_wave`
-- ----------------------------
DROP TABLE IF EXISTS `stock_wave`;
CREATE TABLE `stock_wave` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创库id',
  `status` varchar(45) NOT NULL DEFAULT '1',
  `wave_type` varchar(45) NOT NULL DEFAULT '1' COMMENT '波次类型，1自动，2手动',
  `order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '波次中包含的订单数目',
  `line_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '波次中包含的订单条目数，即在详单中共有多少行',
  `total_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'sum sku*count',
  `company_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1大厨2大果',
  `created_user` int(11) NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` int(11) NOT NULL DEFAULT '0',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `type` int(11) unsigned NOT NULL DEFAULT '200' COMMENT '波次状态:200待运行;201运行中;900已释放',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='波次表';

SET FOREIGN_KEY_CHECKS = 1;
