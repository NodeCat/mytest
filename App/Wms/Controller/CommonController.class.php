<?php
namespace Wms\Controller;
use Think\Controller;

class CommonController extends AuthController {
    public function index() {
    	$this->pk = $M->getPK();
        $condition = $pill;
        $this->pill = array('status=1'=>'已启用','status=0'=>'已禁用');
        $condition = I('query');
        $map=array();
        if(!empty($condition)){
            $M = D(CONTROLLER_NAME);
            $table = $M->tableName;
            if(empty($table)) {
                $table = strtolower(CONTROLLER_NAME);
            }
            $query = get_setting($table);

            foreach ($query['query'] as $key => $v) {
                switch ($v['query_type']) {
                    case 'eq':
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'like':
                        $map[$key]=array($v['query_type'],'%'.$condition[$key].'%');
                        break;
                    case 'between':
                        $map[$key]=array($v['query_type'],$condition[$key].','.$condition[$key].'_1');
                        break;
                }
            }
            $map = queryFilter($map);
        }
        else{
            $condition = I('pill');
             if(!empty($condition)){
                $para=explode('&', urldecode($condition));
                foreach ($para as $key => $v) {
                    $cond=explode('=', $v);
                    if(count($cond)===2)
                        $map[$cond[0]]=$cond[1];
                }
            }
        }
        $this->page($M,$map);
    }

    public function _before_index (){
        $M = D(CONTROLLER_NAME);
        $table = $M->tableName;
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $setting = get_setting($table);
        $this->columns = $setting['list'];
        $this->query = $setting['query'];
        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => true, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
            ),
        );
        $this->status_type='0';
    }
    public function _before_view(){
        
    }
    public function view() {
        $this->display();
    }

    public function add() {
    	if(IS_POST) {
    		$this->save();
    	}
    	else {
    		$this->display();
    	}
    }

    public function edit() {
    	if(IS_POST) {
    		$this->save();
    	}
    	else {
            $M = M(CONTROLLER_NAME);
    		$pk = $M->getPk();
	    	$id=I($pk);
	      	if(empty($id)){
	            $this->msgReturn(0,'param_error');
			}
	        $map = $M->default_map;
	        $res = $M->where($map)->find($id);

	        if(!empty($res) && is_array($res)){
	            //X(CONTROLLER_NAME, $id, $res);
	            $this->data = $res;
	        }
	        else{
	            $this->msgReturn(0,'not_found');
	        }
	        $this->pk = $pk;
			$this->display();
		}
    }

    protected function save() {
        $M = D(CONTROLLER_NAME);
        if($M->create()){
            $this->before($M, 'save');
            $this->before($M);
            if(ACTION_NAME === 'add') {
                $res = $M->add();
            }
            else {
                $res = $M->save();
            }
            $this->after($res);
            $this->after($res, 'save');
            if($res > 0) {
                $this->msgReturn($res);
            }
            else{
                $this->msgReturn($res);
            }
        }
        else {
            $this->error($M->getError());
        }
    }

    public function delete() {
        $pk     =   $M->getPK();
    	$ids    =   I($pk);
        $ids    =   explode(',', $ids);
        $ids    =   array_filter($ids);
        $ids    =   array_unique($ids);
        $M      =   M(CONTROLLER_NAME);
        
        $map[$pk]   =   array('in',$ids);
        $res = $M->where($map)->delete();
        $this->msgReturn($result);
    }

    public function setting(){
        if(IS_POST){
            $M =M('module_column');
            $data=$_POST["query"];
            foreach ($data as $k => $v) {
                $data[$k]['id']=$v['id'];
                $data[$k]['title']=$v['title'];
                if ($v['query_able']==='on')
                    $data[$k]['query_able']=true;
                else
                    $data[$k]['query_able']=false;
                if ($v['list_show']==='on')
                    $data[$k]['list_show']=true;
                else
                    $data[$k]['list_show']=false;
                //if ($v['add_show']==='on')
                //    $data[$k]['add_show']=true;
                //else
                //    $data[$k]['add_show']=false;
                $result=$M->save($data[$k]);
            }
            R('Code/build_config',array(CONTROLLER_NAME));
            $this->ajaxReturn(array('data'=>0,'info'=>$result?'Success':'Fail','status'=>$result?'1':'0'));
        }
        else{
            $M =M('module_column');
            $map['module']=CONTROLLER_NAME;
            $this->data=$M->where($map)->order('list_order')->select();
            $this->display('Code:setting');

        }
       
    }



    public function _empty($action){
       $this->error('unknown',U('index'));
    }
    private function before(&$data, $func_name = '') {
    	$func = 'before_' . (empty($func_name) ? ACTION_NAME : $func_name);
		if(method_exists($this, $func)){
            $this->$func($data);
        }
    }
    private function after(&$res, $func_name = '') {
    	$func = 'after_' . (empty($func_name) ? ACTION_NAME : $func_name);
		if(method_exists($this, $func)){
            $this->$func($res);
        }
    }
    protected function msgReturn($res, $msg='', $data = null){
        $msg = empty($msg)?(empty($res)?'操作成功':'操作失败'):$msg;
        if(IS_AJAX){
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data' => $data));
        }
        else if($result){ 
                $this->success('操作成功');
            }
            else{
                $this->error('操作失败');
            }
    }
    protected function page(&$select,$map='',$template){
        $p              = I("p");
        if(empty($p))$p = 1;
        $page_size      = C('PAGE_SIZE');
        if(isset($select)){
            if($nopage){
                $this->data = $select->where($map)->select();
            }
            else{
                $this->data = $select->where($map)->page($p.','.$page_size)->select();
                $count  = $select->where($map)->count();
            }
        }

        $target = "table-content";
        $pagesId = 'page';
        import("@.Lib");
        $Page = new \Wms\Lib\Page($count, $page_size, $map,$target, $pagesId);
        $this->page     = $Page->show();
        $this->pageinfo = $Page->nowPage.'/'.$Page->totalPages;
        $this->jump_url = $Page->jump_url;
        if(empty($template)){
           $template= IS_AJAX ? 'Table:list':'Table:index';
        }
        $this->display($template);
    }
    
}