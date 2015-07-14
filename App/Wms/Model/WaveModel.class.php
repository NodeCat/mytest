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
class WaveModel extends Model {

    protected $insertFields = array('id','status','created_user','created_time','updated_time','updated_user','is_deleted','end_time','wave_type','order_count','line_count','total_count','site_src','start_time');
    protected $updateFields = array('status','created_user','created_time','updated_time','updated_user','is_deleted','end_time','wave_type','order_count','line_count','total_count','site_src','start_time');
    protected $readonlyField = array('id');
    public $tableName = 'stock_wave';

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
            'field'=>'stock_wave.id as wave_id,stock_wave.*',
            'where'=>array('stock_wave.is_deleted'=>'0'),
            'order'=>'stock_wave.id DESC',
            'join'=>array('inner join user u1 on stock_wave.created_user = u1.id',),
            'field'=>"stock_wave.*,stock_wave.id as wid,u1.nickname as created_user_nickname",
        ),
        'latest'=>array(
            'where'=>array('stock_wave.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}

/* End of file WaveModel.class.php */
/* Location: ./Application/Model/WaveModel.class.php */