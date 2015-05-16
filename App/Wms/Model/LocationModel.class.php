<?php
namespace Wms\Model;
use Think\Model;
class LocationModel extends Model {
    
    public $tableName = 'location'; 
    protected $insertFields = array('id','name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','is_deleted','notes');
    protected $updateFields = array('name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','is_deleted','notes');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
                //array('wh_id','require','请填写仓库标识'),
            );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
            array('status','qualified'),
            array('type','2'),
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
                'where'=>array('location.is_deleted'=>'0'),
                'order'=>'location.id DESC',
                "join"=>array("inner join warehouse on location.wh_id=warehouse.id","inner join location l2 on location.pid = l2.id", "inner join location_detail ld on location.id = ld.location_id"),
                "field"=>"location.*,warehouse.code as warehouse_code,l2.code as area_code, ld.is_mixed_pro, ld.is_mixed_batch, ld.picking_line, ld.putaway_line, ld.type_id"
                ),
            'latest'=>array(
                'where'=>array('is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),


            );
}
