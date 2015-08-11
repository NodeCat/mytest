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
        $not_in_location_ids = A('Location','Logic')->getLocationIdByAreaName($area_name);
        
        //创建成功分拣任务数量
        $pickTaskSum      = 0;
        //创建成功分拣任务包含的总订单数
        $pickTaskOrderSum = 0;
        //因库存不足被打回的订单id
        $rejectOrderArr   = array(); 
        //SKU数量为0的订单
        $sumZero = array();
        foreach($wave_ids as $wave_id){
            //根据波次id查询 出库单id
            $map['pid'] = $wave_id;
            $map['is_deleted'] = 0;
            $bill_out_ids = M('stock_wave_detail')->where($map)->select();
            unset($map);
            if(empty($bill_out_ids)){
                return array('status'=>0,'msg'=>'波次中的出库单不存在');
            }
            $merge = array();
            foreach ($bill_out_ids as $refer_code) {
                if (empty($refer_code['refer_code'])) {
                    $merge = array();
                    $merge[] = $bill_out_ids;
                    break;
                } else {
                    $merge[$refer_code['refer_code']][] = $refer_code;
                }
            }
            //结果
            $result_arr = array();
            //订单数量
            $order_sum = 0;
            //按配送单分组创建分拣单
            foreach ($merge as $key => $dist_group) {
                $code_mark = ''; //分拣单标志
                if (!empty($key)) {
                    //配送单创建波此 用配送单号创建分拣任务
                    $code_mark = $key;
                    $this->order_max = count($dist_group);
                } else {
                    $this->order_max = 10;
                }
                //这个车单下是否有订单创建了分拣任务
                $orderSumTask = 0;
                //遍历出库单id
                $continue_num = 0; //已经循环次数
                $dist_group_long = count($dist_group); //循环数组长度
                foreach($dist_group as $bill_out_id){
                    $continue_num++;
                    //根据bill_out_id 查询出库单信息
                    $map['id'] = $bill_out_id['bill_out_id'];
                    $bill_out_info = M('stock_bill_out')->where($map)->find();
                    unset($map);
                    
                    $is_continue_qty = false;
                    $is_continue_stock = false;

                    //用于多种类型出库单库存判断扩展 加入采购正品退货liuguangping
                    $distribution_logic = A('Distribution','Logic');
                    $keys = $bill_out_info['type'];
                    $type = $distribution_logic->get_stock_bill_out_type($keys);
                    $batch_codeS = null;
                    if ($type[$keys] == 'RTSG') {
                        $batch_codeArr = M('stock_bill_out_detail')->field('batch_code')->where(array('pid'=>$bill_out_info['id']))->find();
                        if($batch_codeArr){
                            $batch_codeS = $batch_codeArr['batch_code'];
                        }
                        
                    }
                    //根据bill_out_id 查询出库单详情
                    $map['pid'] = $bill_out_info['id'];
                    $bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();
                    //优先判断出库单详情中SKU数量是否全部大于0
                    $sku_pro_qty = 0; //出库SKU总数
                    foreach ($bill_out_detail_infos as $bill_out_detail_info_pro_qty) {
                        $sku_pro_qty += $bill_out_detail_info_pro_qty['order_qty'];
                    }
                    if ($sku_pro_qty <= 0) {
                        $sumZero[] = $bill_out_info['id'];
                        //此出库单下SKU出库数量全部为0
                        //获取车单ID
                        $distribution_id = M('stock_wave_distribution')->where(array('dist_code' => $key))->getField('id');
                        //将出库单从波次中踢出
                        M('stock_wave_detail')->where(array('id' => $bill_out_id['id']))->save(array('is_deleted' => 1));
                        //更新波次总单数
                        M('stock_wave')->where(array('id' => $wave_id))->setDec('order_count');
                        //更新波次总行数
                        $line_count_out = M('stock_bill_out_detail')->where(array('pid' => $bill_out_info['id']))->select();
                        $line_count_out_sum = count($line_count_out);
                        M('stock_wave')->where(array('id' => $wave_id))->setDec('line_count', $line_count_out_sum);
                        //关闭出库单 并踢出车单
                        M('stock_wave_distribution_detail')->where(array('pid' => $distribution_id, 'bill_out_id' => $bill_out_id['bill_out_id']))->save(array('is_deleted' => 1));
                        M('stock_bill_out')->where(array('id' => $bill_out_id['bill_out_id']))->save(array('status' => 18));
                        
                        //更新车单信息
                        D('Distribution', 'Logic')->updDistInfoByIds(array($distribution_id));
                        //总数量-1
                        $this->order_max = $this->order_max - 1;
                        if ($continue_num < $dist_group_long) {
                            continue;
                        } else {
                            $is_continue_qty = true;
                        }
                    }
                    //查看出库单中所有sku是否满足数量需求
                    if ($is_continue_qty == false) {
                        $is_enough = A('Stock','Logic')->checkStockIsEnoughByOrderId($bill_out_info['id'],null,$batch_codeS);
                    } else {
                        $is_enough['status'] = 1;
                    }
                    //如果不够 处理下一个订单
                    if($is_enough['status'] == 0){
                        //记录出库单ID
                        $rejectOrderArr[] = $bill_out_info['id'];
                        $data['status'] = 1;
                        //$data['refused_type'] = 2;
                        $map['id'] = $bill_out_info['id'];
                        M('stock_bill_out')->where($map)->save($data);
                        unset($map);
                        unset($data);
                        //将此订单踢出此波次 库存充足时 可加入其他波次继续分拣
                        $data['is_deleted'] = 1;
                        $map['bill_out_id'] = $bill_out_info['id'];
                        $map['pid'] = $wave_id;
                        M('stock_wave_detail')->where($map)->save($data);
                        unset($map);
                        unset($data);
                        //把订单 拒绝标识改为2 缺货 缺货详情记录到到货单的备注中
                        A('Distribution', 'Logic')->getReduceSkuCodesAndUpdate(array($bill_out_info['id']));
                        //更新波次总单数
                        M('stock_wave')->where(array('id' => $wave_id))->setDec('order_count');
                        //更新波次总行数
                        $line_count_reduce = M('stock_bill_out_detail')->where(array('pid' => $bill_out_info['id']))->select();
                        $line_count_out_reduce_sum = count($line_count_reduce);
                        M('stock_wave')->where(array('id' => $wave_id))->setDec('line_count', $line_count_out_reduce_sum);
                        //更新总件数
                        $order_max_qty_sum = 0;
                        $order_max_qty = M('stock_wave_detail')->where(array('pid' => $wave_id, 'is_deleted' => 0))->select();
                        foreach ($order_max_qty as $detail_bill_out_id) {
                            $detail_info = M('stock_bill_out_detail')->where(array('pid' => $detail_bill_out_id['bill_out_id']))->select();
                            foreach ($detail_info as $detail_order_qty) {
                                $order_max_qty_sum += $detail_order_qty['order_qty'];
                            }
                        }
                        M('stock_wave')->where(array('id' => $wave_id))->save(array('order_qty' => $order_max_qty_sum));
                        if ($continue_num < $dist_group_long) {
                            continue;
                        } else {
                            $is_continue_stock = true;
                        }
                    }

                    //按照line_id 创建数组 OR 根据配送单号创建数组
                    if ($is_continue_qty != true && $is_continue_stock != true) {
                        if (empty($code_mark)) {
                            $code_mark = $bill_out_info['lind_id'];
                        } 
                        if (!isset($result_arr[$code_mark])) {
                            $result_arr[$code_mark] = array();
                        }
                    }
                    unset($map);
                    //遍历出库单详情
                    if ($is_continue_stock != true && $is_continue_qty != true) {
                        foreach($bill_out_detail_infos as $bill_out_detail_info){
                            //记录SKU种类数量
                            $result_arr[$code_mark]['pro_type_sum'][$bill_out_detail_info['pro_code']] = true;
                            //记录SKU总数
                            $result_arr[$code_mark]['pro_qty_sum'] += $bill_out_detail_info['order_qty'];
                            
                            //检查应当从哪个库位出库 并锁定库存量 assign_qty
                            //用于多种类型出库单库存判断扩展 加入采购正品退货liuguangping
                            $param = array();
                            $param = array(
                                'wh_id'=>session('user.wh_id'),
                                'pro_code'=>$bill_out_detail_info['pro_code'],
                                'pro_qty'=>$bill_out_detail_info['order_qty'],
                                'not_in_location_ids'=>$not_in_location_ids,
                                'batch_code'=>$batch_codeS
                                );
                            
                            $assign_stock_infos = A('Stock','Logic')->assignStockByFIFOWave($param);
                            
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
                        //订单量自增
                        $orderSumTask++;
                        $pickTaskOrderSum++;
                    } elseif (!empty($result_arr)) {
                        //处理分拣单 每个分拣单最多处理$order_max个订单
                        $this->exec_order($result_arr,$wave_id);
                    }
                }
                if ($orderSumTask > 0) {
                    //分拣任务量自增
                    $pickTaskSum++;
                }
            }
            //查询当前仓库的发货区的location_id
            $map['wh_id'] = session('user.wh_id');
            $map['code'] = 'PACK';
            $pack_info = M('location')->where($map)->field('id')->find();
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
                        $v['created_user'] = session('user.uid');
                        $v['created_time'] = date('Y-m-d H:i:s');
                        $v['updated_user'] = session('user.uid');
                        $v['updated_time'] = date('Y-m-d H:i:s');
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
        $hintInfo = array(
        	   'tasksum'  => $pickTaskSum,
           'ordersum' => $pickTaskOrderSum,
           'orderids' => $rejectOrderArr,
           'sumZero'  => $sumZero,
        );
        return array('status'=>1, 'alert' => $hintInfo);
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
                    $v['created_user'] = session('user.uid');
                    $v['created_time'] = date('Y-m-d H:i:s');
                    $v['updated_user'] = session('user.uid');
                    $v['updated_time'] = date('Y-m-d H:i:s');
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
            $param['refer_code'] = $code;
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
