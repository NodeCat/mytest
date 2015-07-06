<?php

namespace Tms\Logic;

class ListLogic{

	public function storge(){
		$storge=M('Warehouse');
		$storge=$storge->field('name')->select();

		return $storge;
	}

	/*
     *功   能：根据配送单id查询客退入库单状态
     *输入参数：$dist_id,配送单id
    */
    public function view_return_goods_status($dist_id){
        $status = false;
        if(!empty($dist_id)) {
            unset($map);
            //查询条件为配送单id
            $map['stock_wave_distribution_detail.pid'] = $dist_id;
            //根据配送单id查配送详情单里与出库单相关联的出库单id
            $bill_out_id = M('stock_wave_distribution_detail')->field('bill_out_id')->where($map)->select();
            //若查出的出库单id非空
            if(!empty($bill_out_id)){   
                $bill_out_id = array_column($bill_out_id,'bill_out_id');
                unset($map);
                $map['refer_code'] = array('in',$bill_out_id); 
                $back_in = M('stock_bill_in')->where($map)->select();
                if(!empty($back_in)){
                    $status = true;
                }else{      //如果没有查到相应的客退入库单，直接返回FALSE
                    $status = false;
                }
            }  
        }
        return $status;
    }


}