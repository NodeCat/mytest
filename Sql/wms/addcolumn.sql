ALTER TABLE `category` ADD `val` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `type`;

INSERT INTO `category` (`id`, `code`, `name`, `type`, `val`, `pid`, `level`, `queue`, `remark`, `created_time`, `created_user`, `update_time`, `update_user`, `status`, `is_deleted`) VALUES
(27, '1', '米面粮油', 'sku_type', '0.13', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(28, '43', '水果', 'sku_type', '0.13', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(29, '130', '调料干货', 'sku_type', '0.17', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(30, '168', '餐厨用品', 'sku_type', '0.17', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(31, '198', '水产冻品', 'sku_type', '0.17', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(32, '269', '疏菜', 'sku_type', '0', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(33, '303', '酒水饮料', 'sku_type', '0.17', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0),
(34, '326', '肉类禽蛋', 'sku_type', '0', 0, 0, 0, '', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, '', 0);


INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `log`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES ('571', 'export', '4', 'Wms', 'Fms', 'Bill', 'export', 'Fms/Bill/export', '', '', '导出', '0', '1', '454', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');