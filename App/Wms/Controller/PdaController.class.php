<?php
namespace Wms\Controller;
use Think\Controller;
class PdaController extends Controller {
	public function index(){
		$map['id'] = session('user.wh_id');
		$warehouse_info = M('warehouse')->where($map)->find();

		$this->warehouse_name = $warehouse_info['name'];
		C('LAYOUT_NAME','pda');
		$this->display();
	}
}