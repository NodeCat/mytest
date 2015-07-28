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
class TransferInController extends CommonController
{
    protected $filter = array(
        'state'=>array(
            'waiting'=>'待入库',
            'waitingup'=>'待上架',
            'up'=>'已上架',
            'cancelled' => '已作废'
        ),
       'wh_id_out'=>'',

       'wh_id_in'=>'',
    );
    
    protected $columns = array (
        'id' => '',
        'code' => '入库单号',
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
                    'waitingup'=>'待上架',
                    'up'=>'已上架',
                    'cancelled' => '已作废'
                    
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

    protected function before_edit(&$data)
    {
        //详情数据处理
        D('Transfer', 'Logic')->get_process_all_sku_detail($data, 'erp_transfer_in_detail');
    }
    
    
}
/* End of file TransferController.class.php */
/* Location: ./Application/Controller/TransferController.class.php */