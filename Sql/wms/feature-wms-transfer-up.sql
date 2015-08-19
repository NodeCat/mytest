#分拣表
ALTER TABLE `stock_wave_picking` CHANGE `pro_qty_sum` `pro_qty_sum` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00';

#波次表
ALTER TABLE `stock_wave` CHANGE `total_count` `total_count` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT 'sum sku*count';

#出库单总件数
ALTER TABLE `stock_bill_out` CHANGE `total_qty` `total_qty` DECIMAL(18,2) NOT NULL DEFAULT '0.00' COMMENT '总件';

#配送车单
ALTER TABLE `stock_wave_distribution` CHANGE `sku_count` `sku_count` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '总件数';