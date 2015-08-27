CREATE TABLE IF NOT EXISTS `erp_storage_cost` (
  `id` int(11) unsigned NOT NULL,
  `wh_id` int(11) unsigned NOT NULL COMMENT '仓库id',
  `pro_code` varchar(45) NOT NULL COMMENT '货品号',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `price_unit` decimal(18,2) unsigned DEFAULT '0.00' COMMENT '入库单价',
  `product_date` datetime NOT NULL COMMENT '生产日期',
  `created_time` datetime NOT NULL,
  `created_user` int(11) unsigned NOT NULL,
  `updated_time` datetime NOT NULL,
  `updated_user` int(11) unsigned NOT NULL,
  `is_deleted` tinyint(1) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `erp_storage_cost`
--
ALTER TABLE `erp_storage_cost`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pro_code` (`pro_code`,`batch`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `erp_storage_cost`
--
ALTER TABLE `erp_storage_cost`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT;