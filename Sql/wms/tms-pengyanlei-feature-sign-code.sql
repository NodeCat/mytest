ALTER TABLE  `tms_sign_list` ADD  `wh_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '签到仓库' AFTER  `fee` ;
INSERT INTO  `wms`.`menu` (
`id` ,
`name` ,
`icon` ,
`link` ,
`pid` ,
`level` ,
`queue` ,
`show` ,
`target` ,
`location` ,
`status` ,
`is_deleted` ,
`memo` ,
`module`
)
VALUES (
NULL ,  '签到码', NULL ,  'Dispatch/signCode',  '0',  '0',  '1',  '1',  '_self',  'left',  '1',  '0', NULL ,  'Tms'
);

INSERT INTO  `wms_wangshuang`.`menu` (
`id` ,
`name` ,
`icon` ,
`link` ,
`pid` ,
`level` ,
`queue` ,
`show` ,
`target` ,
`location` ,
`status` ,
`is_deleted` ,
`memo` ,
`module`
)
VALUES (
NULL ,  '签到码', NULL ,  'Dispatch/signCode',  '236',  '1',  '1',  '1',  '_self',  'left',  '1',  '0', NULL ,  'Tms'
);

INSERT INTO  `wms_wangshuang`.`auth_authority` (
`id` ,
`name` ,
`type` ,
`app` ,
`group` ,
`module` ,
`action` ,
`url` ,
`condition` ,
`description` ,
`title` ,
`log` ,
`show` ,
`pid` ,
`mpid` ,
`level` ,
`queue` ,
`target` ,
`location` ,
`status` ,
`updated_user` ,
`updated_time` ,
`created_user` ,
`created_time` ,
`is_deleted`
)
VALUES (
NULL ,  'signCode',  '4',  'Erp',  'Tms',  'Dispatch',  'signCode',  'Tms/Dispatch/signCode',  '',  '',  '签到码',  '0',  '1',  '461',  '0',  '0',  '0',  '',  '', '1',  '0',  '0000-00-00 00:00:00',  '0',  '0000-00-00 00:00:00',  '0'
);