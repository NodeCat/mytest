
CREATE TABLE IF NOT EXISTS `erp_transfer` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `trf_code` varchar(45) NOT NULL DEFAULT '' COMMENT '调拨单',
  `wh_id_out` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调出库仓库id',
  `wh_id_in` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调入仓库id',
  `plan_cat_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '计划货品种数',
  `plan_qty_tobal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '计划货品件数',
  `status` varchar(45) NOT NULL DEFAULT 'draft' COMMENT '退货状态：draft草稿audit待审核tbr待出库refunded 已出库 up 已入库 cancelled 已作废 Rejected已驳回',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='调拨单';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_detail`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_detail` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '规格',
  `plan_transfer_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '计划调拨量',
  `real_out_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实际出库量',
  `real_in_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实际入库量',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '单位'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='调拨单详细';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_in`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_in` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '入库单号',
  `wh_id_out` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调出库仓库id',
  `wh_id_in` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调入仓库id',
  `cat_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'SKU种数',
  `qty_tobal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'SKU件数',
  `status` varchar(45) NOT NULL DEFAULT 'waiting' COMMENT '状态 waiting 待入库 waitingup 待上架 up 已上架 cancelled已作废 待入库是发运完了=》待上架是收货完了=》已上架是上架了',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='erp调拨入库单';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_in_detail`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_in_detail` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '规格',
  `batch_code` varchar(45) NOT NULL DEFAULT '' COMMENT '批次',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '单位',
  `price_unit` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `plan_in_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '计划入库库量',
  `prepare_qty` decimal(18,2) NOT NULL COMMENT '待人货量',
  `done_qty` decimal(18,2) NOT NULL COMMENT '已上架量',
  `receipt_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实际收货量',
  `qualified_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '正品数量',
  `unqualified_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '残次数量',
  `product_data` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) DEFAULT '0',
  `status` varchar(45) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='erp调拨入库单详细';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_out`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_out` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `code` varchar(45) NOT NULL DEFAULT '' COMMENT '调拨单',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据 调拨单',
  `wh_id_out` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调出库仓库id',
  `wh_id_in` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '调入仓库id',
  `cat_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'SKU种数',
  `qty_tobal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'SKU件数',
  `status` varchar(45) NOT NULL DEFAULT 'tbr' COMMENT '退货状态：tbr待生产refunded 已出库 cancelled 已作废',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='erp调拨出库单';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_out_container`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_out_container` (
  `id` int(10) unsigned NOT NULL,
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单据 erp 出库单',
  `pro_code` varchar(50) NOT NULL DEFAULT '' COMMENT 'sku编号',
  `pro_qty` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `price` decimal(18,2) unsigned NOT NULL DEFAULT '0' COMMENT '单价',
  `batch` varchar(100) NOT NULL DEFAULT '' COMMENT '批次',
  `location_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '库位',
  `wh_id` int(11) NOT NULL DEFAULT '0' COMMENT '出库单id',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(4) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='调拨出库SKU详细详细记录表';

-- --------------------------------------------------------

--
-- 表的结构 `erp_transfer_out_detail`
--

CREATE TABLE IF NOT EXISTS `erp_transfer_out_detail` (
  `id` int(11) unsigned NOT NULL COMMENT '主键',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT '货号',
  `pro_name` varchar(200) NOT NULL DEFAULT '' COMMENT '货号名称',
  `pro_attrs` varchar(200) NOT NULL DEFAULT '' COMMENT '规格',
  `batch_code` varchar(45) NOT NULL DEFAULT '' COMMENT '批次现在没有',
  `pro_uom` varchar(45) NOT NULL DEFAULT '' COMMENT '单位',
  `price_unit` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '单价现在不用',
  `plan_transfer_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '计划调拨量出库量',
  `real_out_qty` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '实际出库量',
  `product_data` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '生产日期 现在不用',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(11) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(4) DEFAULT '0',
  `status` varchar(45) DEFAULT NULL COMMENT 'tbr待生产refunded 已出库 cancelled 已作废'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='erp调拨出库单详细';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `erp_transfer`
--
ALTER TABLE `erp_transfer`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- Indexes for table `erp_transfer_detail`
--
ALTER TABLE `erp_transfer_detail`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `erp_transfer_detail`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- Indexes for table `erp_transfer_in`
--
ALTER TABLE `erp_transfer_in`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer_in`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';
  
ALTER TABLE `erp_transfer_in` ADD `refer_code` VARCHAR(45) NOT NULL DEFAULT '''''' COMMENT '关联单号：调拨单' AFTER `code`;

--
-- Indexes for table `erp_transfer_in_detail`
--
ALTER TABLE `erp_transfer_in_detail`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer_in_detail`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- Indexes for table `erp_transfer_out`
--
ALTER TABLE `erp_transfer_out`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer_out`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- Indexes for table `erp_transfer_out_container`
--
ALTER TABLE `erp_transfer_out_container`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer_out_container`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

--
-- Indexes for table `erp_transfer_out_detail`
--
ALTER TABLE `erp_transfer_out_detail`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `erp_transfer_out_detail`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键';

#菜单
INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(174, '调拨', NULL, 'Transfer/index', 0, 0, 1000, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(175, '调拨单', NULL, 'Transfer/index', 174, 1, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(176, '调拨单', NULL, 'Transfer/index', 175, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(177, '调拨添加', NULL, 'Transfer/add', 175, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms'),
(178, '调拨详细', NULL, 'Transfer/view', 175, 2, 0, 0, '_self', NULL, '1', 0, NULL, 'Wms'),
(179, '调拨编辑', NULL, 'Transfer/edit', 175, 2, 0, 0, '_self', NULL, '1', 0, NULL, 'Wms'),
(180, '调拨出库单', NULL, 'TransferOut/index', 174, 1, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(181, '调拨出库单', NULL, 'TransferOut/index', 180, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(182, '调拨出库单详细', NULL, 'TransferOut/view', 180, 2, 0, 0, '_self', NULL, '1', 0, NULL, 'Wms'),
(183, '调拨入库单', NULL, 'TransferIn/index', 174, 1, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(184, '调拨入库单', NULL, 'TransferIn/index', 183, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'Wms'),
(185, '调拨入库单详细', NULL, 'TransferIn/view', 183, 2, 0, 0, '_self', NULL, '1', 0, NULL, 'Wms');

#权限
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(430, 'index', '3', 'Wms', 'Wms', 'Transfer', '', 'Wms/Transfer/index', '', '', '调拨单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(431, 'index', '4', 'Wms', 'Wms', 'Transfer', 'index', 'Wms/Transfer/index', '', '', '调拨单列表', 1, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(432, 'index', '4', 'Wms', 'Wms', 'Transfer', 'add', 'Wms/Transfer/add', '', '', '添加调拨', 1, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(433, 'index', '3', 'Wms', 'Wms', 'TransferOut', '', 'Wms/TransferOut/index', '', '', '调拨出库单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(434, 'index', '4', 'Wms', 'Wms', 'TransferOut', 'index', 'Wms/TransferOut/index', '', '', '调拨出库单列表', 1, 433, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(435, 'index', '3', 'Wms', 'Wms', 'TransferIn', '', 'Wms/TransferIn/index', '', '', '调拨入库单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(436, 'index', '4', 'Wms', 'Wms', 'TransferIn', 'index', 'Wms/TransferIn/index', '', '', '调拨入库单列表', 1, 435, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(437, 'view', '4', 'Wms', 'Wms', 'Transfer', 'view', 'Wms/Transfer/view', '', '', '调拨单详细', 1, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(438, 'match_code', '4', 'Wms', 'Wms', 'Transfer', 'match_code', 'Wms/Transfer/match_code', '', '', '调拨获取货品', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(439, 'preview', '4', 'Wms', 'Wms', 'Transfer', 'preview', 'Wms/Transfer/preview', '', '', '调拨批量获取sku', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(440, 'edit', '4', 'Wms', 'Wms', 'Transfer', 'edit', 'Wms/Transfer/edit', '', '', '调拨单编辑', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(441, 'pass', '4', 'Wms', 'Wms', 'Transfer', 'pass', 'Wms/Transfer/pass', '', '', '调拨单批准', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(442, 'reject', '4', 'Wms', 'Wms', 'Transfer', 'pass', 'Wms/Transfer/reject', '', '', '调拨单驳回', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(443, 'close', '4', 'Wms', 'Wms', 'Transfer', 'close', 'Wms/Transfer/close', '', '', '调拨单作废', 0, 430, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(444, 'view', '4', 'Wms', 'Wms', 'TransferOut', 'view', 'Wms/TransferOut/view', '', '', '调拨出库单详细', 0, 433, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(445, 'view', '4', 'Wms', 'Wms', 'TransferIn', 'view', 'Wms/TransferIn/view', '', '', '调拨入库单查看', 0, 435, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(446, 'transferBatch', '4', 'Wms', 'Wms', 'TransferOut', 'view', 'Wms/TransferOut/transferBatch', '', '', '调拨出库单批次详细', 0, 433, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(447, 'transferBatch', '4', 'Wms', 'Wms', 'TransferIn', 'transferBatch', 'Wms/TransferIn/transferBatch', '', '', '调拨入库单批次查看', 0, 435, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0)

#标识
INSERT INTO `numbs` (`name`, `wh_id`, `prefix`, `mid`, `suffix`, `sn`, `date`, `status`, `updated_time`, `updated_user`, `created_time`, `created_user`, `is_deleted`) VALUES
('tp', 0, 'TP', '%date%%wh_id%', '4', 8, '', '1', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, 0);

#出库单加入批次
#ALTER TABLE `stock_bill_in_detail` ADD `batch` VARCHAR(45) NULL DEFAULT '' COMMENT '批次' AFTER `pro_attrs`;

