<?php
namespace Fms\Model;
use Think\Model;
class RefundDetailModel extends Model {

    protected $insertFields = array('id', 'pid', 'primary_category', 'primary_category_cn', 'pro_code', 'pro_name', 
        'price', 'reject_qty', 'reject_price', 'created_time', 'created_user', 'update_time', 'update_user', 'status', 
        'is_deleted',
    );
    protected $updateFields = array('pid', 'primary_category', 'primary_category_cn', 'pro_code', 'pro_name', 
        'price', 'reject_qty', 'reject_price', 'created_time', 'created_user', 'update_time', 'update_user', 'status', 
        'is_deleted',
    );
    protected $readonlyField = array('id');
    public $tableName = 'fms_refund_detail';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        
    );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
        array('created_user',UID,1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,3,'string'),
        array('updated_time','get_time',3,'function'),
        array('is_deleted','0',1,'string'),

    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('fms_refund_detail.is_deleted'=>'0'),
            'order'=>'stock_purchase_detail.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('fms_refund_detail.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}