--
-- 签收表的结构 `tms_sign_in`
--

CREATE TABLE IF NOT EXISTS `tms_sign_in` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dist_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '配送单ID',
  `bill_out_id` int(11) unsigned NOT NULL COMMENT '出库单ID',
  `sign_driver` varchar(32) NOT NULL DEFAULT '''''' COMMENT '签收人',
  `sign_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '签收时间',
  `sign_msg` varchar(320) NOT NULL DEFAULT '''''' COMMENT '签收备注',
  `receivable_sum` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '应收小计',
  `real_sum` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实收小计',
  `minus_amount` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠',
  `pay_reduce` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '支付减免',
  `deliver_fee` decimal(18,2) unsigned DEFAULT '0.00' COMMENT '运费',
  `pay_status` smallint(2) NOT NULL DEFAULT '0' COMMENT '支付状态：－1，0货到付款1已付款',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '类型：0未处理1签收2拒收',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人',
  `is_deleted` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除0未删除1已删除',
  PRIMARY KEY (`id`),
  KEY `dist_id` (`dist_id`) USING BTREE,
  KEY `id` (`id`,`dist_id`,`sign_driver`,`sign_time`,`sign_msg`(255),`receivable_sum`,`real_sum`,`created_user`,`created_time`,`updated_time`,`update_user`,`is_deleted`),
  KEY `bill_out_id` (`bill_out_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='签收表' AUTO_INCREMENT=1 ;

--
-- 签收详情表的结构 `tms_sign_in_detail`
--

CREATE TABLE IF NOT EXISTS `tms_sign_in_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bill_out_detail_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '出库单ID',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `real_sign_qty` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实收数量',
  `delivery_wgt` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发货重量',
  `real_sign_wgt` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实收重量',
  `measure_unit` varchar(32) NOT NULL DEFAULT '' COMMENT '计量单位',
  `charge_unit` varchar(32) NOT NULL DEFAULT '' COMMENT '计价单位',
  `price_unit` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `created_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `update_user` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新人',
  `is_deleted` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除0未删除1已删除',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `bill_out_detail_id` (`bill_out_detail_id`) USING BTREE,
  KEY `pid` (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='签详情收表' AUTO_INCREMENT=1 ;

-- 修改配送单详情增加 状态字段

ALTER TABLE  `stock_wave_distribution_detail` ADD  `status` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '0未处理1已签收2拒收' AFTER  `pid`;

-- 修改配送单 状态字段 增加已签收状态

ALTER TABLE  `stock_wave_distribution` CHANGE  `status`  `status` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT  '1' COMMENT '状态1未发运2已发运3已签收';

