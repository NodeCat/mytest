-- 报错表
ALTER TABLE  `tms_report_error` DROP  `report_time` ;
ALTER TABLE  `tms_report_error` DROP  `driver_name` ;
ALTER TABLE  `tms_report_error` DROP  `driver_mobile` ;
ALTER TABLE  `tms_report_error` DROP  `company_id` ;
ALTER TABLE  `tms_report_error` ADD  `user_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '司机ID' AFTER `develop_bd` ;
ALTER TABLE  `tms_report_error` ADD  `sid` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '签到记录ID' AFTER  `id` ;
-- 签到记录表
ALTER TABLE  `tms_sign_list` DROP  `report_error_time` ;
ALTER TABLE  `tms_delivery` ADD  `sid` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '签到记录ID';