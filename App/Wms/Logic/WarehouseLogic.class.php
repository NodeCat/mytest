<?php
namespace Wms\Logic;

class WarehouseLogic
{

    //根据id获取仓库详细
    public function getWareHouseInfoById($id)
    {
        $result = '';
        if ($id) {
            $m = M('warehouse');
            $where['id'] = $id;
            $result = $m->where($where)->find();
        }
        return $result;
    }
}
