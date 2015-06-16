<?php

namespace Tms\Logic;

class ListLogic{

	public function storge(){
		$storge=M('Warehouse');
		$storge=$storge->field('name')->select();

		return $storge;
	}






}