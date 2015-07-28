CREATE TABLE IF NOT EXISTS `stock_snap` (
  `id` int(11) NOT NULL,
  `pro_code` varchar(45) NOT NULL COMMENT 'SKU',
  `pro_name` varchar(45) NOT NULL COMMENT '商品名称',
  `wh_id` int(11) NOT NULL COMMENT '仓库ID',
  `batch` varchar(32) NOT NULL COMMENT '批次号',
  `stock_qty` decimal(18,2) NOT NULL COMMENT '库存量',
  `price_unit` decimal(18,2) NOT NULL COMMENT '单价',
  `pro_uom` varchar(20) NOT NULL COMMENT '单位',
  `pro_attrs` varchar(100) NOT NULL COMMENT '规格',
  `category1` mediumint(8) NOT NULL COMMENT '一级分类',
  `category2` mediumint(8) NOT NULL COMMENT '二级分类',
  `category3` mediumint(8) NOT NULL COMMENT '三类分类',
  `category_name1` varchar(45) NOT NULL COMMENT '一级分类名称',
  `category_name2` varchar(45) NOT NULL COMMENT '二级分类名称',
  `category_name3` varchar(45) NOT NULL COMMENT '三级分类名称',
  `snap_time` date NOT NULL COMMENT '快照日期(Ymd)',
  `status` varchar(32) NOT NULL COMMENT '状态',
  `created_user` int(11) NOT NULL COMMENT '创建用户',
  `updated_user` int(11) NOT NULL COMMENT '更新用户',
  `created_time` datetime NOT NULL COMMENT '创建时间',
  `updated_time` datetime NOT NULL COMMENT '更新时间',
  `is_deleted` tinyint(1) NOT NULL COMMENT '是否删除'
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE `stock_snap` ADD PRIMARY KEY (`id`);

ALTER TABLE `stock_snap` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;