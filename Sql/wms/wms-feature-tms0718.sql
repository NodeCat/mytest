ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `period` CHAR(15) NOT NULL DEFAULT '上午' COMMENT '时段：上午、下午' AFTER `delivery_time`;

ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `delivery_end_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '配送结束时间' AFTER `delivery_time`;

UPDATE `wms`.`tms_user` SET `tms_user`.`warehouse`='8' where `tms_user`.`warehouse`='6' and `id` >='1';