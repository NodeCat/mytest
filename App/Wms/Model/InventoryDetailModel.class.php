<?php
namespace Wms\Model;
use Think\Model;
class InventoryDetailModel extends Model {

    protected $insertFields = array('id','inventory_code','pro_code','location_id','pro_qty','theoretical_qty','created_user','created_time','updated_user','updated_time','status','is_deleted');
    protected $updateFields = array('inventory_code','pro_code','location_id','pro_qty','theoretical_qty','created_user','created_time','updated_user','updated_time','status','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'stock_inventory_detail';

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
            'where'=>array('stock_inventory_detail.is_deleted'=>'0'),
            'order'=>'stock_inventory_detail.id DESC',
            "join"=>array("inner join location on stock_inventory_detail.location_id=location.id "),
"field"=>"stock_inventory_detail.*,location.name as location_name,location.code as location_code,case stock_inventory_detail.pro_qty when 0 then '' else stock_inventory_detail.pro_qty end pro_qty",
        ),
        'latest'=>array(
            'where'=>array('stock_inventory_detail.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}