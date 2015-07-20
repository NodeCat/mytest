ALTER TABLE `wms`.`tms_sign_list` 
ADD COLUMN `delivery_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `updated_time`;