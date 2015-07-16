<?php
namespace Wms\Api;
use Think\Controller;

/**
 * 配送单接口
 */
class DistApi extends CommApi {

	protected $model;

	protected function _initialize () {
		$this->model = M('stock_wave_distribution');
	}
    
    //配送单主表数据
    public function distInfo($id) {
    	if(!$id) {
    		return false;
    	}
    	$map['id'] = $id;
        $map['is_deleted'] = 0;
        $dist = $this->model->where($map)->find();
        return $this->_return($dist);
    }
    
    /**
     * [lists 出库单列表]
     * @param  array  $map [查询条件，排序条件]
     * @return [type]      [description]
     */
    public function lists($map = array(),$limit = 100) {
        if($json = I('post.json/d')) {
            $map = $_POST;
        }
        if($map['dist_id']) {
            //配送单详情查询出库单id,签收状态
            $dist_detail = M('stock_wave_distribution_detail')
            ->field('bill_out_id,status')
            ->where(array('pid' => $map['dist_id']))
            ->select();
            if($dist_detail) {
                $detail_ids = array();
                foreach ($dist_detail as $value) {
                    $detail_ids[] = $value['bill_out_id'];
                }
            }
        }
        if(empty($detail_ids)) {
            return $this->_return(array(), $json);
        }
        //查询条件
        $map['id'] = array('in', $detail_ids);
        $limit = count($detail_ids);
        $map['is_deleted'] = 0;
        $order_conditions = $map['order'];
        unset($map['dist_id']);
        unset($map['order']);
        //排序条件
        if(is_array($order_conditions)) {
            foreach ($order_conditions as $key => $value) {
                $order .= $key . ' ' . $value . ',';
            }
            $order = substr($order, 0, -1);
        }
        else {
            $order = 'created_time DESC';
        }
        //出库单列表
        $list = $this->model
        ->table('stock_bill_out')
        ->where($map)
        ->order($order)
        ->limit($limit)
        ->select();
        unset($map);
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
        //查询出库单详情、签收状态
        if($bill_out_ids){
            $map['pid'] = array('in',$bill_out_ids);
            $list_details = $this->model->table('stock_bill_out_detail')->where($map)->select();
            foreach ($list_details as $v) {
                foreach ($list as &$bill) {
                    if($bill['id'] == $v['pid']){
                        $bill['detail'][] = $v;
                    }
                }
            }
        }
        return $this->_return($list, $json);
        
    }

    /**
     * [_return 返回数据]
     * @param  [type]  $data [要返回的数据]
     * @param  integer $json [是否需要ajax返回]
     * @return [type]        [description]
     */
    public function _return($data, $json = 0) {
        if($json) {
            $this->ajaxReturn($data);
        }
        else {
            return $data;
        }
    } 
}
