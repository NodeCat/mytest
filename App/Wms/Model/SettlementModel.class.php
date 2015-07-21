<?php
/**
 * User: san77
 * Date: 15/7/14
 * Time: 上午11:09
 */

namespace Wms\Model;
use Think\Model\RelationModel;


class SettlementModel  extends RelationModel {
    protected $insertFields = array('id','code','partner_id','total_amount','invoice','invoice_amount','created_user','created_time','updated_user','updated_time',
        'status', 'is_deleted');
    protected $readonlyField = array('id');
    public $tableName = 'erp_settlement';

    protected $_validate = array(
        array('code','require','结算单号不能为空',1,'regex',1),
        array('partner_id','require','供货商不能为空',1,'regex',1),
        array('total_amount','require','结算金额不能为空',1,'regex',1),
    );

    protected $_auto = array (
        array('created_user',UID,1,'string'),
        array('created_time','get_time',1,'function'),
        array('updated_user',UID,2,'string'),
        array('updated_time','get_time', 2,'function'),
        array('is_deleted','0',1,'string'),
        array('status','0',1,'string'),
    );

    protected $_scope = array(
        'default'=>array(
            'where'=>array('erp_settlement.is_deleted'=>'0'),
            "join"=>array(
                "inner join partner on erp_settlement.partner_id=partner.id ",
                "inner join user on erp_settlement.created_user = user.id ",
                "left join user as s_user on erp_settlement.settlement_user = s_user.id ",
                "left join user as a_user on erp_settlement.audited_user = a_user.id ",
            ),
            "field"=>"erp_settlement.id as id,erp_settlement.code as code,erp_settlement.created_time as created_time,user.nickname as created_user, partner.name as partner_name,erp_settlement.total_amount as total_amount,erp_settlement.status as status, erp_settlement.status as state, erp_settlement.settlement_time as settlement_time, s_user.nickname as settlement_user,erp_settlement.audited_time, a_user.nickname as audited_user, erp_settlement.invoice_amount as invoice_amount, erp_settlement.invoice as invoice ",
        )
    );
}