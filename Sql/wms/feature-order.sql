ALTER TABLE `stock_bill_out` ADD `customer_id` INT(11) NOT NULL DEFAULT '0' COMMENT '客户id' AFTER `customer_realname`;