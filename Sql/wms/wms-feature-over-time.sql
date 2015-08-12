use wms;
ALTER TABLE `stock_wave_distribution` ADD `over_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '发运时间' AFTER `end_time`;
