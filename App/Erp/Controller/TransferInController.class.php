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
namespace Erp\Controller;
use Think\Controller;
class TransferInController extends CommonController
{
    protected $filter = array(
        'state'=>array(
            'waiting'=>'待入库',
            /*'waitingup'=>'待上架',*/
            'up'=>'已入库'
            /*'cancelled' => '已作废'*/
        ),
       'wh_id_out'=>'',

       'wh_id_in'=>'',
    );
    
    protected $columns = array (
        'id' => '',
        'code' => '入库单号',
        'refer_code'=>'调拨单号',
        'wh_id_out' => '调出仓库',
        'wh_id_in' => '调入仓库',
        'cat_total' => 'SKU种数',
        'qty_tobal' => 'SKU件数',
        'created_user_nickname' => '创建人',
        'created_time' => '创建时间',
        'state' => '状态'
    );
    protected $query   = array (
        'erp_transfer_in.code' => array(
                'title' => '入库单号',
                'query_type' => 'eq',
                'control_type' => 'text',
                'value' => 'code',
        ),
        'erp_transfer_in.refer_code' => array(
                'title' => '调拨单号',
                'query_type' => 'eq',
                'control_type' => 'text',
                'value' => 'refer_code',
        ),
        'erp_transfer_in.wh_id_out' => array(
               'title' => '调出仓库',
               'query_type' => 'eq',
               'control_type' => 'select',
               'value' => '',
        ),
        'erp_transfer_in.wh_id_in' => array(
               'title' => '调入仓库',
               'query_type' => 'eq',
               'control_type' => 'select',
               'value' => '',
        ),
        'erp_transfer_in.status' => array(
                'title' => '入库状态',
                'query_type' => 'eq',
                'control_type' => 'select',
                'value' => array(
                    'waiting'=>'待入库',
                    /*'waitingup'=>'待上架',*/
                    'up'=>'已入库'
                    /*'cancelled' => '已作废'*/
                    
                ),
        ),
        'erp_transfer_in.created_time' =>    array (    
            'title' => '日期',
            'query_type' => 'between',     
            'control_type' => 'datetime',     
            'value' => 'created_time',   
        ), 
    );
    protected $warehouseArr = array();
    //加入默认值
    public function __construct(){
        parent::__construct();
        //获得仓库信息
        $warehouse = M('warehouse')->field('id,name')->select();
        $warehouseArr = array();
        foreach ($warehouse as $key => $value) {
            $warehouseArr[$value['id']] = $value['name'];
        }
        $this->warehouseArr = $warehouseArr;
        $this->query['erp_transfer_in.wh_id_in']['value'] = $warehouseArr;
        $this->query['erp_transfer_in.wh_id_out']['value'] = $warehouseArr;
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
        $this->toolbar_tr =array(
            'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'), 
            'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"1,4"), 
            'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"1"),
            'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"1"),
            'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"1,2,4")
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
            ),
        );
    }
    
    //重写view
    public function view()
    {
        $this->_before_index();
        $this->edit();
    }

    protected function before_edit_bak(&$data)
    {
        //详情数据处理
        D('Transfer', 'Logic')->get_transfer_all_sku_detail($data, 'erp_transfer_in_detail');
        $plan_qty = 0;//计划
        $real_qty = 0;//实际
        $isset = array();
        $data['group'] = array();
        foreach ($data['detail'] as $key => $value) {
            if (!isset($isset[$value['pid'].'_'.$value['pro_code']])) {
                $data[$value['pid'].'_'.$value['pro_code'].'_'.'plan_in_qty'] = $value['plan_in_qty'];
                $data[$value['pid'].'_'.$value['pro_code'].'_'.'receipt_qty'] = $value['receipt_qty'];
                $tmpArr = array();
                $tmpArr['pid'] = $value['pid'];
                $tmpArr['pro_code'] = $value['pro_code'];
                $tmpArr['pro_name'] = $value['pro_name'];
                $tmpArr['pro_attrs'] = $value['pro_attrs'];
                $tmpArr['batch_code'] = $value['batch_code'];
                $tmpArr['pro_uom'] = $value['pro_uom'];
                array_push($data['group'], $tmpArr);
                $isset[$value['pid'].'_'.$value['pro_code']] = 1;
            } else {
                $isset[$value['pid'].'_'.$value['pro_code']]+=1;
                $data[$value['pid'].'_'.$value['pro_code'].'_'.'plan_in_qty'] = f_add($data[$value['pid'].'_'.$value['pro_code'].'_'.'plan_in_qty'], $value['plan_in_qty']);
                $data[$value['pid'].'_'.$value['pro_code'].'_'.'receipt_qty'] = f_add($data[$value['pid'].'_'.$value['pro_code'].'_'.'receipt_qty'], $value['receipt_qty']);
            }
            $plan_qty = f_add($plan_qty,$value['plan_in_qty']);
            $real_qty = f_add($real_qty,$value['receipt_qty']);
        }
        foreach ($data['group'] as $ky => $val) {
            if (isset($data[$val['pid'].'_'.$val['pro_code'].'_'.'plan_in_qty'])) {
                $data['group'][$ky]['plan_in_qty'] = $data[$val['pid'].'_'.$val['pro_code'].'_'.'plan_in_qty'];
            } else {
                $data['group'][$ky]['plan_in_qty'] = 0;
            }

            if (isset($data[$val['pid'].'_'.$val['pro_code'].'_'.'receipt_qty'])) {
                $data['group'][$ky]['receipt_qty'] = $data[$val['pid'].'_'.$val['pro_code'].'_'.'receipt_qty'];
            } else {
                $data['group'][$ky]['receipt_qty'] = 0;
            }

            if (isset($isset[$val['pid'].'_'.$val['pro_code']])) {
                $data['group'][$ky]['batch_count'] = $isset[$val['pid'].'_'.$val['pro_code']];
            } else {
                $data['group'][$ky]['batch_count'] = 0;
            }
        }
        $data['plan_qty'] = $plan_qty;//计划
        $data['receipt_qty'] = $real_qty;//实际
        //dump($data);die;
    }

    protected function before_edit(&$data)
    {
        //详情数据处理
        D('Transfer', 'Logic')->get_transfer_all_sku_detail($data, 'erp_transfer_in_detail');
        $plan_qty = 0;//计划
        $real_qty = 0;//实际
        $isset = array();
        foreach ($data['detail'] as $key => $value) {
            
            $plan_qty = f_add($plan_qty,$value['plan_in_qty']);
            $real_qty = f_add($real_qty,$value['receipt_qty']);
        }
        
        $data['plan_qty'] = $plan_qty;//计划
        $data['receipt_qty'] = $real_qty;//实际
    }

    //查看批次
    public function transferBatch(){
        $id = I('post.id');
        $pro_code = I('post.procode');
        if (!$id || !$pro_code) {
            $this->error('0','请正确传值！');
        }
        $map = array();
        $map['pid'] = $id;
        $map['pro_code'] = $pro_code;
        $erp_out_container = M('erp_transfer_in_detail');
        $res = $erp_out_container->where($map)->field('receipt_qty,plan_in_qty,done_qty,prepare_qty,batch_code')->select();
        if (!$res) {
            $this->msgReturn(0,'未查询到批次');           
        }
        $this->msgReturn(1,'查询成功',$res);
    }

    
    
}
/* End of file TransferController.class.php */
/* Location: ./Application/Controller/TransferController.class.php */