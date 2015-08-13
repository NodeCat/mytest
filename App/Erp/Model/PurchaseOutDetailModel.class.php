<?php
namespace Erp\Model;
use Think\Model;
class PurchaseOutDetailModel extends Model {

    protected $insertFields = array('id','wh_id','pro_code','pro_name','pro_attrs','batch_code','pro_uom','price_unit','plan_return_qty','real_return_qty','pid','created_user','created_time','is_deleted');
    protected $updateFields = array('wh_id','pro_code','pro_name','pro_attrs','batch_code','pro_uom','price_unit','plan_return_qty','real_return_qty','pid','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='stock_purchase_out_detail';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('wh_id','require','目标仓库不能为空',1,'regex',3),
        array('pid','require','采购退货单标示不能为空',1,'regex',3),
    );
   
    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
                array('created_user',UID,1,'string'),
                array('created_time','get_time',1,'function'),
                array('updated_user',UID,2,'string'),
                array('updated_time','get_time',2,'function'),
                array('is_deleted','0',1,'string'),
                array('status','0',1,'string'),
    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('stock_purchase_out_detail.is_deleted'=>'0'),
            'order'=>'stock_purchase_out_detail.id DESC',
            ),
        'latest'=>array(
            'where'=>array('stock_purchase_out_detail.is_deleted'=>'0'),
            'order'=>'updated_time DESC',
        ),


    );

}