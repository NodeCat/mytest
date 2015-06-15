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

               'type'=>array(

	               '200'=>'待运行',

	               '201'=>'运行中',

	               '900'=>'已释放'

                )
              );

	protected $columns = array (

  				'id'                => '',

  				'wave_id'				=> '波次号',

  				'company_id'        => '波次主表名称',

  				'order_count'       => '总单数',

  				'line_count'        => '总行数',

  				'total_count'       => '总件数',

  				'type' 				=> '波次状态',

  				'start_time'        => '开始时间',

  				'end_time'          => '结束时间',

			);

	protected $query   = array (

			'stock_wave.id' 	=>    array ( 

			 	'title' 		=> '波次号', 

			 	'query_type' 	=> 'eq', 

			 	'control_type' 	=> 'text', 

			 	'value' 		=> 'id',

			),

			'stock_wave.type'   =>    array ( 

			 	'title'        	=> '波次状态', 

			 	'query_type'   	=> 'eq', 

			 	'control_type' 	=> 'select', 

			 	'value' => array(

			 		'200'		=> '待运行',

			 		'201'		=> '运行中',

			 		'900'		=> '已释放',

			 		),

			),

	);

	protected function before_index() {

        $this->table = array(

            'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等

            'searchbar' => true, //是否显示搜索栏

            'checkbox'  => true, //是否显示表格中的浮选款

            'status'    => false, 

            'toolbar_tr'=> false,

            'statusbar' => true
        );

        $this->search_addon = true;
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

    	if($hasIsAuth === FALSE) echojson('1','','你所选的波次中包含运行中和已释放，请选择待运行波次！');

    	$controllerStatus = $waveLogic->execPack($ids);

    	if($controllerStatus === FALSE){

    		echojson('1','','分拣失败！');

    	}else{

    		echojson('0','','操作成功，分拣中！');

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

    	if($hasIsAuth === FALSE) echojson('1','','你所选的波次中包含运行中和已释放，请选择待运行波次！');

    	$controllerStatus = $waveLogic->delWave($ids);

    	if($controllerStatus === FALSE){

    		echojson('1','','删除失败！');

    	}else{

    		echojson('0','','删除成功！');

    	}

    }

    public function after_search(&$map){

    	$map['wh_id'] = session('user.wh_id');

    }

    /**
     * 分拣任务Hook
     *
     * @author liuguangping@dachuwang.com
     * @since 2015-06-15
     */
    public function packTask(){

    	//@todo这里加个钩子调用李昂的分拣接口

    	//echojson('0','','删除成功！');

    }
}

/* End of file WaveController.class.php */
/* Location: ./Application/Controller/WaveController.class.php */