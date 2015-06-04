<?php
namespace Wms\Model;
use Think\Model;
class PartnerModel extends Model {

    protected $insertFields = array('id','code','name','contact','email','tel','mobile','street','zip','score','company_id','status','description','updated_user','updated_time','created_user','created_time','is_deleted');
    protected $updateFields = array('code','name','contact','email','tel','mobile','street','zip','score','company_id','status','description','updated_user','updated_time','created_user','created_time','is_deleted');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('company_id','require','所属系统不能为空',1,'regex',1),
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
            'where'=>array('partner.is_deleted'=>'0'),
            'order'=>'partner.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('partner.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}