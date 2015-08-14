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

--
-- 表的结构 `tms_dispatch_task`
--
CREATE TABLE IF NOT EXISTS `tms_dispatch_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '任务码',
  `task_name` varchar(128) NOT NULL DEFAULT '' COMMENT '任务名称',
  `wh_id` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '仓库ID',
  `task_type` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '任务类型',
  `apply_user` varchar(64) NOT NULL DEFAULT '' COMMENT '申请人',
  `apply_mobile` varchar(32) NOT NULL DEFAULT '' COMMENT '申请人电话',
  `apply_department` varchar(64) NOT NULL DEFAULT '' COMMENT '用车部门',
  `op_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '期望用车时间',
  `expect_car_type` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '期望车型',
  `expect_fee` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '预计费用',
  `department_approver` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '部门审批人',
  `department_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '部门审批时间',
  `logistics_approver` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '物流审批人',
  `logistics_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '物流审批时间',
  `platform` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '用车平台',
  `car_type` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '实派车型',
  `driver_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '司机ID',
  `delivery_fee` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际运费',
  `distance` decimal(18,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '里程',
  `reason` varchar(320) NOT NULL DEFAULT '' COMMENT '用车事由',
  `remark` varchar(32) NOT NULL DEFAULT '' COMMENT '备注：长期，临时',
  `take_time` varchar(64) NOT NULL DEFAULT '' COMMENT '任务用时',
  `status` varchar(45) NOT NULL DEFAULT '1' COMMENT '状态：1待部门审批2待物流审批3待派车4配送中5已完成6未通过',
  `delivery_time` varchar(45) NOT NULL DEFAULT '1' COMMENT '发货时间1上午2下午',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建用户',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新用户',
  `is_deleted` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='派车任务表' AUTO_INCREMENT=1 ;