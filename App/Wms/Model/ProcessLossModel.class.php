<?php
namespace Wms\Model;
use Think\Model;
class ProcessLossModel extends Model {
    protected $readonlyField = array('id');
    public $tableName = 'erp_process';

    protected $_scope = array(
        'default'=>array(
            'where'=>array('erp_process.is_deleted'=>'0'),
            'order'=>'erp_process.id DESC',
            'join'=>array(
                'INNER JOIN erp_process_detail ON erp_process_detail.pid=erp_process.id ',
                'INNER JOIN erp_process_sku_relation ON erp_process_sku_relation.p_pro_code=erp_process_detail.p_pro_code'
                ),
            'field'=>'erp_process_detail.id, erp_process.code, erp_process_detail.p_pro_name, erp_process_detail.p_pro_code, erp_process_sku_relation.c_pro_code, SUM(erp_process_detail.real_qty) as p_pro_num, erp_process_sku_relation.ratio',
            'group'=>'erp_process_detail.p_pro_code',
        ),
    );
}