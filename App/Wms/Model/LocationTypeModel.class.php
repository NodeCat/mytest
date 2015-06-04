<?php
namespace Wms\Model;
use Think\Model;
class LocationTypeModel extends Model {
    
    public $tableName = 'location_type'; 
    protected $insertFields = array('id','code','name','length','width','height','load','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $updateFields = array('code','name','length','width','height','load','created_time','updated_time','created_user','updated_user','is_deleted');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
                array('code','require','请填写库位类型标识'),
                array('name','require','请填写库位类型名称'),
                array('code','checkCode','此库位类型标识已存在',1,'callback'),
                array('name','checkName','此库位类型名称已存在',1,'callback'),
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
                'where'=>array('location_type.is_deleted'=>'0'),
                'order'=>'location_type.id DESC',
                'join' =>array('inner join user on location_type.created_user=user.id ',
                               'inner join user u on location_type.updated_user=u.id ', 
                              ),
                'field'=>'location_type.*, user.nickname as created_name, u.nickname as updated_name'
                ),
            'latest'=>array(
                'where'=>array('location_type.is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),


            );
    
    protected function checkCode($data) {
        $location = M('location_type');
        $id = I('id');
        $map['code'] = $data;
        $map['id'] = array('neq', $id);
        $map['is_deleted'] = 0;
        $res = $location->where($map)->count();

        if(! empty($res)) {
            return false;    
        }else {
            return true;
        }
    }

    protected function checkName($data) {
        $location = M('location_type');
        $id = I('id');
        $map['name'] = $data;
        $map['id'] = array('neq', $id);
        $map['is_deleted'] = 0;
        $res = $location->where($map)->count();

        if(! empty($res)) {
            return false;    
        }else {
            return true;
        }
    }
}
