DROP TABLE IF EXISTS `erp_purchase_price_log`;
CREATE TABLE IF NOT EXISTS `erp_purchase_price_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `refer_code` varchar(100) NOT NULL DEFAULT '' COMMENT '采购单号',
  `pro_code` varchar(100) NOT NULL DEFAULT '' COMMENT 'SKU货号',
  `purchase_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '采购人',
  `purchase_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '采购时间',
  `old_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '修改前价格',
  `new_price` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT '修改后价格',
  `created_user` int(10) NOT NULL DEFAULT '0',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_user` int(10) NOT NULL DEFAULT '0',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


INSERT INTO  `wms`.`auth_authority` (
`id` ,
`name` ,
`type` ,
`app` ,
`group` ,
`module` ,
`action` ,
`url` ,
`condition` ,
`description` ,
`title` ,
`log` ,
`show` ,
`pid` ,
`mpid` ,
`level` ,
`queue` ,
`target` ,
`location` ,
`status` ,
`updated_user` ,
`updated_time` ,
`created_user` ,
`created_time` ,
`is_deleted`
)
VALUES (
NULL ,  'updatePrice',  '4',  'Wms',  'Erp',  'Purchase',  'updatePrice',  'Erp/Purchase/updatePrice',  '',  '',  '编辑价格',  '0',  '1',  '14',  '0',  '0', '0',  '',  '',  '1',  '0',  '0000-00-00 00:00:00',  '0',  '0000-00-00 00:00:00',  '0'
);
