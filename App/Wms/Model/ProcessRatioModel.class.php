<?php
namespace Wms\Model;
use Think\Model;
class ProcessRatioModel extends Model {

    protected $insertFields = array('id','p_pro_code','c_pro_code','company_id','ratio','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('p_pro_code','c_pro_code','company_id','ratio','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'erp_process_sku_relation';

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('company_id', 'require', '请选择所属系统', 1, 'regex', 1),
        array('p_pro_code', 'require', '请输入父SKU', 1, 'regex', 1),
        array('c_pro_code', 'require', '请输入子SKU', 1, 'regex', 1),
        array('ratio', 'require', '请输入比例', 1, 'regex', 1),
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
            'where'=>array('erp_process_sku_relation.is_deleted'=>'0'),
            'order'=>'erp_process_sku_relation.id',
            'field' => 'erp_process_sku_relation.*',
        ),
        'latest'=>array(
            'where'=>array('erp_process_sku_relation.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}