<?php
namespace Wms\Logic;

class OrderLogic{
	protected $server = '';
	protected $request ;
    public function __construct(){
    	$this->server = C('HOP_API_PATH');
		import("Common.Lib.HttpCurl");
		$this->request = new \HttpCurl();
    }
    public function operate($map='') {
    	$url = '/wave/create_wave2';
		$res = $this->get($url,$map);
		return $res;
    }

	public function order($map=''){
		$url = '/suborder/lists';
		$res = $this->get($url,$map);
		return $res['orderlist'];
	}
	public function sign($map='') {
    	$url = '/suborder/set_status';
		$res = $this->get($url,$map);
		return $res;
    }
	public function line($map='') {
		$url = '/line/lists';
		$res = $this->get($url,$map);
		return $res['list'];
	}
	public function weight_sku($map='') {
		$url = '/order/weight_sku';
		$res = $this->get($url,$map);
		return $res;
	}
	public function get_details_by_wave_and_sku($map='') {
		$url = '/order/get_details_by_wave_and_sku';
		$res = $this->get($url,$map);
		return $res;
	}

	public function city() {
		$url = '/location/get_child';
		$res = $this->get($url);
		foreach ($res['list'] as $key => $val) {
			unset($res['list'][$key]);
			$res['list'][$val['id']] = $val['name'];
		}
		return $res['list'];
	}
	public function distInfo($map='') {
		$url = '/distribution/view';
		$res = $this->get($url,$map);
		return $res['info'];
	}
	public function get($url,$map='') {
		$url = $this->server . $url;
		$map = json_encode($map);
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
	//根据order_id 或者 order_number 查询订单信息
	public function getOrderInfoByOrderId($orderId){
		if(empty($orderId)){
			return false;
		}
		$url = $this->server . '/suborder/info';
		$map = json_encode(array('suborder_id'=>$orderId));
		$res = $this->request->post($url,$map);
		$res = json_decode($res,true);
		return $res;
	}
	
	/**
	 * 根据订单ID批量获取订单
	 * @param array ids 订单id数组
	 * @return array
	 */
	public function getOrderInfoByOrderIdArr($ids = array()) {
	    $return = array('status' => false, 'msg' => '');
	    
	    if (empty($ids)) {
	        $return['msg'] = '参数有误';
	    }
	    $url = $this->server . '/suborder/lists';
	    $map = json_encode(array('suborder_ids' => $ids, 'itemsPerPage' => count($ids)));
	    $res = $this->request->post($url, $map);
	    $res = json_decode($res, true);
	     
	    if ($res['status'] == 0) {
	        $return['status'] = true;
	        $return['msg'] = '成功';
	        $return['list'] = $res['orderlist'];
	    } else {
	        $return['msg'] = '没有符合条件的订单';
	    }
	    return $return;
	}

	//判断订单中的商品合法性
    public function judgeCode($pro_code_arr = array(), $order_number = '')
    {
        $return = array('status'=>-1,'data'=>'','msg'=>'出错！');
        $map = array();
        $map['a.refer_code'] = $order_number;
        $map['a.pro_code']   = array('in', $pro_code_arr);
        $map['a.is_deleted']   = 0;
        $map['b.is_deleted']   = 0;
        $stock_bill_out_container = M('stock_bill_out_container');
        $join = array('as a join stock_bill_out as b on b.code = a.refer_code');
        $res = $stock_bill_out_container->field('a.pro_code')->join($join)->where($map)->group('a.pro_code')->select();
        if (!$res) {
            $return = array('status'=>-1,'data'=>'','msg'=>'查询出库报错！');
        }
        $bill_pro_code_arr = array_column($res, 'pro_code');
        //以第一个数组为基础去差集
        $intersection = array_diff($pro_code_arr, $bill_pro_code_arr);
        if ($intersection){
            $return = array('status'=>-1,'data'=>$intersection,'msg'=>'ERO');
        } else {
            $return = array('status'=>0,'data'=>'','msg'=>'SUC');
        }
        return $return;

    }

    //判断订单退货量是否合法（退货量是否大于出库量） $order_infos 客退详细信息 order_code 订单单号 订单单号==出库单单
    public function judgeOutQty($order_infos = array(),$order_code)
    {
        $return = array('status'=>-1,'data'=>'','msg'=>'');
        if ($order_infos) {
            $pro_code_arr = array_column($order_infos, 'code');
            if ($pro_code_arr && $order_code) {
                $stock_bill_out_container = M('stock_bill_out_container');
                $map = array();
                $map['a.refer_code'] = array('in', $order_code);
                $map['a.pro_code']   = array('in', $pro_code_arr);
                $map['a.is_deleted']   = 0;
                $map['b.is_deleted']   = 0;

                $join = array('as a join stock_bill_out as b on b.code = a.refer_code');
                $res = $stock_bill_out_container->field('a.pro_code,a.refer_code,sum(a.qty) as qty,b.code as order_code')->join($join)->where($map)->group('a.pro_code,b.code')->select();
                if ($res) {
                    //查询入库单入库量
                    $bill_in_detail_m = M('stock_bill_in_detail');
                    $where = array();
                    $where['a.pro_code'] = array('in',$pro_code_arr);
                    $where['b.refer_code'] = array('in',$order_code);
                    $where['b.is_deleted'] = 0;
                    $where['a.is_deleted'] = 0;
                    $joins = array('as a join stock_bill_in as b on a.pid = b.id');
                    $bill_in_res = $bill_in_detail_m->field('a.pro_code,b.code,sum(a.expected_qty) as qty,b.refer_code as order_code')->join($joins)->where($where)->group('a.pro_code,b.refer_code')->select();
                    $expected_qty_arr = array();
                    foreach ($bill_in_res  as $index => $vals) {
                        $expected_qty_arr[$vals['order_code'].'_qty_'.$vals['pro_code']] = $vals['qty'];
                    }
                    //判断退货量是否大于出库量
                    //客退量大与出库量数据
                    $unqulify = array();
                    $error_unqulify = array();
                    foreach($res as $val){
                        foreach ($order_infos as $value) {
                            if ($value['code'] == $val['pro_code']){
                                //出库单的量是 出库量-入库量 等于这次该入库的量
                                $bill_in_qty = isset($expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']])?$expected_qty_arr[$val['order_code'].'_qty_'.$val['pro_code']]:0;
                                $pro_qty = bcsub($val['qty'], $bill_in_qty, 2);
                                if (bccomp($value['qty'], $pro_qty, 2) == 1) {
                                    array_push($unqulify, $val['order_code']);
                                    $mes = "订单号" . $val['order_code'] . '中的商品编号为' . $val['pro_code'] ;
                                    array_push($error_unqulify,$mes);
                                }
                                //array_push($qulify, $val['order_code']);
                            }
                        }
                    }

                    if ($unqulify) {
                        $return = array('status'=>-1,'data'=>$unqulify,'msg'=>implode(',', $error_unqulify).'客退量大与客退量');
                    } else {
                        $return = array('status'=>0,'data'=>'','msg'=>'SUC');
                    }
                    
                } else {
                    $return = array('status'=>-1,'data'=>'','msg'=>'请选择正确的合法商品和订单');
                }
            } else {
                $return = array('status'=>-1,'data'=>'','msg'=>'请选择正确的合法商品和订单');
            }
        }
        return $return;
    }
}