<?php
namespace Wms\Controller;
use Think\Controller;
class MenuController extends CommonController {
    
    public function _before_index() {
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

    protected function before_add(&$M){
        $M->status = '1';
        $M->is_deleted = 0;
    }

    protected function before_save(&$M) {
        $pid = $M->pid;
        if(!empty($pid)){
            $level = $M->getFieldById($pid,'level');
            $M->level = $level + 1;
        }
        else {
            $M->level = 0 ;
        }
    }

    public function role_authority(){
        $id=I('ids');
        $data=M('auth_role')->field('rules')->find($id);
        $data=explode(',', $data['rules']);
        foreach ($data as $v) {
            $nodes[]=array('id' => $v);
        }
        $this->ajaxReturn($nodes,'JSON');
    }
    public function tree(){
        $this->display("Index:tree-view");
    }
    public function treeedit(){
        $this->display("menu");
    }
    public function sidebar(){
        $M=M(CONTROLLER_NAME);
        $data=$M->field("id,level,pid,name")->select();

        //转换成数组，同级节点的索引值相同

        foreach ($data as $key => $value) {
        	$ary[$value['level']][$value['id']]=array();
        	$index[$value['id']]=$key;
        }
        $i=0;
        $n=count($ary);
        //倒序将下一级节点添加到当前节点的子节点
    	foreach (array_reverse($ary) as $key => $value) {
    		if(++$i==$n)break;
    		foreach ($value as $k => $val) {
    			$ary[$data[$k-1]['level']-1][$data[$k-1]['pid']][$k]=$ary[$key][$k];		
    		}
    	}
    	foreach ($ary as $key => $value) {
    		$i=1;$j=1;
    		$content='';
    		$this->show($data,$ary[$key],$index,$i,$j,$content);
    		break;
    	}
        layout(!$this->isAjax());
    	$this->display('Index:sidebar');
    }

    public function get_level_nodes(){
        $M=M(CONTROLLER_NAME);
        $condition=array('is_deleted'=>'0','status' =>'1');
        $result=$M->field("id,level,pid,name")->where($condition)->order('level')->select();

        //转换成数组，同级节点的索引值相同
        $index[0]=0;
        foreach ($result as $key => $value) {
            $data[$value['level']][$key]=null;
            $index[$value['id']]=$key;
        }
        $level=count($data);
        foreach (array_reverse($data,true) as $key => $value) {
            if(++$i===$level)break;
            foreach ($value as $k => $v) {
                $data[$key-1][$index[$result[$k]['pid']]][$k]=$data[$key][$k]; 
            }          
            unset($data[$key]);
        }
        $i=0; 
        $content[0]="顶级菜单";
        $this->node_level($result,$data[0],$i,$content);
        
        return $content;
    }
    protected function node_level(&$result,&$ary,$i,&$content){
        foreach ($ary as $k => $v) {
            $content[$result[$k]['id']]= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',$i).'|──'.$result[$k]['name'];
            if(is_array($v)){
                $this->node_level($result,$v,$i+1,$content);
            }
        }
    }
    public function allnodes($ids=null){
        if(!empty($ids)){
            $M= M();
            $n=count(explode(',', $ids));
            $result=$M->query("
            SELECT id,name,title,pid as pId
            FROM menu
            ");
        }
        else{
            $M= M(CONTROLLER_NAME);
            $map=array('status'=>'1','is_deleted'=>0);
            $result=$M->field("id,name,pid as pId")->where($map)->select();
        }
        $this->ajaxReturn($result,'JSON');
     }
    public function nodes(){
        $id=I('id');
        $type=I('type');
        $data['name']=I('name');
        $data['pid']=I('pid');
        $data['level']=I('level');
        $M=M(CONTROLLER_NAME);
        if(empty($type)){
            if(empty($id))$id=0;
            $result=$M->field("id,name,pid as pId")->where('pid='.$id)->select();
            $this->ajaxReturn($result,'JSON');
        }else if($type=="all"){
            $result=$M->field("id,name,pid as pId")->select();
            $this->ajaxReturn($result,'JSON');
        }else if($type=="edit"){
            $result=$M->where('id='.$id)->save($data);
        }else if($type=='add'){
            $data['isnode']=($data['pid']==0?'0':'1');
            $result=$M->add($data);
        }else if($type=='del'){
            $result=$M->where('id='.$id)->delete();
        }
        $this->ajaxReturn($result!==false?'Success':'Fail');
    }

    public function menu(){
        if(IS_POST){
            $type   = I('t');
            $M= M('Menu');
            if(empty($type)){
                $map=array('status'=>'1','is_deleted'=>0,'show' => 1);
                $result=$M->field("id,name,pid as pId")->where($map)->order('level,queue,id')->select();
                $this->ajaxReturn($result,'JSON');
            }else if($type=="queue"){
                //更新当前节点
                $ids    = I('ids');//当前节点及子节点
                $nids   = I('nids');//同级节点
                $ids    = explode(',', $ids);
                $nids   = explode(',', $nids);
                $data['id']=$ids[0];
                $data['pid'] = I('pid');
                $data['level'] = I('level');
                
                $result = $M->save($data);
                $data=array();

                //更新子节点
                if(count($ids)>1){
                    $data['pid'] = $ids[0];
                    $data['show'] = 1;
                    unset($ids[0]);
                    $level = I('level') + 1;
                    $map['id'] = array('in',$ids);
                    $M->data($data)->where($map)->save();
                }
                
                $data=array();
                $map=array();
                //更新同级节点
                $i = 0;
                foreach ($nids as $v) {
                    $data['id']=$v;
                    $data['queue'] = $i * 10;
                    ++$i;
                    $M->save($data);
                }
            }else if($type=="edit"){
                $data['id'] = I('id');
                $data['name'] = I('name');
                $result=$M->save($data);
            }else if($type=='add'){
                $data['name'] = I('name');
                $data['pid'] = I('pid');
                $data['level'] = I('level');
                $data['queue'] = I('queue');
                $result=$M->add($data);
                $this->ajaxReturn(array('data'=>$result,'info'=>$result?'Success':'Fail','status'=>$result?'1':'0'));
            }else if($type=='delete'){
                $ids =I('ids');
                $ids    = explode(',', $ids);
                $map['id'] = array('in', $ids );
                $data['show'] = 0;
                $result=$M->data($data)->where($map)->save();
            }
            $this->msgReturn($result);
        }
        else{
            $this->display("Auth/menu");
        }
        
    }
}