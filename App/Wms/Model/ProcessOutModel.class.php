<?php
namespace Wms\Model;
use Think\Model\RelationModel;
class ProcessOutModel extends RelationModel {

    protected $insertFields = array('id','wh_id','code','refer_code','process_type','status','remark','created_user','updated_user','created_time','updated_time','is_deleted');
    protected $updateFields = array('wh_id','code','refer_code','process_type','status','remark','created_user','updated_user','created_time','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'erp_process_out';

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
    
    protected $_link = array(
         "Detail" => array(
            'mapping_type' => self::HAS_MANY,
            'class_name'  => 'erp_process_out_detail',
            'foreign_key' => 'pid',
            'mapping_name' => 'detail',
         ),
         "Netail" => array(
         	'mapping_type' => self::HAS_ONE,
            'class_name' => 'erp_process_out_detail',
            'foreign_key' => 'pid',
            'mapping_name' => 'netail',
         ),
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('erp_process_out.is_deleted'=>'0'),
            'order'=>'erp_process_out.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('erp_process_out.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}