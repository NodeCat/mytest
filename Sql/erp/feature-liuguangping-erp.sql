#菜单---
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '主页', '', 'Index/index', '0', '0', '0', '1', '_self', 'top', '1', '0', '', 'Erp');
#erp管理系统
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Wms', '2', 'Wms', 'Erp', '', '', 'Erp', '', '', '企业资源规划系统', '1', '1', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'index', '4', 'Wms', 'Erp', 'Index', 'index', 'Erp/Index/index', '', '', '主页', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

#商家
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '商家', '', 'Partner/index', '0', '0', '1', '1', '_self', 'top', '1', '0', '', 'Erp');
#商家菜单
UPDATE `wms`.`menu` SET `pid` = '193', `module` = 'Erp' WHERE `menu`.`id` = 27;
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`pid` = 27;
#商家权限
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/Partner/index', `pid` = '461' WHERE `auth_authority`.`id` = 11;
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 11 AND is_deleted=0;


#采购单
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`id` = 2;
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`pid` = 2 AND is_deleted =0;
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`pid` IN (11,13,44,119,121,161);
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/Purchase/index', `pid` = '460' WHERE `auth_authority`.`id` = 14;
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 14 AND is_deleted=0;
#采购到货单
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'StockIn', '3', 'Wms', 'Erp', 'StockIn', '', 'Erp/StockIn/index', '', '', '入库', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/StockIn/pview', `pid` = '462' WHERE `auth_authority`.`id` = 151; 
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/StockIn/pindex', `pid` = '462' WHERE `auth_authority`.`id` = 152;

INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'printpage', '4', 'Wms', 'Erp', 'StockIn', 'printpage', 'Erp/StockIn/printpage', '', '', 'printpage', '1', '462', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'onall', '4', 'Wms', 'Erp', 'StockIn', 'onall', 'Erp/StockIn/onall', '', '', '一键上架', '1', '462', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

#采购入库单
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/PurchaseInDetail/index', `pid` = '460' WHERE `auth_authority`.`id` = 319; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 319 AND is_deleted=0;
#采购冲红单
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/PurchaseRefund/index', `pid` = '460' WHERE `auth_authority`.`id` = 331; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 331 AND is_deleted=0;
#采购退货
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/PurchaseOut/index', `pid` = '460' WHERE `auth_authority`.`id` = 403; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 403 AND is_deleted=0;




#仓储管理 ---
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '仓储', '', 'Process/index', '0', '0', '1', '1', '_self', 'top', '1', '0', '', 'Erp');

INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '加工管理', NULL, '', '194', '1', '0', '1', '_self', NULL, '1', '0', '', 'Erp');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '调拨管理', NULL, '', '194', '1', '1', '1', '_self', NULL, '1', '0', '', 'Erp');

#加工单
UPDATE `wms`.`menu` SET `pid` = '195', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 102;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 102;
#加工出口单
UPDATE `wms`.`menu` SET `pid` = '195', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 128;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 128;
#加工入库单
UPDATE `wms`.`menu` SET `pid` = '195', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 125;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 125;
#物理清单
UPDATE `wms`.`menu` SET `pid` = '195', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 112;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3',`queue`='1' WHERE `menu`.`pid` = 112;

#加工权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Process', '3', 'Wms', 'Erp', 'Process', '', 'Erp/Process/index', '', '', '加工', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');
#权限去掉 加工单验证，扫描加工单，扫描父SKU
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/'),`pid` = 465 where `pid` = 210 AND is_deleted=0 AND id NOT IN (371,372,414);
#加工比例
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/ProcessRatio/index', `pid` = '460' WHERE `auth_authority`.`id` = 211; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 211 AND is_deleted=0;


#调拨----
#调拨单
UPDATE `wms`.`menu` SET `pid` = '196', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 175;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 175;
#调拨出库单
UPDATE `wms`.`menu` SET `pid` = '196', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 180;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 180;
#调拨入库单
UPDATE `wms`.`menu` SET `pid` = '196', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 183;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 183;

#调拨权限
#调拨单权限
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/Transfer/index', `pid` = '460' WHERE `auth_authority`.`id` = 431; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 431 AND is_deleted=0;
#调拨出库单
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/TransferOut/index', `pid` = '460' WHERE `auth_authority`.`id` = 434; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 434 AND is_deleted=0;
#调拨入库单
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/TransferIn/index', `pid` = '460' WHERE `auth_authority`.`id` = 436; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 436 AND is_deleted=0;












#财务----
#结算单
UPDATE `wms`.`menu` SET `module` = 'Erp',`queue` = '2' WHERE `menu`.`id` = 165;
UPDATE `wms`.`menu` SET `module` = 'Erp',`name` = '结算单' WHERE `menu`.`pid` = 165;
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`id` = 166;
UPDATE `wms`.`menu` SET `module` = 'Erp' WHERE `menu`.`pid` = 166;

#结算权限
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/Settlement/index', `pid` = '460' WHERE `auth_authority`.`id` = 416; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 416 AND is_deleted=0;

#报表---
UPDATE `wms`.`menu` SET `module` = 'Erp',`link` = 'Purchases/index',`queue` = '4' WHERE `menu`.`id` = 6;
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '采购报表', NULL, '', '6', '1', '0', '1', '_self', NULL, '1', '0', '', 'Erp');
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '库存报表', NULL, '', '6', '1', '1', '1', '_self', NULL, '1', '0', '', 'Erp');


#实时库存报表
UPDATE `wms`.`menu` SET `pid` = '198', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 151;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 151;
#采购需求报表
UPDATE `wms`.`menu` SET `pid` = '197', `module` = 'Erp',`level` = '2' WHERE `menu`.`id` = 158;
UPDATE `wms`.`menu` SET `module` = 'Erp',`level` = '3' WHERE `menu`.`pid` = 158;

#报表权限
UPDATE `wms`.`auth_authority` SET `group` = 'Erp', `url` = 'Erp/Insales/index', `pid` = '460',`module` = 'Insales' WHERE `auth_authority`.`id` = 376; 
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` = 376 AND is_deleted=0;
Update `wms`.`auth_authority` SET `group`='Erp',`url`=REPLACE(url, 'Wms/', 'Erp/') where `pid` IN (377,390) AND is_deleted=0;


#分类权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Category', '3', 'Wms', 'Erp', 'Category', 'index', 'Erp/Category/index', '', '', '分类', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

#分类子集
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Category', '4', 'Wms', 'Erp', 'Category', 'getCatInfoByPid', 'Erp/Category/getCatInfoByPid', '', '', '获取分类', '1', '466', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');


#管理-货品管理
#条形码
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '管理', NULL, 'Product/index', '0', '0', '5', '1', '_self', 'top', '1', '0', '', 'Erp');

INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '货品管理', NULL, '', '199', '1', '4', '1', '_self', NULL, '1', '0', '', 'Erp');

INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '条码管理', NULL, 'ProductBarcode/index', '200', '2', '6', '1', '_self', NULL, '1', '0', '', 'Erp');
INSERT INTO `menu` (`name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
('条码列表', NULL, 'ProductBarcode/index', 201, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
('条码详情', NULL, 'ProductBarcode/view', 201, 3, 2, 0, '_self', NULL, '1', 0, '', 'Erp');
#货品信息

INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '货品信息', NULL, 'Product/index', '200', '2', '2', '1', '_self', NULL, '1', '0', '', 'Erp');
INSERT INTO `menu` (`name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
('货品详情', NULL, 'Product/view', 204, 3, 2, 0, '_self', NULL, '1', 0, '', 'Erp'),
('货品列表', NULL, 'Product/index', 204, 3, 1, 1, '_self', NULL, '1', 0, '', 'Erp');



#条形码权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'ProductBarcode', '3', 'Wms', 'Erp', 'ProductBarcode', '', 'Erp/ProductBarcode/index', '', '', '条码管理', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');
INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'ProductBarcode', 'index', 'Erp/ProductBarcode/index', '', '', '列表', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('get_list', '4', 'Wms', 'Erp', 'ProductBarcode', 'get_list', 'Erp/ProductBarcode/get_list', '', '', '键值列表', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('refer', '4', 'Wms', 'Erp', 'ProductBarcode', 'refer', 'Erp/ProductBarcode/refer', '', '', '引用', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'ProductBarcode', 'view', 'Erp/ProductBarcode/view', '', '', '查看', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('add', '4', 'Wms', 'Erp', 'ProductBarcode', 'add', 'Erp/ProductBarcode/add', '', '', '添加', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('edit', '4', 'Wms', 'Erp', 'ProductBarcode', 'edit', 'Erp/ProductBarcode/edit', '', '', '编辑', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('delete', '4', 'Wms', 'Erp', 'ProductBarcode', 'delete', 'Erp/ProductBarcode/delete', '', '', '删除', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setting', '4', 'Wms', 'Erp', 'ProductBarcode', 'setting', 'Erp/ProductBarcode/setting', '', '', '设置', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('import', '4', 'Wms', 'Erp', 'ProductBarcode', 'import', 'Erp/ProductBarcode/import', '', '', '导入', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('export', '4', 'Wms', 'Erp', 'ProductBarcode', 'export', 'Erp/ProductBarcode/export', '', '', '导出', 1, 468, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#货品信息权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Product', '3', 'Wms', 'Erp', 'Product', '', 'Erp/Product/index', '', '', '产品', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');
INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'Product', 'index', 'Erp/Product/index', '', '', '列表', 1, 479, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'Product', 'view', 'Erp/Product/view', '', '', '查看', 1, 479, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('printpage', '4', 'Wms', 'Erp', 'Product', 'printpage', 'Erp/Product/printpage', '', '', 'printpage', 1, 479, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#用户管理
INSERT INTO `wms`.`menu` (`id`, `name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES 
(NULL, '用户管理', NULL, '', '199', '1', '4', '1', '_self', NULL, '1', '0', '', 'Erp');

INSERT INTO `menu` (`name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
('角色', NULL, 'AuthRole/index', 207, 2, 1, 1, '_self', NULL, '1', 0, '', 'Erp'),
('用户', NULL, 'User/index', 207, 2, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
('数据权限', NULL, 'AuthUserRule/edit', 207, 2, 3, 1, '_self', NULL, '1', 0, '', 'Erp');

INSERT INTO `menu` (`name`, `icon`, `link`, `pid`, `level`, `queue`, `show`, `target`, `location`, `status`, `is_deleted`, `memo`, `module`) VALUES
('用户列表', NULL, 'User/index', 209, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
('角色列表', NULL, 'AuthRole/index', 208, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
('用户详情', NULL, 'User/view', 209, 3, 0, 0, '_self', NULL, '1', 0, '', 'Erp'),
('设置权限', NULL, 'Authority/edit', 208, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp'),
('数据权限列表', NULL, 'AuthUserRule/index', 210, 3, 0, 1, '_self', NULL, '1', 1, '', 'Erp'),
('设置数据权限', NULL, 'AuthUserRule/edit', 210, 3, 0, 1, '_self', NULL, '1', 0, '', 'Erp');

#角色权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'AuthRole', '3', 'Wms', 'Erp', 'AuthRole', '', 'Erp/AuthRole/index', '', '', '角色', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'AuthRole', 'index', 'Erp/AuthRole/index', '', '', '列表', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('get_list', '4', 'Wms', 'Erp', 'AuthRole', 'get_list', 'Erp/AuthRole/get_list', '', '', '键值列表', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('refer', '4', 'Wms', 'Erp', 'AuthRole', 'refer', 'Erp/AuthRole/refer', '', '', '引用', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'AuthRole', 'view', 'Erp/AuthRole/view', '', '', '查看', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('add', '4', 'Wms', 'Erp', 'AuthRole', 'add', 'Erp/AuthRole/add', '', '', '添加', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('edit', '4', 'Wms', 'Erp', 'AuthRole', 'edit', 'Erp/AuthRole/edit', '', '', '编辑', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('delete', '4', 'Wms', 'Erp', 'AuthRole', 'delete', 'Erp/AuthRole/delete', '', '', '删除', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setting', '4', 'Wms', 'Erp', 'AuthRole', 'setting', 'Erp/AuthRole/setting', '', '', '设置', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('import', '4', 'Wms', 'Erp', 'AuthRole', 'import', 'Erp/AuthRole/import', '', '', '导入', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('export', '4', 'Wms', 'Erp', 'AuthRole', 'export', 'Erp/AuthRole/export', '', '', '导出', 1, 483, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#用户
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'User', '3', 'Wms', 'Erp', 'User', '', 'Erp/User/index', '', '', '用户', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'User', 'index', 'Erp/User/index', '', '', '列表', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('get_list', '4', 'Wms', 'Erp', 'User', 'get_list', 'Erp/User/get_list', '', '', '键值列表', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('refer', '4', 'Wms', 'Erp', 'User', 'refer', 'Erp/User/refer', '', '', '引用', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'User', 'view', 'Erp/User/view', '', '', '查看', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('add', '4', 'Wms', 'Erp', 'User', 'add', 'Erp/User/add', '', '', '添加', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('edit', '4', 'Wms', 'Erp', 'User', 'edit', 'Erp/User/edit', '', '', '编辑', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('delete', '4', 'Wms', 'Erp', 'User', 'delete', 'Erp/User/delete', '', '', '删除', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setting', '4', 'Wms', 'Erp', 'User', 'setting', 'Erp/User/setting', '', '', '设置', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('import', '4', 'Wms', 'Erp', 'User', 'import', 'Erp/User/import', '', '', '导入', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('export', '4', 'Wms', 'Erp', 'User', 'export', 'Erp/User/export', '', '', '导出', 1, 494, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#数据权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'AuthUserRule', '3', 'Wms', 'Erp', 'AuthUserRule', '', 'Erp/AuthUserRule/index', '', '', '数据权限', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('edit', '4', 'Wms', 'Erp', 'AuthUserRule', 'edit', 'Erp/AuthUserRule/edit', '', '', '编辑', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('index', '4', 'Wms', 'Erp', 'AuthUserRule', 'index', 'Erp/AuthUserRule/index', '', '', '列表', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('get_list', '4', 'Wms', 'Erp', 'AuthUserRule', 'get_list', 'Erp/AuthUserRule/get_list', '', '', '键值列表', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('refer', '4', 'Wms', 'Erp', 'AuthUserRule', 'refer', 'Erp/AuthUserRule/refer', '', '', '引用', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'AuthUserRule', 'view', 'Erp/AuthUserRule/view', '', '', '查看', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('add', '4', 'Wms', 'Erp', 'AuthUserRule', 'add', 'Erp/AuthUserRule/add', '', '', '添加', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('delete', '4', 'Wms', 'Erp', 'AuthUserRule', 'delete', 'Erp/AuthUserRule/delete', '', '', '删除', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setting', '4', 'Wms', 'Erp', 'AuthUserRule', 'setting', 'Erp/AuthUserRule/setting', '', '', '设置', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('import', '4', 'Wms', 'Erp', 'AuthUserRule', 'import', 'Erp/AuthUserRule/import', '', '', '导入', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('export', '4', 'Wms', 'Erp', 'AuthUserRule', 'export', 'Erp/AuthUserRule/export', '', '', '导出', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('getWhInfoByUserId', '4', 'Wms', 'Erp', 'AuthUserRule', 'getWhInfoByUserId', 'Erp/AuthUserRule/getWhInfoByUserId', '', '', 'getWhInfoByUserId', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setWhIdAndUserId', '4', 'Wms', 'Erp', 'AuthUserRule', 'setWhIdAndUserId', 'Erp/AuthUserRule/setWhIdAndUserId', '', '', 'setWhIdAndUserId', 1, 505, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#权限
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Authority', '3', 'Wms', 'Erp', 'Authority', '', 'Erp/Authority/index', '', '', '权限', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'Authority', 'index', 'Erp/Authority/index', '', '', '列表', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('view', '4', 'Wms', 'Erp', 'Authority', 'view', 'Erp/Authority/view', '', '', '查看', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('edit', '4', 'Wms', 'Erp', 'Authority', 'edit', 'Erp/Authority/edit', '', '', '编辑', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('menu', '4', 'Wms', 'Erp', 'Authority', 'menu', 'Erp/Authority/menu', '', '', 'menu', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('role_authority', '4', 'Wms', 'Erp', 'Authority', 'role_authority', 'Erp/Authority/role_authority', '', '', '权限节点', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('nodes', '4', 'Wms', 'Erp', 'Authority', 'nodes', 'Erp/Authority/nodes', '', '', 'nodes', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('editCat', '4', 'Wms', 'Erp', 'Authority', 'editCat', 'Erp/Authority/editCat', '', '', 'editCat', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('get_list', '4', 'Wms', 'Erp', 'Authority', 'get_list', 'Erp/Authority/get_list', '', '', '键值列表', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('refer', '4', 'Wms', 'Erp', 'Authority', 'refer', 'Erp/Authority/refer', '', '', '引用', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('add', '4', 'Wms', 'Erp', 'Authority', 'add', 'Erp/Authority/add', '', '', '添加', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('delete', '4', 'Wms', 'Erp', 'Authority', 'delete', 'Erp/Authority/delete', '', '', '删除', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('setting', '4', 'Wms', 'Erp', 'Authority', 'setting', 'Erp/Authority/setting', '', '', '设置', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('import', '4', 'Wms', 'Erp', 'Authority', 'import', 'Erp/Authority/import', '', '', '导入', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('export', '4', 'Wms', 'Erp', 'Authority', 'export', 'Erp/Authority/export', '', '', '导出', 1, 518, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#登入
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Login', '3', 'Wms', 'Erp', 'Login', '', 'Erp/Login/index', '', '', '登录', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');


INSERT INTO `auth_authority` (`name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES
('index', '4', 'Wms', 'Erp', 'Login', 'index', 'Erp/Login/index', '', '', '列表', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('logout', '4', 'Wms', 'Erp', 'Login', 'logout', 'Erp/Login/logout', '', '', '退出', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('verify', '4', 'Wms', 'Erp', 'Login', 'verify', 'Erp/Login/verify', '', '', '验证码', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('signup', '4', 'Wms', 'Erp', 'Login', 'signup', 'Erp/Login/signup', '', '', '注册', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('changepwd', '4', 'Wms', 'Erp', 'Login', 'changepwd', 'Erp/Login/changepwd', '', '', '修改密码', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0),
('wh', '4', 'Wms', 'Erp', 'Login', 'wh', 'Erp/Login/wh', '', '', 'wh', 1, 533, 0, 0, 0, '', '', '1', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0);

#仓库
INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'Warehouse', '3', 'Wms', 'Erp', 'Warehouse', '', 'Erp/Warehouse/index', '', '', '仓库', '1', '460', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

INSERT INTO `wms`.`auth_authority` (`id`, `name`, `type`, `app`, `group`, `module`, `action`, `url`, `condition`, `description`, `title`, `show`, `pid`, `mpid`, `level`, `queue`, `target`, `location`, `status`, `updated_user`, `updated_time`, `created_user`, `created_time`, `is_deleted`) VALUES 
(NULL, 'refer', '4', 'Wms', 'Erp', 'Warehouse', 'refer', 'Erp/Warehouse/refer', '', '', '引用', '1', '540', '0', '0', '0', '', '', '1', '0', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0');

#需要的控制器
#1.LoginController.class