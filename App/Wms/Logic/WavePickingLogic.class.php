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

        //获取 收货区 发货区 降级存储区 加工损耗区 加工区 库内报损区 下的库位信息
        $area_name = array('RECV','PACK','Downgrade','Loss','WORK','Breakage');
        $pack_location_infos = A('Location','Logic')->getPackLocationId($area_name);
        foreach($pack_location_infos as $pack_location_infos){
            $not_in_location_ids[] = $pack_location_infos['id'];
        }

        foreach($wave_ids as $wave_id){
            //是否是配送单创建波次
            $map['id'] = $wave_id;
            $res = M('stock_wave')->where($map)->find();
            $code_mark = ''; //配送单波此标志
            if (!empty($res['refer_code'])) {
                //配送单创建波此 用配送单号创建分拣任务
                $code_mark = $res['refer_code'];
                $this->order_max = $res['order_count'];
            } else {
                $this->order_max = 10;
            }
            unset($map);
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
            //遍历出库单id
            foreach($bill_out_ids as $bill_out_id){
                //根据bill_out_id 查询出库单信息
                $map['id'] = $bill_out_id['bill_out_id'];
                $bill_out_info = M('stock_bill_out')->where($map)->find();
                unset($map);
                
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
                //按照line_id 创建数组 OR 根据配送单号创建数组
                if (empty($code_mark)) {
                    $code_mark = $bill_out_info['lind_id'];
                } 
                if (!isset($result_arr[$code_mark])) {
                    $result_arr[$code_mark] = array();
                }
                //根据bill_out_id 查询出库单详情
                $map['pid'] = $bill_out_info['id'];
                $bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
                unset($map);
                //遍历出库单详情
                foreach($bill_out_detail_infos as $bill_out_detail_info){
                    //记录SKU种类数量
                    $result_arr[$code_mark]['pro_type_sum'][$bill_out_detail_info['pro_code']] = true;
                    //记录SKU总数
                    $result_arr[$code_mark]['pro_qty_sum'] += $bill_out_detail_info['order_qty'];
                    
                    //检查应当从哪个库位出库 并锁定库存量 assign_qty
                    $assign_stock_infos = A('Stock','Logic')->assignStockByFIFOWave(array('wh_id'=>session('user.wh_id'),'pro_code'=>$bill_out_detail_info['pro_code'],'pro_qty'=>$bill_out_detail_info['order_qty'],'not_in_location_ids'=>$not_in_location_ids));
                    
                    foreach($assign_stock_infos['data']['stock_info'] as $assign_stock_info){
                        //pro_code
                        $result_arr[$code_mark]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['pro_code'] = $bill_out_detail_info['pro_code'];
                        //数量
                        $result_arr[$code_mark]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['pro_qty'] += $assign_stock_info['qty'];
                        //批次
                        $result_arr[$code_mark]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['batch'] = $assign_stock_info['batch'];
                        //src_location_id
                        $result_arr[$code_mark]['detail'][$bill_out_detail_info['pro_code'].'_'.$assign_stock_info['location_id'].'_'.$assign_stock_info['batch']]['src_location_id'] = $assign_stock_info['location_id'];
                    }
                }
                //增加订单数量
                //$order_sum++;
                $result_arr[$code_mark]['order_sum']++;
                //记录订单id到bill_out_id
                $result_arr[$code_mark]['bill_out_ids'] .= $bill_out_info['id'].',';
                //纪录订单线路
                $result_arr[$code_mark]['line_id'] = $bill_out_info['line_id'];
                
                //把订单状态置为待拣货
                $data['status'] = 4;
                $map['id'] = $bill_out_info['id'];
                M('stock_bill_out')->where($map)->save($data);
                unset($map);
                unset($data);
                //处理分拣单 每个分拣单最多处理$order_max个订单
                $this->exec_order($result_arr,$wave_id);
            }
            //查询当前仓库的发货区的location_id
            $map['wh_id'] = session('user.wh_id');
            $map['code'] = 'PACK';
            $pack_info = M('Location')->where($map)->field('id')->find();
            unset($map);
            $map['pid'] = $pack_info['id'];
            $pack_location_info = M('Location')->where($map)->field('id')->find();
            $dest_location_id = $pack_location_info['id'];
            unset($map);
            
            //处理剩余的线路数据
            if (!empty($result_arr)) {
                foreach($result_arr as $line => $result){
                    $data['code'] = get_sn('picking');
                    $data['wave_id'] = $wave_id;
                    $data['type'] = 'picking';
                    $data['order_sum'] = $result['order_sum'];
                    $data['pro_type_sum'] = count($result['pro_type_sum']);
                    $data['pro_qty_sum'] = $result['pro_qty_sum'];
                    $data['line_id'] = $result['line_id'];
                    $data['wh_id'] = session('user.wh_id');
                    $data['bill_out_ids'] = substr($result['bill_out_ids'],0,strlen($result['bill_out_ids']) - 1);
                    $data['status'] = 'draft';
                    $data['is_print'] = 'OFF';
                    $wave_picking = D('WavePicking');
                    $data = $wave_picking->create($data);
                    foreach($result['detail'] as $val){
                        $v['pro_code'] = $val['pro_code'];
                        $v['pro_qty'] = $val['pro_qty'];
                        $v['batch'] = $val['batch'];
                        $v['src_location_id'] = $val['src_location_id'];
                        $v['dest_location_id'] = $dest_location_id;
                        $data['detail'][] = $v;
                    }
                    //创建分拣单
                    $wave_picking->relation('detail')->add($data);
                    //创建完毕后 把该线路的数据释放掉
                    unset($result_arr[$line]);
                }
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
    protected function exec_order(&$result_arr,$wave_id){
        //查询当前仓库的发货区的location_id
        $map['wh_id'] = session('user.wh_id');
        $map['code'] = 'PACK';
        $pack_info = M('Location')->where($map)->field('id')->find();
        unset($map);
        $map['pid'] = $pack_info['id'];
        $pack_location_info = M('Location')->where($map)->field('id')->find();
        $dest_location_id = $pack_location_info['id'];
        unset($map);
        //开始创建分拣单 按照线路
        foreach($result_arr as $line => $result){
            //如果某个线路上的订单处理了10个 开始创建一个分拣单
            if($result['order_sum'] >= $this->order_max){
                $data['code'] = get_sn('picking');
                $data['wave_id'] = $wave_id;
                $data['type'] = 'picking';
                $data['order_sum'] = $result['order_sum'];
                $data['pro_type_sum'] = count($result['pro_type_sum']);
                $data['pro_qty_sum'] = $result['pro_qty_sum'];
                $data['line_id'] = $result['line_id'];
                $data['wh_id'] = session('user.wh_id');
                $data['bill_out_ids'] = substr($result['bill_out_ids'],0,strlen($result['bill_out_ids']) - 1);
                $data['status'] = 'draft';
                $data['is_print'] = 'OFF';
                $wave_picking = D('WavePicking');
                $data = $wave_picking->create($data);
                foreach($result['detail'] as $val){
                    $v['pro_code'] = $val['pro_code'];
                    $v['pro_qty'] = $val['pro_qty'];
                    $v['batch'] = $val['batch'];
                    $v['src_location_id'] = $val['src_location_id'];
                    $v['dest_location_id'] = $dest_location_id;
                    $data['detail'][] = $v;
                }
                //创建分拣单
                $wave_picking->relation('detail')->add($data);
                //创建完毕后 把该线路的数据释放掉
                unset($result_arr[$line]);
            }
        }
    }
    //$code 分拣单code
    public function updateBiOuStock($code){
        //根据分拣单code 查到出库单
        $map = array();
        $detailW = array();
        $stockW = array();
        $stockSave = array();
        $packingW = array();
        $packSave = array();
        $map['code'] = $code;
        $m = M('stock_wave_picking');
        $pick_detail_m = M('stock_wave_picking_detail');
        $pick_detail_w = array();
        $wave_detail = M('stock_wave_detail');
        $bill_out_m = M('stock_bill_out');
        $wave_R = $m->field('wave_id,id,wh_id')->where($map)->find();
        if(!$wave_R['wave_id'] || !$wave_R['id'] || !$wave_R) return FALSE;
        $wave_id = $wave_R['wave_id'];
        $packing_id = $wave_R['id'];
        $wh_id = $wave_R['wh_id'];
        $packingW['code'] = $code;
        $packSave['status'] = 'done';
        if(!$m->where($packingW)->save($packSave)) return FALSE;
        $pick_detail_w['pid'] = $packing_id;
        $pick_detail_w['is_deleted'] = 0;
        $result = $pick_detail_m->field('pro_qty,pro_code,src_location_id,dest_location_id,batch')->where($pick_detail_w)->select();
        
        if(!$result) return FALSE;
        //扣库存和移动货物
        //@todo liang 修改库存
        foreach ($result as $key => $value) {
            $param = array();
            $param['variable_qty'] = $value['pro_qty'];
            $param['wh_id'] = $wh_id;
            $param['src_location_id'] = $value['src_location_id'];
            $param['dest_location_id'] = $value['dest_location_id'];
            $param['pro_code'] = $value['pro_code'];
            $param['batch'] = $value['batch'];
            $param['status'] = 'qualified';
            $param['change_src_assign_qty'] = '1';
            try{
                $res = A('Stock','Logic')->adjustStockByMove($param);
            }catch(Exception $e){
                continue;
            }
        }
        //判读该波次下得分拣单全部分拣完成，在改波次下得出库单状态为已复核
        /*$pickedW = array();
        $pickedW['wave_id'] = $wave_id;
        $pickedW['status'] = array('in','draft,picking'); 
        if($m->where($pickedW)->select()) return TRUE;   
        $detailW['pid'] = $wave_id;
        $wave_detailR = $wave_detail->field('bill_out_id')->where($detailW)->select();
        if(!$wave_detailR) return FALSE;
        $bill_outArr = getSubByKey($wave_detailR, 'bill_out_id');
        if(!$bill_outArr) return FALSE;
        $bill_out_idStr = implode(',', $bill_outArr);*/
        $pickedW = array();
        $pickedW['id'] = $packing_id;
        $pickedW['status'] = 'done'; 
        $bill_out_ids = $m->where($pickedW)->getField('bill_out_ids');
        if(!$bill_out_ids) return FALSE;
        //$bill_out_idStr = implode(',', $bill_outArr);
        $stockW['id'] = array('in', $bill_out_ids);
        $stockW['status'] = 4;
        $stockSave['status'] = 5;
        if(!$bill_out_m->where($stockW)->save($stockSave)) return FALSE;
        return TRUE;
    }
}
