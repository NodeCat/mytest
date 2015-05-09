<?php
namespace Wms\Model;
use Think\Model;
class AdjustmentModel extends Model {

    protected $insertFields = array('id','type','code','pro_code','refer_code','status','batch','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('type','code','pro_code','refer_code','status','batch','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'stock_adjustment';

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
            'where'=>array('stock_adjustment.is_deleted'=>'0'),
            'order'=>'stock_adjustment.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('stock_adjustment.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}