ALTER TABLE  `tms_sign_list` CHANGE  `note`  `note` VARCHAR( 160 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  '' COMMENT  '备注';
INSERT INTO  `wms`.`auth_authority` (
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
NULL ,  'saveNote',  '4',  'Erp',  'Tms',  'Dispatch',  'saveNote',  'Tms/Dispatch/saveNote',  '',  '',  '更新备注',  '0',  '1',  '461',  '0',  '0',  '0',  '', '',  '1',  '0',  '0000-00-00 00:00:00',  '0',  '0000-00-00 00:00:00',  '0'
);