<?php
namespace Tms\Model;
use Think\Model\RelationModel;

class DistDetailModel extends RelationModel
{
    public $tableName = 'stock_wave_distribution_detail';
    protected $_link=array(
        'StockOut'=>array(
        'mapping_type'=>self::BELONGS_TO,
        'foreign_key'=>'bill_out_id',
        'mapping_fields'=>'refer_code,notes,customer_realname,customer_phone,customer_id,delivery_address,total_amount,total_qty,act_delivery_date',
        'as_fields'=>'refer_code,notes,customer_realname,customer_phone,customer_id,delivery_address,total_amount,total_qty,act_delivery_date',
        
        ),
    );
    
}

    

