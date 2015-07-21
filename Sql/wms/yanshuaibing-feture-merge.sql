INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) 
VALUES
(416, 'index', '3', 'Wms', 'Wms', 'Settlement', '', 'Wms/Settlement/index', '', '', '结算单', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(417, 'index', '4', 'Wms', 'Wms', 'Settlement', 'index', 'Wms/Settlement/index', '', '', '结算单列表', 1, 416, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(418, 'add', '4', 'Wms', 'Wms', 'Settlement', 'add', 'Wms/Settlement/add', '', '', '添加结算单', 1, 416, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(419, 'view', '4', 'Wms', 'Wms', 'Settlement', 'view', 'Wms/Settlement/view', '', '', '查看结算单', 1, 416, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(420, 'pay', '4', 'Wms', 'Wms', 'Settlement', 'pay', 'Wms/Settlement/pay', '', '', '付款', 1, 416, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(421, 'printpage', '4', 'Wms', 'Wms', 'Settlement', 'printpage', 'Wms/Settlement/printpage', '', '', '打印结算单', 1, 416, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(165, '财务', NULL, 'Settlement/index', 0, 0, 999, 1, '_self', NULL, '1', 0, '', 'Wms'),
(166, '结算单列表', NULL, 'Settlement/index', 165, 1, 0, 1, '_self', NULL, '1', 0, '', 'Wms'),
(167, '结算单列表', NULL, 'Settlement/index', 166, 2, 0, 1, '_self', NULL, '1', 0, '', 'Wms'),
(168, '添加结算单', '', 'Settlement/add', 166, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms'),
(169, '查看结算单', NULL, 'Settlement/view', 166, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms'),
(170, '确认收款', NULL, 'Settlement/pay', 166, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms'),
(171, '打印结算单', NULL, 'Settlement/printpage', 166, 2, 0, 0, '_self', NULL, '1', 0, '', 'Wms');

INSERT INTO `numbs` (`name`, `wh_id`, `prefix`, `mid`, `suffix`, `sn`, `date`, `status`, `updated_time`, `updated_user`, `created_time`, `created_user`, `is_deleted`) VALUES
('settlement', 0, 'BA', '%date%', '5', 9, '150721', '1', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, 0);