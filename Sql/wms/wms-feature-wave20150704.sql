ALTER TABLE  `stock_wave_distribution` CHANGE  `line_id`  `line_id` VARCHAR( 60 ) NOT NULL DEFAULT  '' COMMENT  '线路ID组合';
ALTER TABLE  `stock_wave` ADD  `refer_code` VARCHAR( 50 ) NOT NULL DEFAULT  '' COMMENT  '关联单号 非空则关联配送单号' AFTER `company_id` ;
ALTER TABLE  `stock_bill_out` CHANGE  `order_type`  `order_type` INT( 11 ) NULL DEFAULT  '0' COMMENT '1普通订单 2冻品订单 3爆款订单';