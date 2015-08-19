ALTER TABLE  `tms_sign_list` ADD  `wh_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '签到仓库' AFTER  `fee` ;
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
NULL ,  '签到码', NULL ,  'Dispatch/signCode',  '237',  '2',  '1',  '1',  '_self',  '',  '1',  '0', NULL ,  'Tms'
);