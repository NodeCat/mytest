UPDATE  `wms`.`numbs` SET  `prefix` =  'DR' WHERE  `numbs`.`name` =  'dis';
ALTER TABLE `wms`.`stock_bill_out` ADD dis_mark tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否加入车单 0未加入 1已加入' AFTER delivery_address; 
