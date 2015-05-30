<?php
namespace Wms\Model;
use Think\Model;
class StockOutModel extends Model {

    public $tableName='stock_bill_out';
    protected $insertFields = array('id','code','wh_id','type','refer_code','notes','op_date','status','gennerate_method','created_user','created_time','packing_code','updated_user','line','updated_time','process_type','is_deleted','refused_code','total_amount','wave_code','shop_name','customer_name','customer_tel','bd_name','bd_tel','customer_addr','order_time','picking_time','stock_out_time','total_qty');
    protected $updateFields = array('code','wh_id','type','refer_code','notes','op_date','status','gennerate_method','created_user','created_time','packing_code','updated_user','line','updated_time','process_type','is_deleted','refused_code','total_amount','wave_code','shop_name','customer_name','customer_tel','bd_name','bd_tel','customer_addr','order_time','picking_time','stock_out_time','total_qty');
    protected $readonlyField = array('id');

    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
                array('wh_id','require','仓库不能为空',1,'regex',1),
                array('type','require','订单类型不能为空',1,'regex',1),
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
                'where'=>array('stock_bill_out.is_deleted'=>'0'),
                'order'=>'stock_bill_out.id DESC',
                "join"=>array(//"inner join stock_bill_out_detail sbod on stock_bill_out.id=sbod.pid",
                              "inner join stock_bill_out_type sbot on stock_bill_out.type = sbot.id"
                ),
                "field"=>"stock_bill_out.*,stock_bill_out.status as state,sbot.name as type_name "
                ),
            'latest'=>array(
                'where'=>array('stock_bill_out.is_deleted'=>'0'),
                'order'=>'update_time DESC',
                ),

            );
}
