<?php
namespace Wms\Model;
use Think\Model;
class UserModel extends Model {

    protected $insertFields = array('id','username','password','email','nickname','mobile','status');
    protected $updateFields = array('username','password','email','nickname','mobile','status');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('username','require','用户名不能为空',1,'regex',1),
        array('username','','此用户名已存在',1,'unique',1),
        array('password','require','密码不能为空',1,'regex',1),
        array('email','require','用户邮箱不能为空',1,'regex',1),
        array('nickname','require','真实姓名不能为空',1,'regex',1),
    );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
        array('password', 'auth_md5', 3, 'function'),
        array('password','',2,'ignore'),
        array('status', 'job'),
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
            'where'=>array('user.is_deleted'=>'0'),
            'order'=>'user.id DESC',
            //"join"=>array("inner join auth_user_role on auth_user_role.user_id=user.id ",
                //"inner join auth_role on auth_role.id = auth_user_role.role_id"),
            //"field"=>"user.*,auth_role.name as role_name",
        ),
            
        'latest'=>array(
            'where'=>array('user.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}