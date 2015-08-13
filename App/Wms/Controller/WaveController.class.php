<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Controller;
use Think\Controller;
class WaveController extends CommonController {
	protected $filter = array(
        'company_id'=>array(
            '1' =>'大厨波次',
            '2' =>'大果波次'
           ),
         'status'=>array(
           '200'=>'待运行',
           '201'=>'运行中',
           '900'=>'已释放'
          )
        );
	protected $columns = array (
	       'id'                => '',
	       'wid'				    => '波次号',
	       'company_id'        => '所属系统',
	       'order_count'       => '总单数',
	       'line_count'        => '总行数',
         'total_count'       => '总件数',
         'status' 				    => '波次状态',
         'created_user_nickname'=> '创建人',
         'created_time'      => '创建时间',
         'start_time'        => '开始时间',
         'end_time'          => '结束时间',
		);
	protected $query   = array (
  			'stock_wave.id' 	    =>    array ( 
    			 	'title' 		      => '波次号', 
    			 	'query_type' 	    => 'eq', 
    			 	'control_type'    => 'text', 
    			 	'value' 		      => 'id',
  			),
  			'stock_wave.status'     =>    array ( 
  			 	'title'        	    => '波次状态', 
  			 	'query_type'   	    => 'eq', 
  			 	'control_type' 	    => 'select', 
  			 	'value'             => array(
  			 		'200'		          => '待运行',
  			 		'201'		          => '运行中',
  			 		'900'		          => '已释放',
  			 		),
			),
	);
	protected function before_index() {
        $this->table = array(
            'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
        );
        $this->search_addon = true;
        //dump($this->);
    }
    /**
     * 开始分拣
     *
     * @author liuguangping@dachuwang.com
     * @since 2015-06-15
     */
    public function packing(){
      	$ids = I('ids');
      	$m = M('stock_wave');
      	$waveLogic = A('Wave','Logic');
      	$hasIsAuth = $waveLogic->hasIsAuth($ids);
      	if($hasIsAuth === FALSE){
          $this->msgReturn('1','你所选的波次中包含运行中和已释放，请选择待运行波次！','');
        }
      	$controllerStatus = $waveLogic->execPack($ids);
      	if($controllerStatus === FALSE){
      		$this->msgReturn('1','分拣失败！','');
      	}else{
      		$this->msgReturn('0','操作成功，分拣中！','');
      	}
    	
    }
    /**
     * 删除波次
     *
     * @author liuguangping@dachuwang.com
     * @since 2015-06-15
     */
    public function delAll(){
      	$ids = I('ids');
      	$m = M('stock_wave');
      	$waveLogic = A('Wave','Logic');
      	$hasIsAuth = $waveLogic->hasIsAuth($ids);

      	if($hasIsAuth === FALSE){
            $this->msgReturn('1','你所选的波次中包含运行中和已释放，请选择待运行波次！','');
        }
        
      	$controllerStatus = $waveLogic->delWave($ids);

      	if($controllerStatus === FALSE){
      		$this->msgReturn('1','删除失败！','');
      	}else{
      		$this->msgReturn('0','删除成功！','');
      	}
    }
    public function after_search(&$map){
    	$map['wh_id'] = session('user.wh_id');
    }
    public function view(){
        $pid = I('id');
        $m = M('stock_wave_detail');
        $map['pid'] = $pid;
        $map['is_deleted'] = 0;
        $result = array();
        $result = $m->where($map)->select();
        if($result){
            foreach ($result as $key => $value) {
                $bill_out = M('stock_bill_out')->where(array('id'=>$value['bill_out_id']))->find();
                $type_cn = M('stock_bill_out_type')->where(array('id'=>$bill_out['type']))->getField('name');
                //根据pro_code 查询对应的pro_name
                /*$pro_codes = array($data['pro_code']);
                $SKUs = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
                $data['pro_name'] = $SKUs[$data['pro_code']]['wms_name'];*/
                //区域标识
                //$location_info = A('Location','Logic')->getParentById($bill_out['line_id']);
                //echo $bill_out['line_id'];die;
                $result[$key]['area_name'] = getLineNameByid($bill_out['line_id']);//$location_info['name'];
                //dump($result[$key]['area_name']);die;
                $result[$key]['type_cn'] = $type_cn;
                $status_cn = A('Wave', 'Logic')->getStatusCn($bill_out['status']);
                $result[$key]['status_cn'] = $status_cn;
                $process_type_cn = '';
                $order_count = 0;
                $count = A('StockOut','Logic')->sumStockBillOut($value['bill_out_id']);
                if(isset($count['totalCount'])) $order_count = $count['totalCount'];
                if($bill_out['process_type'] == 1) $process_type_cn = '正常单';
                if($bill_out['process_type'] == 2) $process_type_cn = '取消单';
                $result[$key]['process_type_cn'] = $process_type_cn;
                if($bill_out['delivery_date'] == "0000-00-00 00:00:00" || $bill_out['delivery_date'] == "1970-01-01 00:00:00") {
                  $bill_out['delivery_date'] = '无';
                }else {
                  $bill_out['delivery_date'] = date('Y-m-d',strtotime($bill_out['delivery_date'])) .'<br>'. $bill_out['delivery_time'];
                
                }
                $result[$key]['delivery_date'] = $bill_out['delivery_date'];
                $result[$key]['order_qty_count'] = $order_count;
                $result[$key]['bill_out'] = $bill_out;
                
            }
        }
        $this->assign('waveDetail',$result);
        //dump($result);
        parent::view();
  }
  /**
   * 分拣任务Hook
   *
   * @author liuguangping@dachuwang.com
   * @since 2015-06-15
   */
  public function packTask(){
    	//@todo这里加个钩子调用李昂的分拣接口
      $ids       = I('ids');
      $waveLogic = A('Wave','Logic');
      $hasIsAuth = $waveLogic->hasIsAuth($ids, '900');
      if($hasIsAuth === FALSE){
        $this->msgReturn('1','你所选的波次中包含运行中和已释放，请选择待运行波次！','');
      }
      $wave_ids  = explode(',', $ids);

      $pickInfo = A('WavePicking','Logic')->waveExec($wave_ids);
      
      //分拣完成后弹出弹框提示 zhangchaoge
      if ($pickInfo['status'] != 1) {
          $this->msgReturn(false, '分拣失败');
      }
      $hintInfo = $pickInfo['alert'];
      $orderInfo = array();
      foreach ($hintInfo['orderids'] as $key => $orderid) {
          //获取出库单信息
          $orderCode = M('stock_bill_out')->where(array('id'=>$orderid))->find();
          //获取此出库单下的缺货SKU编号
          $result = A('Stock', 'Logic')->checkStockIsEnoughByOrderId($orderid);
          $orderInfo['detail'][$key]['code'] = $orderCode['code'];
          $orderInfo['detail'][$key]['sku']  = implode(',', $result['data']['not_enough_pro_code']);
      }
      //总单数
      $orderInfo['ordersum']  = $hintInfo['ordersum'];
      //总任务数
      $orderInfo['tasksum']   = $hintInfo['tasksum'];
      //被踢回的订单数
      $orderInfo['rejectsum'] = count($hintInfo['orderids']);
      //被删除的订单
      $orderInfo['sumZero'] = count($hintInfo['sumZero']);
      $this->msgReturn(true, '已完成', $orderInfo);
  }
}
/* End of file WaveController.class.php */
/* Location: ./Application/Controller/WaveController.class.php */
