<?php
namespace Erp\Model;
use Think\Model\RelationModel;
class StockInModel extends RelationModel {

    protected $insertFields = array('id','code','wh_id','type','company_id','refer_code','pid','batch_code','partner_id','remark','op_date','status','gennerate_method','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('code','wh_id','type','company_id','refer_code','pid','batch_code','partner_id','remark','op_date','status','gennerate_method','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='stock_bill_in';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
            array('wh_id','require','所属仓库不能为空',1,'regex',1),
            //array('company_id','require','所属系统不能为空',1,'regex',1),
            array('type','require','入库类型不能为空',1,'regex',1),
            //array('partner_id','require','供货商不能为空',1,'regex',1),
    );
    protected $_link = array(
        "Detail" => array(
            'mapping_type' => self::HAS_MANY, 
            'class_name' => 'StockBillInDetail',
            'foreign_key' => 'pid',
            'mapping_name' => 'detail',
        ),
        "Netail" => array(
        	    'mapping_type' => self::HAS_ONE,
            'class_name' => 'StockBillInDetail',
            'foreign_key' => 'pid',
            'mapping_name' => 'netail',
        ),
    );
    //array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto = array (
        array('created_user',UID,1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,1,'string'),
        array('updated_time','get_time',1,'function'),
        array('is_deleted','0',1,'string'),
        //array('status','0',1,'string'),

    );

    //'数据表字段'=>'表单字段'
    protected $_map = array(
        
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('stock_bill_in.is_deleted'=>'0'),
            'order'=>'stock_bill_in.id DESC',
            "join"=>array("inner join warehouse on stock_bill_in.wh_id=warehouse.id",
                "inner join company on stock_bill_in.company_id=company.id ",
                "left join partner on stock_bill_in.partner_id=partner.id ",
                "inner join user u on stock_bill_in.created_user = u.id",
                "inner join stock_bill_in_type t on stock_bill_in.type=t.id",
                "left join stock_purchase sp on stock_bill_in.refer_code = sp.code",
            ),
            "field"=>"stock_bill_in.*,stock_bill_in.status as state,warehouse.name as warehouse_name,company.name as company_name,sp.id as sp_id,
            u.nickname as sp_created_user_name,u.mobile as sp_created_user_mobile,sp.created_time as sp_created_time,sp.cat_total,sp.qty_total,t.name as type,partner.name as partner_name",            
        ),
        'latest'=>array(
            'where'=>array('stock_bill_in.is_deleted'=>'0'),
            'order'=>'update_time DESC',
        ),


    );
}