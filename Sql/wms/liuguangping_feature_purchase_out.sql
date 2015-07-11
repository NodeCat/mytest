CREATE TABLE IF NOT EXISTS `wms`.`stock_purchase_out` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '仓库id',
  `wh_id` INT(11) UNSIGNED NOT NULL,
  `partner_id` INT(11) UNSIGNED NOT NULL COMMENT '供应商',
  `status` VARCHAR(45) NOT NULL DEFAULT 'draft' COMMENT '退货状态：draft草稿audit待审核tbr待出库refunded 已出库 cancelled 已作废 rejected已驳回',
  `out_remark` VARCHAR(45) NOT NULL DEFAULT 'quality' COMMENT '退货原因：quality 质量问题  wrong收错货物  replace替代销售 unsalable滞销退货 overdue过期退货  other其他问题',
  `receivables_state` VARCHAR(45) NOT NULL DEFAULT 'wait' COMMENT '收款状态:wait 待收款 ok 已收款',
  `out_type` VARCHAR(45) NOT NULL DEFAULT 'genuine' COMMENT '退货类型：genuine正品退货 defective残次退货',
  `rtsg_code` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '退货单号',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_user` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  `refer_code` VARCHAR(45) NOT NULL DEFAULT 0,
  `remark` VARCHAR(200) NOT NULL COMMENT '备忘',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC))
ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS `wms`.`stock_purchase_out_detail` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pro_code` VARCHAR(45) NOT NULL COMMENT '货品编号',
  `pro_name` VARCHAR(200) NOT NULL COMMENT '产品名称',
  `pro_attrs` VARCHAR(200) NOT NULL COMMENT '产品属性，规格',
  `batch_code` VARCHAR(45) NOT NULL COMMENT '批次',
  `pro_uom` VARCHAR(45) NOT NULL COMMENT '单位',
  `price_unit` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `plan_return_qty` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '计划退货量',
  `real_return_qty` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实际退货量',
  `pid` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '采购退货单id',
  `created_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `updated_user` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(45) NOT NULL,
  `is_deleted` TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  `wh_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC))
ENGINE = InnoDB

INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(402, 'index', '4', 'Wms', 'Wms', 'PurchaseOut', 'index', 'Wms/PurchaseOut/index', '', '', '采购退货列表', 1, 401, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(401, 'index', '3', 'Wms', 'Wms', 'PurchaseOut', 'index', 'Wms/PurchaseOut/index', '', '', '采购退货单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(400, 'out', '4', 'Wms', 'Wms', 'Purchase', 'out', 'Wms/Purchase/out', '', '', '采购退货', 1, 14, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);
insert into `wms`.`auth_authority` ( `url`, `name`, `type`, `module`, `action`, `show`, `id`, `group`, `title`, `pid`, `app`) values ( 'Wms/Purchase/doOut', 'doOut', '4', 'Purchase', 'doOut', '1', '0', 'Wms', '采购退货提交', '14', 'Wms')
nsert into `wms`.`auth_authority` ( `url`, `name`, `type`, `module`, `action`, `show`, `id`, `group`, `title`, `pid`, `app`) values ( 'Wms/Purchase/veiw', 'view', '4', 'Purchase', 'view', '1', '0', 'Wms', '采购退货查看', '401', 'Wms')



INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(164, '采购退货列表', NULL, 'PurchaseOut/index', 163, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'wms'),
(163, '采购退货单', NULL, 'PurchaseOut/index', 2, 1, 99, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(160, '采购退货', NULL, 'Purchase/out', 11, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms');