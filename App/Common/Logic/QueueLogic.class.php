<?php
namespace Common\Logic;

use Think\Controller;

class QueueLogic extends Controller {
	protected $queue; //队列实例
	protected function _initialize() {
		if(!empty($this->queue)) {
			return;
		}
		import("Common.Lib.pheanstalk_init",APP_PATH,'.php');
		$host = C('QUEUE.HOST');
		$port = C('QUEUE.PORT');
		$timeout= C('QUEUE.TIMEOUT');
		$persistent = C('QUEUE.PERSISTENT');
		$this->queue = new \Pheanstalk_Pheanstalk($host, $port, $timeout,$persistent);
		$availability = $this->queue->getConnection()->isServiceListening();
		if(!$availability) {
			return false;
		}
		set_time_limit(0);
	}

	public function delete(&$job) {
		$res = $this->queue->delete($job);
		return $res;
	}

	public function push($list, $job) {
		$res = $this->queue->useTube($list)->put($job);
		return $res;
	}

	public function pop($list,&$class='', $func='') {
		while($job = $this->queue->watch($list)->ignore('default')->reserve()) {
			if (empty($class) || !method_exists($class, $func)) {
				return $job;
			}
			else {
				$data = $job->getData();
				$res = $class->$func($data);//这里的超时时间最长为30秒
				if ($res) {
					$this->queue->delete($job);
				}
				else {
					//@todo 如果调用失败
					$this->queue->bury($job);
				}
			}
		}
	}
}