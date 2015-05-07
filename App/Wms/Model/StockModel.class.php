<?php
namespace Wms\Model;
use Think\Model;
class StockModel extends Model {

    protected $insertFields = array('id','wh_id','location_id','pro_code','batch_id','status','sotck_qty','assign_qty','prepare_qty','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','wh_id','location_id','pro_code','batch_id','status','sotck_qty','assign_qty','prepare_qty','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array();

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
            'where'=>array('is_deleted'=>'0'),
            'order'=>'id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}