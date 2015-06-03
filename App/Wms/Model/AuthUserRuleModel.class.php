<?php
namespace Wms\Model;
use Think\Model;
class AuthUserRuleModel extends Model {

    protected $insertFields = array('id','user_id','rule_id','type','status');
    protected $updateFields = array('user_id','rule_id','type','status');
    protected $readonlyField = array('id');
    public $tableName='auth_user_rule';

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
            'where'=>array('auth_user_rule.is_deleted'=>'0'),
            'order'=>'auth_user_rule.id DESC',
            'join'=>array(
                'left join user on auth_user_rule.user_id=user.id ',
                ),
            'field'=>'auth_user_rule.*,user.nickname',
        ),
        'latest'=>array(
            'where'=>array('auth_user_rule.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}