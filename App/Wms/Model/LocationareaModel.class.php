<?php
namespace Wms\Model;
use Think\Model;
class LocationareaModel extends Model {
    
    public $tableName = 'location'; 
    protected $insertFields = array('id','name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','is_deleted','notes');
    protected $updateFields = array('name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','is_deleted','notes');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
                array('name','require','区域名称必填'),
                array('code','require','区域标识必填'),
                array('wh_id','require','仓库标识必选'),
                array('status','require','区域状态必选'),
            );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
            array('type','1'),
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
                "join"=>array("inner join warehouse on location.wh_id=warehouse.id "),
                "field"=>"location.*,warehouse.code as wh_code",
                ),
            'latest'=>array(
                'where'=>array('is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),

            );
}
