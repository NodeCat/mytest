<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveController extends CommonController {

    public function pda_stock_move() {
        if(IS_POST ) {
            $data = I('post.');
            if(empty($data['location_code']) || empty($data['pro_code'] || empty($data['batch']))) {
               return false; 
            }
            //获取用户登录的仓库ID 
            $wh_id = session('user.wh_id');
            
            $location = M('location');
            $stock = M('stock');
            //获取库位ID
            $map['code'] = $data['location_code'];
            $map['wh_id'] = $wh_id;
            $location_id = $location->where($map)->getField('id');
            $data['wh_id'] = $wh_id;
            //获取产品信息
            $pro_codes = array($data['pro_code']);
            $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
            $data['pro_name'] = $pms[$data['pro_code']]['wms_name'];
            
            unset($map);
            $map['id'] = $location_id;
            $map['pro_code'] = $data['pro_code'];
            $stock_info = $stock->where($map)->field('pro_code,batch')->find();
            $data['variable_qty'] = $stock_info['stock_qty'] - $stock_info['assign_qty'];
            //dump($data);exit; 
            $this->assign($data); 
            C('LAYOUT_NAME','pda');
		    $this->display('Stockmove:'.'pdaStockMoveTo');
        }else{
             
            C('LAYOUT_NAME','pda');
		    $this->display('Stockmove:'.'pdaStockMove');
        }

    }

    public function check_stock_move() {
        $data = I('post.');
        $stock = M('stock');
        $location = M('location');
        $map['type'] ='2'; 
        $map['code'] = $data['location_code']; 
        $location_id = $location->where($map)->getField('id');

        if(! $location_id) {
            $return['status'] = 0 ;
            $return['msg'] = '查无此库位，请重新输入';
			$this->ajaxReturn($return);
        }
        unset($map);
        $map['id'] = $location_id;
        $stock_info = $stock->where($map)->field('pro_code,batch')->find();
        if(! $stock_info || $stock_info['pro_code'] != $data['pro_code'] || $stock_info['batch'] != $data['batch']) {
            $return['status'] = 0;
            $return['msg'] = '相关信息有误，请重新输入';
            $this->ajaxReturn($return);
        }
       
        $return['status'] =1;
        $this->ajaxReturn($return);
    }

    public function pda_stock() {
        $data = I('post.');
        $location = M('location');
        $map['code'] = $data['location_code'];
        $map['wh_id'] = $data['wh_id'];
        $src_location_id = $location->where($map)->getField('id');
        $map['code'] = $data['dest_location_code'];
        $dest_location_id = $location->where($map)->getField('id');
        
        $list['wh_id'] = $data['wh_id'];
        $list['src_location_id'] = $src_location_id;
        $list['dest_location_id'] = $dest_location_id;
        $list['batch'] = $data['batch'];
        $list['variable_qty'] = $data['variable_qty'];
        
        $list_arr = array($list);

        $stock = A('Stock','Logic')->;

        //dump($dest_location_id);exit;
    }

}

