ALTER TABLE `stock_bill_out` CHANGE `system_type` `company_id` TINYINT(2) UNSIGNED NOT NULL DEFAULT '1' COMMENT '所属系统 1 大厨 2 大果';
