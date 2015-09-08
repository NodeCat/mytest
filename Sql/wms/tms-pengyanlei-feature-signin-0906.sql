ALTER TABLE  `stock_bill_out_detail` ADD  `unit_id` VARCHAR( 45 ) NOT NULL DEFAULT  '' COMMENT  '计量单位' AFTER  `price` ;
ALTER TABLE  `stock_bill_out_detail` ADD  `close_unit` VARCHAR( 45 ) NOT NULL DEFAULT  '' COMMENT  '计价单位' AFTER `unit_id` ;

ALTER TABLE  `stock_bill_out` ADD  `pay_type` TINYINT( 3 ) NOT NULL DEFAULT  '-9' COMMENT '支付方式：0货到付款1微信支付2账期支付' AFTER  `total_qty` ;
ALTER TABLE  `stock_bill_out` ADD  `pay_status` TINYINT( 3 ) NOT NULL DEFAULT  '-9' COMMENT '支付状态：0货到付款1微信支付已付款-1微信付款失败' AFTER  `pay_type` ;