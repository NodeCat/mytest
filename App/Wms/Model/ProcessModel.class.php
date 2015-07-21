<?php
namespace Wms\Model;
use Think\Model;
class ProcessModel extends Model {

    protected $insertFields = array('id','code','type','wh_id','status','task', 'over_task', 'remark','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('code','type','wh_id','status','task', 'over_task', 'remark','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'erp_process';

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
            'where'=>array('erp_process.is_deleted'=>'0'),
            'order'=>'erp_process.id DESC',
            'join'=>array(
                'inner join warehouse on erp_process.wh_id=warehouse.id ',
                'inner join user u2 on erp_process.updated_user = u2.id',
                'inner join user u1 on erp_process.created_user = u1.id',
                ),
            'field'=>'erp_process.*, warehouse.name as wh_id, u2.nickname as updated_user, u1.nickname as created_user
                      ',
        ),
        'latest'=>array(
            'where'=>array('erp_process.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}