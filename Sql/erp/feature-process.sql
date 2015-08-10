
//加工损耗添加菜单和权限
INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(230, '财务报表', NULL, '', 6, 1, 1, 1, '_self', NULL, '1', 0, '', 'Erp'),
(231, '加工损耗报表', NULL, 'ProcessLoss/index', 230, 2, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
(232, '加工损耗报表', NULL, 'ProcessLoss/index', 231, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp');

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'ProcessLoss', 'index', 'Erp/ProcessLoss/index', '', '', '加工损耗单列表', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('exportData', '4', 'Wms', 'Erp', 'ProcessLoss', 'exportData', 'Erp/ProcessLoss/exportData', '', '', '加工损耗清单导出', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

//进销存明细菜单修改
UPDATE `wms`.`menu` SET `pid` = '230', `level`='2', `module`='Erp' WHERE `menu`.`id` = 202;
UPDATE `wms`.`menu` SET `level`=3,  `module`='Erp' WHERE `menu`.`id` = 203;
UPDATE `wms`.`menu` SET `level`=3,  `module`='Erp' WHERE `menu`.`id` = 204;
