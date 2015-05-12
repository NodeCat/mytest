<?php
namespace Wms\Model;
use Think\Model\RelationModel;
class PurchaseModel extends RelationModel {

    protected $insertFields = array('id','code','type','wh_id','company_id','partner_id','invoice_method','piece_total','price_total','cat_total','qty_total','invoice_status','picking_status','expecting_date','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','code','type','wh_id','company_id','partner_id','invoice_method','piece_total','price_total','cat_total','qty_total','invoice_status','picking_status','expecting_date','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='stock_purchase';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        
    );
    protected $_link = array(
        "Detail" => array(
            'mapping_type' => self::HAS_MANY, 
            'class_name'  => 'PurchaseDetail',
            'foreign_key' => 'pid',
            'mapping_name' => 'detail',
        ),
    );
    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
                array('created_user',UID,1,'string'),
                array('created_time','get_time',1,'function'),
                array('updated_user',UID,3,'string'),
                array('updated_time','get_time',3,'function'),
                array('is_deleted','0',1,'string'),
                array('status','0',1,'string'),
    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('stock_purchase.is_deleted'=>'0'),
            'order'=>'stock_purchase.id DESC',
            "join"=>array("inner join warehouse on stock_purchase.wh_id=warehouse.id ",
                "inner join partner on stock_purchase.partner_id=partner.id ",
                "inner join user on stock_purchase.created_user = user.id "
            ),
"field"=>"stock_purchase.*,warehouse.code as warehouse_code,warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as created_user_name,user.mobile as created_user_mobile",
        ),
        'latest'=>array(
            'where'=>array('stock_purchase.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}