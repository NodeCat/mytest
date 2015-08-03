<?php
/**
 * User: san77
 * Date: 15/7/27
 * Time: 上午11:15
 */

namespace Wms\Model;

use Think\Model;

class RepertoryModel extends Model
{
    public $tableName = 'stock_snap';

    public $_scope = array(
        'default'=>array(
            'where'=>array('stock_snap.is_deleted'=>'0'),
            "join"=>array(
                "inner join warehouse on warehouse.id=stock_snap.wh_id ",
            ),
            "field"=>"stock_snap.id, stock_snap.pro_code, stock_snap.pro_name, stock_snap.batch, stock_snap.pro_uom, stock_snap.pro_attrs, stock_snap.category1, stock_snap.category2, stock_snap.category3, stock_snap.category_name1, stock_snap.category_name2, stock_snap.category_name3, warehouse.name as wh_name",
            "group"=>" stock_snap.pro_code ",
            "order"=>" stock_snap.pro_code desc",
        )
    );
}