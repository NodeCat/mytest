ALTER TABLE `stock_bill_out_detail` ADD `price_bw` decimal(18,2) unsigned NOT NULL DEFAULT 0 COMMENT '按照重量记的单价' AFTER `price`;
