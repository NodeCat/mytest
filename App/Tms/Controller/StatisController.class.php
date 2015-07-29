<?php
/**
 *	TMS调度统计控制器
 *
 *  接入saiku,统计司机调度信息
 *
 *	@author    pengyanlei
 */
namespace Tms\Controller;
use Think\Controller;

class StatisController extends \Common\Controller\AuthController
{
	private $charts = array(
		'总平台数据'                   => 'pingtaishuju.saiku',
		'今天各城市各个派车平台的车次比例' => 'jintianpingtaishuju.saiku',
		'昨天各城市各个派车平台的车次比例' => 'zuotianpingtaishuju.saiku',
		'每日车次数'                   => 'paicheshuju.saiku',
		'北京每日订单数'                => 'beijngmeiridanshu.saiku',
		'天津每日订单数'                => 'tianjinmeiridanshu.saiku',
		'上海每日订单数'                => 'shanghaimeiridanshu.saiku',
	);
	private $saiku_tms = 'http://saiku.dachuwang.com/api.html?#query/open/tms/';

	/**
	 * [index 统计结果]
	 * @return [type] [description]
	 */
	public function index() {
		$login = I('get.login/d',0);
		if($login) {
			foreach ($this->charts as &$value) {
				$value = $this->saiku_tms . $value;
			}
			$this->data = $this->charts;
		}
		$this->login = $login;
		$this->display('tms:saiku');
	}

	/**
	 * [login 模拟登陆]
	 * @return [type] [description]
	 */
	public function login() {
		$this->display('tms:saiku-login');
	}
}