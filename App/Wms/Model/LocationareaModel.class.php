<?php
namespace Wms\Model;
use Think\Model;
class LocationareaModel extends Model {
    
    public $tableName = 'location'; 
    protected $insertFields = array('id','name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','id_deleted','notes');
    protected $updateFields = array('name','code','pid','type','path','status','wh_id','created_time','updated_time','created_user','updated_user','id_deleted','notes');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(

            );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
                                array('type','1')

            );

    //'数据表字段'=>'表单字段'
    protected $_map = array(

            );

    protected $_scope = array(
            'default'=>array(
                'where'=>array('location.is_deleted'=>'0'),
                'order'=>'location.id DESC',
                "join"=>array("inner join warehouse on location.wh_id=warehouse.id "),
                "field"=>"location.*,warehouse.code as wh_id",
                ),
            'latest'=>array(
                'where'=>array('is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),


            );
}
