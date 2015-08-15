CREATE TABLE `wms`.`tms_task_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务节点id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '任务码',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '任务点名',
  `geo` varchar(45) NOT NULL DEFAULT '' COMMENT '任务节点坐标',
  `geo_new` varchar(45) NOT NULL DEFAULT '' COMMENT '签收地址坐标',
  `sign_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '任务签收时间',
  `created_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '修改时间',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '删除控制键',
  `updated_user` int(10) unsigned NOT NULL DEFAULT '0',
  `created_user` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(45) NOT NULL DEFAULT '0' COMMENT '0.待认领\n1.派遣中\n2.已签到\n3.已完成',
  `queue` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `customer` varchar(45) DEFAULT '' COMMENT '客户姓名',
  `mobile` varchar(45) DEFAULT '' COMMENT '客户电话',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='任务节点表';


ALTER TABLE `wms`.`tms_delivery` 
ADD COLUMN `type` VARCHAR(45) NOT NULL DEFAULT '0' COMMENT '提货类型' AFTER `city_id`;


