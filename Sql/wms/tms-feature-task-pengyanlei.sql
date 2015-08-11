INSERT INTO `category` (`name`,`type`) 
    VALUES ('库间调拨', 'task_type'), ('市场拉货', 'task_type'), ('样品配送', 'task_type'), ('试吃用车', 'task_type');
    -- 将X替换为线上ID
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`,`log`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(557, 'DispatchTask', '3', 'Erp', 'Tms', 'DispatchTask', 'index', 'Tms/DispatchTask/index', '', '', '调度任务', 0, 1, 556, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(558, 'index', '4', 'Erp', 'Tms', 'DispatchTask', 'index', 'Tms/DispatchTask/index', '', '', '任务列表', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(559, 'addTask', '4', 'Erp', 'Tms', 'DispatchTask', 'addTask', 'Tms/DispatchTask/addTask', '', '', '添加任务', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(560, 'saveFee', '4', 'Erp', 'Tms', 'DispatchTask', 'saveFee', 'Tms/DispatchTask/saveFee', '', '', '保存运费', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(561, 'taskDel', '4', 'Erp', 'Tms', 'DispatchTask', 'taskDel', 'Tms/DispatchTask/taskDel', '', '', '删除任务', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(562, 'taskDetail', '4', 'Erp', 'Tms', 'DispatchTask', 'taskDetail', 'Tms/DispatchTask/taskDetail', '', '', '任务详情', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(563, 'departAudit', '4', 'Erp', 'Tms', 'DispatchTask', 'departAudit', 'Tms/DispatchTask/departAudit', '', '', '部门审批', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(564, 'logisAudit', '4', 'Erp', 'Tms', 'DispatchTask', 'logisAudit', 'Tms/DispatchTask/logisAudit', '', '', '物流审批', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(565, 'getCustomerList', '4', 'Erp', 'Tms', 'DispatchTask', 'getCustomerList', 'Tms/DispatchTask/getCustomerList', '', '', '获取客户信息列表', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(566, 'getExpectFee', '4', 'Erp', 'Tms', 'DispatchTask', 'getExpectFee', 'Tms/DispatchTask/getExpectFee', '', '', '计算预计运费', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);
