<?php
namespace Fms\Model;
use Think\Model\RelationModel;
class RefundModel extends RelationModel {

    protected $insertFields = array(
        'id', 'type', 'order_id' ,'suborder_id', 'reject_reason', 
        'reject_code', 'refer_code', 'pid', 'pay_type', 'sum_reject_price', 'city_id', 'city_name', 'shop_name', 
        'customer_id', 'customer_name', 'customer_mobile', 'remark', 
        'created_time', 'created_user', 'update_time', 'update_user', 'status', 'is_deleted', 
    );
    protected $updateFields = array(
        'type', 'order_id' ,'suborder_id', 'reject_reason', 
        'reject_code', 'refer_code', 'pid', 'pay_type', 'sum_reject_price', 'city_id', 'city_name', 'shop_name', 
        'customer_id', 'customer_name', 'customer_mobile', 'remark', 
        'created_time', 'created_user', 'update_time', 'update_user', 'status', 'is_deleted',
    );
    protected $readonlyField = array('id');
    public $tableName='fms_refund';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
            
    );
    protected $_link = array(
        "Detail" => array(
            'mapping_type' => self::HAS_MANY, 
            'class_name'  => 'RefundDetail',
            'foreign_key' => 'pid',
            'mapping_name' => 'detail',
        ),
    );
    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
        array('created_user',UID,1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,1,'string'),
        array('updated_time','get_time',1,'function'),
        array('is_deleted','0',1,'string'),
        //array('status','0',1,'string'),

    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );
    
    protected $_scope = array(
        'default'=>array(
            'where'=>array('fms_refund.is_deleted' => '0'),
            'order'=>'fms_refund.id DESC',
            "join"=>array(
                "inner join warehouse on warehouse.id = fms_refund.wh_id",
            ),
            "field"=>"fms_refund.*,fms_refund.status as state",            
        ),
        'latest'=>array(
            'where'=>array('fms_refund.is_deleted'=>'0'),
            'order'=>'created_time DESC',
        ),


    );
}