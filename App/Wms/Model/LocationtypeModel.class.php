<?php
namespace Wms\Model;
use Think\Model;
class LocationtypeModel extends Model {
    
    public $tableName = 'location_type'; 
    protected $insertFields = array('id','code','name','length','width','height','load','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $updateFields = array('code','name','length','width','height','load','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $readonlyField = array('id');

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
                'where'=>array('location_type.is_deleted'=>'0'),
                'order'=>'location_type.id DESC',

                ),
            'latest'=>array(
                'where'=>array('location_type.is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),


            );
}
