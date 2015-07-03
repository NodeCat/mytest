<?php
namespace Wms\Logic;

class LocationLogic{

    public function getParentById($id) {
        $parent = M('location');
        $map['id'] = $id; 
        $pid = $parent->where($map)->getField('pid');
        
        unset($map);
        $map['id'] = $pid;
        $parent_info = $parent->where($map)->find();
        return $parent_info;
    }

    //根据区域标识，查询当前仓库的发货区的location_id
    public function getLocationIdByAreaName($area_name = array()){
        $map['wh_id'] = session('user.wh_id');
        $map['code'] = array('in',$area_name);
        $location_infos = M('Location')->where($map)->field('id')->select();
        unset($map);
        $location_id = array();
        foreach($location_infos as $location_info){
            $location_id[] = $location_info['id'];
        }

        $map['pid'] = array('in',$location_id);
        $area_location_infos = M('Location')->where($map)->field('id')->select();

        foreach($area_location_infos as $area_location_info){
            $location_ids[] = $area_location_info['id'];
        }

        return $location_ids;
    }
    
    /**
    * 根据出库单号 返回对应的库存区域标识
    * @param $bill_out_code 出库单号
    * return $location_area_name 库位区域标识名称
    */
    public function getAreaByBillCode($bill_out_code){
        //获取前缀
        $prefix = preg_replace('/\d/', '', $bill_out_code);

        $location_area_name = '';
        switch($prefix){
            //降级
            case 'DG':
                $location_area_name = 'Downgrade';
                break;
            //报废
            case 'BL':
                $location_area_name = 'Breakage';
                break;
            //加工
            case 'MNO':
                $location_area_name = 'WORK';
                break;
            default:
                break;
        }

        return $location_area_name;
    }
}
