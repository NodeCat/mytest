--
-- 表的结构 `fms_refund`
--

CREATE TABLE IF NOT EXISTS `fms_refund` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(45) NOT NULL DEFAULT '' COMMENT '退款单类型，0拒收退款单，1缺货退款单',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `suborder_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '子订单id',
  `reject_reason` varchar(200) NOT NULL DEFAULT '' COMMENT '拒收原因',
  `reject_code` varchar(45) NOT NULL DEFAULT '' COMMENT '拒收入库单号',
  `refer_code` varchar(45) NOT NULL DEFAULT '' COMMENT '关联单号',
  `pid` int(11) unsigned NOT NULL DEFAULT '0',
  `pay_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '退款方式，0微信支付，1银行退款，2现场退款',
  `sum_reject_price` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '子订单退款金额',
  `city_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '城市id',
  `city_name` varchar(45) NOT NULL DEFAULT '' COMMENT '城市名称',
  `shop_name` varchar(45) NOT NULL DEFAULT '' COMMENT '店铺名称',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `customer_name` varchar(45) NOT NULL DEFAULT '' COMMENT '客户姓名',
  `customer_mobile` varchar(45) NOT NULL DEFAULT '' COMMENT '客户手机号',
  `remark` varchar(45) NOT NULL DEFAULT '' COMMENT '备注',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '0' COMMENT '0未处理，1已处理，2已关闭',
  `wh_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='退款单' AUTO_INCREMENT=1 ;

--
-- 表的结构 `fms_refund_detail`
--

CREATE TABLE IF NOT EXISTS `fms_refund_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `primary_category` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '一级分类id',
  `primary_category_cn` varchar(45) NOT NULL DEFAULT '' COMMENT '一级分类的中文',
  `pro_code` varchar(45) NOT NULL DEFAULT '' COMMENT 'sku编号',
  `pro_name` varchar(45) NOT NULL DEFAULT '' COMMENT 'sku名称',
  `price` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'sku单价',
  `reject_qty` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '拒收或缺货数量',
  `reject_price` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT 'sku退款金额',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='退款单详情' AUTO_INCREMENT=1 ;