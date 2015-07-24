<?php
namespace Wms\Model;
use Think\Model;
class TransferModel extends Model {
    protected $insertFields = array('id','trf_code','wh_id_out','wh_id_in','plan_cat_total','plan_qty_tobal','status','created_user','created_time','updated_user','updated_time','is_deleted');
    protected $updateFields = array('trf_code','wh_id_out','wh_id_in','plan_cat_total','plan_qty_tobal','status','updated_user','updated_time','is_deleted');
    protected $readonlyField = array('id');
    public $tableName='erp_transfer';
    //array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
        array('wh_id_out','require','出库仓库不能为空',1,'regex',3),
        array('wh_id_in','require','入库仓库不能为空',1,'regex',3),
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
            'where'=>array('erp_transfer.is_deleted'=>'0'),
            'order'=>'erp_transfer.id DESC',
            "join"=>array("inner join user u1 on erp_transfer.created_user = u1.id",
                "inner join user u2 on erp_transfer.updated_user = u2.id",
                "left join warehouse as w on erp_transfer.wh_id_out = w.id left join warehouse as w2 on erp_transfer.wh_id_in = w2.id"),
            "field"=>"erp_transfer.*,erp_transfer.status as state,w.name as out_name, w2.name as in_name,u1.nickname as created_user_nickname,u2.nickname as updated_user_nickname",
        ),
        'latest'=>array(
            'where'=>array('erp_transfer.is_deleted'=>'0'),
            'order'=>'erp_transfer.updated_time DESC',
        ),


    );

}