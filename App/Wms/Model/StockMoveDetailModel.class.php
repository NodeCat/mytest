<?php
namespace Wms\Model;
use Think\Model;
class StockMoveDetailModel extends Model {

    protected $insertFields = array('id','refer_code','pid','type','batch','pro_code','pro_uom','move_qty','price_unit','src_wh_id','dest_wh_id','src_location_id','dest_location_id','status','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $updateFields = array('refer_code','pid','type','batch','pro_code','pro_uom','move_qty','price_unit','src_wh_id','dest_wh_id','src_location_id','dest_location_id','status','created_time','updated_time','created_user','updated_user','is_deleted');
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
            "join"=>array(
                "left join warehouse src_wh on stock_move.src_wh_id=src_wh.id ",
                "left join warehouse dest_wh on stock_move.dest_wh_id=dest_wh.id ",
                "left join location src_location on stock_move.src_location_id=src_location.id",
                "left join location dest_location on stock_move.dest_location_id=dest_location.id",
                "inner join user u1 on stock_move.created_user = u1.id",
                "inner join user u2 on stock_move.updated_user = u2.id"
                ),
"field"=>"stock_move.*,src_wh.name as src_wh_name,dest_wh.name as dest_wh_name,src_location.name as src_location_name,dest_location.name as dest_location_name,u1.nickname as created_user_nickname,u2.nickname as updated_user_nickname",

        ),
        'latest'=>array(
            'where'=>array('stock_move.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}