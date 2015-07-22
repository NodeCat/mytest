
CREATE TABLE IF NOT EXISTS `erp_transfer` (
  `id` INT(11) UNSIGNED NOT NULL COMMENT '主键',
  `code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '调拨单',
  `wh_id_out` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调出库仓库id',
  `wh_id_in` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调入仓库id',
  `plan_cat_total` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划货品种数',
  `plan_qty_tobal` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '计划货品件数',
  `status` VARCHAR(45) NOT NULL DEFAULT 'draft' COMMENT '退货状态：draft草稿audit待审核tbr待出库refunded 已出库 cancelled 已作废 Rejected已驳回',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = '调拨单';

CREATE TABLE IF NOT EXISTS `erp_transfer_detail` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级id',
  `pro_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '规格',
  `batch_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '批次',
  `pro_uom` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '单位',
  `price_unit` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `plan_transfer_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '计划调拨量',
  `real_out_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '实际出库量',
  `real_in_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '实际入库量',
  `product_data` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) NULL DEFAULT 0,
  `status` VARCHAR(45) NULL DEFAULT '',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = '调拨单详细';

CREATE TABLE IF NOT EXISTS `erp_transfer_out` (
  `id` INT(11) UNSIGNED NOT NULL COMMENT '主键',
  `code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '调拨单',
  `wh_id_out` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调出库仓库id',
  `wh_id_in` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调入仓库id',
  `cat_total` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SKU种数',
  `qty_tobal` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT 'SKU件数',
  `status` VARCHAR(45) NOT NULL DEFAULT 'tbr' COMMENT '退货状态：tbr待生产refunded 已出库 cancelled 已作废',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'erp调拨出库单';

CREATE TABLE IF NOT EXISTS `erp_transfer_out_detail` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级id',
  `pro_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '规格',
  `batch_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '批次',
  `pro_uom` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '单位',
  `price_unit` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `plan_transfer_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '计划调拨量出库量',
  `real_out_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '实际出库量',
  `product_data` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) NULL DEFAULT 0,
  `status` VARCHAR(45) NULL DEFAULT '',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'erp调拨出库单详细';

CREATE TABLE IF NOT EXISTS `erp_transfer_in` (
  `id` INT(11) UNSIGNED NOT NULL COMMENT '主键',
  `code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '入库单号',
  `wh_id_out` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调出库仓库id',
  `wh_id_in` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '调入仓库id',
  `cat_total` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SKU种数',
  `qty_tobal` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT 'SKU件数',
  `status` VARCHAR(45) NOT NULL DEFAULT 'waiting' COMMENT '状态 waiting 待入库  up 已上架 cancelled已作废',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_UNIQUE` (`code` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'erp调拨入库单';

CREATE TABLE IF NOT EXISTS `erp_transfer_in_detail` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级id',
  `pro_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '规格',
  `batch_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '批次',
  `pro_uom` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '单位',
  `price_unit` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `plan_in_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '计划入库库量',
  `real_out_qty` DECIMAL(18,2) NOT NULL DEFAULT 0.00 COMMENT '实际入库量',
  `product_data` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) NULL DEFAULT 0,
  `status` VARCHAR(45) NULL DEFAULT '',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'erp调拨入库单详细';
#调拨
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨', NULL, '', '0', '0', '1000', '1', '_self', NULL, '1', '0', NULL, 'Wms');
#调拨列表
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨单', NULL, 'Transfer/index', '185', '1', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨单', NULL, 'Transfer/index', '186', '2', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨添加', NULL, 'Transfer/add', '186', '2', '0', '0', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨详细', NULL, 'Transfer/view', '186', '2', '0', '0', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨编辑', NULL, 'Transfer/edit', '186', '2', '0', '0', '_self', NULL, '1', '0', NULL, 'Wms');
#调拨出库单
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨出库单', NULL, 'TransferOut/index', '185', '1', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨出库单', NULL, 'TransferOut/index', '192', '2', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨出库单详细', NULL, 'TransferOut/view', '192', '2', '0', '0', '_self', NULL, '1', '0', NULL, 'Wms');

#调拨入库单
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨入库单', NULL, 'TransferI/index', '185', '1', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨入库单', NULL, 'TransferIn/index', '196', '2', '0', '1', '_self', NULL, '1', '0', NULL, 'Wms');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES (NULL, '调拨入库单详细', NULL, 'TransferIn/view', '196', '2', '0', '0', '_self', NULL, '1', '0', NULL, 'Wms');


#权限-调拨
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(NULL, 'index', '3', 'Wms', 'Wms', 'Transfer', '', 'Wms/Transfer/index', '', '', '调拨单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(NULL, 'index', '4', 'Wms', 'Wms', 'Transfer', 'index', 'Wms/Transfer/index', '', '', '调拨单列表', 1, 428, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(NULL, 'index', '4', 'Wms', 'Wms', 'Transfer', 'add', 'Wms/Transfer/add', '', '', '添加调拨', 1, 428, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#调拨出库
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(NULL, 'index', '3', 'Wms', 'Wms', 'TransferOut', '', 'Wms/TransferOut/index', '', '', '调拨出库单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(NULL, 'index', '4', 'Wms', 'Wms', 'TransferOut', 'index', 'Wms/TransferOut/index', '', '', '调拨出库单列表', 1, 431, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#调拨人库
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(NULL, 'index', '3', 'Wms', 'Wms', 'TransferIn', '', 'Wms/TransferIn/index', '', '', '调拨入库单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(NULL, 'index', '4', 'Wms', 'Wms', 'TransferIn', 'index', 'Wms/TransferIn/index', '', '', '调拨入库单列表', 1, 433, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);




