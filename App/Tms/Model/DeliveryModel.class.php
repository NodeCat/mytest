<?php
namespace Tms\Model;
use Think\Model;
class DeliveryModel extends Model {

    public $tableName = 'tms_delivery';
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
                'table' =>'tms_delivery d',
                'order'=>'DATE(d.created_time) DESC',
                'join' => array('inner join tms_user u ON u.id = d.user_id',
                    'INNER JOIN stock_wave_distribution dist ON d.dist_id = dist.id AND dist.is_deleted = 0 and d.status = 1',
                    'inner join warehouse on dist.wh_id=warehouse.id ',),
                'group' => 'd.user_id ,DATE(d.created_time)',
                'field' => 'u.username,
                    d.mobile,
                    GROUP_CONCAT(d.dist_id) dist_ids,
                    GROUP_CONCAT(d.line_name) line_names,
                    DATE(d.created_time) delivery_date',
        ),

    );
}
