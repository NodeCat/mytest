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
     * 检查库存
     */
    public function checkout_stock($data = array()) {
        $return = false;
        
        if (empty($data)) {
            return $return;
        }
        $data['location_ids'] = array($data['location_id']);
        $Stock = A('Stock', 'Logic');
        
        //检查库存
        $is_full = $Stock->outStockBySkuFIFOCheck($data);
        if ($is_full['status'] == 1) {
            $return = true;
        }
        
        return $return;
    }
    
    /**
     * 出库（并且记录SKU批次价格） @todo 出库操作
     * @param unknown $data
     * @return multitype:boolean string
     */
    public function move_stock($data = array()) {
        $return = array('status' => false, 'msg' => '', 'price' => 0);
        
        if (empty($data)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        $data['location_ids'] = array($data['location_id']);
        $Stock = A('Stock', 'Logic');
        
        //出库
        $move = $Stock->outStockBySkuFIFO($data);
        if ($move['status'] == 0) {
            $return['msg'] = '出库失败';
            return $return;
        }
        
        //记录SKU批次 单价
        $M = M('erp_process_out_price');
        $price = 0; //总价格
        foreach ($move['data'] as $key => $value) {
            $param = array();
            $param['pro_code'] = $data['pro_code'];
            $param['pro_qty'] = $value['qty'];
            $param['price'] = $this->get_price_by_sku($value['batch'], $data['pro_code']);
            $param['batch'] = $value['batch'];
            $param['code'] = $data['refer_code'];
            $param['location_id'] = $value['location_id'];
            $param['created_time'] = get_time();
            $param['created_user'] = session('user.uid');
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($M->create($param)) {
                $M->add();
            }
            $price = f_add($price, f_mul($param['price'], $param['pro_qty']));
        }
        $return['price'] = formatMoney($price, 2);
        $return['status'] = true;
        $return['msg'] = '成功';
        return $return;
    }
    
    /**
     * 根据批次获取SKU单价
     * @param string $batch 批次
     * @param string $sku_code SKU编号
     */
    public function get_price_by_sku($batch = '', $sku_code = '') {
        $return = 0;
        
        
        if (empty($batch) || empty($sku_code)) {
            return $return;
        }
        $pos = strpos($batch, 'ASN');
        if ($pos !== false) {
            //采购入库
            $map['code'] = $batch;
            $res = M('stock_bill_in')->where($map)->find();
            if (empty($res)) {
                return $return;
            }
            unset($map);
            $map['code'] = $res['refer_code'];
            $pur = M('stock_purchase')->where($map)->find();
            if (empty($pur)) {
                return $return;
            }
            unset($map);
            $map['pid'] = $pur['id'];
            $map['pro_code'] = $sku_code;
            $result = M('stock_purchase_detail')->where($map)->find();
            if (empty($result)) {
                return $return; 
            }
        } else {
            //加工入库
            $result['price_unit'] = 0;
            $map['erp_process_in_detail.pro_code'] = $sku_code;
            $map['erp_process_in.code'] = $batch;
            $result = M('erp_process_in_detail')
                                    ->join('erp_process_in ON erp_process_in.id=erp_process_in_detail.pid')
                                    ->where($map)
                                    ->find();
            
            if (!empty($result)) {
                $result['price_unit'] = $result['price'];
            }
        }
        $return = formatMoney($result['price_unit'], 2);
        return $return;
    }
    /**
     * 入库
     * @param array $data
     */
    public function in_stock($data = array()) {
        $return = false;
        
        if (empty($data)) {
            return $return;
        }
        
        $product_date = date('Y-m-d');
        $Stock = A('Stock', 'Logic');
        $return = $Stock->adjustStockByShelves(
                $data['wh_id'],
                $data['location_id'],
                $data['refer_code'],
                get_batch($data['refer_code']),
                $data['pro_code'],
                $data['pro_qty'],
                '',
                'qualified',
                $product_date,
                $data['pid']
        );
        
        return $return;
    }
    
    /**
     * 根据父sku编号查询物料清单
     * @param string $pro_code 父sku编号
     */
    public function get_ratio_by_pro_code($pro_code = '') {
        $return = array();
        
        if (empty($pro_code)) {
            return $return;
        }
        
        $map['p_pro_code'] = $pro_code;
        $map['is_deleted'] = 0;
        $M = M('erp_process_sku_relation');
        $res = $M->where($map)->select();
        if (!empty($res)) {
            $return = $res;
        }
        
        return $return;
    }
    /**
     * 根据父sku编号模糊查询物料清单
     * @param string $pro_code 父sku标号
     * @param int $limit 查询数量
     */
    public function get_ratio_like_pro_code($pro_code = '', $limit = 10) {
        $return = array();
        
        if (empty($pro_code)) {
            return $return;
        }
        $map['p_pro_code'] = array('like', $pro_code . '%');
        $map['is_deleted'] = 0;
        $result = M('erp_process_sku_relation')->field('p_pro_code')
                                               ->where($map)
                                               ->limit($limit)
                                               ->select();
        if (!empty($result)) {
            foreach ($result as $value) {
                $return[] = $value['p_pro_code'];
            }
        }
        
        return $return;
    } 
    
    /**
     * 获取去物料清单数据(添加加工单用)
     * @param string $pro_code 父sku标号
     * @param int $limit 查询数量
     */
    public function get_ration_by_pms($pro_code = '', $limit = 10) {
        $return = array();
        
        if (empty($pro_code)) {
            return $return;
        }

        $code_arr = $this->get_ratio_like_pro_code($pro_code, $limit);
        if (empty($code_arr)) {
            return $return;
        }
        $A = A('Pms',"Logic");
        $result = $A->get_SKU_field_by_pro_codes($code_arr, $limit);
        if (!empty($result)) {
            $i = 0;
            foreach ($result as $value) {
                $return[$i]['val']['code'] = $value['pro_code'];
                $return[$i]['val']['name'] = $value['name'];
                $return[$i]['val']['attrs'] = $value['pro_attrs_str'];
                $return[$i]['name'] = '['.$value['pro_code'].'] '.$value['name'] .'（'. $value['pro_attrs_str'].'）';
                $i++;
            }
        }
        
        return $return;
    }
    
    /**
     * 根据加工单基础数据获取所有详情信息
     * 参数采用引用传值得方式  无需返回值
     * @param array $data 加工单数据
     */

    public function get_process_all_sku_detail(&$data) {
        
        if (empty($data)) {
            return;
        }
        
        $result = $this->get_process_detail($data['id']);
        if (!empty($result)) {
            $p_code = array();
            $c_code = array();
            foreach ($result as $key => $value) {
                //获取所有父sku编号
                $p_code[] = $value['p_pro_code'];
                
                $data['detail'][$key] = $value;
                $detail = $this->get_ratio_by_pro_code($value['p_pro_code']);
                $data['detail'][$key]['detail'] = $detail;
                //获取所有子sku编号
                foreach ($detail as $val) {
                    $c_code[] = $val['c_pro_code'];
                }
            }
            //合并所有sku
            $sku_code = array_merge($p_code, $c_code);
            $sku_code = array_unique($sku_code);
        
            $A = A('Pms',"Logic");
            $sku_info_arr = $A->get_SKU_field_by_pro_codes($sku_code, count($sku_code));
        
            /** 这里的多层循环是为了将每个sku添加 名字 规格 计量单位 **/
            foreach ($sku_info_arr as $sku_info) {
                //循环所有sku信息
                foreach ($data['detail'] as &$p_sku_info) {
                    if ($p_sku_info['p_pro_code'] == $sku_info['pro_code']) {
                        //给每个父sku添加计量单位
                        $p_sku_info['p_uom_name'] = $sku_info['uom_name'];
                    }
                    foreach ($p_sku_info['detail'] as &$c_sku_info) {
                        //给每个子sku添加计量单位
                        if ($c_sku_info['c_pro_code'] == $sku_info['pro_code']) {
                            $c_sku_info['c_pro_name'] = $sku_info['name'];
                            $c_sku_info['c_pro_attrs'] = $sku_info['pro_attrs_str'];
                            $c_sku_info['c_uom_name'] = $sku_info['uom_name'];
                            $c_sku_info['plan_qty'] = formatMoney($p_sku_info['plan_qty'] * $c_sku_info['ratio'], 2);
                            $c_sku_info['real_qty'] = formatMoney($p_sku_info['real_qty'] * $c_sku_info['ratio'], 2);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * 格式化子SKU信息和父SKU信息(格式化字段名  创建单据用)
     * @param array $process 处理的数据
     * @param string $type 类型 P 合并父SKU c合并子SKU
     */
    public function format_process_sku($process = array(), $type = 'p') {
        $return = array();
        
        if (empty($process)) {
            return $return;
        }
        $format = array();
        $format['wh_id'] = $process['wh_id'];
        $format['code'] = $process['code'];
        $format['type'] = $process['type'];
        $format['remark'] = $process['remark'];
        $format['detail'] = array();
        if ($type == 'p') {
            //父SKU信息
            foreach ($process['detail'] as $key => $value) {
                $format['detail'][$key]['pro_code'] = $value['p_pro_code'];
                $format['detail'][$key]['pro_name'] = $value['p_pro_name'];
                $format['detail'][$key]['pro_attrs'] = $value['p_pro_attrs'];
                $format['detail'][$key]['uom_name'] = $value['p_uom_name'];
                $format['detail'][$key]['wh_id'] = $process['wh_id'];
                foreach ($value['detail'] as $val) {
                    $format['detail'][$key]['company_id'] = $val['company_id'];
                    $format['company_id'] = $val['company_id'];
                    break;
                }
                $format['detail'][$key]['plan_qty'] = $value['plan_qty'];
            }
        } elseif ($type == 'c') {
            //子SKU信息
            foreach ($process['detail'] as $key => $value) {
                foreach ($value['detail'] as $k => $val) {
                    $format['detail'][$key . '_' . $k]['pro_code'] = $val['c_pro_code'];
                    $format['detail'][$key . '_' . $k]['pro_name'] = $val['c_pro_name'];
                    $format['detail'][$key . '_' . $k]['pro_attrs'] = $val['c_pro_attrs'];
                    $format['detail'][$key . '_' . $k]['uom_name'] = $val['c_uom_name'];
                    $format['detail'][$key . '_' . $k]['wh_id'] = $process['wh_id'];
                    $format['detail'][$key . '_' . $k]['company_id'] = $val['company_id'];
                    $format['detail'][$key . '_' . $k]['plan_qty'] = formatMoney($val['plan_qty'], 2);
                    $format['company_id'] = $val['company_id'];
                }
            }
            //合并相同的SKU
            foreach ($format['detail'] as $index => $v) {
                if (!isset($format['detail'][$v['pro_code']])) {
                    $format['detail'][$v['pro_code']] = $v;
                } else {
                    $format['detail'][$v['pro_code']]['plan_qty'] = f_add($format['detail'][$v['pro_code']]['plan_qty'], $v['plan_qty']);
                }
                unset($format['detail'][$index]);
            }
        }
        
        $return = $format;
        return $return;
    }
    
    /**
     * 创建加工单 包含详情
     * @param array $data 加工单数据
     */
    public function create_process($data = array()) {
        $return = 0;
        
        if (empty($data)) {
            return $return;
        }
        
        $param = array();
        //主表数据
        $param['wh_id'] = session('user.wh_id'); //仓库
        $param['type'] = $data['type']; //类型
        $param['code'] = get_sn('erp_pro_'.$data['type']); //加工单号
        $param['status'] = 1; //状态 默认待审核
        $param['task'] = count($data['detail']); //总任务数
        $param['over_task'] = 0; //默认0
        $param['remark'] = $data['remark']; //备注
        $param['created_user'] = session('user.uid');
        $param['created_time'] = get_time();
        $param['updated_user'] = session('user.uid');
        $param['updated_time'] = get_time();
        $param['is_deleted'] = 0;
        
        $main = M('erp_process');
        if ($main->create($param)) {
            //写入操作
            $pid = $main->add();
            if (!$pid) {
                return $return;
            }
        }
        $detail = array();
        $assist = M('erp_process_detail');
        //附表数据
        foreach ($data['detail'] as $value) {
            $detail['pid'] = $pid;
            $detail['p_pro_code'] = $value['pro_code'];
            $detail['p_pro_name'] = $value['pro_name'];
            $detail['p_pro_attrs'] = $value['pro_attrs'];
            $detail['plan_qty'] = formatMoney($value['pro_qty'], 2);
            $detail['real_qty'] = formatMoney(0, 2);
            $detail['created_user'] = session('user.uid');
            $detail['created_time'] = get_time();
            $detail['updated_user'] = session('user.uid');
            $detail['updated_time'] = get_time();
            $detail['is_deleted'] = 0;
            
            if ($assist->create($detail)) {
                $assist->add();
            }
        }
        $return = $pid;
        
        return $return;
    }
    
    /**
     * 根据加工单ID获取详情
     * @param int $id 加工单id
     */
    public function get_process_detail($id = 0) {
        $return = array();
        
        if (empty($id)) {
            return $return;
        }
        $map['pid'] = $id;
        $result = M('erp_process_detail')->where($map)->select();
        if (!empty($result)) {
            $return = $result;
        }
        
        return $return;
    }
    
    /**
     * 组合加工单及详情中得一种父SKU信息及加工比例关系信息（PDA端加工用）
     * @param int $process_id 加工单id
     * @param int $sku_code SKU货号
     * @param int $real_qty 实际加工量
     */
    public function get_process_and_detail_unite($process_id = 0, $sku_code = 0, $real_qty = 0) {
        $return = array();
        
        if (empty($process_id) || empty($sku_code) || empty($real_qty)) {
            return $return;
        }
        $res = M('erp_process')->where(array('id'=>$process_id))->find();
        if (empty($res)) {
            return $return;
        }
        $res['main_status'] = $res['status'];
        unset($res['status']);
        //获取详情
        $map['pid'] = $process_id;
        $map['p_pro_code'] = $sku_code;
        $result = M('erp_process_detail')->where($map)->find();
        if (empty($result)) {
            return $return;
        }
        $result['true_qty'] = $real_qty; 
        $result['pro_code'] = $result['p_pro_code'];
        //物料清单
        $ratio = $this->get_ratio_by_pro_code($sku_code);
        if (empty($ratio)) {
            return $return;
        }
        unset($result['id']);
        $return = array_merge($res, $result);
        $return['ratio'] = $ratio;
        foreach ($return['ratio'] as &$value) {
            $value['plan_qty'] = formatMoney($return['plan_qty'] * $value['ratio'], 2);
            $value['real_qty'] = formatMoney($return['real_qty'] * $value['ratio'], 2);
            $value['true_qty'] = formatMoney($real_qty * $value['ratio'], 2);
            $value['pro_code'] = $value['c_pro_code'];
            unset($value['p_pro_code']);
        }
        
        return $return;
    }
    
    /**
     * 格式化出库 OR 入库 数据
     * @param array $data
     * @param string $type p or c 子 OR 父
     * @param string $move in or out 进 OR 出
     */
    public function format_move_stock($data = array(), $type = 'p', $move = 'in') {
        $return = array();
        
        if (empty($data)) {
            return $return;
        }
        $mark = $this->in_mark;
        if ($move == 'out') {
            $mark = $this->out_mark;
        }
        //根据加工单获取WMS出库单号 OR 入库单号
        $map['refer_code'] = $data['code'];
        $res = M('stock_bill_in')->where($map)->find();
        if ($move == 'out') {
            $res = M('stock_bill_out')->where($map)->find();
        }
        if (empty($res)) {
            return $return;
        }
        if ($type == 'p') {
            //格式化父SKU
            $return['wh_id'] = $data['wh_id'];
            $return['pro_code'] = $data['p_pro_code'];
            $return['pro_qty'] = $data['true_qty'];
            $return['refer_code'] = $res['code']; 
            $return['pid'] = $res['id']; 
            $return['location_id'] = $this->get_process_stock_id($this->mark, $mark, $data['wh_id']); //库位
        } elseif ($type == 'c') {
            //格式化子SKU
            foreach ($data['ratio'] as $key => $value) {
                $return[$key]['wh_id'] = $data['wh_id'];
                $return[$key]['pro_code'] = $value['c_pro_code'];
                $return[$key]['pro_qty'] = $value['true_qty'];
                $return[$key]['refer_code'] = $res['code'];
                $return[$key]['pid'] = $res['id'];
                $return[$key]['location_id'] = $this->get_process_stock_id($this->mark, $mark, $data['wh_id']); //库位
            }
        }
        return $return;
    }
    
    /**
     * 根据名称获取单据类型ID
     * @param stirng $name  单据名称
     * @param string $type 入库 OR 出库
     */
    public function get_prefix($name = '', $type = 'in') {
        $return = 0;
        
        if (empty($name)) {
            return $return;
        }
        
        $map['name'] = $name;
        $res = M('numbs')->field('prefix')->where($map)->find();
        if (empty($res)) {
            return $return;
        }
        if ($type == 'in') {
            $M = M('stock_bill_in_type');
        } elseif ($type == 'out') {
            $M = M('stock_bill_out_type');
        }
        $result = $M->where(array('type' => $res['prefix']))->find();
        
        if (!empty($result)) {
            $return = $result['id'];
        }
        
        return $return;
    }
    
    /**
     * 生成加工入库单(erp)
     * @param $data array 数据组
     * @param string $code 入库单号
     */
    public function make_process_in_stock_erp($data = array(), $code = '') {
        $return = 0;
    
        if (empty($data) || empty($code)) {
            //参数有误
            return $return;
        }
    
        $param['wh_id'] = $data['wh_id']; //所属仓库
        $param['code'] = $code; //加工入库单号
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['process_type'] = $data['type']; //类型 组合 or 拆分
        $param['status'] = 1; //状态 待入库
        $param['remark'] = $data['remark']; //备注
        $param['created_user'] = session()['user']['uid']; //创建人
        $param['updated_user'] = session()['user']['uid']; //修改人
        $param['created_time'] = get_time(); //创建时间
        $param['updated_time'] = get_time(); //修改时间
    
        $main = M('erp_process_in');
        if ($main->create($param)) {
            $pid = $main->add();
            if (!$pid) {
                return $return;
            }
        }
    
        //写入加工入库单详情
        $assist = M('erp_process_in_detail');
        foreach ($data['detail'] as $value) {
            $detail['pro_code'] = $value['pro_code']; //sku编号
            $detail['plan_qty'] = formatMoney($value['plan_qty'], 2); //计划量
            $detail['real_qty'] = formatMoney('0', 2); //实际量
            $detail['status'] = 1; //状态
            $detail['pid'] = $pid;
            $detail['created_user'] = session()['user']['uid']; //创建人
            $detail['updated_user'] = session()['user']['uid']; //修改人
            $detail['created_time'] = get_time(); //创建时间
            $detail['updated_time'] = get_time(); //修改时间
   
            if ($assist->create($detail)) {
                $assist->add();
            }
        }
    
        $return = $pid;
        return $return;
    }
    
     
    /**
     * 生成加工出库单(erp)
     * @param $data array 数据组
     * @param $code string 出库单号
     */
    public function make_process_out_stock_erp($data = array(), $code = '') {
        $return = 0;
    
        if (empty($data)) {
            //参数有误
            return $return;
        }
    
        //写入加工入库单详情
        $param['wh_id'] = $data['wh_id']; //所属仓库
        $param['code'] = $code; //加工入库单号
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['process_type'] = $data['type']; //类型 组合 or 拆分
        $param['status'] = 1; //状态 待入库
        $param['remark'] = $data['remark']; //备注
        $param['created_user'] = session()['user']['uid']; //创建人
        $param['updated_user'] = session()['user']['uid']; //修改人
        $param['created_time'] = get_time(); //创建时间
        $param['updated_time'] = get_time(); //修改时间
    
        $main = M('erp_process_out');
        if ($main->create($param)) {
            $pid = $main->add();
            if (!$pid) {
                return $return;
            }
        }
    
        $assist = M('erp_process_out_detail');
        foreach ($data['detail'] as $value) {
            $detail['pid'] = $pid;
            $detail['pro_code'] = $value['pro_code']; //sku编号
            $detail['plan_qty'] = formatMoney($value['plan_qty'], 2); //计划量
            $detail['real_qty'] = formatMoney(0 , 2); //实际量
            $detail['status'] = 1; //状态
            $detail['created_user'] = session()['user']['uid']; //创建人
            $detail['updated_user'] = session()['user']['uid']; //修改人
            $detail['created_time'] = get_time(); //创建时间
            $detail['updated_time'] = get_time(); //修改时间
    
            if ($assist->create($detail)) {
                $assist->add();
            }
        }
    
        $return = $pid;
        return $return;
    }
    
    /**
     * 生成加工入库单(wms)
     * @param $status int 状态
     * @param $data array 数据组
     */
    public function make_process_in_stock_wms($data = array()) {
        $return = '';
    
        if (empty($data)) {
            //参数有误
            return $return;
        }
        $name = 'wms_pro_in'; //
        $param['code'] = get_sn($name); //入库单号
        $param['wh_id'] = $data['wh_id']; //仓库id
        $param['type'] = $this->get_prefix($name, 'in'); //入库类型ID
        $param['company_id'] = $data['company_id']; //所属系统
        $param['refer_code'] = $data['code']; //关联加工单号
        $param['pid'] = 0; //关联采购单号ID
        $param['batch_code'] = get_batch($param['code']); //批次号
        $param['partner_id'] = 0; //供应商
        $param['remark'] = $data['remark']; //备注
        $param['status'] = 21; //状态 待入库
        $param['created_user'] = session()['user']['uid']; //创建人
        $param['updated_user'] = session()['user']['uid']; //修改人
        $param['created_time'] = get_time(); //创建时间
        $param['updated_time'] = get_time(); //修改时间
        $main = M('stock_bill_in');
        if ($main->create($param)) {
            $pid = $main->add();
            if (!$pid) {
                return $return;
            }
        }
    
        $assist = M('stock_bill_in_detail');
        //创建wms入库详情单数据
        foreach ($data['detail'] as $value) {
            $detail['wh_id'] = $value['wh_id']; //所属仓库
            $detail['pid'] = $pid; //父id
            $detail['refer_code'] = $param['code']; //关联入库单号
            $detail['pro_code'] = $value['pro_code']; //SKU编号
            $detail['pro_name'] = $value['pro_name']; //SKU名称
            $detail['pro_attrs'] = $value['pro_attrs']; //SKU规格
            $detail['expected_qty'] = formatMoney($value['plan_qty'], 2); //预计数量
            $detail['prepare_qty'] = formatMoney($value['plan_qty'], 2); //待上架量
            $detail['done_qty'] = formatMoney('0' , 2); //已上架量
            $detail['pro_uom'] = $value['uom_name'];
            $detail['price'] = 0;
            $detail['qualified_qty'] = formatMoney($value['plan_qty'] , 2);
            $detail['unqualified_qty'] = formatMoney('0' , 2);;
            $detail['remark'] = $data['remark'];
            $detail['status'] = 1;
            $detail['created_user'] = session()['user']['uid']; //创建人
            $detail['updated_user'] = session()['user']['uid']; //修改人
            $detail['created_time'] = get_time(); //创建时间
            $detail['updated_time'] = get_time(); //修改时间
            if ($assist->create($detail)) {
                $assist->add();
            }
        }
    
        $return = $param['code'];
        return $return;
    }
    
    /**
     * 生成加工出库单（wms）@todo liuguangping 出库操作
     * @param array $data
     */
    public function make_process_out_stock_wms($data = array()) {
        $return = '';
        
        if (empty($data)) {
            return $return;
        }
        
        //主表数据
        $name = 'mno';
        $param['wh_id'] = $data['wh_id'];
        $param['code'] = get_sn($name);
        $param['type'] = $this->get_prefix($name, 'out');
        $param['refer_code'] = $data['code'];
        $param['notes'] = $data['remark'];
        $param['dis_mark'] = 0;
        $param['status'] = 1; //状态 待生产
        $param['op_date'] = get_time();
        $param['company_id'] = $data['company_id'];
        $param['created_time'] = get_time();
        $param['created_user'] = session('user.uid');
        $param['updated_time'] = get_time();
        $param['updated_user'] = session('user.uid');
        
        $main = M('stock_bill_out');
        if ($main->create($param)) {
            $pid = $main->add();
        }
        
        $assist = M('stock_bill_out_detail');
        
        foreach ($data['detail'] as $value) {
            $detail['pid'] = $pid;
            $detail['wh_id'] = $value['wh_id'];
            $detail['pro_code'] = $value['pro_code'];
            $detail['pro_name'] = $value['pro_name'];
            $detail['pro_attrs'] = $value['pro_attrs'];
            $detail['price'] = 0;
            $detail['order_qty'] = formatMoney($value['plan_qty'], 2);
            $detail['status'] = 1; //待生产
            $detail['delivery_qty'] = formatMoney('0', 2);
            $detail['created_time'] = get_time();
            $detail['created_user'] = session('user.uid');
            $detail['updated_time'] = get_time();
            $detail['updated_user'] = session('user.uid');
            $detail['updated_time'] = get_time();
            
            if ($assist->create($detail)) {
                $assist->add();
            }
        }
        
        $return = $param['code'];
        return $return;
    }
    
    /**
     * 根据加工单号获取WMS OR ERP 出 OR 入库单号及ID
     * @param string $code 加工单号
     * @param string $type 类型 wms_in OR wms_out OR erp_in OR erp_out
     */
    public function get_code_by_process($process_code = '', $type = 'wms_in') {
        $return = array();
        
        if (empty($process_code)) {
            return $return;
        }
        $M = M('stock_bill_in');
        if ($type == 'wms_out') {
            $M = M('stock_bill_out');
        } elseif ($type == 'erp_in') {
            $M = M('erp_process_in');
        } elseif ($type == 'erp_out') {
            $M = M('erp_process_out');
        }
        $map['refer_code'] = $process_code;
        $res = $M->where($map)->find();
        if (empty($res)) {
            return $return;
        }
        $return = $res['id'];
        
        return $return;
    }
    /**
     * 更新出库单(wms)
     * @param $data array 出库详情
     */
    public function update_out_stock_wms($data = array(), $id = 0) {
        $return = false;
        
        if (empty($data) || empty($id)) {
            return $return;
        }
        $main = M('stock_bill_out');
        $assist = M('stock_bill_out_detail');
        
        $map['id'] = $id; 
        $result = $main->where($map)->find();
        if ($result['status'] != 2) {
            $param['status'] = 2; //已出库
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($main->create($param)) {
                $affected = $main->where($map)->save();
                if (!$affected) {
                    return $return;
                }
            }
        }
        unset($map);
        $map['pid'] = $id;
        $map['pro_code'] = $data['pro_code'];
        $res = $assist->where($map)->find();
        if ($res['status'] != 2) {
            $detail['status'] = 2; //已出库
        }
        $detail['delivery_qty'] = f_add($res['delivery_qty'], $data['true_qty']);
        $detail['updated_time'] = get_time();
        $detail['updated_user'] = session('user.uid');
        if ($assist->create($detail)) {
            $assist->where($map)->save();
        }
        unset($map);
        $return = true;
        return $return;
    }
    
    /**
     * 更新出库单(erp)
     * @param $id int 出库单id
     * @param $data array 出库详情
     */
    public function update_out_stock_erp($data = array(), $id = 0) {
        $return = false;
    
        if (empty($id) || empty($data)) {
            //参数有误
            return $return;
        }
    
        $main = M('erp_process_out');
        $assist = M('erp_process_out_detail');
        
        $map['id'] = $id;
        $result = $main->where($map)->find();
        if ($result['status'] != 2) {
            $param['status'] = 2; //已出库
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($main->create($param)) {
                $affected = $main->where($map)->save();
                if (!$affected) {
                    return $return;
                }
            }
        }
        unset($map);
        
        $map['pid'] = $id;
        $map['pro_code'] = $data['pro_code'];
        $res = $assist->where($map)->find();
        $detail['real_qty'] = f_add($res['real_qty'], $data['true_qty']);
        if ($res['status'] !=  2) {
            $detail['status'] = 2; //已出库
        }
        $detail['updated_time'] = get_time();
        $detail['updated_user'] = session('user.uid');
        if ($assist->create($detail)) {
            $affect = $assist->where($map)->save();
        }
    
        $return = true;
        return $return;
    }
    
    /**
     * 更新入库单(wms)
     * @param $int string 入库单id
     * @param $data array 入库详情
     */
    public function update_in_stock_wms($data = array(), $id = 0) {
        $return = false;
    
        if (empty($id) || empty($data)) {
            return $return;
        }
        
        $main = M('stock_bill_in');
        $assist = M('stock_bill_in_detail');
    
        $map['id'] = $id;
        $result = $main->where($map)->find();
        if ($result['status'] != 33) {
            $param['status'] = 33; //已上架
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($main->create($param)) {
                $affected = $main->where($map)->save();
                if (!$affected) {
                    return $return;
                }
            }
        }
        unset($map);
        
        $map['pid'] = $id;
        $map['pro_code'] = $data['pro_code'];
        $res = $assist->where($map)->find();
        if ($res['status'] != 33) {
            $detail['status'] = 33;
        }
        //$detail['done_qty'] = $data['true_qty'] + $res['done_qty'];
        $detail['updated_time'] = get_time();
        $detail['updated_user'] = session('user.uid');
        $detail['product_date'] = date('Y-m-d');
        if ($assist->create($detail)) {
            $affect = $assist->where($map)->save();
        }
        $return = true;
        return $return;
    }
    
    /**
     * 更新入库单(erp)
     * @param $pid int 入库单id
     * @param $data array 入库详情
     * @param $price int 总价格
     */
    public function update_in_stock_erp($data = array(), $id = 0, $price = 0) {
        $return = false;
    
        if (empty($id) || empty($data)) {
            return $return;
        }
        $main = M('erp_process_in');
        $assist = M('erp_process_in_detail');
        
        $map['id'] = $id;
        $result = $main->where($map)->find();
        if ($result['status'] != 2) {
            $param['status'] = 2; //已上架
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($main->create($param)) {
                $affected = $main->where($map)->save();
                if (!$affected) {
                    return $return;
                }
            }
        }
        unset($map);
        $map['pid'] = $id;
        $map['pro_code'] = $data['pro_code'];
        //计算单价 @todo 单价 pm liuyonghao=>价格抹掉 
        $res = $assist->where($map)->find();
        $price_unit = f_add(f_mul($res['price'], $res['real_qty']), $price) / f_add($res['real_qty'], $data['true_qty']);
        if ($res['status'] != 2) {
            $detail['status'] = 2;
        }
        $detail['real_qty'] = f_add($data['true_qty'], $res['real_qty']);
        $detail['price'] = formatMoney($price_unit, 2);
        $detail['updated_time'] = get_time();
        $detail['updated_user'] = session('user.uid');
        $detail['product_date'] = date('Y-m-d');
        if ($assist->create($detail)) {
            $affect = $assist->where($map)->save();
        }
        $return = true;
        return $return;
    }
    
    /**
     * 更新加工单
     * @param array $data
     * @param int $id
     */
    public function update_process_info($data = array(), $id = 0) {
        $return = false;
        
        if (empty($data) || empty($id)) {
            return $return;
        }
        $main = M('erp_process');
        $assist = M('erp_process_detail');
        
        if (isset($data['status'])) {
            $map['id'] = $id;
            $param['status'] = $data['status'];
            $param['updated_time'] = get_time();
            $param['updated_user'] = session('user.uid');
            if ($main->create($param)) {
                $affected = $main->where($map)->save();
                if (!$affected) {
                    return $return;
                }
            }
        }
        
        $map = array();
        $map['pid'] = $id;
        $map['p_pro_code'] = $data['p_pro_code'];
        $res = $assist->where($map)->find();
        $detail['real_qty'] = f_add($res['real_qty'], $data['real_qty']);
        $detail['updated_time'] = get_time();
        $detail['updated_user'] = session('user.uid');
        $affect = $assist->where($map)->save($detail);
        if (!$affect) {
            return $return;
        }
        if ($detail['real_qty'] * 100 >= $res['plan_qty'] * 100) {
            //更新任务数
            $map['id'] = $id;
            $over = $main->where($map)->setInc('over_task', 1);
            if (!$over) {
                return $return;
            }
        }
        $return = true;
        return $return;
    }
 }