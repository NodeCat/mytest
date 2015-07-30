<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangping@dachuwang.com>
// +----------------------------------------------------------------------
namespace Wms\Controller;
use Think\Controller;
class TransferController extends CommonController
{
    protected $filter = array(
      
       'state'=>array(
            'draft' => '草稿',
            'audit'=>'待审核',
            'tbr'=>'待出库',
            'refunded'=>'已出库',
            'cancelled' => '已作废',
            'rejected' => '已驳回'
        ),
       'wh_id_out'=>'',

       'wh_id_in'=>'',
    );
    protected $columns = array (
        'id' => '',
        'trf_code' => '调拨单号',
        'wh_id_out' => '调出仓库',
        'wh_id_in' => '调入仓库',
        'plan_cat_total' => '计划货品种数',
        'plan_qty_tobal' => '计划货品件数',
        'created_user_nickname' => '创建人',
        'created_time' => '创建时间',
        'state' => '调拨状态'
    );
    protected $query   = array (
        'erp_transfer.trf_code' => array(
                'title' => '调拨单号',
                'query_type' => 'eq',
                'control_type' => 'text',
                'value' => 'code',
        ),
        'erp_transfer.wh_id_out' => array(
               'title' => '调出仓库',
               'query_type' => 'eq',
               'control_type' => 'select',
               'value' => '',
        ),
        'erp_transfer.wh_id_in' => array(
               'title' => '调入仓库',
               'query_type' => 'eq',
               'control_type' => 'select',
               'value' => '',
        ),
        'erp_transfer.status' => array(
                'title' => '调拨状态',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => array(
                    'draft' => '草稿',
                    'audit'=>'待审核',
                    'tbr'=>'待出库',
                    'refunded'=>'已出库',
                    'cancelled' => '已作废',
                    'rejected' => '已驳回'
                    
                ),
        ),
        'erp_transfer.created_time' =>    array (    
            'title' => '日期',     
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => 'created_time',   
        ), 
    );
    protected $warehouseArr = array();
    //加入默认值
    public function __construct(){
        //加入wms入库单 liuguangping
        $stockin_logic = A('StockIn','Logic');        
        $stockin_logic->addWmsIn(array('594'));
        parent::__construct();
        //获得仓库信息
        $warehouse = M('warehouse')->field('id,name')->select();
        $warehouseArr = array();
        foreach ($warehouse as $key => $value) {
            $warehouseArr[$value['id']] = $value['name'];
        }
        $this->warehouseArr = $warehouseArr;
        $this->query['erp_transfer.wh_id_in']['value'] = $warehouseArr;
        $this->query['erp_transfer.wh_id_out']['value'] = $warehouseArr;
        $this->filter['wh_id_out'] = $warehouseArr;
        $this->filter['wh_id_in'] = $warehouseArr;
    }
    //设置列表页选项
    protected function before_index()
    {
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'true'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['add']),'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    public function _before_index()
    {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );

        //加入权限 状态：draft草稿audit待审核tbr待出库refunded 已出库 cancelled 已作废 rejected已驳回
        $this->toolbar_tr =array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"audit,rejected"), //编辑
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"audit"),//通过 批准
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"audit"),//拒绝 驳回
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"audit,tbr,rejected")//作废
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
            ),
        );
    }
    

    public function _before_add()
    {
        $this->assign('warehouseArr', $this->warehouseArr);
        
    }
    
    
    public function match_code() {
        $code=I('q');
        $A = A('Pms',"Logic");
        $data = $A->get_SKU_by_pro_codes_fuzzy_return_data($code);
        if(empty($data))$data['']='';
        echo json_encode($data);
    }

    /**
     * 加工单新建(编辑)
     * @see 
     */
    public function save()
    {
        if(!IS_POST){
            $this->msgReturn(false, '未知错误');
        }
        $post = I('post.');
        if (empty($post)) {
            $this->msgReturn(false, '参数有误');
        }
        $pros = $post['pros'];
        unset($pros[0]); //删除默认提交数据
        if (empty($pros) || count($pros['pro_code'])<=1) {
            $this->msgReturn(false, '请填写sku信息');
        }
        if (empty($post['wh_id_in'])) {
            $post['wh_id_in'] = $post['wh_id_in_bak'];
        }

        if (empty($post['wh_id_out'])) {
            $post['wh_id_out'] = $post['wh_id_out_bak'];
        }
        if (empty($post['wh_id_out'])) {
            $this->msgReturn(false, '请选择调出仓库');
        }
        if (empty($post['wh_id_in'])) {
            $this->msgReturn(false, '请选择调入仓库');
        }
        

        $Transfer_logic = D('Transfer' , 'Logic');
        $data = array();
        $param = array();
        $detail = array();
        $plan_qty_tobal = 0;
        foreach ($pros['pro_code'] as $key => $value) {
            if ($key == 0) {
                continue;
            }
            //判断是否有sku信息未填写
            if (empty($value)) {
                $this->msgReturn(false, '请选择第' . $key . '行sku');
            }
            if (empty($pros['pro_qty'][$key]) || $pros['pro_qty'][$key] <= 0) {
                $this->msgReturn(false, $value . '请填写大于0计划调拨的数量');
            }

            //验证小数 liuguangping
            $mes = '';
            if (strlen(formatMoney($pros['pro_qty'][$key], 2, 1))>2) {
                $mes = $value . '计划调拨数量只能精确到两位小数点';
                $this->msgReturn(0,$mes);exit;
            }
            $plan_qty_tobal = f_add($plan_qty_tobal,$pros['pro_qty'][$key],2);
            $param[] = $value;
            $detail[$key-1]['pro_code'] = $value;
            $detail[$key-1]['pro_name'] = $pros['pro_name'][$key];
            $detail[$key-1]['pro_attrs'] = $pros['pro_attrs'][$key];
            $detail[$key-1]['pro_uom'] = $pros['pro_uom'][$key];
            $detail[$key-1]['plan_transfer_qty'] = $pros['pro_qty'][$key];
            $detail[$key-1]['created_time'] = get_time();
            $detail[$key-1]['created_user'] = UID;
            $detail[$key-1]['updated_time'] = get_time();
            $detail[$key-1]['updated_user'] = UID;

        }
        if (count($param) > count(array_unique($param))) {
            //发现重复的SKU
            $this->msgReturn(false, '请不要叠加相同的SKU');
        }
        $data['wh_id_out'] = $post['wh_id_out'];
        $data['wh_id_in'] = $post['wh_id_in'];
        $data['trf_code'] = $post['code_eidit']?$post['code_eidit']:get_sn('TP');
        $data['plan_cat_total'] = count(array_unique($param));
        $data['plan_qty_tobal'] = $plan_qty_tobal;
        $data['status'] = 'audit';
        $data['created_time'] = get_time();
        $data['created_user'] = UID;
        $data['updated_time'] = get_time();
        $data['updated_user'] = UID;
        $data['remark'] = !empty($post['remark']) ? $post['remark'] : ''; //备注
        //添加操作
        $back = $Transfer_logic->create_transfer($data, $detail);
        if (!$back) {
            $this->msgReturn(false,'操作失败');
        }

        //id 有值说明是编辑，编辑是先添加在删除
        $id = $post['id'];
        if ($id) {
            $transfer = M('erp_transfer');
            $transfer_detail = M('erp_transfer_detail');
            if($transfer->delete($id)){
                $where = array();
                $where['pid'] = $id;
                $transfer_detail->where($where)->delete();
            }else{
                $this->msgReturn(false,'编辑成功，但是删除原来得失败！');  
            }

        }

        $this->msgReturn(true,'操作成功','','/Transfer/view/id/'.$back);
    }

    //重写view
    public function view()
    {
        $this->_before_index();
        $this->edit();
    }

    protected function before_edit(&$data)
    {
        //调拨单详情数据处理
        $data['warehouseArr'] = $this->warehouseArr;
        D('Transfer', 'Logic')->get_transfer_all_sku_detail($data);
    }

    /**
     * 批准调拨单操作
     */
    public function pass()
    {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $id = I('get.id'); //调拨单id
        //获取加工单数据
        $map['id'] = I('id');
        $transfer =  M('erp_transfer')->where($map)->find();
        unset($map);
        
        if (empty($transfer)) {
            $this->msgReturn(false, '不存在的加工单');
        }
        //是否已经批准
        if ($transfer['status'] != 'audit') { //1 待审核状态
            $this->msgReturn(false, '非新建加工单');
        }
        
        //插入erp出库单和wms出库单
        $transfer_logic = A('Transfer', 'Logic');
        if ($transfer_logic->insertErpWmsTransferout($id)) {
            //更新状态
            $map['id'] = $id;
            $datas['status'] = 'tbr'; //批准
            M('erp_transfer')->where($map)->save($datas);
            $this->msgReturn(true, '已生效');
        }else{
            $this->msgReturn(true, '批准操作失败！');
        }
    
        

    }
    
    /**
     * 驳回
     */
    public function reject()
    {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
            $id = I('get.id');
            if (empty($id)) {
                $this->msgReturn(false, '参数有误');
            }
            $map['id'] = $id;
            $res = M('erp_transfer')->where($map)->find();
            if (empty($res)) {
                $this->msgReturn(false, '不存在的调拨单');
            }
            if ($res['status'] != "audit") {
                //状态为audit 待审核
                $this->msgReturn(false, '非新建调拨单不能驳回');
            }
            $data['status'] = 'rejected'; //rejected驳回
            $res = M('erp_transfer')->where($map)->save($data);
            if (!$res) {
                $this->msgReturn(false, '驳回失败');
            }else{
                $map = array();
                $map['pid'] = $id;
                if(!M('erp_transfer_detail')->where($map)->save($data)){
                    $map = array();
                    $map['id'] = $id;
                    $data['status'] = $res['status'];
                    M('erp_transfer')->where($map)->save($data);
                    $this->msgReturn(false, '驳回失败');
                } else {
                    $this->msgReturn(true, '已驳回');
                }

            }
    
            
    }
    
    /**
     * 调拨单作废
     */
    public function close()
    {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $id = I('get.id');
        if (empty($id)) {
            $this->msgReturn(false, '参数有误');
        }
        $map['id'] = $id;
        //dump($map);die;
        $res = M('erp_transfer')->where($map)->find();
        if (empty($res)) {
            $this->msgReturn(false, '不存在的调拨单');
        }
        if ($res['status'] == 'refunded') {
            //状态为3 已完成
            $this->msgReturn(false, '已出库的调拨单不能作废');
        }
        if ($res['status'] == 'cancelled') {
            //已作废
            $this->msgReturn(false, '此调拨单已经作废，无需重复操作');
        }
        
        $stockR = M('stock_bill_out')->where(array('refer_code'=>$res['trf_code']))->getField('status');
        //待生产wms出库单是可以作废并且为批准的erp调拨单可以作废
        if ($stockR && $stockR != '1') {
            $this->msgReturn(false, '此调拨单已经在调拨途中，不能作废！');
        }

        //作废中做相应的操作
        $transfer_logic = A('Transfer', 'Logic');
        if ($transfer_logic->updateStatus($id)) {
            $this->msgReturn(true, '已作废');
        }else{
            $this->msgReturn(true, '操作失败'); 
        }
        
       
    }
    
    /**
     * 加工单验证操作
     */
    public function order()
    {
        if (IS_POST) {
            $post = I('post.process_code');
            if (empty($post)) {
                $this->msgReturn(false, '请输入加工单号');
                return;
            }
            
            //查询加工单是否存在
            $map['code'] = $post;
            $process = M('erp_process')->where($map)->find();
            
            if (empty($process)) {
                //不存在
                $this->msgReturn(false, '不存在的加工单');
            }
            if ($process['status'] != 2 && $process['status'] != 3) {
                //生产完成
                $this->msgReturn(false, '已生产完成');
            }
            if ($process['over_task'] >= $process['task']) {
                $this->msgReturn(false, '已生产完成');
            }
            unset($map);
            $param = array(
                'process_id' => $process['id'], //加工单ID
            );
            $this->msgReturn(true, '', '', U('confirm', $param));
        } else {
            $this->title = '扫描加工单号';
            C('LAYOUT_NAME','pda');
            $this->display();
        }
    }
    
    /**
     * 批量预览
     */
    public function preview()
    {
        $pro_infos = I('post.pro_infos');
        if(empty($pro_infos)){
            $this->msgReturn(0,'请提交批量处理的信息');
        }
        $pro_infos_list = explode("\n", $pro_infos);
        $pro_codes = array();
        $purchase_infos = array();
        foreach($pro_infos_list as $pro_info){
            $pro_info_arr = explode("\t", $pro_info);
            if ($pro_info_arr[1] == '0.00') {
                $mes = $pro_info_arr[0] . '计划调拨数量不能为零';
                $this->msgReturn(0,$mes);
            }
            if (!$pro_info_arr[1] || strlen(formatMoney($pro_info_arr[1], 2, 1))>2) {
                $mes = $pro_info_arr[0] . '计划调拨数量只能精确到两位小数点';
                $this->msgReturn(0,$mes);
            }

            /*if (!A('Pms','Logic')->get_SKU_field_by_pro_codes(array($pro_info_arr[0]))) {
                $mes = $pro_info_arr[0] . '货物号不存在，请确认在操作！';
                $this->msgReturn(0,$mes);
            }*/
            $pro_codes[] = $pro_info_arr[0];
            $purchase_infos[$pro_info_arr[0]]['pro_qty'] = formatMoney($pro_info_arr[1], 2);
        }
        
        $sku_list = A('Pms','Logic')->get_SKU_field_by_pro_codes($pro_codes);
        
        //拼接模板
        foreach($pro_codes as $pro_code){
            $result .= '<tr class="tr-cur">
                <td style="width:50%;">
                    <input type="hidden" value="'.$pro_code.'" name="pros[pro_code][]" class="pro_code form-control input-sm">
                    <input type="hidden" value="'.$sku_list[$pro_code]['name'].'" name="pros[pro_name][]" class="pro_name form-control input-sm">
                    <input type="hidden" value="'.$sku_list[$pro_code]['uom_name'].'" name="pros[pro_uom][]" class="pro_name form-control input-sm">
                    <input type="hidden" value="'.$sku_list[$pro_code]['pro_attrs_str'].'" name="pros[pro_attrs][]" class="pro_attrs form-control input-sm">
                    <input type="text" value="'.'['.$pro_code.'] '.$sku_list[$pro_code]['wms_name'].'" class="pro_names typeahead form-control input-sm" autocomplete="off">
                </td>
                <td style="width:10%;">
                    <input type="text" sc="pro_qty" name="pros[pro_qty][]" placeholder="数量" value="'.$purchase_infos[$pro_code]['pro_qty'].'" class="pro_qty form-control input-sm text-left p_qty" autocomplete="off">
                </td>
        
                <td style="width:10%;" class="text-center">
                    <a data-href="/Category/delete.htm" data-value="67" class="btn btn-xs btn-delete" data-title="删除" rel="tooltip" data-toggle="tooltip" data-trigger="hover" data-placement="bottom" data-original-title="" title=""><i class="glyphicon glyphicon-trash"></i> </a>
                </td>
            </tr>';
        }


        $this->msgReturn(1,'',array('html'=>$result));
    }
}
/* End of file TransferController.class.php */
/* Location: ./Application/Controller/TransferController.class.php */