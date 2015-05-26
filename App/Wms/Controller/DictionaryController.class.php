<?php
namespace Wms\Controller;
use Think\Controller;
class DictionaryController extends AuthController {
	public function index(){
		$M=M(CONTROLLER_NAME);
		$data=$M->field("key,concat(`key`,' ',value) as value,status")->select();
		foreach ($data as $k => $v) {
			$union_words[$v['key']]='';
			if($v['status'] =='1') {
				if(empty($v['value']))
					$words[]=$v['key'];
				else
					$words[]=$v['value'];
			}
			else{
				$black[] = $v['key'];
			}
		}

		$data = M()->query("select `value` from(
				SELECT `app` as `value` FROM auth_authority where `type`='1'
				union
				SELECT `group` as `value` FROM auth_authority where `type`='2'
				union
				SELECT `module` as `value` FROM auth_authority where `type`='3'
				union
				SELECT `action` as `value` FROM auth_authority where `type`='4'
				union
				SELECT distinct `field` as `value` FROM module_column
				)t
				 group by `value`
				order by `value` ;
			");
		
		foreach ($data as $v) {
			if(!array_key_exists($v['value'],$union_words)) {
				//dump($v['value']); dump($union_words);
				$words[]=$v['value'];
			}
		}

		$this->data=implode(PHP_EOL, $words);
		$this->black=implode(PHP_EOL, $black);
		$this->display();
	}

	public function save(){
		$content 	=	I('content');
		$content_black 		= I('content-black');
		$rows		= explode("\r\n", $content);
		$black		= explode("\r\n", $content_black);
		$rows = array_merge($rows, $black);
		$i=0;
		$exsits = array();
		foreach ($rows as $row) {
			$word= explode(' ', $row);
			if(empty($word[0]))continue;
			if(empty($word[1]))$word[1] = '';
			if(!isset($words[$word[0]])){
				$words[$word[0]]=$i++;
				$status = in_array($word[0], $black)?'0':'1';
				$data[] = array('key' => $word[0],'value'=>$word[1],'status'=>$status);
			}
		}
		$M=M(CONTROLLER_NAME);
		$M->execute('truncate table dictionary');
		
        $result=$M->addAll($data);
        if(!$result){
        	$this->error($M->getError());
        }
        else{
        	S('dictionarykey,value',null);
	        $this->success('success','index',2);
	    }
	}

	public function get_words(){
		$dict = M ('dictionary')->where($map)->getField('key,value');
		return $dict;
	}
	public function get_black_words(){
		$M=M('Dictionary');
		$map['status'] = '0';
		$data=$M->field("key")->where($map)->select();
		foreach ($data as $key => $value) {
			$dict[]=$value['key'];
		}
		return $dict;
	}
}