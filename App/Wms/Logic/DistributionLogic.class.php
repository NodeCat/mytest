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
        
        $map['company_id'] = $search['company_id'];
        $map['wh_id'] = $search['wh_id'];
        $map['line'] = $search['line'];
        $map['status'] = 4; //状态 分拣完成
        
        $result = $M->where($map)->select();
        if (empty($result)) {
            $return['msg'] = '没有符合符号条件的订单';
            return $return;
        }
        $data = array(); //订单筛选条件
        foreach ($result as $value) {
            $data['order_ids'][] = $value['refer_code'];
        }
        $data['deliver_date'] = date($search['date']);
        if (isset($search['time'])) {
            $data['deliver_time'] = $search['time'];
        }
        $data['order_type'] = $search['order_type'];
        //获取订单详情
        $order = D('Order', 'Logic');
        $order_info = $order->getOrderInfoByOrderIds($data);
        if ($order_info['status'] == false) {
            $return['msg'] = $order_info['msg'];
            return $return;
        }
        $list = array();
        $list = $this->format_data($order_info['orderlist']);
        
        $return['msg'] = '成功';
        $return['status'] = true;
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 订单数据处理
     * @param unknown $data
     * @return multitype:
     */
    public function format_data($data = array()) {
        $return = array();
        
        if (empty($data)) {
            return $return;
        }
        $list = array();
        foreach ($data as $key => $value) {
            $list[$key]['id'] = $value['id']; //id
            $list[$key]['line'] = $value['line']; //路线
            $list[$key]['address'] = $value['address']; //地址
            $list[$key]['time'] = $value['deliver_date'] . $value['deliver_time']; //时间
            foreach ($value['detail'] as $k => $v) {
                //sku信息
                $list[$key]['sku'][$k]['name'] = $v['name'];  //名称
                $list[$key]['sku'][$k]['attrs'] = '';
                foreach ($v as $vv) {
                    $list[$key]['sku'][$k]['attrs'] .= $vv['name'] . ':' . $vv['val'] . ','; //规格
                }
                $list[$key]['sku'][$k]['qty'] = $value['quantity'] . $vv['close_unit']; //数量
            } 
            $list[$key]['colspan'] = count($list[$key]['sku']);
        }
        $return = $list;
        return $return;
    }
    
    /**
     * 根据线路id获取线路名称
     * @param number $line_id
     * @return string|Ambigous <string, unknown>
     */
    public function format_line($line_id = 0) {
        $return = '';
        
        //获取线路 只获取一次
        if (empty($this->line)) {
            $lines = D('Wave', 'Logic');
            $result = $lines->line();
            $this->line = $result;
        }
        if (empty($line_id)) {
            //返回所有
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
     * 根据pid获取出库单详情 支持批量获取
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