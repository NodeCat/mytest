<?php
namespace Wms\Controller;
use Think\Controller;
class CategoryController extends CommonController {
	public function getCatInfoByPid(){
		//获得分类
    	$cats = A('Pms','Logic')->get_SKU_category();
    	//一级分类
    	$cat_1 = $cats['list']['top'];
    	$cat_2 = $cats['list']['second'];
    	$cat_3 = $cats['list']['second_child'];

    	$cat_data = false;
    	//父级id
		$cat_pid = I('pid');
		//父级id的级别 top second
		$cat_level = I('level');

		if($cat_level == 'top'){
			$cat_data = $cat_2[$cat_pid];
		}

		if($cat_level == 'second'){
			$cat_data = $cat_3[$cat_pid];
		}

		if(empty($cat_data)){
			$data = array('status'=>0,'msg'=>'分类不存在');
			$this->ajaxReturn($data);
		}

		$data['status'] = 1;
		$data['data'] = $cat_data;
		$this->ajaxReturn($data);
	}
}