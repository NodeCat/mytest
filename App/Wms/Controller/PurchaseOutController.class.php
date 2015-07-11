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
class PurchaseOutController extends CommonController {
    protected $filter = array(
        'out_type'=>array( 
            'genuine'   =>'正品退货',
            'defective' =>'残次退货'
           ),
         'out_remark'=>array(
           'quality'=>'质量问题',
           'wrong'=>'收错货物',
           'replace'=>'替代销售',
           'unsalable'=>'滞销退货',
           'overdue'=>'过期退货',
           'other'=>'其他问题'
          ),
         'status'=>array(
            'draft' => '草稿',
            'audit'=>'待审核',
            'tbr'=>'待出库',
            'refunded'=>'已出库',
            'cancelled' => '已作废',
            'rejected' => '已驳回'
          ),
         'receivables_state'=>array(
           'wait'=>'待收款',
           'ok'=>'已收款'
          )

        );
    protected $columns = array (
         'id'                => '',
         'rtsg_code'         => '采退单号',
         'wh_name'             => '仓库',
         'partnername'       => '供应商',
         'out_type'        => '退货类型',
         'out_remark'       => '退货原因',
         'created_user_nickname'=> '创建人',
         'created_time'      => '创建时间',
         'status'        => '单据状态',
         'receivables_state' => '收款状态',
        );
    protected $query   = array (
            'stock_purchase_out.rtsg_code'         =>    array ( 
                    'title'               => '采退单号', 
                    'query_type'        => 'eq', 
                    'control_type'    => 'text', 
                    'value'               => 'rtsg_code',
            ),
            'stock_purchase_out.refer_code'         =>    array ( 
                    'title'               => '采购单号', 
                    'query_type'        => 'eq', 
                    'control_type'    => 'text', 
                    'value'               => 'refer_code',
            ),
            'stock_purchase_out.wh_id'      => array (     
                'title'                 => '仓库',     
                'query_type'            => 'eq',     
                'control_type'          => 'getField',     
                'value'                 => 'warehouse.id,name',
            ),
            'stock_purchase_out.out_type'     =>    array ( 
                'title'             => '退货类型', 
                'query_type'        => 'eq', 
                'control_type'      => 'select', 
                'value'             => array( 
                  'genuine'   =>'正品退货',
                  'defective' =>'残次退货'
                 ),
            ),
            'stock_purchase_out.status'     =>    array ( 
                'title'             => '单据状态', 
                'query_type'        => 'eq', 
                'control_type'      => 'select', 
                'value'             => array( 
                  'draft' => '草稿',
                  'audit'=>'待审核',
                  'tbr'=>'待出库',
                  'refunded'=>'已出库',
                  'cancelled' => '已作废',
                  'rejected' => '已驳回'
                 ),
            ),
            'stock_purchase_out.receivables_state'     =>    array ( 
                'title'             => '收款状态', 
                'query_type'        => 'eq', 
                'control_type'      => 'select', 
                'value'             => array( 
                  'wait'=>'待收款',
                  'ok'=>'已收款'
                 ),
                ),
            'stock_purchase_out.out_remark'     =>    array ( 
                'title'             => '退货原因', 
                'query_type'        => 'eq', 
                'control_type'      => 'select', 
                'value'             => array( 
                   'quality'=>'质量问题',
                   'wrong'=>'收错货物',
                   'replace'=>'替代销售',
                   'unsalable'=>'滞销退货',
                   'overdue'=>'过期退货',
                   'other'=>'其他问题'
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
            //'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"audit,cancelled,rejected"), //编辑
            //'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"audit"),//通过 批准
            //'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"audit"),//拒绝 驳回
            //'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"audit,rejected")//作废
        );
        $this->search_addon = true;
        //dump($this->);
    }

    public function view(){
        $pid = I('id');
        $m = M('stock_purchase_out_detail');
        $map['pid'] = $pid;
        $result = array();
        $result = $m->where($map)->select();
        $price_total = 0;
        $qty_total = 0;
        foreach ($result as $key => $value) {
          $price_total += $value['price_unit']*$value['real_return_qty'];
          $qty_total += $value['real_return_qty'];
        }

        $this->price_total = $price_total;
        $this->qty_total = $qty_total;
        $this->pros = $result;
        //加入权限
        $this->toolbar_tr =array(
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"audit,cancelled,rejected"), //编辑
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"audit"),//通过 批准
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"audit"),//拒绝 驳回
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"audit,rejected")//作废
        );
        parent::view();
  }

  /**
   * 确认收货
   *
   * @author liuguangping@dachuwang.com
   * @since 2015-06-15
   */
  public function confirm(){
      $ids = I('ids');
      if(!trim($ids)){
        $this->msgReturn('0','请选择内容！');
      }

      $m = M('stock_purchase_out');
      $where = array();
      $save = array();

      //确认收款：待退货或已收款的退货单不能确认收款
      $sql = "SELECT * FROM `stock_purchase_out` WHERE ( `id` IN (".$ids.") AND (`status` NOT IN ('refunded') OR `receivables_state` = 'ok') )";
      if($resut =$m->query($sql)){
        $this->msgReturn('1','请选择已出库和待收款的退货单，确认收款！');
      }

      unset($where);
      $where['id'] = array('in',$ids);
      $save['receivables_state'] = 'ok';

      if($m->where($where)->save($save)){
        $this->msgReturn('1','操作成功！');
      }else{
        $this->msgReturn('0','操作失败！');
      }
  }

  public function edit(){

    $id = I('id');
    $m = M('stock_purchase_out');
    $pm = M('stock_purchase_out_detail');
    $map['id'] = $id;
    $pmap['pid'] = $id;
    $this->mresult = $m->where($map)->find();
    $this->presult = $pm->where($pmap)->select();
    if(!$this->presult || !$this->mresult || !$id){
      $this->msgReturn('请正确操作');
    }
    $this->p_code = $this->mresult['refer_code'];
    $this->out_remarks = $this->mresult['out_remark'];
    $this->pids = $id;
    $this->wh_id = $this->mresult['wh_id'];
    $this->partner_id = $this->mresult['partner_id'];
    $this->remark = $this->mresult['remark'];
    $this->out_remark = C('OUTREMARK');
    if($this->mresult['out_type'] == 'genuine'){
        $flg = 'success';
    }elseif($this->mresult['out_type'] == 'defective'){
        $flg = 'error';
    }
    $this->flg = $flg;
    parent::edit();
  }

  public function doEdit(){
    //修改退货单
    $pid = I('pid');
    $out_remark = I('out_remark');
    $remark = I('remark');
    $pros = I('pros');
    if(!$pros || !$pid){
      $this->msgReturn('1','请正确修改！');
    }
    $pm = M('stock_purchase_out');
    $m = M('stock_purchase_out_detail');
    $pmap  = array();
    $psave = array();
    $pmap['id'] = $pid;
    if($out_remark){
      $psave['out_remark'] = $out_remark;
    }
    $psave['remark'] = $remark;
    $psave['updated_time'] = get_time();
    $psave['status'] = 'audit';
    $psave['updated_user'] = UID;
    if($pm->where($pmap)->save($psave)){
      foreach ($pros['id'] as $key => $value) {
        $map = array();
        $save = array();
        $map['id'] = $value;
        $save['plan_return_qty'] = intval($pros['plan_return_qty'][$key]);
        //如果计划量为零的话则删除
        if($save['plan_return_qty']<=0){
            $datau['is_deleted'] = 1;
            $m->where($map)->save($datau);
        }else{
            $save['updated_time'] = get_time();
            $save['updated_user'] = UID;
            $m->where($map)->save($save);
        }

      }
      //判断是否子集还有有数据没有把父也删除
      $pwhere = array();
      $pwhere['pid'] = $pid;
      $pwhere['is_deleted'] = 0;
      $pdata['is_deleted'] = 1;
      if(!$m->where($pwhere)->find()){
        $pm->where($pmap)->save($pdata);
      }
      $this->msgReturn('1','修改成功！','',U('index'));
    }else{
        $this->msgReturn('0','修改错误！');
    }

  }

  //批准
  public function pass(){
      $id = I('id');
      if(!$id){
          $this->msgReturn('0','请正确操作！');
      }
      //批准后才能进出库单
      $pm = M('stock_purchase_out_detail');
      $m = M('stock_purchase_out');
      $map['id'] = $id;
      $mresult = $m->where($map)->find();
      $pmap['pid'] = $id;
      $presult = $pm->where($pmap)->select();
      if(!$presult || !$mresult){
        $this->msgReturn('0','请正确操作！');
      }
      //插入出库单
      $purchaseOutLogic = A('PurchaseOut','Logic');
      $param = array();
      $param['wh_id'] = $mresult['wh_id'];
      $param['refer_code'] = $mresult['rtsg_code'];
      $param['remark'] = $mresult['remark'];
      if($purchaseOutLogic->addStockOut($presult,$param)){
        //修改状态
        $this->editStatus($id,'tbr');
      }else{
        //插入出库单表 出库单详细表失败，则删除退货单 详细
        //$purchaseoutM->where(array('id'=>$result))->save(array('is_deleted'=>1));
        //M('stock_purchase_out_detail')->where(array('pid'=>$result))->save(array('is_deleted'=>1));
        $this->msgReturn('1','提货单创建失败，审核失败！');
      }

  }

  //驳回
  public function reject(){
    $id = I('id');
    $this->editStatus($id,'rejected');
  }

  //作废
  public function close(){
    $id = I('id');
    $this->editStatus($id,'cancelled');
  }

  //编辑状态
  public function editStatus($id,$status){

      if(!trim($id) || !$status){
        $this->msgReturn('0','请合法操作');
      }

      $m = M('stock_purchase_out');
      $where = array();
      $save = array();

      $where['id'] = $id;
      $save['status'] = $status;

      if($m->where($where)->save($save)){
        $this->msgReturn('1','操作成功！');
      }else{
        $this->msgReturn('0','操作失败！');
      }
    }
   
  
}
/* End of file PurchaseOutController.class.php */
/* Location: ./Application/Controller/PurchaseOutController.class.php */
