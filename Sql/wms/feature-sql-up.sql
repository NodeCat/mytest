#修复调拨单为TP150817080002 到库库单单号STI150817070003 
DELETE FROM `stock_bill_in_detail` WHERE `stock_bill_in_detail`.`id` = 11277;
DELETE FROM `stock_bill_out_container` WHERE `stock_bill_out_container`.`id` = 19848;
#调拨单
UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 35;
#调拨信息
DELETE FROM `erp_transfer_out_container` WHERE `erp_transfer_out_container`.`id` = 131;
#调拨入库单
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 28;  
DELETE FROM `erp_transfer_in_detail` WHERE `erp_transfer_in_detail`.`id` = 131;
UPDATE `erp_transfer_in_detail` SET `receipt_qty` = '160.00',`done_qty` = '160.00',`qualified_qty` = '160.00' WHERE `erp_transfer_in_detail`.`id` = 132;
UPDATE `erp_transfer_in_detail` SET `receipt_qty` = '40.00',`done_qty` = '40.00',`qualified_qty` = '40.00' WHERE `erp_transfer_in_detail`.`id` = 133;
#修复调拨单为 TP150816070004 到库库单单号 STI150816070004 
UPDATE `stock_bill_in_detail` SET `receipt_qty` = `expected_qty`,`done_qty` = `expected_qty` WHERE `stock_bill_in_detail`.`id`<= 10462 and `id`>=10459;
ALTER TABLE `erp_transfer_out_container` CHANGE `pro_qty` `pro_qty` DECIMAL(18.2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '数量';
UPDATE `erp_transfer_out_container` SET `pro_qty` = '45.50' WHERE `erp_transfer_out_container`.`id` = 109;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '0.80' WHERE `erp_transfer_out_container`.`id` = 110;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '0.20' WHERE `erp_transfer_out_container`.`id` = 111;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '30.60' WHERE `erp_transfer_out_container`.`id` = 113;

UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 32;
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 23;
UPDATE `erp_transfer_in_detail` SET `done_qty` = `plan_in_qty`,`receipt_qty` = `plan_in_qty`,`qualified_qty`=`plan_in_qty` WHERE `erp_transfer_in_detail`.`pid` = 23;

#修复调拨单为 TP150816070003 到库库单单号 STI150816070003 
UPDATE `stock_bill_in_detail` SET `receipt_qty` = `expected_qty`,`done_qty` = `expected_qty` WHERE `stock_bill_in_detail`.`pid`= 1626;
UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 31;
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 22;
UPDATE `erp_transfer_in_detail` SET `done_qty` = `plan_in_qty`,`receipt_qty` = `plan_in_qty`,`qualified_qty`=`plan_in_qty` WHERE `erp_transfer_in_detail`.`pid` = 22;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '7.20' WHERE `erp_transfer_out_container`.`id` = 103;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '5.60' WHERE `erp_transfer_out_container`.`id` = 104;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '21.60' WHERE `erp_transfer_out_container`.`id` = 105;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '12.60' WHERE `erp_transfer_out_container`.`id` = 107;


#修复调拨单为 TP150816070001 到库库单单号 STI150816070002 
UPDATE `stock_bill_in_detail` SET `receipt_qty` = `expected_qty`,`done_qty` = `expected_qty` WHERE `stock_bill_in_detail`.`pid`= 1625;
UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 29;
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 21;
UPDATE `erp_transfer_in_detail` SET `done_qty` = `plan_in_qty`,`receipt_qty` = `plan_in_qty`,`qualified_qty`=`plan_in_qty` WHERE `erp_transfer_in_detail`.`pid` = 21;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '1.60' WHERE `erp_transfer_out_container`.`id` = 99;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '4.80' WHERE `erp_transfer_out_container`.`id` = 100;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '12.40' WHERE `erp_transfer_out_container`.`id` = 101;
UPDATE `erp_transfer_out_container` SET `pro_qty` = '1.20' WHERE `erp_transfer_out_container`.`id` = 102;
#修复调拨单为 TP150815070004 到库库单单号 STI150816070001
UPDATE `stock_bill_in_detail` SET `receipt_qty` = `expected_qty`,`done_qty` = `expected_qty` WHERE `stock_bill_in_detail`.`pid`= 1624;
UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 28;
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 20;
UPDATE `erp_transfer_in_detail` SET `done_qty` = `plan_in_qty`,`receipt_qty` = `plan_in_qty`,`qualified_qty`=`plan_in_qty` WHERE `erp_transfer_in_detail`.`pid` = 20;



#修复调拨单为 TP150813080002 到库库单单号 STI150814070001
UPDATE `erp_transfer` SET `status` = 'up' WHERE `erp_transfer`.`id` = 23;
UPDATE `erp_transfer_in` SET `status` = 'up' WHERE `erp_transfer_in`.`id` = 17;
UPDATE `erp_transfer_in_detail` SET `done_qty` = `plan_in_qty`,`receipt_qty` = `plan_in_qty`,`qualified_qty`=`plan_in_qty` WHERE `erp_transfer_in_detail`.`pid` = 17;

