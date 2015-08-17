<?php
namespace Wms\Logic;

class StockLogic{
    /**
    * 波次生产，检查出库单中所有sku是否满足数量需求
    * @param
    * $order_id
    **/
    public function checkStockIsEnoughByOrderId($order_id, $loc_type = null, $batch_code = null){
        if(empty($order_id)){
            return false;
        }

        //根据pid查询bill_out_detail
        $map['pid'] = $order_id;
        $bill_out_detail_infos = M('stock_bill_out_detail')->where($map)->select();

        //判断每条sku是否够用
        $is_enough = true;

        //获取 收货区 发货区 降级存储区 加工损耗区 加工区 库内报损区 下的库位信息
        $area_name = array('RECV','PACK','Downgrade','Loss','WORK','Breakage');
        $not_in_location_ids = A('Location','Logic')->getLocationIdByAreaName($area_name);
        if ($loc_type != null) {
            $area_name = array($loc_type);
            $location_ids = A('Location','Logic')->getLocationIdByAreaName($area_name);
        }
        foreach($bill_out_detail_infos as $bill_out_detail_info){
            $data['wh_id'] = session('user.wh_id');
            $data['pro_code'] = $bill_out_detail_info['pro_code'];
            $data['pro_qty'] = $bill_out_detail_info['order_qty'];
            if ($loc_type == null) {
                $data['not_in_location_ids'] = $not_in_location_ids;
            } else {
                //指定库位检查
                $data['location_ids'] = $location_ids;
            }
            //加入批次条件满足采购退货 liuguangping
            $data['batch_code'] = $batch_code;
            $check_re = $this->outStockBySkuFIFOCheck($data);
            if($check_re['status'] != 1){
                $is_enough = false;
                $not_enough_pro_code[] = $check_re['data']['pro_code'];
            }
            unset($data);

        }

        //如果不够 直接返回
        if(!$is_enough){
            return array('status'=>0,'data'=>array('not_enough_pro_code'=>$not_enough_pro_code));
        }

        return array('status'=>1);
    }

    /**
    * 波次生产，返回应该从哪个库位出货，按照先进先出原则
    * @param
    * $wh_id
    * $pro_code 
    * $pro_qty
    * $not_in_location_ids 过滤掉某些库位id
    */
    public function assignStockByFIFOWave($params = array()){
        if(empty($params['wh_id']) || empty($params['pro_code'])){
            return array('status'=>0,'msg'=>'参数有误！');
        }

        $diff_qty = $params['pro_qty'];

        //根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
        $map['pro_code'] = $params['pro_code'];
        $map['wh_id'] = $params['wh_id'];
        //目前只出合格商品
        $map['stock.status'] = 'qualified';
        //过滤某些仓库id
        if(!empty($params['not_in_location_ids'])){
            $map['location_id'] = array('not in',$params['not_in_location_ids']);
        }

        //加入批次条件满足采购退货 liuguangping
        if($params['batch_code']){
            $map['stock.batch'] = $params['batch_code'];
        }

        $stock_list = M('Stock')->where($map)->order('product_date')->select();
        unset($map);

        //检查所有的 库存量 是否满足 出库量
        /*foreach($stock_list as $stock){
            $stock_total += $stock['stock_qty'] - $stock['assign_qty'];
        }

        //是否有足够的货
        $is_enough = true;
        if($stock_total < $params['pro_qty']){
            $is_enough = false;
        }

        $return['is_enough'] = $is_enough;
        */

        $diff_qty = intval($diff_qty);

        //按照现进先出原则 锁定库存量 assign_qty
        foreach($stock_list as $key=>$stock){
            //可用量
            $stock_available = $stock['stock_qty'] - $stock['assign_qty'];
            if($stock_available <= 0){
                continue;
            }
            if($diff_qty > 0){
                //可用量小于等于差异量
                if($stock_available <= $diff_qty){
                    //获取此次销库存的相关信息
                    $return['stock_info'][$key]['location_id'] = $stock['location_id'];
                    $return['stock_info'][$key]['batch'] = $stock['batch'];
                    $return['stock_info'][$key]['qty'] = $stock_available;

                    $map['id'] = $stock['id'];
                    $data['assign_qty'] = $stock['assign_qty'] + $stock_available;
                    M('stock')->where($map)->data($data)->save();
                    unset($map);
                    unset($data);

                    $diff_qty = $diff_qty - $stock_available;

                //可用量大于差异量
                }else{
                    //返回销库存的相关信息
                    $return['stock_info'][$key]['location_id'] = $stock['location_id'];
                    $return['stock_info'][$key]['batch'] = $stock['batch'];
                    $return['stock_info'][$key]['qty'] = $diff_qty;

                    //根据id 更新库存表
                    $map['id'] = $stock['id'];
                    $data['assign_qty'] = $stock['assign_qty'] + $diff_qty;
                    M('stock')->where($map)->data($data)->save();
                    unset($map);
                    unset($data);

                    break;
                }

            }else{

                break;
                
            }
        }

        
        return array('status'=>1, 'data'=>$return);
    }

    /**
     * 检查是否可以一键出库，按照先进先出原则 
     * @param 
     * $wh_id 仓库id
     * $pro_code sku编号
     * $pro_qty 产品数量
     * $refer_code 出库单号
     * $location_ids 指定从哪个库位上检查
     * $not_in_location_ids 过过滤掉哪个库位
     * )
     */
    public function outStockBySkuFIFOCheck($params = array()){
        if(empty($params['wh_id']) || empty($params['pro_code'])){
            return array('status'=>0,'msg'=>'参数有误！');
        }

        if($params['pro_qty'] == 0){
            return array('status'=>1);
        }

        $diff_qty = $params['pro_qty'];

        //根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
        $map['pro_code'] = $params['pro_code'];
        $map['wh_id'] = $params['wh_id'];
        $map['stock.status'] = 'qualified';

        //过滤某些仓库id
        if(!empty($params['not_in_location_ids'])){
            $map['location_id'] = array('not in',$params['not_in_location_ids']);
        }

        //指定从哪个库位上检查
        if($params['location_ids']){
            $map['location_id'] = array('in',$params['location_ids']);
            //查询目标库位的状态 并替换掉要查询的库存的status
            $location_map['id'] = $params['location_ids'][0];
            $location_status = M('Location')->where($location_map)->find();
            $map['stock.status'] = $location_status['status'];
            unset($location_map);
        }

        //加入批次条件满足采购退货 liuguangping
        if($params['batch_code']){
            $map['stock.batch'] = $params['batch_code'];
        }
        $stock_list = M('Stock')->where($map)->order('product_date')->select();
        unset($map);

        //检查所有的 库存量 是否满足 出库量
        foreach($stock_list as $stock){
            $stock_total += $stock['stock_qty'] - $stock['assign_qty'];
        }

        if($stock_total < formatMoney($params['pro_qty'], 2)){
            return array('status'=>0,'msg'=>'库存总量不足！','data'=>array('pro_code'=>$params['pro_code']));
        }

        return array('status'=>1);
    }
    /**
     * 一键出库，按照先进先出原则 减少库存 如果库存不够 则返回失败
     * @param 
     * $wh_id 仓库id
     * $pro_code sku编号
     * $pro_qty 产品数量
     * $refer_code 出库单号
     * $location_ids 指定从哪个库位上出库
     * )
     */
    public function outStockBySkuFIFO($params = array()){
        if(empty($params['wh_id']) || empty($params['pro_code'])){
            return array('status'=>0,'msg'=>'参数有误！');
        }

        $diff_qty = $params['pro_qty']; 

        //根据pro_code location_id 查询库存stock 按照batch排序，最早的批次在前面
        $map['pro_code'] = $params['pro_code'];
        $map['wh_id'] = $params['wh_id'];
        //目前只出合格商品
        $map['stock.status'] = 'qualified';
        //指定从哪个库位上出库
        if($params['location_ids']){
            $map['location_id'] = array('in',$params['location_ids']);
            //查询目标库位的状态 并替换掉要查询的库存的status
            $location_map['id'] = $params['location_ids'][0];
            $location_status = M('Location')->where($location_map)->find();
            $map['stock.status'] = $location_status['status'];
            unset($location_map);
        }
        $stock_list = M('Stock')->where($map)->order('product_date')->select();
        unset($map);

        //检查所有的 库存量 是否满足 出库量
        foreach($stock_list as $stock){
            $stock_total += $stock['stock_qty'] - $stock['assign_qty'];
        }

        if($stock_total < $params['pro_qty']){
            return array('status'=>0,'msg'=>'库存总量不足！');
        }

        $diff_qty = formatMoney($diff_qty, 2);
        
        //按照现进先出原则 减去最早的批次量
        foreach($stock_list as $key=>$stock){
            if($diff_qty > 0){
                //如果库存量小于等于差异量 则删除该条库存记录 然后减去差异量diff_qty
                if($stock['stock_qty'] < $diff_qty){
                    //获取此次销库存的相关信息
                    $return[$key]['location_id'] = $stock['location_id'];
                    $return[$key]['batch'] = $stock['batch'];
                    $return[$key]['qty'] = $stock['stock_qty'];

                    $map['id'] = $stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);

                    //通知实时库存接口
                    $notice_params['wh_id'] = $params['wh_id'];
                    $notice_params['pro_code'] = $params['pro_code'];
                    $notice_params['type'] = '';
                    $notice_params['qty'] = $stock['stock_qty'];
                    A('Dachuwang','Logic')->notice_stock_update($notice_params);
                    unset($notice_params);

                    //写入出库详情表
                    $stock_container_data = array(
                        'refer_code' => $params['refer_code'],
                        'pro_code' => $params['pro_code'],
                        'batch' => $stock['batch'],
                        'wh_id' => $params['wh_id'],
                        'location_id' => $stock['location_id'],
                        'qty' => $stock['stock_qty'],
                        );
                    $stock_container = D('stock_bill_out_container');
                    $stock_container_data = $stock_container->create($stock_container_data);
                    $stock_container->data($stock_container_data)->add();
                    unset($stock_container_data);

                    $diff_qty = $diff_qty - $stock['stock_qty'];
                    $log_qty = $stock['stock_qty'];
                    $log_old_qty = $stock['stock_qty'];
                    $log_new_qty = 0;
                    
                    //写入库存交易日志
                    $stock_move_data = array(
                        'wh_id' => session('user.wh_id'),
                        'location_id' => $stock['location_id'],
                        'pro_code' => $stock['pro_code'],
                        'type' => 'ship',
                        'refer_code' => $params['refer_code'],
                        'direction' => 'OUTPUT',
                        'move_qty' => $log_qty,
                        'old_qty' => $log_old_qty,
                        'new_qty' => $log_new_qty,
                        'batch' => $stock['batch'],
                        'status' => $stock['status'],
                        );
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();
                    unset($log_qty);
                    unset($log_old_qty);
                    unset($log_new_qty);
                    unset($stock_move_data);

                }elseif($stock['stock_qty'] == $diff_qty){
                    //返回销库存的相关信息
                    $return[$key]['location_id'] = $stock['location_id'];
                    $return[$key]['batch'] = $stock['batch'];
                    $return[$key]['qty'] = $stock['stock_qty'];

                    $map['id'] = $stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);

                    //通知实时库存接口
                    $notice_params['wh_id'] = $params['wh_id'];
                    $notice_params['pro_code'] = $params['pro_code'];
                    $notice_params['type'] = '';
                    $notice_params['qty'] = $stock['stock_qty'];
                    A('Dachuwang','Logic')->notice_stock_update($notice_params);
                    unset($notice_params);

                    //写入出库详情表
                    $stock_container_data = array(
                        'refer_code' => $params['refer_code'],
                        'pro_code' => $params['pro_code'],
                        'batch' => $stock['batch'],
                        'wh_id' => $params['wh_id'],
                        'location_id' => $stock['location_id'],
                        'qty' => $stock['stock_qty'],
                        );
                    $stock_container = D('stock_bill_out_container');
                    $stock_container_data = $stock_container->create($stock_container_data);
                    $stock_container->data($stock_container_data)->add();
                    unset($stock_container_data);

                    $diff_qty = $diff_qty - $stock['stock_qty'];
                    $log_qty = $stock['stock_qty'];
                    $log_old_qty = $stock['stock_qty'];
                    $log_new_qty = 0;

                    //写入库存交易日志
                    $stock_move_data = array(
                        'wh_id' => session('user.wh_id'),
                        'location_id' => $stock['location_id'],
                        'pro_code' => $stock['pro_code'],
                        'type' => 'ship',
                        'refer_code' => $params['refer_code'],
                        'direction' => 'OUTPUT',
                        'move_qty' => $log_qty,
                        'old_qty' => $log_old_qty,
                        'new_qty' => $log_new_qty,
                        'batch' => $stock['batch'],
                        'status' => $stock['status'],
                        );
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();
                    unset($log_qty);
                    unset($log_old_qty);
                    unset($log_new_qty);
                    unset($stock_move_data);

                    break;
                }else{
                    //返回销库存的相关信息
                    $return[$key]['location_id'] = $stock['location_id'];
                    $return[$key]['batch'] = $stock['batch'];
                    $return[$key]['qty'] = $diff_qty;
                    //根据id 更新库存表
                    $map['id'] = $stock['id'];
                    $log_qty = $diff_qty;
                    $log_old_qty = $stock['stock_qty'];
                    $data['stock_qty'] = $stock['stock_qty'] - $diff_qty;
                    $log_new_qty = $data['stock_qty'];
                    M('stock')->where($map)->data($data)->save();
                    unset($map);
                    unset($data);


                    //通知实时库存接口
                    $notice_params['wh_id'] = $params['wh_id'];
                    $notice_params['pro_code'] = $params['pro_code'];
                    $notice_params['type'] = '';
                    $notice_params['qty'] = $diff_qty;
                    A('Dachuwang','Logic')->notice_stock_update($notice_params);
                    unset($notice_params);

                    //写入出库详情表
                    $stock_container_data = array(
                        'refer_code' => $params['refer_code'],
                        'pro_code' => $params['pro_code'],
                        'batch' => $stock['batch'],
                        'wh_id' => $params['wh_id'],
                        'location_id' => $stock['location_id'],
                        'qty' => $diff_qty,
                        );
                    $stock_container = D('stock_bill_out_container');
                    $stock_container_data = $stock_container->create($stock_container_data);
                    $stock_container->data($stock_container_data)->add();
                    unset($stock_container_data);

                    //写入库存交易日志
                    $stock_move_data = array(
                        'wh_id' => session('user.wh_id'),
                        'location_id' => $stock['location_id'],
                        'pro_code' => $stock['pro_code'],
                        'type' => 'ship',
                        'refer_code' => $params['refer_code'],
                        'direction' => 'OUTPUT',
                        'move_qty' => $log_qty,
                        'old_qty' => $log_old_qty,
                        'new_qty' => $log_new_qty,
                        'batch' => $stock['batch'],
                        'status' => $stock['status'],
                        );
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();
                    unset($log_qty);
                    unset($log_old_qty);
                    unset($log_new_qty);
                    unset($stock_move_data);

                    break;
                }
            }
        }

        
        return array('status'=>1, 'data'=>$return);
    }

    /**
     * 入库上架时，库存表变化，调整库存量
     * @param 
     * $wh_id 仓库id
     * $location_id 库位id
     * $refer_code 关联单号
     * $pro_code sku编号
     * $pro_qty 产品数量
     * $pro_uom 产品计量单位
     * $status 库存状态
     * $product_date 生产日期
     * )
     */
    public function adjustStockByShelves($wh_id,$location_id,$refer_code,$batch,$pro_code,$pro_qty,$pro_uom,$status,$product_date,$inId,$batch_bak = ''){
        $stock = D('stock');
        //增加库存
        $row['wh_id'] = $wh_id;
        $row['location_id'] = $location_id;
        $row['pro_code'] = $pro_code;
        $row['batch'] = $batch;
        $row['status'] =$status;
        
        $stock_info = $stock->where($row)->find();
        
        if(empty($stock_info)) {
            $row['prepare_qty'] = 0;
            $row['stock_qty'] = $pro_qty;
            $row['assign_qty'] = 0;
            //增加生产日期
            $row['product_date'] = (empty($product_date)) ? date('Y-m-d') : $product_date;
            
            $data = $stock->create($row);

            $res = $stock->add($data);

            $log_old_qty = 0;
            $log_new_qty = $pro_qty;
            unset($data);
        }
        else{
            $log_old_qty = $stock_info['stock_qty'];
            $log_new_qty = $stock_info['stock_qty'] + $pro_qty;

            //增加数量
            $map['id'] = $stock_info['id'];
            $data['stock_qty'] = $stock_info['stock_qty'] + $pro_qty;
            $data = $stock->create($data,2);
            $res = $stock->where($map)->save($data);
            unset($data);

            //是否修改生产日期 暂定每个批次只有一个生产日期 如果有不同 取最早的生产日期
            if(strtotime($stock_info['product_date']) > strtotime($product_date) || $stock_info['product_date'] == '0000-00-00 00:00:00'){
                $data['product_date'] = (empty($product_date)) ? date('Y-m-d') : $product_date;
                $data = $stock->create($data,2);
                $res = $stock->where($map)->save($data);
                unset($data);
            }
            unset($map);
        }
        if($res == false) {
            return false;
        }
        unset($row);

        //减待上架库存 增加已上量
        $map['pid'] = $inId;
        $map['pro_code'] = $pro_code;
        //$map['pro_uom'] = $pro_uom;
        if ($batch_bak) {
            $map['batch'] = $batch;
        }
        M('stock_bill_in_detail')->where($map)->setDec('prepare_qty',$pro_qty);
        M('stock_bill_in_detail')->where($map)->setInc('done_qty',$pro_qty);
        M('stock_bill_in_detail')->where($map)->setInc('qualified_qty',$pro_qty);
        unset($map);

        //通知实时库存接口
        $notice_params['wh_id'] = $wh_id;
        $notice_params['pro_code'] = $pro_code;
        $notice_params['type'] = '';
        $notice_params['qty'] = $pro_qty;
        A('Dachuwang','Logic')->notice_stock_update($notice_params);
        unset($notice_params);

        //写入库存交易日志
        $stock_move_data = array(
            'wh_id' => $wh_id,
            'location_id' => $location_id,
            'pro_code' => $pro_code,
            'type' => 'putaway',
            'refer_code' => $refer_code,
            'direction' => 'INPUT',
            'move_qty' => $pro_qty,
            'old_qty' => $log_old_qty,
            'new_qty' => $log_new_qty,
            'batch' => $batch,
            'status' => $status,
            );
        $stock_move = D('StockMoveDetail');
        $stock_move_data = $stock_move->create($stock_move_data);
        $stock_move->data($stock_move_data)->add();

        return true;
    }

    /**
    * 移库操作 库存表变化，调整库存量 没有批次参数 按照批次先进先出
    * @param 
    * $params = array(
    *     'variable_qty' => 80,
    *     'wh_id'=>'xxx',
    *    'src_location_id'=>xxxx,
    *    'dest_location_id'=>xxxx,
    *    'pro_code'=>xxxxx,
    *    'dest_location_status'=>xxxx,
    * )
    *
    */
    public function adjustStockByMoveNoBatchFIFO($param = array()){
        if($param['variable_qty'] == 0 || 
            empty($param['wh_id']) || 
            empty($param['src_location_id']) || 
            empty($param['dest_location_id']) || 
            empty($param['pro_code']) || 
            empty($param['dest_location_status']) ){

            //添加错误信息
            return array('status'=>0,'msg'=>'参数有误');
        }
        //查询目标库位上是否有商品
        $map['location_id'] = $param['dest_location_id'];
        $map['pro_code'] = $param['pro_code'];
        $map['wh_id'] = $param['wh_id'];
        $dest_stock_info = M('Stock')->where($map)->find();
        unset($map);

        //查询源库位上信息
        $map['location_id'] = $param['src_location_id'];
        $map['pro_code'] = $param['pro_code'];
        $map['wh_id'] = $param['wh_id'];
        $src_stock_list = M('Stock')->where($map)->order('product_date')->group('batch')->select();
        unset($map);

        //检查变化量是否大于总库存量，如果大于则报错
        foreach($src_stock_list as $src_stock){
            $src_total_qty += $src_stock['stock_qty'] - $src_stock['assign_qty'];
        }


        if(formatMoney($param['variable_qty'], 2) > formatMoney($src_total_qty, 2)){
            return array('status'=>0,'msg'=>'移库量大于库存总量！');
        }

        //剩余移动量
        $diff_qty = formatMoney($param['variable_qty'], 2);

        //整理数据格式
        foreach($src_stock_list as $key => $value){
            $src_stock_list[$key]['stock_qty'] = formatMoney($value['stock_qty'], 2);
            $src_stock_list[$key]['assign_qty'] = formatMoney($value['assign_qty'], 2);
            $src_stock_list[$key]['prepare_qty'] = formatMoney($value['prepare_qty'], 2);
            //生产日期 liuguangping
            $src_stock_list[$key]['product_date'] = $value['product_date'];
        }

        //按照现进先出原则 减去最早的批次量
        foreach($src_stock_list as $src_stock){
            if($diff_qty > 0){
                //库存量大于剩余移动量
                if($src_stock['stock_qty'] > $diff_qty){
                    //增加目标库存量 减少原库存量
                    $param['variable_qty'] = $diff_qty;
                    $this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);
                
                    //diff应该归零，没有再需要移动的数量了
                    $diff_qty = 0;
                }

                //库存量等于剩余移动量
                if($src_stock['stock_qty'] == $diff_qty){
                    //增加目标库存量 减少原库存量
                    $param['variable_qty'] = $diff_qty;
                    $this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);

                    //删除原库存记录
                    $map['id'] = $src_stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);

                    //diff应该归零，没有再需要移动的数量了
                    $diff_qty = 0;
                }

                //库存量小于剩余移动量
                if($src_stock['stock_qty'] < $diff_qty){
                    //增加目标库存量 减少原库存量
                    $param['variable_qty'] = $src_stock['stock_qty'];
                    $this->incDestStockDecSrcStock($src_stock,$dest_stock_info,$param);

                    //删除原库存记录
                    $map['id'] = $src_stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);

                    $diff_qty = $diff_qty - $src_stock['stock_qty'];
                }
            }else{
                break;
            }
        }


        return array('status'=>1);
    }

    /**
    *     
    */
    public function incDestStockDecSrcStock($src_stock,$dest_stock_info,$param){
        //如果没有记录，则新加一条记录
        if(empty($dest_stock_info)){
            //查询目标库位的默认status
            $map['id'] = $param['dest_location_id'];
            $dest_location_info = M('Location')->where($map)->find();
            unset($map);
            
            $add_info['wh_id'] = $param['wh_id'];
            $add_info['location_id'] = $param['dest_location_id'];
            $add_info['pro_code'] = $param['pro_code'];
            $add_info['batch'] = $src_stock['batch'];
            $add_info['product_date'] = $src_stock['product_date'];
            $add_info['status'] = $dest_location_info['status'];
            $add_info['stock_qty'] = $param['variable_qty'];
            $add_info['assign_qty'] = 0;
            $add_info['prepare_qty'] = 0;

            try{
                //插入数据
                $stock = D('Stock');
                $add_info = $stock->create($add_info);
                $stock->data($add_info)->add();

                //写入库存交易日志
                $stock_move_data = array(
                    'wh_id' => $param['wh_id'],
                    'location_id' => $param['dest_location_id'],
                    'pro_code' => $param['pro_code'],
                    'type' => 'move',
                    'direction' => 'INPUT',
                    'move_qty' => $param['variable_qty'],
                    'old_qty' => 0,
                    'new_qty' => $param['variable_qty'],
                    'batch' => $src_stock['batch'],
                    'status' => $dest_location_info['status'],
                    );
                $stock_move = D('StockMoveDetail');
                $stock_move_data = $stock_move->create($stock_move_data);
                $stock_move->data($stock_move_data)->add();

                //减少原库存量 如果和原库存量相等，则直接删除库存记录
                if($param['variable_qty'] == $src_stock['stock_qty']){
                    $map['id'] = $src_stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);
                }else{
                    $map['location_id'] = $param['src_location_id'];
                    $map['pro_code'] = $param['pro_code'];
                    $map['batch'] = $src_stock['batch'];
                    $map['status'] = $src_stock['status'];
                
                    M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);    
                }

                //写入库存交易日志

                $stock_move_data['location_id'] = $param['src_location_id'];
                $stock_move_data['direction'] = 'OUTPUT';
                $stock_move_data['old_qty'] = $src_stock['stock_qty'];
                $stock_move_data['new_qty'] = $src_stock['stock_qty'] - $param['variable_qty'];
                $stock_move = D('StockMoveDetail');
                $stock_move_data = $stock_move->create($stock_move_data);
                $stock_move->data($stock_move_data)->add();
                unset($map);
            }catch(Exception $e){
                //添加错误信息
                return array('status'=>0,'msg'=>'添加库存记录错误');
            }
            
        }
        //如果有记录，则更新记录
        else{
            //如果变化量大于0 增加目标库存 减少原库存
            if($param['variable_qty'] > 0){
                try{
                    //检查是否有库存记录 如果有 则增加目标库存 如果没有 则新建库存记录
                    $map['wh_id'] = $param['wh_id'];
                    $map['location_id'] = $param['dest_location_id'];
                    $map['pro_code'] = $param['pro_code'];
                    $map['batch'] = $src_stock['batch'];
                    $map['status'] = $src_stock['status'];
                    $stock_info = M('Stock')->where($map)->find();
                    if(empty($stock_info)){
                        //新增目标库存记录
                        $stock_add_data = $map;
                        $stock_add_data['stock_qty'] = $param['variable_qty'];
                        //liuguangping 加入成产日期
                        $stock_add_data['product_date'] = $src_stock['product_date'];
                        $stock = D('Stock');
                        $stock_add_data = $stock->create($stock_add_data);
                        $stock->data($stock_add_data)->add();
                    }else{
                        //增加目标库存
                        M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);
                    }

                    //写入库存交易日志
                    $stock_move_data = array(
                        'wh_id' => $dest_stock_info['wh_id'],
                        'location_id' => $param['dest_location_id'],
                        'pro_code' => $param['pro_code'],
                        'type' => 'move',
                        'direction' => 'INPUT',
                        'move_qty' => $param['variable_qty'],
                        'old_qty' => $dest_stock_info['stock_qty'],
                        'new_qty' => $dest_stock_info['stock_qty'] + $param['variable_qty'],
                        'batch' => $src_stock['batch'],
                        'status' => $dest_stock_info['status'],
                        );
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();

                    //减少原库存
                    $map['location_id'] = $param['src_location_id'];
                    M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);

                    //写入库存交易日志
                    $stock_move_data['location_id'] = $param['src_location_id'];
                    $stock_move_data['direction'] = 'OUTPUT';
                    $stock_move_data['old_qty'] = $src_stock['stock_qty'];
                    $stock_move_data['new_qty'] = $src_stock['stock_qty'] - $param['variable_qty'];
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();
                    unset($map);
                }catch(Exception $e){
                    //添加错误信息
                    return array('status'=>0,'msg'=>'变更数量错误');
                }
            }
        }
        
        //如果源库存状态由合格状态改为不合格，库存量发生变化，需要通知实时库存接口
        if($src_stock['status'] == 'qualified' && $dest_location_info['status'] != 'qualified'){
            //通知实时库存接口
            $notice_params['wh_id'] = $param['wh_id'];
            $notice_params['pro_code'] = $param['pro_code'];
            $notice_params['type'] = '';
            $notice_params['qty'] = $param['variable_qty'];
            A('Dachuwang','Logic')->notice_stock_update($notice_params);
            unset($notice_params);
        }
        return true;
    }

    /**
    * 移库操作 库存表变化，调整库存量
    * @param 
    * $params = array(
    *     'variable_qty' => 80,
    *     'wh_id'=>'xxx',
    *    'src_location_id'=>xxxx,
    *    'dest_location_id'=>xxxx,
    *    'pro_code'=>xxxxx,
    *    'batch'=>xxxx,
    *    'status'=>xxxx,
    *    'change_src_assign_qty'=>xxxx, 是否减少src的assign_qty
    *    'refer_code'=>xxxx, 关联单号
    * )
    *
    */
    public function adjustStockByMove($param = array()){
        if($param['variable_qty'] == 0 || 
            empty($param['wh_id']) || 
            empty($param['src_location_id']) || 
            empty($param['dest_location_id']) || 
            empty($param['pro_code']) || 
            empty($param['batch']) ||
            empty($param['status'])){

            //添加错误信息
            return array('status'=>0,'msg'=>'参数有误');
        }
        //判断目标库位上是否有商品
        $map['location_id'] = $param['dest_location_id'];
        $map['pro_code'] = $param['pro_code'];
        $map['batch'] = $param['batch'];
        $map['wh_id'] = $param['wh_id'];
        $dest_stock_info = M('Stock')->where($map)->find();

        //查询源库位上信息
        $map['location_id'] = $param['src_location_id'];
        $src_stock_info = M('Stock')->where($map)->find();
        unset($map);

        if(empty($src_stock_info)){
            return array('status'=>0,'msg'=>'原库存为空');
        }

        //查询目标库位的默认status
        $map['id'] = $param['dest_location_id'];
        $dest_location_info = M('Location')->where($map)->find();
        unset($map);

        //如果没有记录，则新加一条记录
        if(empty($dest_stock_info)){
            $add_info['wh_id'] = $param['wh_id'];
            $add_info['location_id'] = $param['dest_location_id'];
            $add_info['pro_code'] = $param['pro_code'];
            $add_info['batch'] = $param['batch'];
            $add_info['status'] = $dest_location_info['status'];
            $add_info['stock_qty'] = $param['variable_qty'];
            $add_info['assign_qty'] = 0;
            $add_info['prepare_qty'] = 0;
            $add_info['product_date'] = $src_stock_info['product_date'];

            try{
                //插入数据
                $stock = D('Stock');
                $add_info = $stock->create($add_info);
                $stock->data($add_info)->add();

                //写入库存交易日志
                $stock_move_data = array(
                    'wh_id' => $param['wh_id'],
                    'location_id' => $param['dest_location_id'],
                    'pro_code' => $param['pro_code'],
                    'type' => 'move',
                    'direction' => 'INPUT',
                    'move_qty' => $param['variable_qty'],
                    'old_qty' => 0,
                    'new_qty' => $param['variable_qty'],
                    'batch' => $src_stock_info['batch'],
                    'status' => $param['status'],
                    'refer_code' => $param['refer_code'],
                    );
                $stock_move = D('StockMoveDetail');
                $stock_move_data = $stock_move->create($stock_move_data);
                $stock_move->data($stock_move_data)->add();

                //减少原库存量
                $map['location_id'] = $param['src_location_id'];
                $map['pro_code'] = $param['pro_code'];
                $map['batch'] = $param['batch'];
                $map['status'] = $param['status'];
            
                //检查原库存 如果库存量与变化量相等 则删除数据 如果不等 则减掉库存量
                $stock = M('Stock')->where($map)->find();
                if($stock['stock_qty'] == $param['variable_qty']){
                    //删除库存记录
                    $map['id'] = $stock['id'];
                    M('Stock')->where($map)->delete();
                    unset($map);
                }else{
                    //减少原库存
                    M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);
                
                    if($param['change_src_assign_qty']){
                        //减少assign_qty
                        M('Stock')->where($map)->setDec('assign_qty',$param['variable_qty']);
                    }
                }
                

                //写入库存交易日志

                $stock_move_data['location_id'] = $param['src_location_id'];
                $stock_move_data['direction'] = 'OUTPUT';
                $stock_move_data['old_qty'] = $src_stock_info['stock_qty'];
                $stock_move_data['new_qty'] = $src_stock_info['stock_qty'] - $param['variable_qty'];
                $stock_move = D('StockMoveDetail');
                $stock_move_data = $stock_move->create($stock_move_data);
                $stock_move->data($stock_move_data)->add();
                unset($map);
            }catch(Exception $e){
                //添加错误信息
                return array('status'=>0,'msg'=>'添加库存记录错误');
            }
            
        }
        //如果有记录，则更新记录
        else{
            //如果变化量大于0 增加目标库存 减少原库存
            if($param['variable_qty'] > 0){
                try{
                    //增加目标库存
                    $map['location_id'] = $param['dest_location_id'];
                    $map['pro_code'] = $param['pro_code'];
                    $map['batch'] = $param['batch'];
                    M('Stock')->where($map)->setInc('stock_qty',$param['variable_qty']);

                    //是否修改生产日期 暂定每个批次只有一个生产日期 如果有不同 取最早的生产日期
                    if(strtotime($dest_stock_info['product_date']) > strtotime($src_stock_info['product_date']) || $dest_stock_info['product_date'] == '0000-00-00 00:00:00'){
                        $data['product_date'] = (empty($src_stock_info['product_date'])) ? date('Y-m-d') : $src_stock_info['product_date'];
                        $stock = D('Stock');
                        $data = $stock->create($data,2);
                        $res = $stock->where($map)->save($data);
                        unset($data);
                    }

                    //写入库存交易日志
                    $stock_move_data = array(
                        'wh_id' => $param['wh_id'],
                        'location_id' => $param['dest_location_id'],
                        'pro_code' => $param['pro_code'],
                        'type' => 'move',
                        'direction' => 'INPUT',
                        'move_qty' => $param['variable_qty'],
                        'old_qty' => $dest_stock_info['stock_qty'],
                        'new_qty' => $dest_stock_info['stock_qty'] + $param['variable_qty'],
                        'batch' => $param['batch'],
                        'status' => $param['status'],
                        'refer_code' => $param['refer_code'],
                        );
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();

                    
                    $map['location_id'] = $param['src_location_id'];

                    //检查原库存 如果库存量与变化量相等 则删除数据 如果不等 则减掉库存量
                    $stock = M('Stock')->where($map)->find();
                    if($stock['stock_qty'] == $param['variable_qty']){
                        //删除库存记录
                        $map['id'] = $stock['id'];
                        M('Stock')->where($map)->delete();
                        unset($map);
                    }else{
                        //减少原库存
                        M('Stock')->where($map)->setDec('stock_qty',$param['variable_qty']);

                        if($param['change_src_assign_qty']){
                            //减少assign_qty
                            M('Stock')->where($map)->setDec('assign_qty',$param['variable_qty']);
                        }
                    }

                    

                    //写入库存交易日志
                    $stock_move_data['location_id'] = $param['src_location_id'];
                    $stock_move_data['direction'] = 'OUTPUT';
                    $stock_move_data['old_qty'] = $src_stock_info['stock_qty'];
                    $stock_move_data['new_qty'] = $src_stock_info['stock_qty'] - $param['variable_qty'];
                    $stock_move = D('StockMoveDetail');
                    $stock_move_data = $stock_move->create($stock_move_data);
                    $stock_move->data($stock_move_data)->add();
                    unset($map);
                }catch(Exception $e){
                    //添加错误信息
                    return array('status'=>0,'msg'=>'变更数量错误');
                }
            }
        }

        //如果源库存状态由合格状态改为不合格，库存量发生变化，需要通知实时库存接口
        if($src_stock_info['status'] == 'qualified' && $dest_stock_info['status'] != 'qualified'){
            //通知实时库存接口
            $notice_params['wh_id'] = $param['wh_id'];
            $notice_params['pro_code'] = $param['pro_code'];
            $notice_params['type'] = '';
            $notice_params['qty'] = $param['variable_qty'];
            A('Dachuwang','Logic')->notice_stock_update($notice_params);
            unset($notice_params);
        }

        return array('status'=>1);
    }

    /**
    * 根据货品号，仓库编号，批次编号，货品名称，库位编号，库存状态，标示（flg=》不等于null 统计 =》null 查看）,查询库存记录
    * $params = array( liuguangping
    *    'pro_code' => 'xxxx',
    *    'wh_id'    => 'xxx'
    *    'pro_name' => 'xxxx',
    *    'batch_code'=>'xxxx',
    *    'location_code' => 'xxxx',
    *    'no_location_code'=> array('DownGrade,PACK')
    *    'stock_status' => 'xxxxx',
    *    
    * )
    * return array $stock_infos
    */
    public function getStockInfosByCondition($params = array(), $flg = null){
        $pro_code = $params['pro_code'];
        $wh_id    = $params['wh_id'];
        $batch_code = $params['batch_code'];
        $pro_name = $params['pro_name'];
        $location_code = $params['location_code'];
        $no_in_location_area_code = $params['no_in_location_area_code'];
        $stock_status = $params['stock_status'];

        $map = array();
        //根据pro_code添加map
        if($pro_code){
            $map['stock.pro_code'] = array('LIKE','%'.$pro_code.'%');
        }
        //仓库编号
        if($wh_id){
            $map['wh_id'] = $wh_id;
        }
        //批次号
        if($batch_code){
            $map['batch'] = $batch_code;
        }
        //根据pro_name查询对应的pro_code
        if($pro_name){
            $SKUs = A('Pms','Logic')->get_SKU_by_pro_name($pro_name);
            $pro_codes = array();
            foreach($SKUs['list'] as $SKU){
                $pro_codes[] = $SKU['sku_number'];
            }
            $map['stock.pro_code'] = array('in',$pro_codes);
        }
        //根据库位编号 查询对应的location_id
        /*
        * @TODO: 下面的逻辑是有问题的。强制要求库位名称有特征。而不是传入了一个区域。
        */
        if($location_code){
            $location_map['code'] = array('LIKE','%'.$location_code.'%');
            $location_ids = M('Location')->where($location_map)->getField('id',true);

            if(empty($location_ids)){
                $location_ids = array(0);
            }

            $map['stock.location_id'] = array('in',$location_ids);
        }
        //排斥库位 liuguangping
        if($no_in_location_area_code){
            //升级方法 liuguangpng 
            $location_ids = A('Location','Logic')->getLocationIdByAreaName($no_in_location_area_code);
            if(empty($location_ids)){
                $location_ids = array(0);
            }

            $map['stock.location_id'] = array('not in',$location_ids);
        }

        if(!empty($stock_status)){
            $map['stock.status'] = array('eq',$stock_status);
        }

        $m = M('Stock')->where($map);
        if($flg === null){
            $stock_infos = $m->select();
        }else{
            $M = clone $m;
            $result = $m->select();
            $stock_infos['result'] = $result;
            if(!$pro_code || !$wh_id || !$batch_code){
                $stock_qty = 0;
            }else{
                $stock_qty   = $M->sum('stock_qty');
            }
            
            $stock_infos['sum'] = $stock_qty;
        }

        return $stock_infos;
    }

    /**
    * 创建库存记录 并添加库存交易日志
    * $params = array(
    *    'wh_id' => xxx,
    *    'location_id' => xxx,
    *    'pro_code' => xxx,
    *    'batch' => xxxx,
    *    'status' => xxx,
    *    'stock_qty' => xxxx,
    *    'assgin_qty' => xxxx,
    *    'prepare_qty' => xxx,
    * )
    */
    public function addStock($params = array()){
        if(!is_array($params)){
            return false;
        }

        if(empty($params['location_id']) || empty($params['pro_code']) || empty($params['batch']) || empty($params['stock_qty'])){
            return false;
        }

        $add_data = $params;

        //如果状态为空 则读取location对应的默认状态
        if(empty($params['status'])){
            $map['id'] = $params['location_id'];
            $location_info = M('Location')->where($map)->find();
            $add_data['wh_id'] = $location_info['wh_id'];
            $add_data['status'] = $location_info['status'];
            unset($map);
        }

        $add_data['stock_qty'] = (empty($params['stock_qty'])) ? 0 : $params['stock_qty'];
        $add_data['assgin_qty'] = (empty($params['assgin_qty'])) ? 0 : $params['assgin_qty'];
        $add_data['prepare_qty'] = (empty($params['prepare_qty'])) ? 0 : $params['prepare_qty'];

        //插入记录
        $stock = D('Stock');
        $add_data = $stock->create($add_data);
        $stock->data($add_data)->add();

        //写入库存交易记录
        $stock_move_data = array(
            'wh_id' => session('user.wh_id'),
            'location_id' => $params['location_id'],
            'pro_code' => $params['pro_code'],
            'type' => 'move',
            'refer_code' => $params['refer_code'],
            'direction' => 'INPUT',
            'move_qty' => $params['stock_qty'],
            'old_qty' => 0,
            'new_qty' => $params['stock_qty'],
            'batch' => $params['batch'],
            'status' => $add_data['status'],
            );
        $stock_move = D('StockMoveDetail');
        $stock_move_data = $stock_move->create($stock_move_data);
        $stock_move->data($stock_move_data)->add();

        return true;
    }

    /**
    * 插入库存记录时 检查目标库位是否允许 混货 混批次
    * @param
    * $params = array(
    *    'src_location_id' => xxx,
    *     'dest_location_id' => xxx,
    *    'wh_id' => xxxx,
    *    'status' => xxxx,
    *    'pro_code' => xxxx,
    *    'batch' => xxx
    * );
    */
    public function checkLocationMixedProOrBatch($params = array()){
        if(empty($params) || empty($params['dest_location_id']) || empty($params['wh_id']) || empty($params['pro_code'])){
            return array('status'=>0,'msg'=>'参数有误');
        }

        //根据location_id 查询目标库位详情
        $map['location_id'] = $params['dest_location_id'];
        $location_detail = M('location_detail')->field('is_mixed_pro,is_mixed_batch')->where($map)->find();
        unset($map);

        if($location_detail['is_mixed_pro'] ==2){
            //检查目标库位上的货品
            $map['wh_id'] = $params['wh_id'];
            $map['location_id'] = $params['dest_location_id'];
            //$map['status'] = $params['status'];
            $map['stock_qty'] = array('neq','0');
            $map['is_deleted'] = 0;
            $dest_stock_info = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();
            unset($map);

            //如果有记录 则禁止混货
            if(!empty($dest_stock_info)) {
                if($location_detail['is_mixed_pro'] == 2) {
                    foreach ($dest_stock_info as $key => $val) {
                        if($val['pro_code'] != $params['pro_code']) {
                            return array('status'=>0,'msg'=>'该库位不允许混放货品。');
                        }
                    }
                }
            }
        }

        //检查混批次
        if($location_detail['is_mixed_batch'] == 2) {
            //检查目标库位上的货品
            $map['wh_id'] = $params['wh_id'];
            $map['location_id'] = $params['dest_location_id'];
            //$map['status'] = $params['status'];
            $map['pro_code'] = $params['pro_code'];
            $map['stock_qty'] = array('neq','0');
            $map['is_deleted'] = 0;
            //由于已经是禁止混批次，所以理论上查询的结果只有一条记录
            $dest_stock_info = M('stock')->field('pro_code,batch,status')->group('pro_code,status')->where($map)->select();
            unset($map);

            //根据src_location_id 查询对应的原库位数据
            if(!empty($params['src_location_id'])){
                $map['location_id'] = $params['src_location_id'];
                $map['pro_code'] = $params['pro_code'];
                $map['wh_id'] = $params['wh_id'];
                $map['stock_qty'] = array('neq','0');
                $map['is_deleted'] = 0;
                $src_stock_info = M('Stock')->where($map)->select();

                //如果有不同的批次 则直接返回错误
                foreach($src_stock_info as $src_stock){
                    $src_stock_batch[] = $src_stock['batch'];
                }

                if(count($src_stock_batch) > 1){
                    return array('status'=>0,'msg'=>'该库位不允许混放批次。');
                }
                unset($map);
            }

            //禁止混批次
            if(!empty($dest_stock_info)) {
                if(!empty($src_stock_info)){
                    foreach ($src_stock_info as $key => $val) {
                        //由于已经是禁止混批次，所以理论上查询的结果只有一条记录
                        if($val['batch'] != $dest_stock_info[0]['batch']) {
                            return array('status'=>0,'msg'=>'该库位不允许混放批次。');
                        }
                    }
                }else{
                    foreach($dest_stock_info as $key => $val){
                        if($val['batch'] != $params['batch']){
                            return array('status'=>0,'msg'=>'该库位不允许混放批次。');
                        }
                    }
                }
            }
        }
        return array('status'=>1);
    }

    /**
    * 调整已有库存记录的库存状态
    * @param
    * $params = array(
    *    'wh_id' => xxxx,
    *     'location_id' => xxx,
    *    'pro_code' => xxx,
    *    'batch' => xxxx,
    *    'origin_status' => xxxx,
    *    'new_status' => xxxx,
    * );
    */
    public function adjustStockStatus($params = array()){
        if(empty($params['wh_id']) || 
            empty($params['location_id']) || 
            empty($params['pro_code']) || 
            empty($params['batch']) || 
            empty($params['origin_status']) || 
            empty($params['new_status']) ){
            return array('status'=>0,'msg'=>'参数有误！');
        }

        //如果没有变更状态 则报错
        if($params['origin_status'] === $params['new_status']){
            return array('status'=>0,'msg'=>'请修改库存状态');
        }

        //根据 wh_id location_id pro_code batch origin_status 查询对应记录id
        $map['wh_id'] = $params['wh_id'];
        $map['location_id'] = $params['location_id'];
        $map['pro_code'] = $params['pro_code'];
        $map['batch'] = $params['batch'];
        $map['status'] = $params['origin_status'];
        $stock_info = M('Stock')->where($map)->find();
        unset($map);

        //变更状态
        //查询是否有变更状态后的记录
        $map['wh_id'] = $stock_info['wh_id'];
        $map['location_id'] = $stock_info['location_id'];
        $map['pro_code'] = $stock_info['pro_code'];
        $map['batch'] = $stock_info['batch'];
        $map['status'] = $params['new_status'];
        $dest_stock_info = M('Stock')->where($map)->find();
        unset($map);
        

        $map['id'] = $stock_info['id'];
        $save_data['status'] = $params['new_status'];
        $res = M('Stock')->where($map)->save($save_data);
        unset($map);

        //如果变更后的状态有记录 则需要合并记录
        if(!empty($dest_stock_info)){
            $this->mergeStockInfo(array('src_stock_id'=>$stock_info['id'],'dest_stock_id'=>$dest_stock_info['id']));
        }

        if($res){
            //写入库存交易日志
            $stock_move_data = array(
                'wh_id' => $params['wh_id'],
                'location_id' => $params['location_id'],
                'pro_code' => $params['pro_code'],
                'type' => 'status',
                'direction' => 'OUTPUT',
                'move_qty' => 0,
                'old_qty' => $stock_info['stock_qty'],
                'new_qty' => $stock_info['stock_qty'],
                'batch' => $params['batch'],
                'status' => $params['origin_status'],
                );
            $stock_move = D('StockMoveDetail');
            $stock_move_data = $stock_move->create($stock_move_data);
            $stock_move->data($stock_move_data)->add();
            
            $stock_move_data['direction'] = 'INPUT';
            $stock_move_data['status'] = $params['new_status'];
            $stock_move->data($stock_move_data)->add();

            //创建库存调整单
            $adjustment_code = get_sn('adjust');
            $adjustment_data = array(
                'code' => $adjustment_code,
                'type' => 'change_status',
                'refer_code' => 'STOCK'.$stock_info['id'],
                'wh_id'=>session('user.wh_id'),
                );
            $stock_adjustment = D('Adjustment');
            $adjustment_data = $stock_adjustment->create($adjustment_data);
            $stock_adjustment->data($adjustment_data)->add();

            //创建库存调整单详情
            $adjustment_detail_data = array(
                'adjustment_code' => $adjustment_code,
                'pro_code' => $params['pro_code'],
                'origin_qty' => $stock_info['stock_qty'],
                'adjusted_qty' => 0,
                'origin_status' => $params['origin_status'],
                'adjust_status' => $params['new_status'],
                );
            $stock_adjustment_detail = D('AdjustmentDetail');
            $stock_adjustment_detail_data = $stock_adjustment_detail->create($adjustment_detail_data);
            $stock_adjustment_detail->data($stock_adjustment_detail_data)->add();
        
            //如果源库存状态由合格状态改为不合格，库存量发生变化，需要通知实时库存接口
            if($stock_info['status'] == 'qualified' && $dest_stock_info['status'] != 'qualified'){
                //通知实时库存接口
                $notice_params['wh_id'] = $params['wh_id'];
                $notice_params['pro_code'] = $params['pro_code'];
                $notice_params['type'] = '';
                $notice_params['qty'] = $stock_info['stock_qty'];
                A('Dachuwang','Logic')->notice_stock_update($notice_params);
                unset($notice_params);
            }
        }

        return array('status'=>1);
    }

    /**
    * 合并两条相同的记录 库存量相加
    * 条件：wh_id location_id pro_code batch status 全部相等
    * @param
    * $params = array(
    *    'src_stock_id' => xxxx,
    *     'dest_stock_id' => xxxx,
    * );
    */
    public function mergeStockInfo($params){
        if(empty($params['src_stock_id']) || empty($params['dest_stock_id'])){
            return false;
        }

        //根据 src_stock_id dest_stock_id 获得库存记录
        $map['id'] = $params['src_stock_id'];
        $src_stock_info = M('Stock')->where($map)->find();
        unset($map);

        $map['id'] = $params['dest_stock_id'];
        $dest_stock_info = M('Stock')->where($map)->find();
        unset($map);

        //判断wh_id location_id pro_code batch status 是否全部相等
        if($src_stock_info['wh_id'] != $dest_stock_info['wh_id'] || 
            $src_stock_info['location_id'] != $dest_stock_info['location_id'] || 
            $src_stock_info['pro_code'] != $dest_stock_info['pro_code'] || 
            $src_stock_info['batch'] != $dest_stock_info['batch'] || 
            $src_stock_info['status'] != $dest_stock_info['status']
            ){
            return false;
        }

        //如果全部相等 则将src合并到dest里面 同时删除src记录
        $map['id'] = $dest_stock_info['id'];
        $data['stock_qty'] = $src_stock_info['stock_qty'] + $dest_stock_info['stock_qty'];
        M('Stock')->where($map)->save($data);
        unset($map);

        //删除src记录
        $map['id'] = $src_stock_info['id'];
        M('Stock')->where($map)->delete();
        unset($map);
        
        return true;
    }

    //为数组添加pro_name字段
    public function add_fields($data = array(),$add_field = ''){
        if(empty($data) || empty($add_field)){
            return $data;
        }

        if($add_field == 'stock_qty'){
            $prepare_data = array();
            //整理pro_codes
            foreach($data as $k => $val){
                $pro_codes[] = $val['pro_code'];
            }

            $map['pro_code'] = array('in',$pro_codes);
            $stock_infos = M('stock')->where($map)->field('sum(stock_qty) as stock_qty, pro_code')->group('pro_code')->select();

            foreach($data as $k => $val){
                $prepare_data[$k] = $val;
                foreach($stock_infos as $stock_info){
                    if($val['pro_code'] == $stock_info['pro_code']){
                        $prepare_data[$k]['stock_qty'] = $stock_info['stock_qty'];
                        break;
                    }
                }
            }

            return $prepare_data;
        }

        return $data;
    }

}
