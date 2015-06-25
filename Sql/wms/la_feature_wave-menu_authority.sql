INSERT INTO `menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
(132, '波次浏览', NULL, 'Wave/index', 80, 1, 1, 1, '_self', NULL, '1', 0, '', 'wms'),
(133, '波次列表', NULL, 'Wave/index', 132, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'wms'),
(134, '波次详细', NULL, 'Wave/view', 132, 2, 0, 0, '_self', NULL, '1', 0, '', 'wms'),
(135, '分拣任务', NULL, 'Pick/index', 80, 1, 50, 1, '_self', NULL, '1', 0, '', 'wms'),
(136, '分拣列表', NULL, 'Pick/index', 135, 2, 0, 1, '_self', NULL, '1', 0, NULL, 'wms'),
(137, '分拣详细', NULL, 'Pick/view', 135, 2, 0, 0, '_self', NULL, '1', 0, '', 'wms'),
(141, '装载发运', NULL, '', 80, 1, 60, 1, '_self', NULL, '1', 0, '', 'wms'),
(142, '配送线路单列表', NULL, 'Distribution/index', 141, 2, 0, 1, '_self', NULL, '1', 0, '', 'wms'),
(143, '分配线路', NULL, 'DistDetail/index', 141, 2, 0, 1, '_self', NULL, '1', 0, '', 'wms'),
(144, '配送单详情', NULL, 'Distribution/view', 142, 3, 0, 0, '_self', NULL, '1', 0, '', 'wms'),
(145, '配送单打印', NULL, 'Disbution/printpage', 142, 3, 0, 0, '_self', NULL, '1', 0, '', 'wms'),
(147, '配送单列表', NULL, 'Distribution/index', 142, 3, 0, 1, '_self', NULL, '1', 0, '', 'wms'),
(151, '进销存分析', NULL, 'Insales/index', 6, 1, 0, 1, '_self', NULL, '1', 0, '', 'wms'),
(152, '进销存分析', NULL, 'Insales/index', 151, 2, 0, 1, '_self', NULL, '1', 0, '', 'wms'),
(153, '分配线路', NULL, 'DistDetail/index', 143, 3, 0, 1, '_self', NULL, '1', 0, '', 'wms');

INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(351, 'Wave', '3', 'Wms', 'Wms', 'Wave', '', 'Wms/Wave/index', '', '', '波次', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(352, 'index', '4', 'Wms', 'Wms', 'Wave', 'index', 'Wms/Wave/index', '', '', '波次列表', 1, 351, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(353, 'packing', '4', 'Wms', 'Wms', 'Wave', 'packing', 'Wms/Wave/packing', '', '', '开始分拣', 1, 351, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(354, 'Pick', '3', 'Wms', 'Wms', 'Pick', '', 'Wms/Pick/index', '', '', '分拣', 1, 2, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(356, 'index', '4', 'Wms', 'Wms', 'Pick', 'index', 'Wms/Pick/index', '', '', '分拣列表', 1, 354, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(357, 'view', '4', 'Wms', 'Wms', 'Pick', 'view', 'Wms/Pick/view', '', '', '查看', 1, 354, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(358, 'view', '4', 'Wms', 'Wms', 'Wave', 'view', 'Wms/Wave/view', '', '', '查看', 1, 351, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(359, 'Distribution', '3', 'Wms', 'Wms', 'StockOut', 'index', 'Wms/Distribution/index', '', '', '装载发运', 1, 19, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(360, 'index', '4', 'Wms', 'Wms', 'Distribution', 'index', 'Wms/Distribution/index', '', '', '配送单列表', 1, 359, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(361, 'index', '4', 'Wms', 'Wms', 'DistDetail', 'index', 'Wms/DistDetail/index', '', '', '配送单筛选', 1, 363, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(362, 'view', '4', 'Wms', 'Wms', 'Distribution', 'view', 'Wms/Distribution/index', '', '', '配送单详情', 1, 359, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(363, 'DistDetail', '3', 'Wms', 'Wms', 'Distdetail', 'index', 'Wms/Distbution/index', '', '', '配送单筛选', 1, 359, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(364, 'deleted', '4', 'Wms', 'Wms', 'Wave', 'deleted', 'Wms/Wave/delAll', '', '', '波次删除', 1, 351, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(365, 'pickPrint', '4', 'Wms', 'Wms', 'Pick', 'pickPrint', 'Wms/Pick/pickPrint', '', '', '分拣单打印', 1, 354, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

INSERT INTO `numbs` (`name`, `wh_id`, `prefix`, `mid`, `suffix`, `sn`, `date`, `status`, `updated_time`, `updated_user`, `created_time`, `created_user`, `is_deleted`) VALUES
('picking', 0, 'F', '%date%%wh_id%', '4', 1, '', '1', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, 0);
