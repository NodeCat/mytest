ALTER TABLE `stock_wave_distribution_detail` ADD `pay_type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '支付方式:0货到付款,1微信支付,2账期支付' AFTER `deliver_fee`;

ALTER TABLE `stock_wave_distribution` ADD `payment_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' comment '结算时间';
ALTER TABLE `stock_wave_distribution` ADD  `payment_user` int(11) unsigned NOT NULL DEFAULT '0' comment '结算人';

INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(245, '结算报表', NULL, 'FmsReport/index', 155, 1, 1, 1, '_self', 'left', '1', 0, NULL, 'Fms'),
(246, '结算列表', NULL, 'FmsReport/index', 245, 2, 1, 1, '_self', '', '1', 0, NULL, 'Fms'),
(247, '结算详情', NULL, 'FmsReport/view', 245, 2, 1, 0, '_self', '', '1', 0, NULL, 'Fms');

INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `log`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(587, 'index', '3', 'Wms', 'Fms', 'FmsReport', 'index', 'Fms/FmsReport/index', '', '', '结算报表', 0, 1, 380, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(588, 'index', '4', 'Wms', 'Fms', 'FmsReport', 'index', 'Fms/FmsReport/index', '', '', '结算列表', 0, 1, 587, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(589, 'view', '4', 'Wms', 'Fms', 'FmsReport', 'view', 'Fms/FmsReport/view', '', '', '结算详情', 0, 1, 587, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `log`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(593, 'view_all', '4', 'Wms', 'Fms', 'FmsReport', 'view_all', 'Fms/FmsReport/view_all', '', '', '查看所有报表', 0, 1, 590, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);
