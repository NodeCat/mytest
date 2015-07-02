<?php
namespace Wms\Model;
use Think\Model;
class StockBillOutContainerModel extends Model {
    
    protected $insertFields = array('id','refer_code','pro_code','batch','wh_id','location_id', 'qty', 'created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','refer_code','pro_code','batch','wh_id','location_id', 'qty', 'created_user','created_time','updated_user','updated_time','is_deleted');
    public $tableName = 'stock_bill_out_container'; 
    protected $readonlyField = array('id');

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

}
