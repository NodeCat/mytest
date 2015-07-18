<?php
/**
 * Created by PhpStorm.
 * User: san77
 * Date: 15/7/14
 * Time: 上午11:09
 */

namespace Wms\Model;
use Think\Model\RelationModel;


class SettlementDetailModel  extends RelationModel {
    protected $insertFields = array('id','code','order_code','order_type','wh_id','cat_total','qty_total','purchase_time','stock_time','total_amount','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'erp_settlement_detail';
}