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
class WaveDetailModel extends Model {

    protected $insertFields = array('id','pid','bill_out_id','created_time','updated_time','created_user','updated_user','status','is_deleted');
    protected $updateFields = array('pid','bill_out_id','created_time','updated_time','created_user','updated_user','status','is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'stock_wave_detail';

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        
    );

    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
                array('created_user',UID,1,'string'),
        array('type','200',1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,3,'string'),
        array('updated_time','get_time',3,'function'),
        array('start_time','get_time',1,'function'),
        array('is_deleted','0',1,'string'),

    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('stock_wave_detail.is_deleted'=>'0'),
            'order'=>'stock_wave_detail.id DESC',
            
        ),
        'latest'=>array(
            'where'=>array('stock_wave_detail.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}
/* End of file WaveDetailModel.class.php */
/* Location: ./Application/Model/WaveDetailModel.class.php */