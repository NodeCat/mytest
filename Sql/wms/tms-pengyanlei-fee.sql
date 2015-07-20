ALTER TABLE  `tms_sign_list` ADD  `fee` DECIMAL( 18, 0 ) UNSIGNED NOT NULL DEFAULT  '0.00' COMMENT  '调度运费' AFTER  `department` ;

ALTER TABLE  `tms_sign_list` ADD  `period` VARCHAR( 45 ) NOT NULL DEFAULT  '''am''' COMMENT  '时段：am,pm' AFTER  `fee` ;