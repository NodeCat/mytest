ALTER TABLE `wms`.`stock_wave_distribution_detail` 
ADD COLUMN `sign_status` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '0正常1异常' AFTER `deposit`;