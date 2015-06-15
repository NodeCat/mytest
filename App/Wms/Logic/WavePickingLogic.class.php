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

        	//遍历出库单id
        	foreach($bill_out_ids as $bill_out_id){
        		//根据bill_out_id 查询出库单信息
        		$map['id'] = $bill_out_id['bill_out_id'];
        		$bill_out_infos = M('stock_bill_out')->where($map)->select();
        		unset($map);

        		//遍历出库单
        		foreach($bill_out_infos as $bill_out_info){
        			//根据bill_out_id 查询出库单详情
        			$map['pid'] = $bill_out_info['id'];
        			$bill_out_detail_info = M('stock_bill_out_detail')->where($map)->select();
        			unset($map);

        			//遍历出库单详情
        			var_dump($bill_out_detail_info);exit;
        		}
        		

        	}

        }

        return array('status'=>1);
    }
}