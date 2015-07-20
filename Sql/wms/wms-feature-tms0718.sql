ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `delivery_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `updated_time`;

ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `period` CHAR(15) NOT NULL DEFAULT '上午' COMMENT '时段：上午、下午' AFTER `delivery_time`;