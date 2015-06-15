<?php
namespace Wms\Logic;

class WavePickingLogic{
    /**波次运行
    * $wave_ids 波次id 数据
    */
    public function waveExec($wave_ids = array()){
        if(empty($wave_ids) || !is_array($wave_ids)){
        	return array('status'=>0,'msg'=>'参数有误！');
        }
        foreach($wave_ids as $wave_id){
        	//根据波次id查询 出库单id
        	$map['pid'] = $wave_id;
        	$bill_out_ids = M('stock_wave_detail')->where($map)->field('bill_out_id')->select();
        	unset($map);

        	if(empty($bill_out_ids)){
        		return array('status'=>0,'msg'=>'波次中的出库单不存在');
        	}

            //结果
            $tmp_arr = array();
            //订单数量
            $order_count = 0;
        	//遍历出库单id
        	foreach($bill_out_ids as $bill_out_id){
        		//根据bill_out_id 查询出库单信息
        		$map['id'] = $bill_out_id['bill_out_id'];
        		$bill_out_infos = M('stock_bill_out')->where($map)->select();
        		unset($map);

                
        		//遍历出库单
        		foreach($bill_out_infos as $bill_out_info){
                    //安装line_id 创建数组
                    if(!isset($tmp_arr[$bill_out_info['line_id']])){
                        $tmp_arr[$bill_out_info['line_id']] = array();
                    }

        			//根据bill_out_id 查询出库单详情
        			$map['pid'] = $bill_out_info['id'];
        			$bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
        			unset($map);

        			//遍历出库单详情
                    foreach($bill_out_detail_infos as $bill_out_detail_info){
                        //检查应当从哪个库位出库
                        $assign_stock_infos = A('Stock','Logic')->assignStockByFIFOWave(array('wh_id'=>session('user.wh_id'),'pro_code'=>$bill_out_detail_info['pro_code'],'pro_qty'=>$bill_out_detail_info['order_qty']));
                        foreach($assign_stock_infos['stock_info'] as $assign_stock_info){
                            var_dump($bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']);exit;
                            $tmp_arr[$bill_out_info['line_id']][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['pro_qty'] += $bill_out_detail_info['order_qty'];
                        }
                        
                        
                    }
        		}

        		//增加订单数量
                $order_count++;
                $tmp_arr[$bill_out_info['line_id']]['order_count'] = $order_count;
        	}
            var_dump($tmp_arr);exit;
        }

        return array('status'=>1);
    }
}