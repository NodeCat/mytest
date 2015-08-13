-- 派车任务类型
INSERT INTO `category` (`name`,`type`) 
    VALUES ('库间调拨', 'task_type'), ('市场拉货', 'task_type'), ('样品配送', 'task_type'), ('试吃用车', 'task_type');
-- 权限表
INSERT INTO `auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`,`log`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
(557, 'Dispatchtask', '3', 'Erp', 'Tms', 'Dispatchtask', 'index', 'Tms/Dispatchtask/index', '', '', '调度任务', 0, 1, 556, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(558, 'index', '4', 'Erp', 'Tms', 'Dispatchtask', 'index', 'Tms/Dispatchtask/index', '', '', '任务列表', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(559, 'addTask', '4', 'Erp', 'Tms', 'Dispatchtask', 'addTask', 'Tms/Dispatchtask/addTask', '', '', '添加任务', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(560, 'saveFee', '4', 'Erp', 'Tms', 'Dispatchtask', 'saveFee', 'Tms/Dispatchtask/saveFee', '', '', '保存运费', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(561, 'taskDel', '4', 'Erp', 'Tms', 'Dispatchtask', 'taskDel', 'Tms/Dispatchtask/taskDel', '', '', '删除任务', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(562, 'taskDetail', '4', 'Erp', 'Tms', 'Dispatchtask', 'taskDetail', 'Tms/Dispatchtask/taskDetail', '', '', '任务详情', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(563, 'departAudit', '4', 'Erp', 'Tms', 'Dispatchtask', 'departAudit', 'Tms/Dispatchtask/departAudit', '', '', '部门审批', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(564, 'logisAudit', '4', 'Erp', 'Tms', 'Dispatchtask', 'logisAudit', 'Tms/Dispatchtask/logisAudit', '', '', '物流审批', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(565, 'getCustomerList', '4', 'Erp', 'Tms', 'Dispatchtask', 'getCustomerList', 'Tms/Dispatchtask/getCustomerList', '', '', '获取客户信息列表', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
(566, 'getExpectFee', '4', 'Erp', 'Tms', 'Dispatchtask', 'getExpectFee', 'Tms/Dispatchtask/getExpectFee', '', '', '计算预计运费', 0, 1, 557, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

-- 调度运费改两位小数
ALTER TABLE  `tms_sign_list` CHANGE  `fee`  `fee` DECIMAL( 18, 2 ) UNSIGNED NOT NULL DEFAULT  '0.00' COMMENT '调度运费';