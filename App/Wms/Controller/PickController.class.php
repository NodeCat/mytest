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

       'draft'=>'未开始',

       'picking'=>'执行中',

       'done'=>'已完成'

      )
  );

	protected $columns = array (

		'id'                => '',

    'pick_id'           => '分拣号',

    'type'              => '类型',

    'wave_id'           => '波次号',

	  'status'			      => '状态',

		'order_sum'         => '订单数',

		'pro_type_sum'      => '总行数',

		'pro_qty_sum'       => '总件数',

		'created_time'      => '开始时间',

		'updated_time'      => '结束时间',

  );

	protected $query   = array (

    'stock_wave_picking.id'   =>    array ( 

      'title'         => '分拣号', 

      'query_type'    => 'eq', 

      'control_type'  => 'text', 

      'value'         => 'id',

    ),

    'stock_wave_picking.type'   =>    array ( 

              'title'           => '类型', 

              'query_type'      => 'eq', 

              'control_type'    => 'select', 

              'value'           => array(

                  'pick'        => '拣货'

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

  );

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
    

    $this->search_addon = false;

   }

  public function view(){

    $pid = I('id');

    $m = M('stock_wave_picking_detail');

    $map['pid'] = $pid;

    $result = array();

    $result = $m->where($map)->select();

    $this->assign('pickDetail',$result);

    parent::view();

  }

  public function pickPrint(){

    $this->display('Pick::pickPrint');  
  }
  
}

/* End of file PackController.class.php */
/* Location: ./Application/Controller/PackController.class.php */
