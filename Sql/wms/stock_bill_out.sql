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

 Date: 06/16/2015 11:51:03 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `stock_bill_out`
-- ----------------------------
DROP TABLE IF EXISTS `stock_bill_out`;
CREATE TABLE `stock_bill_out` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `notes` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '业务日期',
  `status` varchar(45) NOT NULL DEFAULT '' COMMENT '1待生产2已出库3波次中4待拣货5待复核6己复核',
  `gennerate_method` varchar(45) NOT NULL DEFAULT '' COMMENT '产生方式\ncustom\nsystem',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `packing_code` varchar(45) NOT NULL DEFAULT '',
  `line_name` varchar(45) NOT NULL DEFAULT '',
  `process_type` int(11) NOT NULL DEFAULT '0',
  `refused_type` int(11) NOT NULL DEFAULT '0',
  `total_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `wave_id` varchar(45) NOT NULL DEFAULT '',
  `shop_name` varchar(45) NOT NULL DEFAULT '',
  `customer_name` varchar(45) NOT NULL DEFAULT '',
  `customer_tel` varchar(45) NOT NULL DEFAULT '',
  `bd_name` varchar(45) NOT NULL DEFAULT '',
  `bd_tel` varchar(45) NOT NULL DEFAULT '',
  `customer_addr` varchar(45) NOT NULL DEFAULT '',
  `order_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `picking_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `stock_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `total_qty` int(11) NOT NULL DEFAULT '0',
  `op_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8 COMMENT='出库单';

SET FOREIGN_KEY_CHECKS = 1;
