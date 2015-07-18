CREATE TABLE `stock_purchase_out` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '仓库id',
  `wh_id` int(11) unsigned NOT NULL,
  `partner_id` int(11) unsigned NOT NULL COMMENT '供应商',
  `status` varchar(45) NOT NULL DEFAULT 'draft' COMMENT '退货状态：draft草稿audit待审核tbr待出库refunded 已出库 cancelled 已作废 Rejected已驳回',
  `out_remark` varchar(45) NOT NULL DEFAULT 'quality' COMMENT '退货原因：quality 质量问题  wrong收错货物  replace替代销售 unsalable滞销退货 overdue过期退货  other其他问题',
  `receivables_state` varchar(45) NOT NULL DEFAULT 'wait' COMMENT '收款状态:wait 待收款 ok 已收款',
  `out_type` varchar(45) NOT NULL DEFAULT 'genuine' COMMENT '退货类型：genuine正品退货 defective残次退货',
  `rtsg_code` varchar(45) NOT NULL DEFAULT '' COMMENT '退货单号',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `refer_code` varchar(45) NOT NULL DEFAULT '0',
  `remark` varchar(200) NOT NULL COMMENT '备忘',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `stock_purchase_out_detail`;
CREATE TABLE `stock_purchase_out_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pro_code` varchar(45) NOT NULL COMMENT '货品编号',
  `pro_name` varchar(200) NOT NULL COMMENT '产品名称',
  `pro_attrs` varchar(200) NOT NULL COMMENT '产品属性，规格',
  `batch_code` varchar(45) NOT NULL COMMENT '批次',
  `pro_uom` varchar(45) NOT NULL COMMENT '单位',
  `price_unit` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
  `plan_return_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划退货量',
  `real_return_qty` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '实际退货量',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '采购退货单id',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL,
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `stock_bill_out_detail` ADD COLUMN `batch_code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '批次号';

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('out', '4', 'Wms', 'Wms', 'Purchase', 'out', 'Wms/Purchase/out', '', '', '采购退货', 0, 14, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('index', '3', 'Wms', 'Wms', 'PurchaseOut', '', 'Wms/PurchaseOut/index', '', '', '采购退货单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('index', '4', 'Wms', 'Wms', 'PurchaseOut', 'index', 'Wms/PurchaseOut/index', '', '', '采购退货列表', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('doOut', '4', 'Wms', 'Wms', 'Purchase', 'doOut', 'Wms/Purchase/doOut', '', '', '采购退货提交', 1, 14, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Wms', 'PurchaseOut', 'view', 'Wms/PurchaseOut/view', '', '', '采购退货查看', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('confirm', '4', 'Wms', 'Wms', 'PurchaseOut', 'confirm', 'Wms/PurchaseOut/confirm', '', '', '确认收款', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('eidit', '4', 'Wms', 'Wms', 'PurchaseOut', 'edit', 'Wms/PurchaseOut/edit', '', '', '编辑', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('pass', '4', 'Wms', 'Wms', 'PurchaseOut', 'pass', 'Wms/PurchaseOut/pass', '', '', '批准', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('reject', '4', 'Wms', 'Wms', 'PurchaseOut', 'reject', 'Wms/PurchaseOut/reject', '', '', '驳回', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('close', '4', 'Wms', 'Wms', 'PurchaseOut', 'close', 'Wms/PurchaseOut/close', '', '', '作废', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('doEdit', '4', 'Wms', 'Wms', 'PurchaseOut', 'doEdit', 'Wms/PurchaseOut/doEdit', '', '', '采购退货编辑提交', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);


INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(163, '采购退货', NULL, 'Purchase/out', 11, 2, 0, 0, '_self', NULL, '1', 0, NULL, 'Wms'),
(164, '采购退货单', NULL, 'PurchaseOut/index', 2, 1, 99, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(165, '采购退货列表', NULL, 'PurchaseOut/index', 164, 2, 0, 1, '_self', NULL, '1', 0, '', 'Wms'),
(166, '采购退货查看', NULL, 'PurchaseOut/view', 164, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms'),
(167, '采购退货编辑', NULL, 'PurchaseOut/edit', 164, 2, 0, 0, '_self', NULL, '1', 0, '', 'wms');