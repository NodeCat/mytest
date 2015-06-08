<?php
namespace Wms\Model;
use Think\Model\RelationModel;
class PurchaseRefundModel extends RelationModel {

    protected $insertFields = array('id','code','refer_code','type','wh_id','company_id','partner_id','invoice_method','price_total','paid_amount','for_paid_amount','cat_total','qty_total','invoice_status','picking_status','expecting_date','remark','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('id','code','refer_code','type','wh_id','company_id','partner_id','invoice_method','price_total','paid_amount','for_paid_amount','cat_total','qty_total','invoice_status','picking_status','expecting_date','remark','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='erp_purchase_refund';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('wh_id','require','目标仓库不能为空',1,'regex',1),
        array('company_id','require','所属系统不能为空',1,'regex',1),
        array('partner_id','require','供货商不能为空',1,'regex',1),
    );
    protected $_link = array(
        "Detail" => array(
            'mapping_type' => self::HAS_MANY, 
            'class_name'  => 'PurchaseRefundDetail',
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
            'where'=>array('erp_purchase_refund.is_deleted'=>'0'),
            'order'=>'erp_purchase_refund.id DESC',
            "join"=>array("inner join warehouse on erp_purchase_refund.wh_id=warehouse.id ",
                "inner join partner on erp_purchase_refund.partner_id=partner.id ",
                "inner join user on erp_purchase_refund.created_user = user.id ",
                "inner join company on erp_purchase_refund.company_id=company.id ",
                "left join stock_bill_in sbi on sbi.refer_code = erp_purchase_refund.code AND sbi.is_deleted = 0"
            ),
"field"=>"erp_purchase_refund.*,erp_purchase_refund.status as state,sbi.code as in_code,sbi.id as in_id,warehouse.code as warehouse_code,warehouse.name as warehouse_name,partner.name as partner_name,user.nickname as user_nickname,user.mobile as user_mobile,company.name as company_name",
        ),
        'latest'=>array(
            'where'=>array('erp_purchase_refund.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}