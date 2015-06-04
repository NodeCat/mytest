CREATE DATABASE  IF NOT EXISTS `wms` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `wms`;
-- MySQL dump 10.13  Distrib 5.6.22, for osx10.8 (x86_64)
--
-- Host: 123.59.54.246    Database: wms
-- ------------------------------------------------------
-- Server version	5.5.43-0ubuntu0.14.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auth_authority`
--

DROP TABLE IF EXISTS `auth_authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_authority` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '名称',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '0菜单\n1应用 app\n2分组 group\n3模块 module\n3操作 action',
  `app` varchar(45) NOT NULL DEFAULT '' COMMENT '项目',
  `group` varchar(45) NOT NULL DEFAULT '' COMMENT '分组',
  `module` varchar(45) NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(45) NOT NULL DEFAULT '' COMMENT '方法',
  `url` varchar(200) NOT NULL DEFAULT '',
  `condition` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(45) NOT NULL DEFAULT '',
  `show` smallint(6) unsigned NOT NULL DEFAULT '1',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `mpid` int(11) unsigned NOT NULL DEFAULT '0',
  `level` smallint(6) unsigned NOT NULL DEFAULT '0',
  `queue` smallint(6) unsigned NOT NULL DEFAULT '0',
  `target` varchar(45) NOT NULL DEFAULT '',
  `location` varchar(45) NOT NULL DEFAULT '',
  `status` varchar(45) NOT NULL DEFAULT '1' COMMENT '状态',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=331 DEFAULT CHARSET=utf8 COMMENT='节点信息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_role`
--

DROP TABLE IF EXISTS `auth_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(200) NOT NULL COMMENT '用户组所属模块',
  `numb` varchar(45) NOT NULL DEFAULT '' COMMENT '编号',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '名称',
  `rules` text NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0' COMMENT '组类型',
  `description` varchar(80) NOT NULL DEFAULT '' COMMENT '描述信息',
  `updated_time` datetime DEFAULT NULL COMMENT '更新时间',
  `updated_user` int(11) unsigned DEFAULT NULL COMMENT '更新人',
  `status` varchar(20) DEFAULT '1' COMMENT '用户组状态：为1正常，为0禁用',
  `is_deleted` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='角色';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_role_authority`
--

DROP TABLE IF EXISTS `auth_role_authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_role_authority` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `auth_id` int(11) unsigned NOT NULL COMMENT '权限ID',
  `updated_time` datetime DEFAULT NULL COMMENT '更新时间',
  `updated_user` int(11) unsigned DEFAULT NULL COMMENT '更新人',
  `status` varchar(20) DEFAULT '1',
  `is_deleted` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_authority` (`role_id`,`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色权限';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_user_role`
--

DROP TABLE IF EXISTS `auth_user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_user_id_role_id` (`user_id`,`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COMMENT='用户角色';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_user_rule`
--

DROP TABLE IF EXISTS `auth_user_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rule_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(45) NOT NULL DEFAULT '',
  `status` varchar(45) NOT NULL DEFAULT '1',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL,
  `name` varchar(45) NOT NULL COMMENT '名称',
  `type` varchar(45) NOT NULL COMMENT '类型',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `level` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '层级',
  `queue` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '顺序',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8 COMMENT='一个通用的表，用于支持“分类”类型的数据模型';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `remark` varchar(1000) NOT NULL DEFAULT '',
  `mobile` varchar(45) NOT NULL DEFAULT '',
  `adress` varchar(45) NOT NULL DEFAULT '',
  `status` varchar(45) NOT NULL DEFAULT '0',
  `is_deleted` smallint(6) NOT NULL DEFAULT '0',
  `created_time` varchar(45) NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` varchar(45) NOT NULL DEFAULT '0',
  `updated_user` int(11) NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '名称',
  `value` varchar(45) NOT NULL DEFAULT '' COMMENT '值',
  `module` varchar(45) NOT NULL DEFAULT '' COMMENT '模块',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
  `status` varchar(45) NOT NULL DEFAULT '' COMMENT '状态',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='保存整个应用的一些可能用到的全局配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dictionary`
--

DROP TABLE IF EXISTS `dictionary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dictionary` (
  `key` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '键',
  `value` varchar(45) DEFAULT NULL COMMENT '值',
  `type` varchar(45) DEFAULT NULL COMMENT '类型',
  `status` varchar(45) DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process`
--

DROP TABLE IF EXISTS `erp_process`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0',
  `plan_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `real_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `p_pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '父sku编号',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '状态',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process_in`
--

DROP TABLE IF EXISTS `erp_process_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process_in` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL DEFAULT '',
  `refer_code` varchar(32) NOT NULL DEFAULT '',
  `process_type` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '状态',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process_in_detail`
--

DROP TABLE IF EXISTS `erp_process_in_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process_in_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `pro_code` varchar(45) NOT NULL DEFAULT '',
  `batch` varchar(32) NOT NULL DEFAULT '',
  `plan_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `real_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(32) NOT NULL COMMENT '状态',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process_out`
--

DROP TABLE IF EXISTS `erp_process_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process_out` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL DEFAULT '',
  `refer_code` varchar(32) NOT NULL DEFAULT '',
  `process_type` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '状态',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process_out_detail`
--

DROP TABLE IF EXISTS `erp_process_out_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process_out_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `pro_code` varchar(45) NOT NULL DEFAULT '',
  `batch` varchar(32) NOT NULL DEFAULT '',
  `plan_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `real_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(32) NOT NULL COMMENT '状态',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_process_sku_relation`
--

DROP TABLE IF EXISTS `erp_process_sku_relation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_process_sku_relation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `p_pro_code` int(10) unsigned NOT NULL DEFAULT '0',
  `c_pro_code` int(10) unsigned NOT NULL DEFAULT '0',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ratio` int(10) unsigned NOT NULL DEFAULT '0',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_purchase_in_detail`
--

DROP TABLE IF EXISTS `erp_purchase_in_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_purchase_in_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_code` varchar(45) NOT NULL DEFAULT '',
  `stock_in_code` varchar(45) NOT NULL DEFAULT '',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `pro_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `price_unit` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `price_subtotal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `pro_status` varchar(45) NOT NULL DEFAULT '',
  `status` varchar(45) NOT NULL DEFAULT '',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL COMMENT '库位名称',
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '库位标识',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '库位类型',
  `path` varchar(45) NOT NULL COMMENT '完整路径',
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '库位状态',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `notes` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COMMENT='库位表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location_detail`
--

DROP TABLE IF EXISTS `location_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联库位id',
  `picking_line` varchar(45) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT '拣货线路',
  `putaway_line` varchar(45) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT '上架线路',
  `type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '库位类型',
  `is_mixed_pro` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '混放货品',
  `is_mixed_batch` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '混放批次',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COMMENT='部分类型库位的扩展信息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `location_type`
--

DROP TABLE IF EXISTS `location_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '库位类型标识',
  `name` varchar(45) NOT NULL COMMENT '库位类型名称',
  `length` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '长度',
  `width` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '宽度',
  `height` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '高度',
  `load` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '载重量',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='库位类型';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `icon` varchar(100) DEFAULT NULL,
  `link` varchar(100) DEFAULT NULL COMMENT '链接',
  `pid` int(10) unsigned DEFAULT '0' COMMENT '父级',
  `level` smallint(5) unsigned DEFAULT '0' COMMENT '层级',
  `queue` smallint(5) unsigned DEFAULT '0' COMMENT '顺序',
  `show` smallint(6) DEFAULT '1',
  `target` enum('_self','_blank','_parent','_top') DEFAULT '_self' COMMENT '打开方式',
  `location` enum('top','left','right','bottom') DEFAULT NULL COMMENT '位置',
  `status` varchar(45) DEFAULT '1',
  `is_deleted` tinyint(1) unsigned DEFAULT '0',
  `memo` varchar(100) DEFAULT NULL COMMENT '备注',
  `module` varchar(45) DEFAULT 'wms',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=121 DEFAULT CHARSET=utf8 COMMENT='菜单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_auto`
--

DROP TABLE IF EXISTS `module_auto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_auto` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(45) DEFAULT NULL,
  `module` varchar(45) DEFAULT NULL,
  `col_id` varchar(45) DEFAULT NULL,
  `rule` varchar(45) DEFAULT NULL,
  `cond` varchar(45) DEFAULT NULL,
  `addtion` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_column`
--

DROP TABLE IF EXISTS `module_column`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_column` (
  `id` varchar(100) NOT NULL,
  `module` varchar(45) NOT NULL COMMENT '模块',
  `field` varchar(100) NOT NULL COMMENT '名称',
  `title` varchar(45) NOT NULL COMMENT '标题',
  `type` varchar(255) DEFAULT NULL COMMENT '类型',
  `pk` varchar(45) DEFAULT NULL COMMENT '主键',
  `default` varchar(100) DEFAULT NULL COMMENT '默认',
  `readonly` tinyint(1) DEFAULT NULL COMMENT '只读',
  `empty` tinyint(1) DEFAULT NULL COMMENT '允许为空',
  `insert_able` tinyint(1) DEFAULT NULL COMMENT '可插入',
  `update_able` tinyint(1) DEFAULT NULL COMMENT '可更新',
  `query_able` tinyint(1) NOT NULL DEFAULT '0' COMMENT '可查询',
  `list_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '列表显示',
  `add_show` tinyint(1) NOT NULL DEFAULT '1' COMMENT '添加显示',
  `list_order` smallint(5) unsigned DEFAULT NULL COMMENT '列表顺序',
  `add_order` smallint(5) unsigned DEFAULT NULL COMMENT '添加顺序',
  `control_type` enum('text','area','select','checkbox','date','datetime','time','hidden','digit','refer','getField') DEFAULT NULL COMMENT '控件类型',
  `validate` varchar(255) DEFAULT NULL COMMENT '自动验证',
  `tips` varchar(100) DEFAULT NULL COMMENT '提示信息',
  `status` varchar(45) DEFAULT NULL COMMENT '状态',
  `query_type` varchar(45) DEFAULT NULL COMMENT '匹配方式',
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模块字段';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_refer`
--

DROP TABLE IF EXISTS `module_refer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_refer` (
  `id` varchar(100) NOT NULL,
  `module` varchar(100) DEFAULT NULL COMMENT '参照表名',
  `fk` varchar(45) DEFAULT NULL COMMENT '参照外键',
  `module_refer` varchar(100) DEFAULT NULL COMMENT '被参照表',
  `pk` varchar(45) DEFAULT NULL COMMENT '参照主键',
  `condition` varchar(100) DEFAULT NULL COMMENT '参照条件',
  `fk_id` varchar(100) DEFAULT NULL COMMENT '外键',
  `pk_id` varchar(100) DEFAULT NULL COMMENT '主键',
  `relation_table` varchar(45) DEFAULT NULL,
  `map_name` varchar(45) DEFAULT NULL COMMENT '映射名称',
  `map_type` varchar(45) DEFAULT NULL COMMENT '关联类型',
  `refer_type` enum('INNER','LEFT','RIGHT','FULL') DEFAULT 'INNER' COMMENT '参照类型',
  `map_fields` varchar(1000) DEFAULT NULL COMMENT '关联字段',
  `as_fields` varchar(1000) DEFAULT NULL COMMENT '映射字段',
  `field_show` varchar(45) DEFAULT NULL COMMENT '显示字段',
  `order` varchar(45) DEFAULT NULL COMMENT '关联顺序',
  `limit` varchar(45) DEFAULT NULL COMMENT '关联条目',
  `status` varchar(45) DEFAULT '1' COMMENT '状态',
  `type` varchar(45) DEFAULT 'refer' COMMENT '类型',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='表间关系';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_table`
--

DROP TABLE IF EXISTS `module_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_table` (
  `name` varchar(100) NOT NULL COMMENT '名称',
  `title` varchar(100) DEFAULT NULL,
  `module` varchar(45) DEFAULT NULL COMMENT '模块',
  `group` varchar(45) DEFAULT NULL COMMENT '分组',
  `status` varchar(45) DEFAULT NULL COMMENT '状态',
  `rows` int(10) unsigned DEFAULT NULL COMMENT '记录',
  `data_length` int(10) unsigned DEFAULT NULL COMMENT '数据长度',
  `index_length` int(10) unsigned DEFAULT NULL COMMENT '索引长度',
  `engine` varchar(45) DEFAULT NULL COMMENT '引擎',
  `collation` varchar(45) DEFAULT NULL COMMENT '字符集',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `update_user` int(11) unsigned DEFAULT NULL COMMENT '更新人',
  `create_time` varchar(45) DEFAULT NULL COMMENT '创建时间',
  `build` bit(1) DEFAULT NULL,
  `list` varchar(2000) DEFAULT NULL,
  `query` varchar(2000) DEFAULT NULL,
  `validate` varchar(1000) DEFAULT NULL,
  `auto` varchar(1000) DEFAULT NULL,
  `filter` varchar(1000) DEFAULT NULL,
  `type` enum('system','customer') DEFAULT 'customer' COMMENT '类型',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='模块';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_validate`
--

DROP TABLE IF EXISTS `module_validate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_validate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(45) DEFAULT NULL,
  `module` varchar(45) DEFAULT NULL,
  `col_id` varchar(45) DEFAULT NULL,
  `rule` varchar(45) DEFAULT NULL,
  `error_msg` varchar(45) DEFAULT NULL,
  `cond` varchar(45) DEFAULT NULL,
  `addtion` varchar(45) DEFAULT NULL,
  `validate_time` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `numbs`
--

DROP TABLE IF EXISTS `numbs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `numbs` (
  `name` varchar(45) NOT NULL COMMENT '名称',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库ID',
  `prefix` varchar(45) NOT NULL DEFAULT '' COMMENT '前缀',
  `mid` varchar(45) NOT NULL DEFAULT '' COMMENT '中间',
  `suffix` varchar(45) NOT NULL DEFAULT '' COMMENT '后缀',
  `sn` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '流水号',
  `status` varchar(20) NOT NULL DEFAULT '' COMMENT '状态',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='编号';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partner`
--

DROP TABLE IF EXISTS `partner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '编号',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '名称',
  `contact` varchar(45) NOT NULL DEFAULT '' COMMENT '联系人',
  `email` varchar(45) NOT NULL DEFAULT '',
  `tel` varchar(45) NOT NULL DEFAULT '',
  `mobile` varchar(45) NOT NULL DEFAULT '',
  `street` varchar(45) NOT NULL DEFAULT '',
  `zip` varchar(45) NOT NULL DEFAULT '',
  `score` varchar(45) NOT NULL DEFAULT '',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属系统',
  `status` varchar(45) NOT NULL DEFAULT '',
  `description` varchar(200) NOT NULL DEFAULT '',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='供应商';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `partner_supply`
--

DROP TABLE IF EXISTS `partner_supply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner_supply` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parter_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '供应商',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `status` varchar(45) NOT NULL DEFAULT '',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_barcode`
--

DROP TABLE IF EXISTS `product_barcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_barcode` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pro_code` varchar(45) NOT NULL COMMENT '内部货号',
  `barcode` varchar(64) NOT NULL COMMENT '条码',
  `pro_uom` varchar(45) NOT NULL COMMENT '计量单位',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='条码管理表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `location_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '库位id',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `batch` varchar(32) NOT NULL DEFAULT '' COMMENT '批次号',
  `status` varchar(32) NOT NULL COMMENT '状态',
  `stock_qty` int(11) unsigned DEFAULT '0' COMMENT '库存量',
  `assign_qty` int(11) unsigned DEFAULT '0' COMMENT '分配量',
  `prepare_qty` int(11) unsigned DEFAULT '0' COMMENT '待上架量',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `index3` (`location_id`),
  KEY `index4` (`pro_code`),
  KEY `index5` (`batch`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8 COMMENT='库存表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_adjustment`
--

DROP TABLE IF EXISTS `stock_adjustment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `code` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL COMMENT '库内状态',
  `refer_code` varchar(45) DEFAULT '' COMMENT '关联单据单号',
  `status` varchar(32) DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_adjustment_detail`
--

DROP TABLE IF EXISTS `stock_adjustment_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustment_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_code` varchar(32) NOT NULL DEFAULT '',
  `pro_code` varchar(32) NOT NULL DEFAULT '',
  `origin_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `adjusted_qty` int(10) NOT NULL DEFAULT '0',
  `origin_status` varchar(32) NOT NULL DEFAULT '',
  `adjust_status` varchar(32) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_batch`
--

DROP TABLE IF EXISTS `stock_batch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_batch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '批次编号',
  `refer_code` varchar(45) DEFAULT NULL,
  `product_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期',
  `expire_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '过期日期',
  `life_time` int(11) NOT NULL DEFAULT '0' COMMENT '保质期(天)',
  `alert_time` int(11) NOT NULL DEFAULT '0' COMMENT '预警日期',
  `status` varchar(45) NOT NULL DEFAULT '' COMMENT '状态',
  `udpated_user` int(11) NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) DEFAULT '0',
  `create_time` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_detail`
--

DROP TABLE IF EXISTS `stock_bill_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '单据ID',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '产品规格',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '计量单位',
  `pro_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `status` varchar(45) NOT NULL DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='业务单据详情';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_in`
--

DROP TABLE IF EXISTS `stock_bill_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_in` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '单据分类',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_code` varchar(32) NOT NULL DEFAULT '' COMMENT '批次',
  `partner_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '供货商',
  `remark` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '业务日期',
  `status` varchar(45) NOT NULL DEFAULT '',
  `gennerate_method` varchar(45) NOT NULL DEFAULT '' COMMENT '产生方式\ncustom\nsystem',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=213 DEFAULT CHARSET=utf8 COMMENT='入库单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_in_detail`
--

DROP TABLE IF EXISTS `stock_bill_in_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_in_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '到货单号',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '产品规格',
  `expected_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '预期数量',
  `prepare_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '待入库量',
  `done_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已上架量',
  `receipt_qty` int(11) unsigned NOT NULL DEFAULT '0',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '计量单位',
  `price_unit` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `qualified_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `unqualified_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_in_type`
--

DROP TABLE IF EXISTS `stock_bill_in_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_in_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_move`
--

DROP TABLE IF EXISTS `stock_bill_move`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_move` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '单据分类',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `batch` varchar(32) NOT NULL DEFAULT '' COMMENT '批次',
  `parter_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '供货商',
  `remark` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '业务日期',
  `status` varchar(45) NOT NULL DEFAULT '',
  `gennerate_method` varchar(45) NOT NULL DEFAULT '' COMMENT '产生方式\ncustom\nsystem',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_out`
--

DROP TABLE IF EXISTS `stock_bill_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_out` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '单据类型\n入库 in\n出库 out\n移库 move',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据号',
  `notes` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '业务日期',
  `status` varchar(45) NOT NULL DEFAULT '',
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
  `wave_code` varchar(45) NOT NULL DEFAULT '',
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
) ENGINE=InnoDB AUTO_INCREMENT=204 DEFAULT CHARSET=utf8 COMMENT='出库单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_out_container`
--

DROP TABLE IF EXISTS `stock_bill_out_container`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_out_container` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `refer_code` varchar(45) NOT NULL,
  `pro_code` varchar(45) NOT NULL,
  `batch` varchar(45) NOT NULL,
  `wh_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `updated_time` datetime NOT NULL,
  `created_user` int(10) unsigned NOT NULL,
  `updated_user` int(10) unsigned NOT NULL,
  `is_deleted` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_out_detail`
--

DROP TABLE IF EXISTS `stock_bill_out_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_out_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `wh_id` int(11) unsigned NOT NULL,
  `pro_code` varchar(45) NOT NULL DEFAULT '',
  `pro_name` varchar(45) NOT NULL DEFAULT '',
  `pro_attrs` varchar(45) NOT NULL DEFAULT '',
  `price` decimal(18,2) NOT NULL DEFAULT '0.00',
  `order_qty` int(11) NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `delivery_qty` int(11) NOT NULL DEFAULT '0',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_bill_out_type`
--

DROP TABLE IF EXISTS `stock_bill_out_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_bill_out_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '	',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_inventory`
--

DROP TABLE IF EXISTS `stock_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `location_id` int(10) unsigned NOT NULL DEFAULT '0',
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '盘点单单号',
  `type` varchar(32) NOT NULL COMMENT '盘点类型',
  `is_diff` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '有无差异',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `status` varchar(32) NOT NULL,
  `op_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COMMENT='盘点表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_inventory_detail`
--

DROP TABLE IF EXISTS `stock_inventory_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_inventory_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_code` varchar(45) NOT NULL DEFAULT '' COMMENT '盘点单单号',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT 'sku编号',
  `location_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '库位id',
  `pro_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '盘点数量',
  `theoretical_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '理论仓库数',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` varchar(32) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COMMENT='盘点明细表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_move`
--

DROP TABLE IF EXISTS `stock_move`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_move` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) NOT NULL DEFAULT '0',
  `location_id` int(11) NOT NULL DEFAULT '0',
  `pro_code` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '',
  `refer_code` varchar(32) DEFAULT '',
  `direction` varchar(32) NOT NULL DEFAULT '',
  `move_qty` int(11) NOT NULL DEFAULT '0',
  `old_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `new_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `batch` varchar(32) NOT NULL DEFAULT '',
  `pid` int(11) DEFAULT '0',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '库位状态',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=366 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_purchase`
--

DROP TABLE IF EXISTS `stock_purchase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_purchase` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '编号',
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '类型',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0',
  `partner_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '供货商',
  `invoice_method` varchar(45) NOT NULL DEFAULT '' COMMENT '付款方式',
  `price_total` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `paid_amount` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
  `cat_total` int(11) unsigned NOT NULL DEFAULT '0',
  `qty_total` int(11) NOT NULL DEFAULT '0',
  `invoice_status` varchar(45) NOT NULL DEFAULT '' COMMENT '付款状态',
  `picking_status` varchar(45) NOT NULL DEFAULT '' COMMENT '入库单状态',
  `expecting_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '入库日期',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `status` varchar(45) NOT NULL DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8 COMMENT='采购单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_purchase_detail`
--

DROP TABLE IF EXISTS `stock_purchase_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_purchase_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '采购单ID',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '产品编号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '产品规格',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '计量单位',
  `pro_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `price_unit` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `price_subtotal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '金额小记',
  `status` varchar(45) NOT NULL DEFAULT '',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8 COMMENT='采购单详情';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_wave`
--

DROP TABLE IF EXISTS `stock_wave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_wave` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) DEFAULT NULL,
  `wh_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '1',
  `wave_type` varchar(45) NOT NULL DEFAULT '1' COMMENT '波次类型，1自动，2手动',
  `order_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '波次中包含的订单数目',
  `line_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '波次中包含的订单条目数，即在详单中共有多少行',
  `total_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'sum sku*count',
  `company_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1大厨2大果',
  `pick_task_created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '0未生成，1已生成',
  `created_user` int(11) DEFAULT '0',
  `created_time` datetime NOT NULL,
  `update_user` int(11) DEFAULT '0',
  `updated_time` datetime NOT NULL,
  `is_deleted` int(11) DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='波次表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tms_delivery`
--

DROP TABLE IF EXISTS `tms_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tms_delivery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dist_id` int(10) unsigned DEFAULT NULL,
  `dist_code` varchar(45) DEFAULT NULL,
  `mobile` varchar(45) DEFAULT NULL,
  `line_name` varchar(45) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `updated_time` datetime DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `order_count` int(11) DEFAULT NULL,
  `sku_count` int(11) DEFAULT NULL,
  `line_count` int(11) DEFAULT NULL,
  `total_price` int(11) DEFAULT NULL,
  `site_src` varchar(45) DEFAULT NULL,
  `city_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(45) NOT NULL COMMENT '用户名',
  `password` varchar(45) NOT NULL COMMENT '密码',
  `email` varchar(45) NOT NULL COMMENT '用户邮箱',
  `nickname` varchar(45) NOT NULL COMMENT '真实姓名',
  `mobile` varchar(15) NOT NULL COMMENT '用户手机',
  `status` varchar(45) NOT NULL DEFAULT '' COMMENT '用户状态',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warehouse`
--

DROP TABLE IF EXISTS `warehouse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '仓库标识',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '仓库名称',
  `area_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '地址id',
  `address` varchar(200) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT '' COMMENT '仓库状态',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='仓库表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'wms'
--
/*!50003 DROP PROCEDURE IF EXISTS `sn` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE  PROCEDURE `sn`(in id varchar(45))
BEGIN
select `sn` from `numbs` where `name` = id limit 1 for update;
UPDATE numbs 
SET 
    `sn` = `sn` + 1
WHERE
    `name` = id;
commit;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-06-03 21:31:01
