ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `period` CHAR(15) NOT NULL DEFAULT '上午' COMMENT '时段：上午、下午' AFTER `delivery_time`;