<?php
namespace Tms\Model;
use Think\Model\RelationModel;
class TmsUserModel extends RelationModel{
		public $tableName = 'tms_user';
		protected $_link=array(

			'TmsSignList'=>array(
			'mapping_type'=>self::BELONGS_TO,
			'foreign_key'=>'userid',
			'mapping_fields'=>'username,mobile',
			'as_fields'=>'username,mobile',
			),

		);
}



