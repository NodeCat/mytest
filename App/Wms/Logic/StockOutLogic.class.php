<?php
namespace Wms\Logic;
class StockOutLogic{
	/**
	* 创建出库单
	*  
	* $params = array(
	* 	'wh_id'=>'',      //仓库id
    *   'type'=>'',       //出库单类型(具体查看配置中的出库单类型设置,默认是SO)
	*	'line_id'=>'',            //线路名称(目前存的就是线路的字符串)
    *   'refer_code'=>'',           //关联单据号
    *   'detail'=>array(array('pro_code'=>'','order_qty'=>''),
    *                         array('pro_code'=>'','order_qty'=>''),
    *                        ....
    *                           ),
	* )
	*
	*/
   public function addStockOut($params = array()) {
   		if(empty($params['wh_id']) || empty($params['detail'])){
   			return false;
   		}
        //查找出库单类型
        $stock_out_type = isset($params['type'])? $params['type'] : 'SO';
        $map['type'] = $stock_out_type;
        $type = M('stock_bill_out_type')->where($map)->getField('id');
        unset($map);
        $data['code'] = ($params['code']) ? $params['code'] : get_sn($stock_out_type, $params['wh_id']);
        $data['wh_id'] = $params['wh_id'];
        $data['line_id'] = isset($params['line_id']) ? $params['line_id'] : '';
        $data['type'] = $type;
        $data['status'] = 1;//创建出库单的初始状态默认是待出库
        $data['process_type'] = 1;//出库单处理类型默认是正常单
        $data['refused_type'] = 1;//出库单拒绝类型默认是空
        $data['refer_code'] = isset($params['refer_code'])? $params['refer_code'] : '';
        $data['delivery_date'] = $params['delivery_date'];
        $data['delivery_time'] = $params['delivery_time'];
        $data['delivery_ampm'] = $params['delivery_ampm'];
        $data['customer_realname'] = $params['customer_realname'];
        $data['customer_id'] = $params['customer_id'];
        $data['delivery_address'] = $params['delivery_address'];
        $data['op_date'] = $params['op_date'];
        $data['order_type'] = $params['order_type'];
        $data['created_time'] = date('Y-m-d H:i:s');
        $data['created_user'] = UID;
        $data['updated_time'] = date('Y-m-d H:i:s');
        $data['updated_user'] = UID;
        $data['is_deleted'] = 0;
        //添加出库单
        $stock_out_id = M('stock_bill_out')->add($data);
        //请求pms数据
        $pro_codes = array_column($params['detail'],'pro_code');
        $pms = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        unset($pro_codes);
        $total = 0;//计算一个出库单的总出库数量
        $total_amount = 0; //总金额
        //添加明细
        foreach($params['detail'] as $val) {
            $detail = array();
            $detail['pid'] = $stock_out_id;
            $detail['pro_code'] = $val['pro_code'];
            $detail['order_qty'] = $val['order_qty'];
            $detail['former_qty'] = $val['order_qty'];
            $detail['price'] = $val['price'];
            //如果是加工出库单 采购出库单，则默认的实际发货量为0，其余类型出库单默认发货量等于订单量
            if(in_array($stock_out_type , array('MNO','SO')) ) {
                $detail['delivery_qty'] = 0;
            }else {
                $detail['delivery_qty'] = $val['order_qty'];
            }
            $detail['pro_name'] = $pms[$val['pro_code']]['name'];  
            //拼接货品的规格
            unset($detail['pro_attrs']);
            foreach($pms[$val['pro_code']]['pro_attrs'] as $k=>$v) {
                $detail['pro_attrs'] .= $v['name'] . ":" . $v['val'] . ",";
            }
            $detail['wh_id'] = $params['wh_id'];
            $detail['pro_attrs'] = substr($detail['pro_attrs'], 0, strlen($detail['pro_attrs'])-1);
            $detail['status'] = 1;
            $detail['created_time'] = date('Y-m-d H:i:s');
            $detail['created_user'] = UID;
            $detail['updated_time'] = date('Y-m-d H:i:s');
            $detail['updated_user'] = UID;
            $detail['is_deleted'] = 0;
            $total += $val['order_qty'];
            $res = M('stock_bill_out_detail')->add($detail);

            $total_amount += $val['order_qty'] * $val['price'];
        }
        
        $stock_out_data['total_qty'] = $total;
        $stock_out_data['total_amount'] = $total_amount;
        $map['id'] = $stock_out_id;
        M('stock_bill_out')->where($map)->save($stock_out_data);
       
        return ture;
    } 

    /**
     * 如果没有选择出库单，则根据条件搜索到的出库单ids做相应的操作
     * 
     * @author liuguangping@dachuwang.com
     * @return Boolean $result;
     * 
     */
    public function getSearchDate($whereArr = array()){
        $map                = array();
        $map['is_deleted']  = 0;
        $result             = array();
        $code               = $whereArr['code'];
        $wave_id            = $whereArr['wave_id'];
        $type               = $whereArr['type'];
        $refused_type       = $whereArr['refused_type'];
        $line_id            = $whereArr['line_id'];
        $process_type       = $whereArr['process_type'];
        $created_time       = $whereArr['created_time'];
        $created_time_1     = $whereArr['created_time_1'];
        /*$customer_realname    = I('customer_realname');
        $delivery_address   = I('delivery_address');*/
        $delivery_date      = $whereArr['delivery_date'];
        $delivery_ampm      = $whereArr['delivery_ampm'];
        $company_id         = $whereArr['company_id'];
        $status             = $whereArr['status'];
        if($code) $map['code'] = $code;
        if($wave_id) $map['wave_id'] = $wave_id;
        if($type) $map['type'] = $type;
        if($refused_type) $map['refused_type'] = $refused_type;
        if($line_id) $map['line_id'] = $line_id;
        if($process_type) $map['process_type'] = $process_type;
        if($customer_realname) $map['customer_realname'] = array('like','%'.$customer_realname.'%');
        if($delivery_address) $map['delivery_address'] =array('like','%'.$delivery_address.'%');
        if($delivery_date) $map['delivery_date'] = $delivery_date;
        if($delivery_ampm) $map['delivery_ampm'] = $delivery_ampm;
        if($company_id) $map['company_id'] = $company_id;
        if($status) $map['status'] = $status;
        if($created_time && $created_time_1){
            if($created_time >= $created_time_1){
                $map['created_time'] = array('gt', $created_time);
            }else{
                $map['created_time'] = array('between', array($created_time, $created_time_1));
            }
        }elseif($created_time && !$created_time_1){
            $map['created_time'] = array('gt', $created_time);
        }elseif(!$created_time && $created_time_1){
            $map['created_time'] = array('lt', $created_time_1);
        }
        if(!empty($map)){
            $m = M('stock_bill_out');
            $map['wh_id'] = session('user.wh_id');
            $result = $m->field('id')->where($map)->select();
            $result = getSubByKey($result, 'id');
            return implode(',', $result);
        }else{
            return $result;
        }
        
    }

    /**
     * 根据出库单Id判断出库单是否可以创建波次
     * 
     * @param String $ids 出库单id 
     * @author liuguangping@dachuwang.com
     * @return Boolean $result;
     * 
     */
    public function hasProductionAuth($ids = ''){
        if(!$ids) return FALSE;
        $map = array();
        $map['status'] =  array('neq', '1');
        $map['id'] = array('in', $ids);
        $m = M('stock_bill_out');
        $result = $m->where($map)->select();
        if($result) return FALSE;
        return TRUE;
    }
    /**
     * 查看出库单中所有sku是否满足数量需求出库单
     * 
     * @param String $ids 条件
     * @param String $loc_type 库位
     * @param String $batch_code 批次
     * @author liuguangping@dachuwang.com
     * @return String $Result;
     * 
     */
    public function enoughaResult($ids, $loc_type = null, $batch_code = null){
        $idsArr = explode(',', $ids);
        $result = array();
        $result['tureResult'] = array();
        $result['falseResult'] = array();
        if(!$idsArr) return '';
        foreach($idsArr as $key=>$value){
            $is_enough = A('Stock','Logic')->checkStockIsEnoughByOrderId($value, $loc_type, $batch_code);
            if($is_enough['status'] == 1){ 
                array_push($result['tureResult'], $value);
            }else{
                $tablename = 'stock_bill_out';
                $data['refused_type'] = 2;
                $map['id'] = $value;
                A('Wave','Logic')->updateStauts($tablename, $data, $map);
                array_push($result['falseResult'], $value);
            }
        }
        $result['tureResult'] = implode(',', $result['tureResult']);
        return $result;
    }

    /**
     * 根据出库单格式化出库单数据 (预计出库量,SKU总数)
     *  
     * @param String $ids 出库单id
     * @author liuguangping@dachuwang.com
     * @return Array $data;
     * 
     */
    public function sumStockBillOut($idsArr){
        $m = M('stock_bill_out_detail');
        $map['pid']  = array('in',$idsArr);
        $skuCount   =  count($m->field('count(id) as num')->where($map)->group('pro_code')->select());
        $totalCount = $m->where($map)->sum('order_qty');//预计出库量
        $data       = array();
        $data['skuCount']   = $skuCount?$skuCount:0;
        $data['totalCount'] = $totalCount?$totalCount:0;
        return $data;
    }

    /**
     * [lists 出库单列表]
     * @param  array  $params dist_id或bill_out_ids,可选order
     * @return [type]      [description]
     */
    public function bill_out_list($params = array()) {
        $dist_id = isset($params['dist_id']) ? $params['dist_id'] : 0;
        $bill_out_ids = isset($params['bill_out_ids']) ? $params['bill_out_ids'] : array();
        if (empty($dist_id) && empty($bill_out_ids)) {
            return array(
                'status' => -1,
                'msg'    => '配送单ID或出库单ID不能为空'
            );
        }
        if (empty($bill_out_ids)) {
            //配送单详情查询出库单id,签收状态
            $map['pid'] = $params['dist_id'];
            $map['is_deleted'] = 0;
            $dist_detail = M('stock_wave_distribution_detail')
                ->field('bill_out_id,status')
                ->where($map)
                ->select();
            //组合一个出库单ID数组
            if ($dist_detail) {
                $bill_out_ids = array();
                foreach ($dist_detail as $value) {
                    $bill_out_ids[] = $value['bill_out_id'];
                }
            }
        } else {
            //配送单详情查询签收状态
            $map['bill_out_id'] = array('in', $bill_out_ids);
            $map['is_deleted'] = 0;
            $dist_detail = M('stock_wave_distribution_detail')
                ->field('id,status')
                ->where($map)
                ->select();
        }
        //如果出库单ID数组为空
        if (empty($bill_out_ids)) {
            return array(
                'status' => -1,
                'msg'    => '参数错误'
            );
        }
        unset($map);
        //查询条件
        $map['id'] = array('in', $bill_out_ids);
        $map['is_deleted'] = 0;
        //排序条件
        $order = isset($params['order']) ? $params['order'] : 'created_time DESC';
        //出库单列表
        $list = M('stock_bill_out')
            ->where($map)
            ->order($order)
            ->select();
        unset($map);
        if ($list) {
           $bill_out_ids = array();
           foreach ($list as &$value) {
               $bill_out_ids[] = $value['id'];
               foreach ($dist_detail as $v) {
                   //配送单详情签收状态
                   if ($value['id'] == $v['bill_out_id']) {
                       $value['sign_status'] = $v['status'];
                   }
               }
           }
           //查询出库单详情关联到出库单列表
           if($bill_out_ids){
               $map['pid'] = array('in',$bill_out_ids);
               $map['is_deleted'] = 0;
               $list_details = M('stock_bill_out_detail')->where($map)->select();
               foreach ($list_details as $v) {
                   foreach ($list as &$bill) {
                       if($bill['id'] == $v['pid']){
                           $bill['detail'][] = $v;
                       }
                   }
               }
           }
           return array(
                'status' => 0,
                'list'   => $list
            ); 
        }
        return array(
            'status' => -1,
            'msg'    => '数据不存在'
        );        
    }
}
