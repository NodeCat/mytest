<?php
namespace Wms\Model;
use Think\Model;
class StorageCostModel extends Model {

    protected $insertFields = array('id','wh_id','pro_code','batch','price_unit','product_date','created_user','updated_user','created_time','updated_time','is_deleted');
    protected $updateFields = array('wh_id','pro_code','batch','price_unit','product_date','created_user','updated_user','created_time','updated_time','is_deleted');
    public $tableName = 'erp_storage_cost';

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
                array('created_user',UID,1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,3,'string'),
        array('updated_time','get_time',3,'function'),
        array('is_deleted','0',1,'string'),

    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('erp_storage_cost.is_deleted'=>'0'),
            'order'=>'erp_storage_cost.id DESC',
            
        ),
    );
}