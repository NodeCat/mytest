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
class PickController extends CommonController {
  protected $filter = array(
      'type'=>array(
          'picking' =>'拣货'
         ),
       'status'=>array(
         'draft'=>'未运行',
         'picking'=>'执行中',
         'done'=>'已完成'
        ),
       'line_id'=>array(),
       /*'is_print'=>array(
         'ON'=>'打印',
         'OFF'=>'未打印'
        )*/
  );

  protected $columns = array (
      'id'                => '',
      'code'              => '分拣单号',
      'type'              => '类型',
      'wave_id'           => '波次号',
  	  'status'			      => '状态',
      'line_id'           => '线路片区',
  		'order_sum'         => '订单数',
  		'pro_type_sum'      => '总行数',
  		'pro_qty_sum'       => '总件数',
      /*'is_print'          => '是否打印',*/
      'created_user_nickname' => '创建人',
  		'created_time'      => '开始时间',
  		'updated_time'      => '结束时间',
  );
	protected $query   = array (
      'stock_wave_picking.code'   =>    array ( 
        'title'         => '分拣单号', 
        'query_type'    => 'eq', 
        'control_type'  => 'text', 
        'value'         => 'code',
      ),
      'stock_wave_picking.type'   =>    array ( 
                'title'           => '类型', 
                'query_type'      => 'eq', 
                'control_type'    => 'select', 
                'value'           => array(
                'picking'        => '拣货'
                    ),
        ),
      'stock_wave_picking.wave_id'      =>    array ( 
                'title'                 => '波次号', 
                'query_type'            => 'eq', 
                'control_type'          => 'text', 
                'value'                 => 'wave_id',
        ),
      'stock_wave_picking.status'       =>    array ( 
                'title'                 => '状态', 
                'query_type'            => 'eq', 
                'control_type'          => 'select', 
                'value'                 => array(
                    'draft'             => '未运行',
                    'picking'           => '执行中',
                    'done'              => '已完成',
                    )
        ),
      'stock_wave_picking.line_id'      => array (     
                'title'                 => '路线片区',     
                'query_type'            => 'eq',     
                'control_type'          => 'select',     
                'value'                 => '' 
        ),
      /*'stock_wave_picking.is_print'       =>    array ( 
                'title'                 => '已打印', 
                'query_type'            => 'eq', 
                'control_type'          => 'checkbox', 
                'value'                 => 'is_print'
        ),*/
  );
  public function __construct(){
      parent::__construct();
      //修改线路value值
      $lines = A('Wave','Logic')->line();
      //dump($lines);die;
      $this->query['stock_wave_picking.line_id']['value'] = $lines;
      $this->filter['line_id'] = $lines;
  }
	protected function before_index() {
      $this->table = array(
          'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
          'searchbar' => true, //是否显示搜索栏
          'checkbox'  => true, //是否显示表格中的浮选款
          'status'    => false, 
          'toolbar_tr'=> true,
          'statusbar' => false
      );
      $this->toolbar_tr =array(
          'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
      );
      
      $this->search_addon = true;
  }
  public function after_search(&$map){
      $map['wh_id'] = session('user.wh_id');
  }
  public function view(){
      $pid = I('id');
      $m = M('stock_wave_picking_detail');
      $map['stock_wave_picking_detail.pid'] = $pid;
      $result = array();
      //按照库位标识排序
      $result = $m
      ->join('location on stock_wave_picking_detail.src_location_id = location.id')
      ->where($map)
      ->order('location.code')->select();

      $result = A('Pms','Logic')->add_fields($result,'pro_name');
      $this->assign('pickDetail',$result);
      parent::view();
  }
  /*protected function after_lists(&$data) {
      $lines = A('Wave','Logic')->line();
      foreach($data as $k => $v){
          $data[$k]['line_id_name'] = $lines[$v['line_id']];
      }
  }*/
  /**
   * 分拣单打印
   *
   * @author liuguangping@dachuwang.com
   * @since 2015-06-15
   */
  public function pickPrint(){
      if(!IS_GET) $this->error('请正确操作！');
      $ids = I('ids');
      if(!$ids) $this->error('请选择出库单再操作！');
      $idsArr = explode(',', $ids);
      $map = array();
      $items = array();
      $map['id'] = array('in',$idsArr);
      $map['is_deleted'] = 0;
      $m = M('stock_wave_picking');
      $detail = M('stock_wave_picking_detail');
      $pickArr = $m->where($map)->select();
      unset($map);
      if($pickArr){
          foreach($pickArr as $key => $value){

              $items[$key]['items'] = '';
              $items[$key]['detail'] = '';
              $items[$key]['items'] = $value;
              $items[$key]['items']['barcode'] = "http://api.pda.dachuwang.com/barcode/get?text=".$value['code'];
              $map['stock_wave_picking_detail.pid'] = $value['id'];
              $map['stock_wave_picking_detail.is_deleted'] = 0;

              //按照库位标识排序
              $child = $detail
              ->join('location on stock_wave_picking_detail.src_location_id = location.id')
              ->where($map)
              ->order('location.code')->select();
              
              $child = A('Pms','Logic')->add_fields($child,'pro_name');
              if($child){
                $items[$key]['detail'] = $child;
              }
          }
      }else{
        $this->error('操作失败！');
      }
      
      //结果集处理
      /*foreach ($items as &$val) {
          foreach ($val['detail'] as $k => $v) {
              if (!isset($val['detail'][$v['pro_code']])) {
                  $val['detail'][$v['pro_code']] = $v;
                  unset($val['detail'][$k]);
              } else {
                  $val['detail'][$v['pro_code']]['pro_qty'] += $v['pro_qty'];
                  unset($val['detail'][$k]);
              }
          }
      }*/
      layout(false);

      $this->assign('list',$items);

      $this->display('Pick::pickPrint');  
  }
  /**
   * 分拣单打印状态修改
   *
   * @author liuguangping@dachuwang.com
   * @since 2015-06-15
   */
  public function doPrint(){
      $ids = I('ids');
      $map = array();
      if($ids){
        $map['id'] = array('in',$ids); 
        $data = array('is_print'=>'ON');
        $m = M('stock_wave_picking');
        $m->where($map)->save($data);
      }
  }
  /**
   * pda 打印单
   *
   * @author liuguangping@dachuwang.com
   * @since 2015-06-15
   */
  public function pickOn($t = 'scan_incode'){
      $this->cur = '拣货';
      C('LAYOUT_NAME','pda');
      if(IS_GET) {
        switch ($t) {
          case 'scan_incode':
            $this->title = '批量拣选';
            $tmpl = 'Pick:in_scan_Sorting';
            break;
        }
        $this->display($tmpl);
      }
      if(IS_POST){
        $m = M('stock_wave_picking');
        $code = I('code');//拣货单号
        $type = I('t');//类型
        if($type === 'scan_outcode'){
          $map['status'] = 'done';
          $map['code'] = $code;
          if($m->where($map)->getField('id')){
              $this->msgReturn(0, '该分拣单已经拣货完成,请不要重复操作！');
          }
          //修改库存
          if(A('WavePicking','Logic')->updateBiOuStock($code)){
            $result = array();
            $result['title'] = '拣货确认';
            $result['code_qty'] = I('code_qty');
            $result['cut'] = 'scan_outcode';
            $url = '/Pick/pickOn';
            $result['url'] = $url;
            $this->msgReturn(2, '分拣单拣货完成!', $result, $url);
          }else{
            $this->msgReturn(0, '分拣失败！');
          }
        }elseif($type == 'scan_incode'){
          if(!$code){
             $this->msgReturn(0, '请扫描分拣单条形码');
          }

          $map = array();
          $map['code'] = $code;
          $map['status'] = array('in','draft,picking');
          $map['is_deleted'] = 0;

          $order_sum = $m->where($map)->getField('order_sum');

          if(!$order_sum){
              $this->msgReturn(0, '该分拣单已经拣货完成,请不要重复操作！');
          }

          $result = array();
          $result['title'] = '拣货确认';
          $result['code_qty'] = $order_sum;
          $result['cut'] = 'scan_outcode';

          $save = array();
          $save['status'] = 'picking';
          $save['updated_time'] = date('Y-m-d H:i:s',time());

          if(!$m->where($map)->save($save)){
              $this->msgReturn(0, '操作失败，请重新提交');
          } 

          $this->msgReturn(1, '请确认拣货单', $result);
        }else{
          $this->msgReturn(0, '请正确操作！');
        }
        
      }
  }
  
}
/* End of file PackController.class.php */
/* Location: ./Application/Controller/PackController.class.php */
