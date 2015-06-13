<?php
namespace Wms\Model;
use Think\Model;
class DistributionModel extends Model {

    protected $insertFields = array('id','dist_code','remarks','total_price','deal_price','company_id','line_id','deliver_time','order_count','line_count','sku_count','total_distance','begin_time','end_time','created_user','updated_user','created_time','updated_time','is_deleted','status','is_printed','city_id');
    protected $updateFields = array('dist_code','remarks','total_price','deal_price','company_id','line_id','deliver_time','order_count','line_count','sku_count','total_distance','begin_time','end_time','created_user','updated_user','created_time','updated_time','is_deleted','status','is_printed','city_id');
    protected $readonlyField = array('id');
    public $tableName = 'order_distribution';
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
                    'where'=>array('order_distribution.is_deleted'=>'0'),
                    'order'=>'order_distribution.id DESC',

            ),
            'latest'=>array(
                    'where'=>array('order_distribution.is_deleted'=>'0'),
                    'order'=>'update_time DESC',
            ),


    );
}
