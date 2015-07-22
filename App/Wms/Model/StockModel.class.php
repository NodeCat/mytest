<?php
namespace Wms\Model;
use Think\Model;
class StockModel extends Model {

    protected $insertFields = array('id','wh_id','location_id','pro_code','batch','status','stock_qty','assign_qty','prepare_qty','product_date','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','wh_id','location_id','pro_code','batch','status','stock_qty','assign_qty','prepare_qty','product_date','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array();

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
            'where'=>array('stock.is_deleted'=>'0'),
            'order'=>'stock.id DESC',
            "join"=>array("inner join location on stock.location_id=location.id ",
                "inner join warehouse on stock.wh_id=warehouse.id ",
                "inner join user u1 on stock.created_user = u1.id",
                "inner join user u2 on stock.updated_user = u2.id",
                "left join stock_batch on stock.batch=stock_batch.code",),
"field"=>"stock.*,location.name as location_name,location.code as location_code, warehouse.code as wh_code, warehouse.name as wh_name,u1.nickname as created_user_nickname,u2.nickname as updated_user_nickname",
        ),
        'latest'=>array(
            'where'=>array('stock.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}