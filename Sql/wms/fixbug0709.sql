ALTER TABLE `stock_wave_distribution` CHANGE `total_price` `total_price` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '应收总金额';
ALTER TABLE `stock_wave_distribution` CHANGE `deal_price` `deal_price` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '实收总金额';