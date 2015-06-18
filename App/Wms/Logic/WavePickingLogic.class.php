<?php
namespace Wms\Logic;

class WavePickingLogic{
    protected $order_max = 10; //每个线路每次处理最多订单数
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
            $map['is_deleted'] = 0;
        	$bill_out_ids = M('stock_wave_detail')->where($map)->field('bill_out_id')->select();
        	unset($map);

        	if(empty($bill_out_ids)){
        		return array('status'=>0,'msg'=>'波次中的出库单不存在');
        	}

            //结果
            $result_arr = array();
            //订单数量
            $order_sum = 0;
            //SKU种类数量
            $pro_type_sum = array();
            //SKU总数
            $pro_qty_sum = 0;
        	//遍历出库单id
        	foreach($bill_out_ids as $bill_out_id){
        		//根据bill_out_id 查询出库单信息
        		$map['id'] = $bill_out_id['bill_out_id'];
        		$bill_out_infos = M('stock_bill_out')->where($map)->select();
        		unset($map);
                
        		//遍历出库单
        		foreach($bill_out_infos as $bill_out_info){
                    $is_enough = true;
                    //查看出库单中所有sku是否满足数量需求
                    $is_enough = A('Stock','Logic')->checkStockIsEnoughByOrderId($bill_out_info['id']);
                    //如果不够 处理下一个订单
                    if(!$is_enough){
                        //把订单状态置为待生产 拒绝标识改为2 缺货
                        $data['status'] = 1;
                        $data['refused_type'] = 2;
                        $map['id'] = $bill_out_info['id'];
                        M('stock_bill_out')->where($map)->save($data);
                        unset($map);
                        unset($data);
                        continue;
                    }

                    //按照line_id 创建数组
                    if(!isset($result_arr[$bill_out_info['line_id']])){
                        $result_arr[$bill_out_info['line_id']] = array();
                    }

        			//根据bill_out_id 查询出库单详情
        			$map['pid'] = $bill_out_info['id'];
        			$bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
        			unset($map);


        			//遍历出库单详情
                    foreach($bill_out_detail_infos as $bill_out_detail_info){
                        //记录SKU种类数量
                        $pro_type_sum[$bill_out_detail_info['pro_code']] = true;
                        //记录SKU总数
                        $pro_qty_sum += $bill_out_detail_info['order_qty'];
                        
                        //检查应当从哪个库位出库
                        $assign_stock_infos = A('Stock','Logic')->assignStockByFIFOWave(array('wh_id'=>session('user.wh_id'),'pro_code'=>$bill_out_detail_info['pro_code'],'pro_qty'=>$bill_out_detail_info['order_qty']));
                        
                        foreach($assign_stock_infos['data']['stock_info'] as $assign_stock_info){
                            //pro_code
                            $result_arr[$bill_out_info['line_id']]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['pro_code'] = $bill_out_detail_info['pro_code'];
                            //数量
                            $result_arr[$bill_out_info['line_id']]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['pro_qty'] += $assign_stock_info['qty'];
                            //批次
                            $result_arr[$bill_out_info['line_id']]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['batch'] = $assign_stock_info['batch'];
                            //src_location_id
                            $result_arr[$bill_out_info['line_id']]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['src_location_id'] = $assign_stock_info['location_id'];
                        }
                    }

                    //增加订单数量
                    $order_sum++;
                    $result_arr[$bill_out_info['line_id']]['order_sum'] = $order_sum;
                    //统计SKU种类
                    $result_arr[$bill_out_info['line_id']]['pro_type_sum'] = count($pro_type_sum);
                    //统计SKU总数
                    $result_arr[$bill_out_info['line_id']]['pro_qty_sum'] = $pro_qty_sum;

                    //把订单状态置为待拣货
                    $data['status'] = 4;
                    $map['id'] = $bill_out_info['id'];
                    M('stock_bill_out')->where($map)->save($data);
                    unset($map);
                    unset($data);

                    //处理分拣单 每个分拣单最多处理$order_max个订单
                    $this->exec_order($result_arr);
                }        		
        	}
            
            //处理剩余的线路数据
            foreach($result_arr as $line => $result){
                $data['code'] = get_sn('picking');
                $data['wave_id'] = $wave_id;
                $data['type'] = 'picking';
                $data['order_sum'] = $result['order_sum'];
                $data['pro_type_sum'] = $result['pro_type_sum'];
                $data['pro_qty_sum'] = $result['pro_qty_sum'];
                $data['line_id'] = $line;
                $data['wh_id'] = session('user.wh_id');
                $data['status'] = 'draft';


                $wave_picking = D('WavePicking');
                $data = $wave_picking->create($data);

                foreach($result['detail'] as $val){
                    $v['pro_code'] = $val['pro_code'];
                    $v['pro_qty'] = $val['pro_qty'];
                    $v['batch'] = $val['batch'];
                    $v['src_location_id'] = $val['src_location_id'];
                    $v['dest_location_id'] = 0;
                    $data['detail'][] = $v;
                }

                //创建分拣单
                $wave_picking->relation('detail')->add($data);

                //创建完毕后 把该线路的数据释放掉
                unset($result_arr[$line]);
            }

            //更新波次的状态
            $data['status'] = 900;
            $map['id'] = $wave_id;
            M('stock_wave')->where($map)->save($data);
            unset($data);
            unset($map);
        }

        return array('status'=>1);
    }

    /**
    * 处理分拣单 每个分拣单最多处理$order_max个订单
    * @param
    * $result_arr
    */
    protected function exec_order(&$result_arr){
        //开始创建分拣单 按照线路
        foreach($result_arr as $line => $result){
            //如果某个线路上的订单处理了10个 开始创建一个分拣单
            if($result['order_sum'] >= $this->order_max){
                $data['code'] = get_sn('picking');
                $data['wave_id'] = $wave_id;
                $data['type'] = 'picking';
                $data['order_sum'] = $result['order_sum'];
                $data['pro_type_sum'] = $result['pro_type_sum'];
                $data['pro_qty_sum'] = $result['pro_qty_sum'];
                $data['line_id'] = $line;
                $data['wh_id'] = session('user.wh_id');
                $data['status'] = 'draft';


                $wave_picking = D('WavePicking');
                $data = $wave_picking->create($data);

                foreach($result['detail'] as $val){
                    $v['pro_code'] = $val['pro_code'];
                    $v['pro_qty'] = $val['pro_qty'];
                    $v['batch'] = $val['batch'];
                    $v['src_location_id'] = $val['src_location_id'];
                    $v['dest_location_id'] = 0;
                    $data['detail'][] = $v;
                }

                //创建分拣单
                $wave_picking->relation('detail')->add($data);

                //创建完毕后 把该线路的数据释放掉
                unset($result_arr[$line]);
            }
        }
    }
}














































