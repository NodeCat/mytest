ALTER TABLE `wms`.`stock_bill_out` 
DROP COLUMN `gennerate_method`;

INSERT INTO `wms`.`numbs` (`name`, `wh_id`, `prefix`, `mid`, `suffix`, `sn`, `status`) VALUES ('picking', '0', 'F', '%date%%wh_id%', '4', '1', '1');
