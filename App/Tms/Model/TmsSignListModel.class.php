<?php
namespace Tms\Model;
use Think\Model\RelationModel;
class TmsSignListModel extends RelationModel{

	protected $_link=array(
		'TmsUser'=>array(
		'mapping_type'=>self::BELONGS_TO,
		'foreign_key'=>'userid',
		'mapping_fields'=>'username,mobile,car_num,car_type,car_from,warehouse',
		'as_fields'=>'username,mobile,car_num,car_type,car_from,warehouse',
		
		),

	);
	
}

	

