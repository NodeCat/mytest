ALTER TABLE  `stock_bill_out_detail` ADD  `former_qty` DECIMAL( 18, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' COMMENT '客户原始下单量' AFTER  `price` ;
ALTER TABLE  `stock_bill_out` CHANGE  `status`  `status` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' COMMENT  '1待生产2已出库3波次中4待拣货5待复核18已关闭';
INSERT INTO  `wms_20150804`.`auth_authority` (
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
NULL ,  'open',  '4',  'Wms',  'Wms',  'StockOut',  'open',  'Wms/StockOut/open',  '',  '',  '开启出库单',  '1',  '19',  '0',  '0',  '0',  '',  '',  '1',  '0', '0000-00-00 00:00:00',  '0',  '0000-00-00 00:00:00',  '0'
);