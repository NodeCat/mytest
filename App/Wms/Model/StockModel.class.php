<?php
namespace Wms\Model;
use Think\Model;
class StockModel extends Model {

    protected $insertFields = array('id','wh_id','location_id','pro_code','batch','status','stock_qty','assign_qty','prepare_qty','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','wh_id','location_id','pro_code','batch','status','stock_qty','assign_qty','prepare_qty','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array();

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        
    );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
        
    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('stock.is_deleted'=>'0'),
            'order'=>'stock.id DESC',
            "join"=>array("inner join location on stock.location_id=location.id ","inner join warehouse on stock.wh_id=warehouse.id "),
"field"=>"stock.*,location.name as location_name,location.code as location_code, warehouse.name as warehouse_name",
        ),
        'latest'=>array(
            'where'=>array('stock.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}