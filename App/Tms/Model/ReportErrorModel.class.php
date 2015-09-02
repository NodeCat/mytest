<?php
namespace Tms\Model;
use Think\Model;
class ReportErrorModel extends Model {

    public $tableName = 'tms_report_error';
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
            'table' => 'tms_report_error te',
            'order' => 'te.created_time DESC',
            'join'  => array(
                'INNER JOIN tms_sign_list ts ON te.sid = ts.id',
                'INNER JOIN tms_user tu ON te.user_id = tu.id',
            ),
            'field' => 
                'ts.id,
                te.type,
                te.customer_name,
                te.customer_mobile,
                te.customer_address,
                te.line_name,
                te.shop_name,
                te.current_bd,
                tu.username driver_name,
                tu.mobile driver_mobile,
                ts.id sid,
                te.created_time report_time,
                te.is_deleted',
        ),

    );
}
