<?php
namespace Wms\Controller;
use Think\Controller;
class WaveController extends CommonController {
	protected $columns = array('id' => '',
			'id' => '',
            );

	//开发测试用
	public function test(){
		$wave_ids = array(25,26);
		A('WavePicking','Logic')->waveExec($wave_ids);
	}
}