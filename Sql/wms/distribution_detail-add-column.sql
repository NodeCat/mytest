ALTER TABLE `stock_wave_distribution_detail` ADD `wipe_zero` decimal(18,2) unsigned DEFAULT '0.00' COMMENT '抹零金额';
ALTER TABLE `stock_wave_distribution_detail` ADD `deposit` decimal(18,2) unsigned DEFAULT '0.00' COMMENT '押金';