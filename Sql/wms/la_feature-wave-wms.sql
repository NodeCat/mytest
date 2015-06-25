-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- 主机: 123.59.54.246:3306
-- 生成日期: 2015-06-25 06:10:18
-- 服务器版本: 5.5.43-0ubuntu0.14.04.1
-- PHP 版本: 5.5.9-1ubuntu4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `wms`
--


-- --------------------------------------------------------

--
-- 表的结构 `stock_wave_distribution`
--

CREATE TABLE IF NOT EXISTS `stock_wave_distribution` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dist_code` char(32) NOT NULL DEFAULT '' COMMENT '配送单号',
  `remarks` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `total_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '应收总金额',
  `deal_price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实收总金额',
  `company_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1大厨网，2 大果网',
  `line_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '线路ID',
  `deliver_date` varchar(50) NOT NULL DEFAULT '' COMMENT '配送日期',
  `deliver_time` tinyint(4) NOT NULL DEFAULT '0' COMMENT '配送时段 1上午 2下午',
  `order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总单数',
  `line_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总行数',
  `sku_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总件数',
  `total_distance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '预估总里程数',
  `begin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '配送开始时间',
  `end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '配送结束时间',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新人id',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '0未删除 >0已删除',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态1未发运2已发运',
  `is_printed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否打印：1已打印，0未打印',
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '仓库ID',
  PRIMARY KEY (`id`),
  KEY `dist_number` (`dist_code`),
  KEY `deliver_date` (`deliver_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='配送单表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `stock_wave_distribution_detail`
--

CREATE TABLE IF NOT EXISTS `stock_wave_distribution_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bill_out_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联配送单id',
  `created_user` int(4) NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改人ID',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改人ID',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已删除 0未删除 >0已删除',
  PRIMARY KEY (`id`),
  KEY `order_id` (`bill_out_id`,`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='配送单详情表' AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `stock_bill_out`;
CREATE TABLE `stock_bill_out` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `notes` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '业务日期',
  `process_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `line_id` int(10) unsigned NOT NULL DEFAULT '0',
  `wave_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '波次id',
  `refused_type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '拒绝标识',
  `delivery_date` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '发货日期',
  `delivery_time` varchar(45) DEFAULT '' COMMENT '发货时间',
  `delivery_ampm` varchar(32) DEFAULT '' COMMENT '发货时段 am 上午 pm 下午',
  `customer_realname` varchar(45) DEFAULT '' COMMENT '客户名称',
  `delivery_address` varchar(200) DEFAULT '' COMMENT '发货地址',
  `status` varchar(45) NOT NULL DEFAULT '' COMMENT '1待生产2已出库3波次中4待拣货5待复核6己复核',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '  ',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='出库单';


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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='波次表';

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='波次_出库单关联表';

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
