#采购单数量表
ALTER TABLE `stock_purchase_detail` CHANGE `pro_qty` `pro_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '数量';
ALTER TABLE `stock_purchase` CHANGE `cat_total` `cat_total` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'sku种类数';
ALTER TABLE `stock_purchase` CHANGE `paid_amount` `paid_amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '已结算金额';

#采购到货单涉及的表
ALTER TABLE `stock_purchase` CHANGE `qty_total` `qty_total` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '预计到货件数';
ALTER TABLE `stock_bill_in_detail` CHANGE `expected_qty` `expected_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '预期数量';
ALTER TABLE `stock_bill_in_detail` CHANGE `prepare_qty` `prepare_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '待入库量';
ALTER TABLE `stock_bill_in_detail` CHANGE `done_qty` `done_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '已上架量';
ALTER TABLE `stock_bill_in_detail` CHANGE `qualified_qty` `qualified_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '正品数量';
ALTER TABLE `stock_bill_in_detail` CHANGE `unqualified_qty` `unqualified_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '残次品数量';
ALTER TABLE `stock_bill_in_detail` CHANGE `receipt_qty` `receipt_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '实际收货数量';

#采购入库单
ALTER TABLE `erp_purchase_in_detail` CHANGE `pro_qty` `pro_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '入库数量';
ALTER TABLE `erp_purchase_in_detail` CHANGE `price_subtotal` `price_subtotal` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '小计';
ALTER TABLE `erp_purchase_in_detail` CHANGE `status` `status` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付状态:paid 已支付nopaid:待支付';
ALTER TABLE `erp_purchase_in_detail` CHANGE `stock_in_code` `stock_in_code` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '到货单号';
ALTER TABLE `erp_purchase_in_detail` CHANGE `purchase_code` `purchase_code` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '采购单号';
ALTER TABLE `erp_purchase_in_detail` CHANGE `pro_status` `pro_status` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '产品状态：unqualified 不合格 qualified 合格';

#采购冲红单
ALTER TABLE `erp_purchase_refund` CHANGE `cat_total` `cat_total` INT(11) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT 'sku种类数目';
ALTER TABLE `erp_purchase_refund` CHANGE `qty_total` `qty_total` DECIMAL(18,2) NOT NULL DEFAULT '0.00' COMMENT '冲红数量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `expected_qty` `expected_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '预期数量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `unqualified_qty` `unqualified_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '不合格数量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `prepare_qty` `prepare_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '待入库量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `done_qty` `done_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '已上架量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `receipt_qty` `receipt_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '已收数量';
ALTER TABLE `erp_purchase_refund_detail` CHANGE `qualified_qty` `qualified_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '合格数量';
ALTER TABLE `erp_purchase_refund` CHANGE `status` `status` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '''norefund''=>''未收款'',''refund''=>''已收款'',''cancel''=>''已作废''';

#采购退货单
ALTER TABLE `stock_purchase_out_detail` CHANGE `plan_return_qty` `plan_return_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '计划退货量';
ALTER TABLE `stock_purchase_out_detail` CHANGE `real_return_qty` `real_return_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '实际退货量';

#库存表
ALTER TABLE `stock` CHANGE `stock_qty` `stock_qty` DECIMAL(18,2) UNSIGNED NULL DEFAULT '0' COMMENT '库存量';
ALTER TABLE `stock` CHANGE `assign_qty` `assign_qty` DECIMAL(18,2) UNSIGNED NULL DEFAULT '0' COMMENT '分配量';
ALTER TABLE `stock` CHANGE `prepare_qty` `prepare_qty` DECIMAL(18,2) UNSIGNED NULL DEFAULT '0.00' COMMENT '待上架量';

#移动库存表
ALTER TABLE `stock_move` CHANGE `move_qty` `move_qty` DECIMAL(18,2) NOT NULL DEFAULT '0.00' COMMENT '变化数量';
ALTER TABLE `stock_move` CHANGE `old_qty` `old_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '旧库存量';
ALTER TABLE `stock_move` CHANGE `new_qty` `new_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '旧加上的库存量';

#盘点列表
ALTER TABLE `stock_inventory_detail` CHANGE `pro_qty` `pro_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '盘点数量';
ALTER TABLE `stock_inventory_detail` CHANGE `theoretical_qty` `theoretical_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '理论仓库数';

#加工单
ALTER TABLE `erp_process_detail` CHANGE `plan_qty` `plan_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '计划生产量';
ALTER TABLE `erp_process_detail` CHANGE `real_qty` `real_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '实际生产量';

#物料清单
ALTER TABLE `erp_process_sku_relation` CHANGE `ratio` `ratio` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '比例数量';

#erp 加工入库单详细
ALTER TABLE `erp_process_in_detail` CHANGE `plan_qty` `plan_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '计划量';
ALTER TABLE `erp_process_in_detail` CHANGE `real_qty` `real_qty` DECIMAL(10) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '实际量';

#erp 加工出库单详细
ALTER TABLE `erp_process_out_detail` CHANGE `plan_qty` `plan_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '计划量';
ALTER TABLE `erp_process_out_detail` CHANGE `real_qty` `real_qty` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '实际量';
