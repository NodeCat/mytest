#采购单数量表
ALTER TABLE `stock_purchase_detail` CHANGE `pro_qty` `pro_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '数量';


#采购到货单涉及的表

ALTER TABLE `stock_purchase` CHANGE `qty_total` `qty_total` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '预计到货件数';