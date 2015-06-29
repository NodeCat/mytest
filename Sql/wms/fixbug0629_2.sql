ALTER TABLE `stock_bill_out` ADD `total_amount` DECIMAL(18,2) NOT NULL DEFAULT '0' AFTER `company_id`;
ALTER TABLE `stock_bill_out` ADD `total_qty` INT(11) NOT NULL DEFAULT '0' AFTER `total_amount`;