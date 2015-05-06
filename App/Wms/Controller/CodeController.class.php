<?php
namespace Wms\Controller;
use Think\Controller;
class CodeController extends CommonController {
	public function _before_validate() {
		$data = M('module_validate')->select();
		foreach ($data as $key => $val) {
			$data[$key]['col_id'] = $val['module'].'.'.$val['col_id'];
			switch ($val['cond']) {
				case '1':
				$data[$key]['cond'] = '必须验证';
					break;
				case '2':
				$data[$key]['cond'] = '值不为空就验证';
					break;
				default:
					$data[$key]['cond'] = '存在就验证';
					break;
			}
			switch ($val['validate_time']) {
				case '1':
				$data[$key]['validate_time'] = '添加时';
					break;
				case '2':
				$data[$key]['validate_time'] = '更新时';
					break;
				default:
					$data[$key]['validate_time'] = '添加及更新时';
					break;
			}
		}
		$this->columns =array(
			'id'=>'',
			'col_id'=>'验证字段',
			'addtion'=>'附加规则',
			'rule'=>'验证规则',
			'validate_time'=>'验证时间',
			'cond'=>'验证条件',
			'error_msg'=>'错误提示',
			'remark' => '备注'
		);
		$this->data = $data;
	}
	public function validate() {
		$module=I('name','menu');
        $M=M($module);
        $this->module=$module;
        $this->pk=$M->getpk();
        $this->fk=I('fk');
        if(strpos($this->fk,'_id')!==false)
        	$this->refer_module = substr($this->fk,0,-3);
        $M=M('module_table');
        $this->Modules = $M->getField('name',true);

		$data=array('name' => $module);
		$result=$M->where($data)->find();
		$data = $M->query("SHOW FULL COLUMNS FROM `{$module}`;");
        $fields = array();
        $i=0;
        foreach ($data as $k=>&$v){
            $fields["$i"] = "{$v['field']}";
            ++$i;
        }
		$this->module_columns=$fields;
		$this->display(ACTION_NAME);
	}
	public function validate_save(){
		$M = M('module_validate');
		$data = $M->create();
		$res = $M->add($data);
		$data = $M->find($res);
		$data['col_id'] = $data['module'].'.'.$data['col_id'];
		switch ($val['cond']) {
				case '1':
				$data['cond'] = '必须验证';
					break;
				case '2':
				$data['cond'] = '值不为空就验证';
					break;
				default:
					$data['cond'] = '存在就验证';
					break;
			}
			switch ($val['validate_time']) {
				case '1':
				$data['validate_time'] = '添加时';
					break;
				case '2':
				$data['validate_time'] = '更新时';
					break;
				default:
					$data['validate_time'] = '添加及更新时';
					break;
			}

		$data ='<tr><td><label class="checkbox">'.$data['id'].'</label></td>'
					."<td>".$data['col_id']."</td>"
                    ."<td>".$data['addtion']."</td>"
                    ."<td>".$data['rule']."</td>"
                    ."<td>".$data['validate_time']."</td>"
                    ."<td>".$data['cond']."</td>"
                    ."<td>".$data['error_msg']."</td>"
                    ."<td></td></tr>";
		$this->msgReturn(1,'添加成功',$data);
	}
	public function filter(){
		switch ($val['cond']) {
				case '1':
				$data['cond'] = '必须验证';
					break;
				case '2':
				$data['cond'] = '值不为空就验证';
					break;
				default:
					$data['cond'] = '存在就验证';
					break;
			}
			switch ($val['validate_time']) {
				case '1':
				$data['validate_time'] = '添加时';
					break;
				case '2':
				$data['validate_time'] = '更新时';
					break;
				default:
					$data['validate_time'] = '添加及更新时';
					break;
			}
			$filter = array(
				'cond' => array('1' => '必须验证','2'=>'值不为空就验证','0'=>'存在就验证'), 
			);
	}
	public function _before_auto() {
		$data = M('module_auto')->select();
		foreach ($data as $key => $val) {
			$data[$key]['col_id'] = $val['module'].'.'.$val['col_id'];
			switch ($val['cond']) {
				case '1':
				$data[$key]['cond'] = '添加时';
					break;
				case '2':
				$data[$key]['cond'] = '更新时';
					break;
				default:
					$data[$key]['cond'] = '添加及更新时';
					break;
			}
		}
		$this->columns =array(
			'id'=>'',
			'col_id'=>'完成字段',
			'addtion'=>'附加规则',
			'rule'=>'完成规则',
			'cond'=>'完成条件',
			'remark' => '备注'
		);
		$this->data = $data;
	}
	public function auto() {
		$module=I('name','menu');
        $M=M($module);
        $this->module=$module;
        $this->pk=$M->getpk();
        $this->fk=I('fk');
        if(strpos($this->fk,'_id')!==false)
        	$this->refer_module = substr($this->fk,0,-3);
        $M=M('module_table');
        $this->Modules = $M->getField('name',true);

		$data=array('name' => $module);
		$result=$M->where($data)->find();
		$data = $M->query("SHOW FULL COLUMNS FROM `{$module}`;");
        $fields = array();
        $i=0;
        foreach ($data as $k=>&$v){
            $fields["$i"] = "{$v['field']}";
            ++$i;
        }
		$this->module_columns=$fields;
		$this->display(ACTION_NAME);
	}
	public function auto_save(){
		$M = M('module_auto');
		$data = $M->create();
		$res = $M->add($data);
		$data = $M->find($res);
		$data['col_id'] = $data['module'].'.'.$data['col_id'];
		$this->columns =array(
			'id'=>'',
			'col_id'=>'完成字段',
			'addtion'=>'附加规则',
			'rule'=>'完成规则',
			'cond'=>'完成条件',
			'remark' => '备注'
		);
		switch ($data['cond']) {
			case '1':
			$data['cond'] = '添加时';
				break;
			case '2':
			$data['cond'] = '更新时';
				break;
			default:
				$data['cond'] = '添加及更新时';
				break;
		}
		$data ='<tr><td><label class="checkbox">'.$data['id'].'</label></td>'
					."<td>".$data['col_id']."</td>"
                    ."<td>".$data['addtion']."</td>"
                    ."<td>".$data['rule']."</td>"
                    ."<td>".$data['cond']."</td>"
                    ."<td></td></tr>";
		$this->msgReturn(1,'添加成功',$data);
	}
	public function index($refresh=false){
		$M= M('Module_table');
		$count=$M->count();
		$table_detail=$M->query('SHOW TABLE STATUS');
        //if($refresh)$M->where("name!=''")->setField('status','0');
        //if($refresh){$M->where("status='0'")->delete();}
		foreach ($table_detail as $k => $v) {
			$data[$k]['name'] 			= $v['name'];
			$data[$k]['title']			=$v['comment'];
			$data[$k]['module']			= ucwords($v['name']);
			$data[$k]['group']			= 'Wms';
			$data[$k]['build']			=0;
			$data[$k]['status']			='1';
			$data[$k]['rows']			=$v['rows'];
			$data[$k]['engine']			=$v['engine'];
			$data[$k]['collation']		=$v['collation'];
			$data[$k]['data_length']	=$v['data_length'];
			$data[$k]['index_length']	=$v['index_length'];
			$data[$k]['create_time']	=$v['create_time'];
			$data[$k]['update_time']	=$v['update_time'];
			$M->save($data[$k]);
		}
		$this->get_refer();
		$this->showpk=1;
    	$this->mpage($M);
	}

	public function _before_index (){
        $this->table = array(
            'toolbar'   => FALSE,
            'searchbar' => false, 
            'checkbox'  => true, 
            'status'    => true, 
            'toolbar_tr'=> true
        );
        $this->module   = 'module_table';
        $this->columns  = C("columns.".'Module_table');

    }

	public function edit($refresh=false){
		$refresh=I('refresh');
		$M=M('Module_table');
		$this->pk=$M->getpk();
		$this->modules = $M->getField($this->pk,true);
		$this->groups=$this->getGroup();

		$name = I($this->pk,'menu');
		$result=$M->find($name);

		$module=$result['name'];
		$this->name=$result['name'];
        $this->group=$result['group'];
        $this->module=$result['module'];
        $this->validate=$result['validate'];
        $this->auto=$result['auto'];
        $M =M('module_column');
        $map['module']=$module;
        $result=$M->where($map)->order('list_order')->select();
        if(empty($result) || $refresh){
        	$this->build_column($module,$refresh);
			$this->data=$M->where($map)->order('list_order')->select();
		}
		else{
			$this->data=$result;
		}

		$M=M('module_refer');
		$this->refer=$M->where("module='%s'",$module)->getField('fk,pk,module_refer,field_show');

		$this->type=ACTION_NAME.'-'.(IS_AJAX?'ajax':'');

		$this->display('design');
	}
	protected function build_column($module,$refresh=false){

		$M=M('module_refer');
		$refer=$M->where("module='%s'",$module)->getField('fk,pk,module_refer,field_show');

		$M=M('module_column');
		$column_saved=$M->where("module='%s'",$module)->getField('field,field,type');
		if($refresh)$M->where("module='%s'",$module)->setField('status','0');
		$result = $M->query("SHOW FULL COLUMNS FROM `{$module}`");

        foreach ($result as $k=>$v){
        	//字段存在且类型无变化
        	//if(!empty($column_saved) && $v['Type']===$column_saved[$v['Field']]['type'] && $refresh!=true)continue;
        	$show=true;
        	if($v['key']==='PRI'){
        		$show=false;
        	}
        		//如果类型有变化或不存在，则添加
        	if($v['type']!==$column_saved[$v['field']]['type']){
	        	$data[$k]['id']=$module.'.'.$v['field'];
		        $data[$k]['module']=$module;
		        $data[$k]['field']=$v['field'];
		        $data[$k]['title']=$v['comment'];
		        $data[$k]['type']=$v['type'];
		        $data[$k]['empty']=$v['null'];
		        $data[$k]['pk']=$v['key'];
		        $data[$k]['default']=$v['default'];

		        $data[$k]['query_able']= false;
		        $data[$k]['query_type']= strpos($v['type'], 'date')!==false?'between':'eq';
		        $data[$k]['insert_able']=true;
		        $data[$k]['update_able']=$show;
		        $data[$k]['readonly']=false;
		        $data[$k]['list_show']=$show;
		        $data[$k]['list_order']=$k;
				$data[$k]['add_show']=$show;
		        $data[$k]['add_order']=$k;
		        $data[$k]['control_type']=control_type($v);
		        
		        if(!empty($refer[$v['field']])){
		        	$data[$k]['control_type']='refer';
		        }
		        $data[$k]['validate']=validator($v);
		        $data[$k]['tips']='';
		        $data[$k]['status']='1';
		        $result=$M->add($data[$k],$options=array(),$replace=true);
		        
		    }
		    else
		    if($refresh==true){
        		$data[$k]['id']=$module.'.'.$v['field'];
        		$data[$k]['title']=$v['comment'];
        		$data[$k]['type']=$v['type'];
        		$data[$k]['status']='1';
        		$data[$k]['control_type']=control_type($v);
        		if(!empty($refer[$v['field']]))
		        	$data[$k]['control_type']='refer';
	        	$data[$k]['validate']=validator($v);
	        	$result=$M->save($data[$k]);
        	}
		}
		$M->where("status='0'")->delete();
	}

	protected function operate(){ 
        $op = I('op');
        $module = I('mod');
        $M=M('module_refer');
        switch ($op) {
            case 'enable':
                $data = array( 'status'=> '1');
            case 'disable':
                $data = array( 'status'=> '0');
                
		        if(isset($data)){
		            $result = $M->where($M->getPk().'='.$id)->setField($data);
		            if(empty($result)) {
		                $this->error('Sorry for we can not deal your handle.'); 
		                
		            }
		        }
                break;
            case 'add':
            case 'edit':
            case 'save':
            case 'delete':
            	$map=array($M->getPk() => I($M->getPk()));
            	$M->where($map)->delete();
            	break;
            case 'view':
            case 'index':
                $this->type='index';
            case 'refer':
            case 'export':
            case 'improt':
            case 'upload':
            	if(empty($module))$module=MODULE_NAME;
            	$module=ucwords($module);
            	$this->operate=ACTION_NAME.'?mod=module_refer&op=';
            	R('Comm/'.$module.'/'.$op);
                exit();
            default:
            $this->error('Unknow handle.'); 
            exit();
                break;
        }
        
    }
    public function relation(){
    	$this->refer();
    }
	public function refer(){
		$this->get_refer();
		$op=I('op');
		$this->type='index';
		if(!empty($op)){
			$this->operate();
			return;
		}
        $module=I('name','menu');
        $M=M($module);
        $this->module=$module;
        $this->pk=$M->getpk();
        $this->fk=I('fk');
        if(strpos($this->fk,'_id')!==false)
        	$this->refer_module = substr($this->fk,0,-3);
        $M=M('module_table');
        $this->Modules = $M->getField('name',true);

		$data=array('name' => $module);
		$result=$M->where($data)->find();
		$data = $M->query("SHOW FULL COLUMNS FROM `{$module}`;");
        $fields = array();
        $i=0;
        foreach ($data as $k=>&$v){
            $fields["$i"] = "{$v['field']}";
            ++$i;
        }
		$this->module_columns=$fields;
		if(isset($result)){
			$M=M('module_column');
			$condition=array('module_id' => $result['id']);

			$this->data=$M->field("`field` field,`title` `comment`,`type` type,query_able,list_show,list_order,control_type,add_show,add_order,validate
				,tips")->where($condition)->select();
		}
		else{
	        $M=M();
			$this->data=$M->query("SHOW FULL COLUMNS FROM {$module}");
		}


		$module='Module_refer';
		$this->columns  = C("columns.".$module);
		$M=M($module);
		$map=array('type' => ACTION_NAME );
		$this->data=$M->where($map)->select();//->where("module='".I('module')."'")
		$this->operate=ACTION_NAME.'?mod=module_refer&op=';
		$this->toolbar_tr =  array(
                'delete'
        );

		$this->display(ACTION_NAME);
	}



	protected function get_refer(){
		$M=M('module_refer');
		$db = C('DB_NAME');
		$data=$M->query("select TABLE_NAME module,COLUMN_NAME fk,REFERENCED_TABLE_NAME module_refer,REFERENCED_COLUMN_NAME pk from INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
					where TABLE_SCHEMA='".$db."' and REFERENCED_TABLE_SCHEMA='".$db."' and POSITION_IN_UNIQUE_CONSTRAINT=1"
		);
		$pk = $M->getPk();

		foreach ($data as $key => $v) {
			$data[$key][$pk]=$v['module'].'-'.$v['fk'].'-'.$v['module_refer'].'-'.$v['pk'];
			$data[$key]['fk_id']=$v['module'].'.'.$v['fk'];
        	$data[$key]['pk_id']=$v['module_refer'].'.'.$v['pk'];
        	$data[$key]['condition']=$data[$key]['fk_id'].'='.$data[$key]['pk_id'];
        	$data[$key]['refer_type']='INNER';
        	$M=M($v['module_refer']);
        	$refer_fields = $M->getDbFields();
        	if(count($refer_fields)>1)
        		$data[$key]['field_show']=$refer_fields[1];
        	else
        		$data[$key]['field_show']=$refer_fields[0];

		}
        $M=M('module_refer');
        $M->addAll($data);

	}
	public function getColumn(){
		$table=I('id');
		$M = M();
        $data = $M->query("SHOW COLUMNS FROM `{$table}`;");
        $fields = array();
        $i=0;
        foreach ($data as $k=>&$v){
            $fields["$i"] = $v['field'];
            ++$i;
        }
        $this->ajaxReturn($fields);
    }

	public function save(){
        $data['app']=$this->getAppName();
        $data['group']=I('group');
        $data['module']=I('module');
        $data['validate']=I('validate');
        $data['auto']=I('auto');
        $module=I('name');
        $map['name']=$module;

        $M=M('module_table');
        $M->where($map)->save($data);
        
        $data=null;
        $M =M('module_column');
        $data=$_POST["query"];
        foreach ($data as $k => $v) {
        	/*
    		$data[$k]['id']=$v['id'];
	        $data[$k]['field']=$v['field'];
	        $data[$k]['title']=$v['title'];
	        $data[$k]['type']=$v['type'];
	        $data[$k]['empty']=$v['empty'];
	        $data[$k]['pk']=$v['dey'];
	        $data[$k]['default']=$v['default'];
	        $data[$k]['control_type']=$v['control_type'];
	        $data[$k]['validate']=$v['validate'];
	        $data[$k]['tips']=$v['tips'];
    		$data[$k]['list_order']=$v['list_order'];
	        $data[$k]['add_order']=$v['add_order'];
			*/
	        $data[$k]['status']='1';
	        $data[$k]['module']=$module;
	        if ($v['insert_able']==='on')
    			$data[$k]['insert_able']=true;
    		else
    			$data[$k]['insert_able']=false;
    		if ($v['update_able']==='on')
    			$data[$k]['update_able']=true;
    		else
    			$data[$k]['update_able']=false;
    		if ($v['query_able']==='on')
    			$data[$k]['query_able']=true;
    		else
    			$data[$k]['query_able']=false;
    		if ($v['list_show']==='on')
    			$data[$k]['list_show']=true;
    		else
    			$data[$k]['list_show']=false;
			if ($v['add_show']==='on')
    			$data[$k]['add_show']=true;
    		else
    			$data[$k]['add_show']=false;
    		$result=$M->add($data[$k],$options=array(),$replace=true);
        }
        $msg=$result?'Success':'Fail';
        $this->redirect('Code/edit',array('name' => $module),2,$msg);
	}
	public function mapping_save(){ 
     	 
     	$data=I();
     	unset($data['_URL_']);
     	//$data['pk'] =I('module').'.'.I('pk');
     	//$data['fk'] =I('module_refer').'.'.I('fk');

        $M = M('module_refer');
        if(!$M->create()){
            $this->error($M->getError());
            exit();
        }

        $pk = $M->getPk();
        if(empty($M->$pk)){
        	$data[$pk]=I('module').'-'.I('fk').'-'.I('module_refer').'-'.I('pk');
        	$data['fk_id']=I('module').'.'.I('fk');
        	$data['pk_id']=I('module_refer').'.'.I('pk');
        	$condition=I('condition');
        	if(empty($condition)){
        		$data['condition']=$data['fk_id'].'='.$data['pk_id'];
        	}
            $result = $M->add($data,$options=array(),$replace=true);
        }
        else{
            $result = $M->save($data);
        }
        $this->redirect('Code/refer',array('module' => I('module')));        
    }

	public function view(){
		$M=M('module_table');
		$this->pk=$M->getPk();
		$this->modules = $M->getField($M->getPk(),true);
		$module=I($M->getPk());
		$result=$M->find($module);
		
		if(!empty($result)){
			$this->group=$result['group'];
			$M=M('module_column');
			$this->name=$module;
			$this->data = $M->where("module='%s'",$module)->order('add_order')->select();
			
			$M=M('module_refer');
			$this->refers=$M->where("module='%s'",$module)->getField('fk,pk,id,module,module_refer,field_show');
			$this->module = ucwords($module);
			layout(false);
			$this->add_show= $this->fetch('add');
			$this->edit_show=$this->fetch('edit');
			$this->add= htmlentities($this->add_show,ENT_NOQUOTES,"utf-8");
			$this->edit=htmlentities($this->edit_show,ENT_NOQUOTES,"utf-8");
			$this->action=htmlentities($this->fetch('controller'),ENT_NOQUOTES,"utf-8");
			$data=$this->data;
			foreach ($data as $key => $v) {
				if($v['insert_able']==1 || $v['pk']=='PRI'){
					$insert_fields[]="'".$v['field']."'";
				}
				if($v['update_able']==1){
					$update_fields[]="'".$v['field']."'";
				}
				if($v['insert_able']==1 && $v['update_able']==0){
					$readonly_fields[]="'".$v['field']."'";
				}
			}

			$M=M('module_table');
			$module=$name;
			$result=$M->getByModule($module);
			$data['validate']=$result['validate'];
			$data['auto']=$result['auto'];

			$refers=$this->refers;
			foreach ($refers as $key => $v) {
				$scope[]='"join '.$v['module_refer'].' on '.$v['module'].'.'.$v['fk'].'='.$v['module_refer'].'.'.$v['pk'].' '.$v['condition'].'"';
				$refer_fields[]=$v['module_refer'].'.'.$v['field_show'];
			}
			if(count($scope)>0){
				$data['scope']='"join"=>array('.implode(",", $scope)."),\n";
				$data['scope'].='"field"=>"'.$result['name'].'.*,'.implode(",", $refer_fields).'",';
			}
			
			$M=M($module);
			$this->pk=$M->getPk();
			$data['insert_fields']=implode(",", $insert_fields);
			$data['update_fields']=implode(",", $update_fields);
			$data['readonly_fields']=implode(",", $readonly_fields);
			$this->data=$data;
			$this->model=htmlentities($this->fetch('model'),ENT_NOQUOTES,"utf-8");
			$M=M($module);
			$this->data=$M->limit(10)->select();
			$this->module   = $module;
	        $this->columns  = C("columns.".$module);
	        $this->query    = C("query.".$module);
	        $this->toolbar_tr =array(
    	  		'view',
    	  		'edit',
    	  		'delete'
    	  	);
    	  	$this->index=$this->fetch('Table:index');
    	  	$M=M('module_table');
			$this->pk=$M->getPk();
		}

		$this->display();
	}

	public function build_all(){
		exit();
		$M=M('module_table');
		$modules=$M->where("type='customer'")->getField('name,group');
		$this->get_refer();
		foreach ($modules as $module => $group) {
			$this->build_column($module);
			$this->build_code($group,$module);
		}
		$this->success('Success.');
	}

	public function build(){
		$M=M('module_table');
		$module=I($M->getPk());
		$data=$M->find($module);
		if(!empty($data)){
			if($data['type']!=='system'){
				$this->build_code($data['group'],$data['module']);
		
				$this->redirect($data['group'].'/'.ucwords($module).'/index',null, 2, 'Success');
			}
			else{
				$this->error("Sorry, please don't rewrite the system module $module.");
			}
		}
		else{
			$this->error("Sorry, module not exists.");
			//日志记录，防止暴力枚举数据库表，超过3次输入验证码，超过10次拉黑名单
		}
	}

	//根据分组和模块生成代码文件Action和tpl
	protected function build_code($group=null,$module=null){
		if(empty($module)){return;}
		if(empty($group))$group = 'Admin';

		$M=M('module_column');
		$this->data = $M->where("module='%s'",$module)->order('add_order')->select();
		$module = ucwords($module);
		$this->group=$group;
		$this->module=$module;

		$M=M('module_refer');
		$this->refers=$M->where("module='%s'",$module)->getField('fk,pk,id,module,module_refer,field_show');
		
		$this->build_tpl($group,$module);
		$this->build_model($group,$module);
		$this->build_action($group,$module);
		$this->build_config($group,$module);
		exit();
	}
	protected function build_tpl($group,$module){
		$path = APP_PATH."$group/View/$module/";
		if(!file_exists($path)){
    	    mkdirs($path);
    	}
    	layout(false);
    	$file = "add.html".$this->ext;
		$content=$this->fetch('add');dump($content);
		file_put_contents($path.$file, $content);
		$file = "edit.html".$this->ext;
		$content=$this->fetch('edit');dump($content);
		file_put_contents($path.$file, $content);
	}
	protected function build_model($group,$module){
		$insert_fields=array();
		$update_fields=array();
		$readonly_fields=array();
		$scope=array();
		$refer_fields=array();
		$data=$this->data;
		foreach ($data as $key => $v) {
			if($v['insert_able']==1 || $v['pk']=='PRI'){
				$insert_fields[]="'".$v['field']."'";
			}
			if($v['update_able']==1){
				$update_fields[]="'".$v['field']."'";
			}
			if($v['insert_able']==1 && $v['update_able']==0){
				$readonly_fields[]="'".$v['field']."'";
			}
		}

		$M=M('module_table');
		$result=$M->getByModule($module);
		$data['validate']=$result['validate'];
		$data['auto']=$result['auto'];

		$refers=$this->refers;
		foreach ($refers as $key => $v) {
			$scope[]='"inner join '.$v['module_refer'].' on '.$v['module'].'.'.$v['fk'].'='.$v['module_refer'].'.'.$v['pk'].' '.$v['condition'].'"';
			$refer_fields[]=$v['module_refer'].'.'.$v['field_show'];
		}
		if(count($scope)>0){
			$data['scope']='"join"=>array('.implode(",", $scope)."),\n";
			$data['scope'].='"field"=>"'.$result['name'].'.*,'.implode(",", $refer_fields).'",';
		}
		
		$M=M($module);
		$this->pk=$M->getPk();

		$M =M('module_validate');
		$map['module'] = strtolower($module);
		//$map['status'] = '1';
		$res = $M->where($map)->select();
		foreach ($res as $key => $val) {
			$validate .= 
			"array('"
				.$val['col_id']."','"
				.$val['rule']."','"
				.$val['error_msg']."',"
				.$val['cond'].",'"
				.$val['addtion']."',"
				.$val['validate_time'].
			"),".PHP_EOL; 
		}
		$M =M('module_auto');
		$map['module'] = strtolower($module);
		//$map['status'] = '1';
		$res = $M->where($map)->select();

		foreach ($res as $key => $val) {
			$auto .= 
			"array('"
				.$val['col_id']."','"
				.$val['rule']."',"
				.$val['cond'].",'"
				.$val['addtion']."'".
			"),".PHP_EOL; 
		}
		$data['insert_fields']=implode(",", $insert_fields);
		$data['update_fields']=implode(",", $update_fields);
		$data['readonly_fields']=implode(",", $readonly_fields);
		$data['validate'] 		= $validate;
		$data['auto']		= $auto;
		$this->data=$data;

		$path= APP_PATH."$group/Model/";
		if(!file_exists($path)){
    	    mkdirs($path);
    	}
    	layout(false);
		$file=$module."Model.class.php".$this->ext;
		$content="<?php\n".$this->fetch('model');
		dump($content);
		file_put_contents($path.$file, $content);
	}

	protected function build_action($group,$module){
		$path=APP_PATH."$group/Controller/";
		if(!file_exists($path)){
    	    mkdirs($path);
    	}
    	layout(false);
		$file=$module."Controller.class.php".$this->ext;
		$content="<?php\n".$this->fetch('controller');
		dump($content);
		file_put_contents($path.$file, $content);
	}
	public function build_config($group,$module){

		$M=M('module_column');
		$data = $M->where("module='%s'",strtolower($module))->order('list_order')->select();
		$M=M('module_refer');
		$refer = $this->refers;

		$columns=array();
		$query=array();
		$join=array();
		foreach ($this->refers as $key => $v) {
			$join[$v['fk']]=$v['field_show'];
		}
		foreach ($data as $key => $v) {
			if($v['list_show']==1 || $v['pk']=='PRI'){
				if(isset($join[$v['field']]))
					$columns[$join[$v['field']]]=$v['title'];
				else
				$columns[$v['field']]=$v['title'];
			}

			if($v['query_able']==1){
				if(empty($refer[$v['field']])){
				 	if($v['control_type']==="select"){
				 		if(strpos($v['type'],'enum')!==false){
				 			$value= str_replace("'",'',substr($v['type'],5,-1));
				 		}
				 	}
				}
				elseif($v['control_type']==='refer')
			 			$value=$refer[$v['field']]['id'].','.$refer[$v['field']]['pk'].','.$refer[$v['field']]['field_show'].','.ucwords($refer[$v['field']]['module_refer']).'/refer';
			 	elseif($v['control_type']==='getField'){
			 			$value=ucwords($refer[$v['field']]['module_refer']).'.'.$refer[$v['field']]['pk'].','.$refer[$v['field']]['field_show'];
			 	}
				$query[$v['field']]=array(
					'title' => $v['title'], 
					'query_type'	=>$v['query_type'],		//eq,between,like
					'control_type'	=>$v['control_type'],	//input select checkboc refer getField
					'value'		=>$value,
				);
			}
		}
		$table = strtolower($module);
		$map['name'] = $table;
		unset($data);
		$data['list'] = json_encode($columns);
		$data['query'] = json_encode($query);

		M('module_table')->where($map)->save($data);
		$this->msgReturn(1);
		//$this->write_config($group,$module,$columns,'columns');
		//$this->write_config($group,$module,$query,'query');
	}
	protected function write_config($group,$module,$value,$type){
		if($type==='columns' || $type==='query'){
			$path = APP_PATH."$group/Conf/";
			$config=OF($type,'',$path);
	        C($type.'.'.$module,$value);
	        $config[$type][$module]=$value;
	        OF($type,$config,$path);
	    }
	}
	private $module_table='auth_authority';
	private $module_folder='Controller';
	private $module_ext='Controller.class.php';
	private $ext = '.auto';
	//生成模块结构信息 app/分组/模块/方法
	public function fetch_module(){

		$app = $this->getAppName();
		$groups = $this->getGroup();

		$M = M($this->module_table);
		$map=array('app' =>$app);
		$M->execute('truncate table auth_authority');//先删除当前项目已经生成的节点数据 建议首次执行采用这种方式，以后采用下面的，不然，深坑
		$data = array('status' => '0' );//逻辑置0，不可用，后面如果已经存在，再改为1，这样，被删除的方法就逻辑删除了
		$M->where($map)->save($data);
		$n=0;
		$dict=R(MODULE_NAME.'/Dictionary/get_words');
		$exists = $M->getField('id,url');
		$data_app[] = array('app' => $app, 'type' => '1', 'name'=>$app,'title'=>isset($dict[$app])?$dict[$app]:$app);
		foreach ($groups as $group) {
			$data_groups[] = array('app' => $app, 'group' =>$group, 'type' => '2','name'=>$group, 'url'=>$group, 'title'=>isset($dict[$group])?$dict[$group] : $group);
			$modules = $this->getModule($group);
			foreach ($modules as $module) {
				$data_modules[] = array('app' => $app, 'group' =>$group, 'module'=>$module, 'type' => '3', 'name'=>$module, 'url'=>$module.'/index', 'title'=>isset($dict[$module])?$dict[$module]:$module);
				$module_name=$group.'/'.$module;
				$actions = $this->getAction($group, $module);
				foreach ($actions as $action) {
					$data_actions[$n]['app'] 	= $app;
					$data_actions[$n]['group'] 	= $group;
					$data_actions[$n]['module'] = $module;
					$data_actions[$n]['action'] = $action;
					$data_actions[$n]['type'] 	= '4';
					$data_actions[$n]['url']  	= $group.'/'.$module.'/'.$action;
					$data_actions[$n]['url']  	= $module.'/'.$action;
					$data_actions[$n]['title'] 	= (isset($dict[$action])?$dict[$action]:$action);//(isset($dict[$module])?$dict[$module]:$module).
					$data_actions[$n]['name'] 	= $action;
					++$n;
				}
			}
		}
		
		$data=array('data_app','data_groups','data_modules','data_actions');
		foreach ($data as $value) {
			$value=$$value;
			$new =array();
			foreach ($value as $k => $v) {
				if(in_array($v['url'], $exists)){
					$model = $M->where($v)->find();
					$model['status'] = '1';
					$model = array_merge($model,$v);
					$M->save($model);
				}
				else{
					$new[]=$v;
				}
			}
			
			$M->addAll($new);
		}
		
		//$M->addAll($data_app);
		//$M->addAll($data_groups);
		//$M->addAll($data_modules);
		//$M->addAll($data_actions);
		
		$data = $M->where($map)->order('type')->select();
		foreach ($data as $k => $v) {
			switch ($v['type']) {
				case '1':
				$parent[$v['app']] = $v['id'];
				$data[$k]['pid']=0;
					break;
				case '2':
				$parent[$v['group'].'.'.$v['app']] = $v['id'];
				$data[$k]['pid']=$parent[$v['app']];
					break;
				case '3':
				$parent[$v['module'].'.'.$v['group'].'.'.$v['app']] = $v['id'];
				$data[$k]['pid']=$parent[$v['group'].'.'.$v['app']];
					break;
				case '4':
				$data[$k]['pid']=$parent[$v['module'].'.'.$v['group'].'.'.$v['app']];
					break;
				default:
					# code...
					break;
			}
			$M->save($data[$k]);
		}
		//dump($data);exit();
		//$M->addAll($data,$options=array(),$replace=true);

		$this->success('所有分组/模块/方法已成功读取到'.$this->module_table.'表中.',null,2);
	}
	protected function getAppName(){
		return APP_NAME;
	}

	protected function getGroup(){
		$group_list=C('MODULE_ALLOW_LIST');
		$result = explode(',',$group_list);
		return $result;
	}

	protected function getModule($group){
		if(empty($group))return null;
		$group_path = APP_PATH.$group.'/'.$this->module_folder;

		if(!is_dir($group_path))return null;
		$group_path.='/*.class.php';
		$ary_files = glob($group_path);
	    foreach ($ary_files as $file) {
	        if (is_dir($file)) {
	            continue;
	        }else {
	        	if(strpos($file,$this->module_ext))
	            	$files[] = basename($file,$this->module_ext);
	        }
	    }
	    return $files;
	}
	// 暂时没有考虑操作方法后缀和控制器分层，即以下两项配置
	//'ACTION_SUFFIX'      =>  'Action', 
	//'CONTROLLER_LEVEL'   =>  2,		
	protected function getAction($group, $module){
		$module_name = $group .'/'. $module;
		if(empty($module_name))return null;
		$module=A($module_name);
		$actions=get_class_methods($module);
		$inherents_actions = array(
			'_initialize','__construct','getActionName','isAjax','display','show','fetch','theme',
			'buildHtml','assign','__set','get','__get','__isset',
			'__call','error','success','ajaxReturn','redirect','__destruct','_empty','__hack_module','__hack_action'
		);
		$M=new \ReflectionClass($module);
		foreach ($actions as $action){
			if(!in_array($action, $inherents_actions)){
				$func =   $M->getMethod($action);
                if($func->isPublic()) {
                	if(substr($action, 0, 1) !='_')
						$customer_actions[]=$action;
				}
			}
		}
		return $customer_actions;
	}
}