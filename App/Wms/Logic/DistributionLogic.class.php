<?php
namespace Wms\Logic;
/**
 * 配送路线逻辑封装
 * @author zhangchaoge
 *
 */
class DistributionLogic {
    
    public static $line = array(); //线路 （缓存线路）
    /**
     * 搜索订单
     */
    public function search($search = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($search)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        $M = M('stock_bill_out');
        
        //$map['company_id'] = $search['company_id'];
        $map['wh_id'] = $session('user.wh_id');
        $map['line_id'] = $search['line'];
        if (isset($search['time'])) {
            switch ($search['time']) {
                	case 1:
                	    $search['time'] = 'am';
                	    break;
                	case 2:
                	    $search['time'] = 'pm';
                	    break;
            }
            $map['delivery_ampm'] = $search['time'];
        }
        $map['delivery_date'] = $search['date'];
        $map['process_type'] = $search['type'];
        $map['status'] = 5; //状态 分拣完成
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
        $data = array(); //订单筛选条件
        foreach ($result as $value) {
            $data[] = $value['refer_code'];
        }
        //获取订单详情
        $order = D('Order', 'Logic');
        $order_info = $order->getOrderInfoByOrderIdArr($data);
        if ($order_info['status'] == false) {
            $return['msg'] = $order_info['msg'];
            return $return;
        }
        $list = array();
        $this->format_data($order_info['list']);
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $order_info['list'];
        return $return;
    }
    
    public function order_lists($post) {
        $return = array('status' => false, 'msg' => '');

        if (empty($post['company_id'])) {
            $return['msg'] = '请选择系统';
            return $return;
        }
        /*if (empty($post['wh_id'])) {
            $return['msg'] = '请选择仓库';
            return $return;
        }*/
        if (empty($post['type'])) {
            $return['msg'] = '请选择订单类型';
            return $return;
        }
        if (empty($post['line'])) {
            $return['msg'] = '请选择线路';
            return $return;
        }
        if (empty($post['time'])) {
            $return['msg'] = '请选择时段';
            return $return;
        }
        if (empty($post['date'])) {
            $return['msg'] = '请选择日期';
            return $return;
        }
        //时段是否区分
        if ($post['time'] == 3) {
            unset($post['time']);
        }
        //获取搜索结果
        $seach_info = $this->search($post);
        if ($seach_info['status'] == false) {
            //搜索失败
            $return['msg'] = $seach_info['msg'];
            return $return;
        }
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $seach_info['list']; 
        return $return;
    }
    
    /**
     * 订单数据处理
     * @param unknown $data
     * @return multitype:
     */
    public function format_data(&$data) {
        foreach ($data as $key => &$value) {
            $value['order_id'] = $value['id'];
            $value['line_total'] = count($value['detail']);
             foreach ($value['detail'] as $k => &$v) {
                $value['sku_total'] += $v['quantity'];
                $v['attrs'] = '';
                foreach ($v['spec'] as $spec) {
                    $v['attrs'] .= $spec['name'] . ':' . $spec['val'] . ',';
                }
            }
        }
    }
    
    /**
     * 根据线路id获取线路名称
     * @param number $line_id
     * @return string|Ambigous <string, unknown>
     */
    public function format_line($line_id = 0) {
        $return = '';
        
        //获取线路
        if (empty($this->line)) {
            $lines = D('Wave', 'Logic');
            $result = $lines->line();
            $this->line = $result;
        }
        
        if (empty($line_id)) {
            //不指定线路id则返回所有
            $return = array();
            $return = $this->line;
            return $return;
        }
        
        foreach ($this->line as $key => $value) {
            if ($key == $line_id) {
                $return = $value;
                break;
            }
        }
        
        return $return;
    }
    
    /**
     * 根据配送单id获取订单
     * @param int $dis_id 订单ID
     */
    public function get_order_ids_by_dis_id($dis_id = array()) {
        $return = array();
        
        if (empty($dis_id)) {
            return $return;
        }
        $M = M('stock_wave_distribution_detail');
        $map['pid'] = array('in', $dis_id);
        $result = $M->field('bill_out_id')->where($map)->select();
        if (empty($result)) {
            return $return;
        }
        foreach ($result as $value) {
            //格式化数据
            $return[] = $value['bill_out_id'];
        }
        
        return $return;
    }
    
    /**
     * 创建新配送单
     * @param array $ids 订单id组
     */
    public function add_distributioin($ids = array()) {
        $return = array('status' => false, 'msg' => '');
        
        if (empty($ids['ids'])) {
            $return['msg'] = '没有选择订单';
            return $return;
        }
        dump($ids);exit;
        $nid = array();
        //字符串处理
        foreach ($ids['ids'] as $values) {
            $nid[] = explode(',', $values);
        }
        $D = D('Order', 'Logic');
        $dis = D('Distribution'); 
        $det = M('stock_wave_distribution_detail');
        $M = M('stock_bill_out');
        foreach ($nid as $value) {
            //判断是否已创建
            foreach ($value as $id) {
                if (empty($id)) {
                    //没有选择订单
                    $return['msg'] = '请选择订单';
                    return $return;
                }
                $map['refer_code'] = $id;
                $sta = $M->field('status')->where($map)->find();
                if ($sta['status'] != 5) { //5 检货完成 只有状态5才可以加入配送单
                    $return['msg'] = '订单已经加入了配送单';
                    return $return;
                }
            }
            //获取订单详细信息
            $result = $D->getOrderInfoByOrderIdArr($value);
            if ($result['status'] == false) {
                $return['msg'] = $result['msg'];
                return $return;
            }
            $result = $result['list'];
            //创建配送单
            $data = array();
            $data['dist_code'] = get_sn('dis'); //配送单号
            $data['total_price'] = 0; //应收金额
            $data['company_id'] = 1;
            $data['order_count'] = count($value); //订单数
            $data['status'] = 1; //状态 未发运
            $data['is_printed'] = 0; //未打印
            $i = 0;
            foreach ($result as $val) {
                $data['total_price'] += $val['total_price']; //总价格
                $data['line_count'] += count($val['detail']); //总种类
                foreach ($val['detail'] as $v) {
                    $data['sku_count'] += $v['quantity']; //sku总数量
                }
                if ($i < 1) {
                    //重复数据  取一次即可
                    $data['line_id'] = $val['line_id']; //路线
                    $data['deliver_date'] = $val['deliver_date']; //配送日期
                    $data['deliver_time'] = $val['deliver_time']; //配送时段
                    $data['wh_id'] = intval($val['warehouse_id']); //所属仓库
                }
                $i ++;
            }
            if ($dis->create($data)) {
                //写入操作
                $pid = $dis->add();
            }
            if (!$pid) {
                $return['msg'] = '写入失败';
                return $return;
            }
            
            //创建配送单详情
            $detail = array();
            $detail['created_user'] = session()['user']['uid'];
            $detail['created_time'] = get_time();
            $detail['updated_user'] = session()['user']['uid'];
            $detail['updated_time'] = get_time();
            $detail['is_deleted'] = 0;
            foreach ($value as $vv) {
                $detail['bill_out_id'] = $vv;
                $detail['pid'] = $pid;
                if ($det->create($detail)) {
                    //写入操作
                    $det->add();
                }
            }
            unset($map);
            unset($data);
            //更新出库单状态
            
            $map['refer_code'] = array('in', $value);
            $data['status'] = 6;
            if ($M->create($data)) {
                $M->where($map)->save();
            }
        }
       
        $return['status'] = true;
        $reurn['msg'] = '成功';
        return $return;
    }
    
    /**
     * 获取所有已分拣订单 并按线路ID统计数量
     * @return array
     */
    public function get_all_orders() {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out');
        $map['type'] = 1; //类型 1销售出库
        $map['status'] = 5; //状态 检货完成
        //$map['wh_id'] = $session('user.wh_id');
        
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['status'] = true;
            $return['msg'] = '没有待发运的订单';
            $return['list'] = array();
            return $return;
        }
        $list = array();
        foreach ($result as $value) {
            if (!isset($list[$value['line_id']])) {
                $list[$value['line_id']] = 1;
            } else {
                $list[$value['line_id']] += 1;
            }
        }
        $list['sum'] = array_sum($list); //总计
        $return['status'] = true;
        $return['msg'] = '成功';
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 根据pid获取出库单详情 可批量获取
     * @param array or int $pid 父id
     */
    public function get_out_detail_by_pids($pid) {
        $return = array('status' => false, 'msg' => '');
        
        $M = M('stock_bill_out_detail');
        if (empty($pid)) {
            $return['msg'] = '参数有误';
            return $return;
        }
        if (is_array($pid)) {
            $map['pid'] = array('in', $pid);
        } else {
            $map['pid'] = $pid;
        }
        $result = $M->where($map)->select();
        if (!empty($result)) {
            $return['msg'] = '成功';
            $return['status'] = true;
            $return['list'] = $result;
        }
        
        return $return;
    }
}