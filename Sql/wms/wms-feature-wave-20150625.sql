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

 Date: 06/25/2015 11:18:09 AM
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
  `status` int(11) unsigned NOT NULL DEFAULT '200' COMMENT '波次状态:200待运行;201运行中;900已释放',
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='波次表';

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='波次_出库单关联表';

-- ----------------------------
--  Table structure for `stock_wave_picking`
-- ----------------------------
DROP TABLE IF EXISTS `stock_wave_picking`;
CREATE TABLE `stock_wave_picking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL DEFAULT '',
  `wave_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(32) NOT NULL DEFAULT '',
  `order_sum` int(10) unsigned NOT NULL DEFAULT '0',
  `pro_type_sum` int(10) unsigned NOT NULL DEFAULT '0',
  `pro_qty_sum` int(10) unsigned NOT NULL DEFAULT '0',
  `line_id` int(10) unsigned NOT NULL DEFAULT '0',
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bill_out_ids` varchar(32) NOT NULL DEFAULT '''''',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '状态\ndraft 未开始\npicking 执行中\ndone 已完成',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_print` char(3) NOT NULL DEFAULT 'OFF' COMMENT 'ON 已打印 OFF 未打印',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `stock_wave_picking_detail`
-- ----------------------------
DROP TABLE IF EXISTS `stock_wave_picking_detail`;
CREATE TABLE `stock_wave_picking_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `pro_qty` int(11) unsigned DEFAULT '0' COMMENT '库存量',
  `src_location_id` int(11) NOT NULL DEFAULT '0',
  `dest_location_id` int(11) NOT NULL DEFAULT '0',
  `batch` varchar(32) NOT NULL DEFAULT '' COMMENT '批次号',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '状态',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
