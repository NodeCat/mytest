<?php
namespace Common\Logic;

class CategoryLogic
{
	public function lists($type ='')
	{
		$map['is_deleted'] = 0 ;
		$map['type'] = $type;
		$data = M('Category')->where($map)->order('id')->getField('id,name');
		return $data;
	}
}