ALTER TABLE  `stock_wave_distribution_detail` CHANGE  `status`  `status` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '0' COMMENT  '0已分拨1已装车2已签收3已拒收4已完成5已发运';