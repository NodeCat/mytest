<?php
namespace Wms\Model;
use Think\Model\RelationModel;
class WavePickingModel extends RelationModel {
	protected $insertFields = array('id','code','wave_id','type','order_sum','pro_type_sum','pro_qty_sum','line_id','wh_id','status','created_user','created_time','updated_time','updated_user','is_deleted');
    protected $updateFields = array('code','wave_id','type','order_sum','pro_type_sum','pro_qty_sum','line_id','wh_id','status','created_user','created_time','updated_time','updated_user','is_deleted');
    protected $readonlyField = array();
    public $tableName = 'stock_wave_picking';

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        
    );

    protected $_link = array(
        "Detail" => array(
            'mapping_type' => self::HAS_MANY, 
            'class_name'  => 'WavePickingDetail',
            'foreign_key' => 'pid',
            'mapping_name' => 'detail',
        ),
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
            'where'=>array('stock_wave_picking.is_deleted'=>'0'),
            'order'=>'stock_wave_picking.id DESC',
            ),
        'latest'=>array(
            'where'=>array('stock_wave_picking.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
	
}