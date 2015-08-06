INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(204, '进销存明细', NULL, 'Repertory/view', 202, 2, 0, 0, '_self', NULL, '1', 0, '', 'wms'),
(203, '进销存报表', NULL, 'Repertory/index', 202, 2, 999, 1, '_self', NULL, '1', 0, '', 'wms'),
(202, '进销存报表', NULL, 'Repertory/index', 6, 1, 0, 1, '_self', NULL, '1', 0, '', 'wms');

INSERT INTO `auth_authority` ( `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted` ) VALUES
('exportData', '4', 'Wms', 'Wms', 'Repertory', 'exportData', 'Wms/Repertory/exportData', '', '', '进销存明细导出', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Wms', 'Repertory', 'view', 'Wms/Repertory/view', '', '', '进销存详情', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('index', '4', 'Wms', 'Wms', 'Repertory', 'index', 'Wms/Repertory/index', '', '', '进销存明细报表', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);
