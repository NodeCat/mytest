<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Model;
use Think\Model;
class PickModel extends Model {

    protected $insertFields = array('id','wave_id','type','order_sum','pro_type_sum','pro_qty_sum','wh_id','line_id','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('wave_id','type','order_sum','pro_type_sum','pro_qty_sum','wh_id','line_id','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'stock_wave_picking';

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
            'field'=>'stock_wave_picking.id as pick_id,stock_wave_picking.*',
            'where'=>array('stock_wave_picking.is_deleted'=>'0'),
            'order'=>'stock_wave_picking.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('stock_wave_picking.is_deleted'=>'0'),
            'order'=>'updated_time DESC',
        ),


    );
}

/* End of file WaveModel.class.php */
/* Location: ./Application/Model/WaveModel.class.php */