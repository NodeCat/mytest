<?php
namespace Wms\Logic;
/**
 * 加工区操作
 */

class ProcessLogic {
    
    private $mark = 'WORK'; //加工区标示
    private $in_mark = 'WORK-02'; //入库库位标示
    private $out_mark = 'WORK-01'; //出库库位标示
    /**
     * 根据标示获取加工区库位ID
     * @param $wh_id int 所属仓库
     * @param $mark string 加工区标示
     * @param $type string 库位标示
     */
    public function get_process_stock_id($mark = 'WORK', $type = 'WORK-01', $wh_id = 1) {
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
        $out_id = $this->get_process_stock_id($this->mark, $this->out_mark, $data['wh_id']);
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
        $num = 0;
        foreach ($number as $value) {
            $num += $value['stock_qty'];
        }
        if ($num >= $data['real_qty']) {
            $return = true;
        }
         
        return $return;
    }
    
    /**
     * 出库批次纪录操作
     */
    public function add_batch_log($data = array()) {
        $return = false;
        
        if (empty($data)) {
            return $return;
        }
        $D = D('StockBillOutContainer');
        if ($D->create($data)) {
            $affect = $D->add();
            if ($affect) {
                $return = true;
            }
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
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data)) {
            //参数有误
            return $return;
        }
        $param['wh_id'] = $data['wh_id'];
        $param['real_qty'] = $data['real_qty'];
        $param['pro_code'] = $data['pro_code'];
        
        //获取库位id
        $out_id = $this->get_process_stock_id($this->mark, $this->out_mark, $data['wh_id']);
        if ($out_id <= 0) {
            $return['msg'] = '还未创建加工区出库库位';
            return $return;
        }
        //获取入库库位
        $id = $this->get_process_stock_id($this->mark, $this->in_mark, $data['wh_id']);
        if ($id <= 0) {
            $return['msg'] = '还未创建加工区入库库位';
            return $return;
        }
        //库存是否充足
        $is_full = $this->process_stock_status($param);
        if (!$is_full) {
            //库存不足
            $return['msg'] = '库存不足';
            return $return;
        }
        unset($param);
         
        //出库
        $surplus = $data['real_qty']; //预扣数量
        $stock = M('stock');
        $map['location_id'] = $out_id;
        $map['wh_id'] = $data['wh_id'];
        $map['pro_code'] = $data['pro_code'];
        $map['stock.status'] = 'qualified'; //合格
		$stock_list = $stock->join('LEFT JOIN stock_batch on stock_batch.code = stock.batch')
		                        ->where($map)
		                        ->order('stock_batch.product_date')
		                        ->field('stock.*,stock_batch.product_date')
		                        ->select();
		unset($map);
		//先进先出
		$container = array();
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
                $map['id'] = $value['id'];
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
            //出库批次纪录
            $container[] = array(
            	    'refer_code' => $data['refer_code'],
                'pro_code' => $data['pro_code'],
                'batch' => $value['batch'],
                'wh_id' => $data['wh_id'],
                'location_id' => $out_id,
                'qty' => $move_qty,
                'created_time' => get_time(),
                'updated_time' => get_time(),
                'created_user' => session('user.uid'),
                'updated_user' => session('user.uid'),
            );
            //$this->add_batch_log($container);
            if ($break) {
                break;
            }
        }
		//出库批次纪录
		$merge = array();
		//合并同类
		foreach ($container as $val) {
		    if (!isset($merge[$val['batch'] . $val['pro_code']])) {
		        $merge[$val['batch'] . $val['pro_code']] = $val;
		    } else {
		        $merge[$val['batch'] . $val['pro_code']] += $val['qty'];
		    }
		}
		//纪录操作
		foreach ($merge as $mer) {
            $this->add_batch_log($mer);
		}
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 更新出库单(wms)
     * @param $pid int 出库单ID
     * @param $data array 出库详情
     * array(
     *     array('qty' => 出库数量, 'pro_code' => sku编号, 'wh_id' => 仓库ID) 
     *     ..........
     * )
     */
    public function update_out_stock_detail($pid = 0, $data = array()) {
        $return = false;
        
        if (empty($pid) || empty($data)) {exit('ggggggg');
            //参数有误
            return $return;
        }
        
        $M = M('stock_bill_out_detail');
        $D = M('stock_bill_out');
        //$where['id'] = $pid;
        //$wh_id = $D->field('wh_id')->where($where)->find();
        foreach ($data as $value) {
            $map = array();
            $map['pid'] = $pid;
            //$map['wh_id'] = $wh_id['wh_id'];
            $map['pro_code'] = $value['pro_code'];
            $data['status'] = 2;
            $M->where($map)->setInc('delivery_qty', $value['qty']);
            if ($M->create($data)) {
                $M->where($map)->save();
            }
        }
        unset($map);
        //更新出库单
        $map['id'] = $pid;
        $update['status'] = 2;
        $D->where($map)->save($update);
        
        $return = true;
        return $return;
    }
    
    /**
     * 更新出库单(erp)
     * @param $pid int 出库单id
     * @param $data array 出库详情
     * array(
     *     array('qty' => 出库数量, 'pro_code' => sku编号, 'batch' => 批次)
     *     ..........
     * )
     */
    public function erp_out_stock_detail($pid = 0, $data = array()) {
        $return = false;
    
        if (empty($pid) || empty($data)) {
            //参数有误
            return $return;
        }
    
        $M = M('erp_process_out_detail');
        foreach ($data as $value) {
            $map = array();
            $map['pid'] = $pid;
            $map['batch'] = $value['batch'];
            $map['pro_code'] = $value['pro_code'];
            $data['status'] = 'on';
            $M->where($map)->setInc('real_qty', $value['qty']);
            if ($M->create($data)) {
                $affect = $M->where($map)->save();
            }
        }
        unset($map);
        //更新出库单
        $D = M('erp_process_out');
        $map['id'] = $pid;
        $update['status'] = 'on';
        $D->where($map)->save($update);
    
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
        $id = $this->get_process_stock_id($this->mark, $this->in_mark, $wh_id);
        if ($id <= 0) {
            $return['msg'] = '还未创建加工区库位';
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
     * 更新入库单(wms)
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
            $data['status'] = 33;
            $M->where($map)->setInc('done_qty', $value['qty']);
            if ($M->create($data)) {
                $affect = $M->where($map)->save();
            }
        }
        unset($map);
        //更新入库单
        $D = M('stock_bill_in');
        $map['id'] = $pid;
        $update['status'] = 33;
        $D->where($map)->save($update);
    
        $return = true;
        return $return;
    }
    
    /**
     * 更新入库单(erp)
     * @param $pid int 入库单id
     * @param $data array 入库详情
     * array(
     *     array('qty' => 入库数量, 'pro_code' => sku编号, 'batch' => 批次)
     *     ..........
     * )
     */
    public function erp_in_stock_detail($pid = 0, $data = array()) {
        $return = false;
    
        if (empty($pid) || empty($data)) {
            //参数有误
            return $return;
        }
    
        $M = M('erp_process_in_detail');
    
        foreach ($data as $value) {
            $map = array();
            $map['pid'] = $pid;
            $map['batch'] = $value['batch'];
            $map['pro_code'] = $value['pro_code'];
            $data['status'] = 'on';
            $M->where($map)->setInc('real_qty', $value['qty']);
            if ($M->create($data)) {
                $affect = $M->where($map)->save();
            }
        }
        
        unset($map);
        //更新入库单
        $D = M('erp_process_in');
        $map['id'] = $pid;
        $update['status'] = 'on';
        $D->where($map)->save($update);
    
        $return = true;
        return $return;
    }
    
    /**
     * 生成加工入库单(erp)
     * @param $status string 状态
     * @param $data array 数据组
     * array(
     *     'code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     * )
     */
    public function make_process_in_stock($status = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
        
        $param['wh_id'] = $data['wh_id']; //所属仓库
        $param['code'] = $data['common_in_code']; //加工入库单号
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['process_type'] = $data['type']; //类型 组合 or 拆分
        $param['status'] = $status; //状态 待入库
        $param['remark'] = $data['remark']; //备注
        
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $param;
        return $return;
    }
    
    /**
     * 生成加工入库单详情(erp)
     * @param $status string 状态
     * @param $data array 数据组
     * array(
     *     'pro_code' => 'sku编号'
     *     'batch' => 批次 关联加工单号
     *     'plan_qty' => 计划量
     *     'real_qty'=> 实际量
     *     'pid' => 父id 关联入库单id
     * )
     */
    public function make_process_in_stock_detail($status = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
        
        //写入加工入库单详情
        $detail_data['pro_code'] = $data['pro_code']; //sku编号
        $detail_data['batch'] = get_batch($data['code']);; //批次 关联加工单号
        $detail_data['plan_qty'] = $data['plan_qty']; //计划量
        $detail_data['real_qty'] = $data['real_qty']; //实际量
        $detail_data['status'] = $status; //状态
        $detail_data['pid'] = $data['pid'];
        $detail_data['created_user'] = session()['user']['uid']; //创建人
        $detail_data['updated_user'] = session()['user']['uid']; //修改人
        $detail_data['created_time'] = get_time(); //创建时间
        $detail_data['updated_time'] = get_time(); //修改时间
        
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $detail_data;
        return $return;
    }
    
       
    /**
     * 生成加工出库单(erp)
     * @param $status string 状态
     * @param $data array 数据组
     * array(
     *     'code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'refer_code' => 关联单号
     * )
     */
    public function make_process_out_stock($status = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($data)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
        
        //写入加工入库单详情
        $param['wh_id'] = $data['wh_id']; //所属仓库
        $param['code'] = $data['back_code']; //加工入库单号
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['process_type'] = $data['type']; //类型 组合 or 拆分
        $param['status'] = $status; //状态 待入库
        $param['remark'] = $data['remark']; //备注
                
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $param;
        return $return;
    }
    
    /**
     * 生成加工出库单详情(erp)
     * @param $status string 状态
     * @param $data array 数据组
     * array(
     *     'pro_code' => 'sku编号'
     *     'batch' => 批次 关联加工单号
     *     'plan_qty' => 计划量
     *     'real_qty'=> 实际量
     *     'pid' => 父id
     * )
     */
    public function make_process_out_stock_detail($status = '', $data = array()) {
        $return = array('status' => false, 'msg' => '');
    
        if (empty($data)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
    
       //加工出库单详情
        $detail_data['pid'] = $data['pid'];
        $detail_data['pro_code'] = $data['pro_code']; //sku编号
        $detail_data['batch'] = $data['code']; //批次 关联加工单号
        $detail_data['plan_qty'] = $data['plan_qty']; //计划量
        $detail_data['real_qty'] = $data['real_qty']; //实际量
        $detail_data['status'] = $data['status']; //状态
        $detail_data['created_user'] = session()['user']['uid']; //创建人
        $detail_data['updated_user'] = session()['user']['uid']; //修改人
        $detail_data['created_time'] = get_time(); //创建时间
        $detail_data['updated_time'] = get_time(); //修改时间
    
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $detail_data;
        return $return;
    }
    
    
    /**
     * 生成加工入库单(wms)
     * @param $status int 状态
     * @param $data array 数据组
     * array(
     *     'id' => 入库类型
     *     'name' => 入库类型标示
     *     'code' => '加工单号'
     *     'wh_id' => 仓库id
     *     'type' => 加工类型
     *     'remark'=> 备注
     *     'company_id' => 所属系统
     * )
     */
    public function make_process_in_stock_wms($status = 0, $data = array()) {
        $return = array('status' => false, 'msg' => '');
    
        if (empty($data)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
    
        $param['code'] = get_sn($data['name']); //入库单号
        $param['wh_id'] = $data['wh_id']; //仓库id
        $param['type'] = $data['id']; //入库类型ID
        $param['company_id'] = $data['company_id']; //所属系统
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['pid'] = 0; //关联采购单号ID
        $param['batch_code'] = get_batch($data['code']);; //批次号
        $param['partner_id'] = 0; //供应商
        $param['remark'] = $data['remark']; //备注
        $param['status'] = $status; //状态 
        //修改时间
    
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $param;
        return $return;
    }
    
    /**
     * 生成加工入库单详情(wms)
     * @param $status int 状态
     * @param $data array 数据组
     * array(
     *     'expected_qty' => 预计数量
     *     'code' => '入库单号'
     *     'pro_code' => sku编号
     *     'wh_id' => 仓库id
     *     'pid' => 父id
     *     'refer_code' => 关联单号
     * )
     */
    
    public function make_process_in_stock_wms_detail($status = 0, $data = array()) {
        $return = array('status' => false, 'msg' => '');
    
        if (empty($data) || empty($status)) {
            //参数有误
            $return['msg'] = '参数有误';
            return $return;
        }
    
         //创建wms入库详情单数据
        $detail_data['wh_id'] = $data['wh_id']; //所属仓库
        $detail_data['pid'] = $data['pid']; //父id 
        $detail_data['refer_code'] = $data['code']; //关联入库单号
        $detail_data['pro_code'] = $data['pro_code']; //SKU编号
        $detail_data['expected_qty'] = $data['expected_qty']; //预计数量
        $detail_data['prepare_qty'] = 0; //待上架量
        $detail_data['done_qty'] = 0; //已上架量
        $detail_data['pro_uom'] = '件';
        $detail_data['remark'] = $data['remark'];
        $detail_data['status'] = $status;
        $detail_data['created_user'] = session()['user']['uid']; //创建人
        $detail_data['updated_user'] = session()['user']['uid']; //修改人
        $detail_data['created_time'] = get_time(); //创建时间
        $detail_data['updated_time'] = get_time(); //修改时间
        
        //调用PMS接口根据编号查询SKU名称规格
        $pms = D('Pms', 'Logic');
        $sku_info = $pms->get_SKU_field_by_pro_codes(array($data['pro_code']));
        $detail_data['pro_name'] = $sku_info[$data['pro_code']]['name']; //SKU名称
        $detail_data['pro_attrs'] = $sku_info[$data['pro_code']]['pro_attrs_str']; //SKU规格
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['data'] = $detail_data;
        return $return;
    } 
}