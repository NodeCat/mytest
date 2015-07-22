<?php
namespace Wms\Model;
use Think\Model;
class TransferModel extends Model {
    protected $insertFields = array('id','wh_id','partner_id','status','out_remark','receivables_state','out_type','rtsg_code','refer_code','remark','created_user','created_time','is_deleted');
    protected $updateFields = array('wh_id','partner_id','status','out_remark','receivables_state','out_type','rtsg_code','refer_code','remark','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='stock_purchase_out';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('wh_id','require','目标仓库不能为空',1,'regex',3),
        array('partner_id','require','所属系统不能为空',1,'regex',3),
        array('rtsg_code','require','退货单号不能为空',1,'regex',3),
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
            'where'=>array('stock_purchase_out.is_deleted'=>'0'),
            'order'=>'stock_purchase_out.id DESC',
            "join"=>array("inner join warehouse on stock_purchase_out.wh_id=warehouse.id",
                "inner join user u1 on stock_purchase_out.created_user = u1.id",
                "inner join user u2 on stock_purchase_out.updated_user = u2.id",
                "left join partner on stock_purchase_out.partner_id = partner.id"),
            "field"=>"stock_purchase_out.*,stock_purchase_out.status as state,partner.name as partnername, warehouse.code as wh_code, warehouse.name as wh_name,u1.nickname as created_user_nickname,u2.nickname as updated_user_nickname",
        ),
        'latest'=>array(
            'where'=>array('stock_purchase_out.is_deleted'=>'0'),
            'order'=>'stock_purchase_out.updated_time DESC',
        ),


    );

}