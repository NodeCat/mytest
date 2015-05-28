<?php
namespace Wms\Model;
use Think\Model;
class WarehouseModel extends Model {

    protected $insertFields = array('id','code','name','area_id','address','status','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $updateFields = array('code','name','area_id','address','status','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $readonlyField = array('id');
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
            array('code','require','仓库标识必填'),
            array('code','','此仓库标识已存在',1,'unique',1),
            array('name','require','仓库名称必填'),
            array('name','','此仓库名称已存在',1,'unique',1)
            );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
            array('status','2'),
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
                'where'=>array('is_deleted'=>'0'),
                'order'=>'id DESC',

                ),
            'latest'=>array(
                'where'=>array('is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),


            );
}
