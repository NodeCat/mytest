ALTER TABLE  `stock_bill_out_detail` ADD  `unit_id` VARCHAR( 45 ) NOT NULL DEFAULT  '' COMMENT  '计量单位' AFTER  `price` ;
ALTER TABLE  `stock_bill_out_detail` ADD  `close_unit` VARCHAR( 45 ) NOT NULL DEFAULT  '' COMMENT  '计价单位' AFTER `unit_id` ;
ALTER TABLE  `stock_bill_out_detail` ADD  `net_weight` VARCHAR( 12 ) NOT NULL DEFAULT  '' COMMENT  '单位重量' AFTER `delivery_qty` ;
ALTER TABLE  `stock_bill_out_detail` ADD  `od_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '关联订单详情ID' AFTER  `wh_id` ;