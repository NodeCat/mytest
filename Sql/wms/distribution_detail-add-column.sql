ALTER TABLE `stock_wave_distribution_detail` ADD `pay_type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '支付方式:0货到付款,1微信支付,2账期支付' AFTER `deliver_fee`;

ALTER TABLE `stock_wave_distribution_detail` ADD `sign_driver_mobile` varchar(32) NOT NULL DEFAULT '' COMMENT '签收手机号' AFTER `sign_driver`;