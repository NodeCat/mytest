--
-- 转存表中的数据 `auth_authority`
--

INSERT INTO `auth_authority` (`id`,`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(374, 'Category', '3', 'Wms', 'Wms', 'Category', 'index', 'Wms/Category/index', '', '', '分类', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(375, 'Category', '4', 'Wms', 'Wms', 'Category', 'getCatInfoByPid', 'Wms/Category/getCatInfoByPid', '', '', '获取分类', 1, 374, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(376, 'Report', '3', 'Wms', 'Wms', 'Report', '', 'Wms/Insales/index', '', '', '报表', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(377, 'Report', '4', 'Wms', 'Wms', 'Report', 'index', 'Wms/Insales/index', '', '', '进销存分析', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(378, 'export', '4', 'Wms', 'Wms', 'Insales', 'export', 'Wms/Insales/export', '', '', '进销存导出', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(379, 'exportInsales', '4', 'Wms', 'Wms', 'Insales', 'exportInsales', 'Wms/Insales/exportInsales', '', '', '进销存导出ajax', 1, 376, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
