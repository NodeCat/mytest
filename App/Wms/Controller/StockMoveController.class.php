<?php
namespace Wms\Controller;
use Think\Controller;
class StockMoveController extends CommonController {
    public function pdaStockMove() {
        if(IS_POST ) {
            $data = I('post.');
            if(empty($data['location_code']) || empty($data['pro_code'])) {
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
            $data['location_id'] = $location_id;
                        
            //获取产品信息
            $pro_codes = array($data['pro_code']);
            $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
            $data['pro_name'] = $pms[$data['pro_code']]['wms_name'];
            
            unset($map);
            $map['location_id'] = $location_id;
            $map['pro_code'] = $data['pro_code'];
            $stock_info_list = $stock->where($map)->select();
            
            //合并移库量
            $variable_qty = $stock->field('sum(stock_qty - assign_qty) as stock_qty')->group('pro_code')->where($map)->find();
            $data['variable_qty'] = $variable_qty['stock_qty'];
            
            $this->assign($data); 
            C('LAYOUT_NAME','pda');
		    $this->display('StockMove:'.'pdaStockMoveTo');
        }else{
             
            C('LAYOUT_NAME','pda');
		    $this->display('StockMove:'.'pdaStockMove');
        }

    }

    public function checkStockMove() {
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

        /*$map['location_id'] = $location_id;
        $map['pro_code'] = .....
        $stock_info = $stock->where($map)->field('pro_code,batch')->find();
        if(! $stock_info || $stock_info['pro_code'] != $data['pro_code'] || $stock_info['batch'] != $data['batch']) {
            $return['status'] = 0;
            $return['msg'] = '相关信息有误，请重新输入';
            $this->ajaxReturn($return);
        }*/

        //检查库位上pro_code是否存在
        $map['pro_code'] = $data['pro_code'];
        $map['location_id'] = $location_id;
        $stock_info = $stock->where($map)->find();
        if(empty($stock_info)){
            $return['status'] = 0;
            $return['msg'] = '相关信息有误，请重新输入';
            $this->ajaxReturn($return);
        }
       
        $return['status'] =1;
        $this->ajaxReturn($return);
    }

    public function pdaStock() {
        $data = I('post.');
        if(empty($data['wh_id']) || empty($data['location_id']) || empty($data['pro_code']) || empty($data['dest_location_code'])) {
            return false;
        }
        $location = M('location');
        $stock = M('stock');
      
        //查询目的库位的id和状态
        $map['wh_id'] = $data['wh_id'];
        $map['code'] = $data['dest_location_code'];
        $dest_location = $location->field('id,status')->where($map)->find();
        
        //查询相关库存的移库量
        /*unset($map);
        $map['wh_id'] = $data['wh_id'];
        $map['location_id'] = $data['location_id'];
        $map['pro_code'] = $data['pro_code'];
        $stock_info_list = $stock->field('stock_qty-assign_qty as variable_qty')->where($map)->select();
        */
        //组装数据
        $list['wh_id'] = $data['wh_id'];
        $list['src_location_id'] = $data['location_id'];
        $list['dest_location_id'] = $dest_location['id'];
        $list['pro_code'] = $data['pro_code'];
        $list['status'] = $dest_location['status'];

        $res = A('Stock', 'Logic')->checkLocationMixedProOrBatch($list);

        if($res['status'] == 0) {
           $this->error_msg = $res['msg'];
           C('LAYOUT_NAME','pda');
           $this->display('/StockMove/pdaStockMove'); 
           return;
        }
        
        $list['variable_qty'] = $data['variable_qty'];
        $list['dest_location_status'] = $dest_location['status'];
        $stock = A('Stock', 'Logic')->adjustStockByMoveNoBatchFIFO($list);
        //$stock = A('Stock', 'Logic')->adjustStockByMove($stock_info_list);
        if($stock['status'] == 0) {
           $this->error_msg = $stock['msg'];
           C('LAYOUT_NAME','pda');
           $this->display('/StockMove/pdaStockMove'); 
           return;
        }

        $this->msg = '操作成功';
        C('LAYOUT_NAME','pda');
        $this->display('/StockMove/pdaStockMove'); 
        //header('Location:/StockMove/pda_stock_move');
    }

}

