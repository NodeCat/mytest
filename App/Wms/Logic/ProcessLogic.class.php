<?php
namespace Wms\Logic;
/**
 * 加工区操作
 */

class ProcessLogic {
    /**
     * 根据标示获取加工区库位ID
     * @param $wh_id int 所属仓库
     * @param $mark string 加工区标示
     * @param $type string 库位标示
     */
    public function get_process_stock_id($mark = 'MN', $type = 'MN1001', $wh_id = 1) {
        $return = 0;
         
        $M = M('location');
        $map['code'] = $mark;
        $map['wh_id'] = $wh_id;
        $location = $M->field('id')->where($map)->find();
         
        unset($map);
        $map['pid'] = $location['id'];
        $map['code'] = $type;
        $id = $M->field('id')->where($map)->find();
        if (!empty($id)) {
            $return = $id['id'];
        }
         
        return $return;
    }
    /**
     * 加工区出库库存是否充足
     * @param $data array
     * array(
     *     'wh_id' => 所属仓库
     *     'real_qty' => 需求数量
     *     'pro_code' => 需求SKU编号
     * )
     */
    public function process_stock_status($data = array()) {
        $return = false;
         
        if (empty($data)) {
            //参数有误
            return $return;
        }
        //获取出库库位ID
        $out_id = $this->get_process_stock_id('MN', 'MN1001', $data['wh_id']);
        if ($out_id <= 0) {
            return $return;
        }
         
        $M = M('stock');
        $map['location_id'] = $out_id;
        $map['wh_id'] = $data['wh_id'];
        $map['pro_code'] = $data['pro_code'];
        $map['status'] = 'qualified'; //合格
        $number = $M->field('stock_qty')->where($map)->select();
        if (empty($number)) {
            return $return;
        }
        foreach ($number as $value) {
            $num += $value['stock_qty'];
        }
        if ($num > $data['real_qty']) {
            $return = true;
        }
         
        return $return;
    }
    
    /**
     * 加工区出库操作
     * @param $data array
     * array(
     *    'wh_id' => 仓库id
     *    'real_qty' => 出库数量
     *    'refer_code' => 关联出库单号
     *    'pro_code' => 出库sku编号
     * )
     */
    public function process_out_stock($data = array()) {
        $return = array('stats' => false, 'msg' => '');
         
        if (empty($data)) {
            //参数有误
            return $return;
        }
        $param['wh_id'] = $data['wh_id'];
        $param['real_qty'] = $data['real_qty'];
        $param['pro_code'] = $data['pro_code'];
        //库存是否充足
        $is_full = $this->process_stock_status($param);
        if (!$is_full) {
            //库存不足
            $return['msg'] = '库存不足';
            return $return;
        }
        unset($param);
         
        //获取库位id
        $out_id = $this->get_process_stock_id('MN', 'MN1001', $data['wh_id']);
        if ($out_id <= 0) {
            $return['msg'] = '不存在的库位';
            return $return;
        }
         
        //出库
        $surplus = $data['real_qty']; //预扣数量
        $stock = M('stock');
        $map['location_id'] = $out_id;
        $map['wh_id'] = $data['wh_id'];
        $map['pro_code'] = $data['pro_code'];
        $map['status'] = 'qualified'; //合格
		$stock_list = M('Stock')->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')
		                        ->where($map)
		                        ->order('stock_batch.product_date')
		                        ->field('stock.*,stock_batch.product_date')
		                        ->select();
		unset($map);
		//先进先出
        foreach ($stock_list as $value) {
            $break = false;
            if ($surplus > $value['stock_qty']) {
                //删除纪录
                $map['id'] = $value['id'];
                $stock->where($map)->delete();
                $surplus = $surplus - $value['stock_qty'];
                $move_qty = $value['stock_qty']; //移动数量
                $old_qty = $value['stock_qty']; //原有数量
                $new_qty = 0; //剩余数量
                unset($map);
            } else {
                $map['id'] = $stock['id'];
                $move_qty = $surplus; //移动数量
                $old_qty = $value['stock_qty']; //原有数量
                $new_qty = $value['stock_qty'] - $surplus; //剩余数量
                
                //削减库存
                $stock->where($map)->setDec('stock_qty', $surplus);
                unset($map);
                $break = true;
            }
            
            //写库存纪录
            $stock_move_data = array(
                    'wh_id' => $data['wh_id'], //仓库ID
                    'location_id' => $out_id, //库位ID
                    'pro_code' => $data['pro_code'], //SKU编号
                    'type' => 'move', //类型
                    'refer_code' => $data['refer_code'], //关联单号＝＝出库单号
                    'direction' => 'OUT', //出库
                    'move_qty' => $move_qty, //出库数量
                    'old_qty' => $old_qty, //原有数量
                    'new_qty' => $new_qty, //剩余数量
                    'batch' => $value['batch'], //批次
                    'status' => 'qualified', //状态==合格
            );
            $stock_move = D('StockMoveDetail');
            if ($stock_move->create($stock_move_data)) {
                $stock_move->add();
            }
            if ($break) {
                break;
            }
        }
		
         
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 更新出库单
     * @param $code string 出库单号
     * @param $data array 出库详情
     * array(
     *     array('qty' => 出库数量, 'pro_code' => sku编号, 'wh_id' => 仓库ID) 
     *     ..........
     * )
     */
    public function update_out_stock_detail($pid = '', $data = array()) {
        $return = false;
        
        if (empty($pid) || empty($data)) {
            //参数有误
            return $return;
        }
        
        $M = M('stock_bill_out_detail');
        
        foreach ($data as $value) {
            $map = array();
            $map['pid'] = $pid;
            $map['wh_id'] = $value['wh_id'];
            $map['pro_code'] = $value['pro_code'];
            $M->where($map)->setInc('delivery_qty', $data['qty']);
        }
        
        $return = true;
        return $return;
    }
    
    /**
     * 加工区入库操作
     * @param $pro_code string 入库数量
     * @param $qty int 入库数量
     * @param $wh_id int 仓库ID
     * @param $batch string 批次
     */
    public function process_in_stock($pro_code = '', $qty = 0, $wh_id = 1, $batch = '') {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($pro_code) || empty($qty)) {
            //参数有误
            return $return;
        }
        //获取入库库位
        $id = $this->get_process_stock_id('MN', 'MN1002', $wh_id);
        if ($id <= 0) {
            $return['msg'] = '不存在的仓库';
            return $return;
        }
        
        $M = M('stock');
        $map['location_id'] = $id;
        $map['wh_id'] = $wh_id;
        $map['pro_code'] = $pro_code;
        $map['batch'] = $batch;
        $info = $M->where($map)->find();
        if (empty($info)) {
           //没有同批次 则新建
           $data['wh_id'] = $wh_id;
           $data['location_id'] = $id;
           $data['pro_code'] = $pro_code;
           $data['batch'] = $batch;
           $data['status'] = 'qualified';
           $data['stock_qty'] = $qty;
           $data['created_user'] = session()['user']['uid'];
           $data['updated_user'] = session()['user']['uid'];
           $data['created_time'] = get_time();
           $data['updated_time'] = get_time();
           $data['is_delete'] = 0;
           if ($M->create($data)) {
               $M->add();
           } 
        } else {
            //同批次叠加
            $M->where($map)->setInc('stock_qty', $qty);
        }
        
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 更新入库单
     * @param $int string 入库单id
     * @param $data array 入库详情
     * array(
     *     array('qty' => 入库数量, 'pro_code' => sku编号, 'wh_id' => 仓库ID)
     *     ..........
     * )
     */
    public function update_in_stock_detail($pid = 0, $data = array()) {
        $return = false;
    
        if (empty($pid) || empty($data)) {
            //参数有误
            return $return;
        }
    
        $M = M('stock_bill_in_detail');
    
        foreach ($data as $value) {
            $map = array();
            $map['pid'] = $pid;
            $map['wh_id'] = $value['wh_id'];
            $map['pro_code'] = $value['pro_code'];
            $M->where($map)->setInc('done_qty', $data['qty']);
        }
    
        $return = true;
        return $return;
    }
    
    /**
     * 生成加工入库单(erp)
     * @param $type string 关联类型
     * @param $data array 数据组
     * array(
     *     'process_code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'status' => 状态
     *     'real_qty' => 实际生产量
     *     'plan_qty' => 计划生产量
     * )
     */
    public function make_process_in_stock($type = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data) || empty($type)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
        $process_in = D('ProcessIn');
        $process_in_detail = D('ProcessInDetail');
        $data['wh_id'] = $data['wh_id']; //所属仓库
        $data['code'] = get_sn('erp_pro_in'); //加工入库单号
        $data['refer_code'] = $data['process_code']; //关联加工单号
        $data['process_type'] = $data['type']; //类型 组合 or 拆分
        $data['status'] = $data['status']; //状态 待入库
        $data['remark'] = $data['remark']; //备注
        
        //写入加工入库单详情
        $detail_data['pro_code'] = $data['p_pro_code']; //sku编号
        $detail_data['batch'] = $data['process_code']; //批次 关联加工单号
        $detail_data['plan_qty'] = $data['plan_qty']; //计划量
        $detail_data['real_qty'] = $data['real_qty']; //实际量
        $detail_data['status'] = $data['status']; //状态
        $process_in_detail->create($detail_data);
        //关联操作
        $data[$type] = $detail_data;
        
        $affect = $process_in->relation($type)->add($data);
        if (!$affect) {
            $return['msg'] = '写入失败';
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
       
    /**
     * 生成加工出库单(erp)
     * @param $type string 关联类型
     * @param $data array 数据组
     * array(
     *     'process_code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'status' => 状态
     *     'real_qty' => 实际生产量
     *     'plan_qty' => 计划生产量
     * )
     */
    public function make_process_out_stock($type = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data) || empty($type)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
        $process_out = D('ProcessOut');
        
        $data['wh_id'] = $data['wh_id']; //所属仓库
        $data['code'] = get_sn('erp_pro_out'); //加工入库单号
        $data['refer_code'] = $data['process_code']; //关联加工单号
        $data['process_type'] = $data['type']; //类型 组合 or 拆分
        $data['status'] = 'prepare'; //状态 待入库
        $data['remark'] = $data['remark']; //备注
        
        //加工出库单详情
        $detail_data['pro_code'] = $data['p_pro_code']; //sku编号
        $detail_data['batch'] = $data['process_code']; //批次 关联加工单号
        $detail_data['plan_qty'] = $data['plan_qty']; //计划量
        $detail_data['real_qty'] = $data['real_qty']; //实际量
        $detail_data['status'] = $data['status']; //状态
        $detail_data['created_user'] = session()['user']['uid']; //创建人
        $detail_data['updated_user'] = session()['user']['uid']; //修改人
        $detail_data['created_time'] = get_time(); //创建时间
        $detail_data['updated_time'] = get_time(); //修改时间
        //关联操作
        $data[$type] = $detail_data;
        
        $affect = $process_out->relation($type)->add($data);
        if (!$affect) {
            $return['msg'] = '写入失败';
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 生成加工入库单(wms)
     * @param $type string 关联类型
     * @param $data array 数据组
     * array(
     *     'id' => 入库类型
     *     'name' => 入库类型标示
     *     'process_code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'status' => 状态
     *     'real_qty' => 实际生产量
     *     'plan_qty' => 计划生产量
     *     'company_id' => 所属系统
     * )
     */
    public function make_process_in_stock_wms($type = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
    
        $data['code'] = get_sn($data['name']); //入库单号
        $data['wh_id'] = $data['wh_id']; //仓库id
        $data['type'] = $data['id']; //入库类型ID
        $data['company_id'] = $data['company_id']; //所属系统
        $data['refer_code'] = $data['process_code']; //关联采购单号
        $data['pid'] = 0; //关联采购单号ID
        $data['batch_code'] = 'batch' . NOW_TIME; //批次号
        $data['partner_id'] = 0; //供应商
        $data['remark'] = $data['remark']; //备注
        $data['status'] = 21; //状态 21待入库
    
        //创建wms入库详情单数据
        $detail_data['wh_id'] = $data['wh_id']; //所属仓库
        $detail_data['refer_code'] = $data['process_code']; //关联入库单号
        $detail_data['pro_code'] = $data['p_pro_code']; //SKU编号
        $detail_data['expected_qty'] = $process['plan_qty']; //预计数量
        $detail_data['prepare_qty'] = 0; //待上架量
        $detail_data['done_qty'] = 0; //已上架量
        $detail_data['pro_uom'] = '件';
        $detail_data['remark'] = $process['remark'];
        $detail_data['created_user'] = session()['user']['uid']; //创建人
        $detail_data['updated_user'] = session()['user']['uid']; //修改人
        $detail_data['created_time'] = get_time(); //创建时间
        $detail_data['updated_time'] = get_time(); //修改时间
    
        //调用PMS接口根据编号查询SKU名称规格
        $pms = D('Pms', 'Logic');
        $sku_info = $pms->get_SKU_field_by_pro_codes($process['p_pro_code']);
        $detail_data['pro_name'] = $sku_info[$process['p_pro_code']]['name']; //SKU名称
        $detail_data['pro_attrs'] = $sku_info[$process['p_pro_code']]['pro_attrs_str']; //SKU规格
    
        //写入入库单
        $stock_in = D('StockIn');
        $data['detail'] = $detail_data;
        $stock_in->relation('detail')->add($data);
        unset($data);
        unset($detail_data);
    }
    
    
    /**
     * 生成加工出库单(wms)
     * @param $type string 关联类型
     * @param $data array 数据组
     * array(
     *     'process_code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'status' => 状态
     *     'real_qty' => 实际生产量
     *     'plan_qty' => 计划生产量
     * )
     */
    public function make_process_out_stock_wms($type = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}