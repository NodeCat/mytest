ALTER TABLE  `stock_wave_distribution_detail` CHANGE  `sign_driver`  `sign_driver` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' COMMENT  '签收人';
ALTER TABLE  `stock_wave_distribution_detail` CHANGE  `sign_msg`  `sign_msg` VARCHAR( 320 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' COMMENT  '签收备注';
ALTER TABLE  `stock_wave_distribution_detail` CHANGE  `signature`  `signature` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' COMMENT  '客户签名';