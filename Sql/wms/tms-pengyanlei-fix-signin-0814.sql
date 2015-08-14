
-- 签收详情增加
ALTER TABLE  `tms_sign_in_detail` ADD  `delivery_qty` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '发货数量' AFTER  `real_sign_qty` ;

ALTER TABLE  `tms_sign_in_detail` ADD  `reject_qty` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '拒收数量' AFTER `real_sign_wgt` ;

ALTER TABLE  `tms_sign_in_detail` ADD  `sign_sum` DECIMAL( 18, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' COMMENT '签收小计' AFTER  `reject_qty` ;

ALTER TABLE  `tms_sign_in_detail` ADD  `reject_sum` DECIMAL( 18, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' COMMENT '拒收小计' AFTER  `sign_sum` ;