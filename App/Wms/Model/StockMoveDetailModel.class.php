<?php
namespace Wms\Model;
use Think\Model;
class StockMoveDetailModel extends Model {

    protected $insertFields = array('id','refer_code','wh_id','pid','location_id','type','batch','pro_code','direction','move_qty','old_qty','new_qty','status','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $updateFields = array('refer_code','wh_id','pid','location_id','type','batch','pro_code','direction','move_qty','old_qty','new_qty','status','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='stock_move';

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
            'where'=>array('stock_move.is_deleted'=>'0'),
            'order'=>'stock_move.id DESC',
            "join"=>array("inner join location as src_location on stock_move.location_id=src_location.id ",
                "inner join location as dest_location on stock_move.location_id=dest_location.id ",
                "inner join warehouse on stock_move.wh_id=warehouse.id ",
                "inner join user on stock_move.created_user = user.id"),
"field"=>"stock_move.*,src_location.code as src_location_code,dest_location.code as dest_location_code, warehouse.code as wh_code,user.nickname as user_nickname",
        ),
        'latest'=>array(
            'where'=>array('stock_move.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}
