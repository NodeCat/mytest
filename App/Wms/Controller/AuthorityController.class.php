<?php
namespace Wms\Controller;
use Think\Controller;
class AuthorityController extends CommonController {
	public function index(){
    }

    public function view(){
        $this->display("Index:tree-view");
    }
    public function edit(){
        if(IS_POST){
            $this->save();
        }
        else{
            $this->roles= M('auth_role')->getField('id,name,description');
            $this->display("Auth/authority");
        }
    }
    public function menu(){
        if(IS_POST){
            $type   = I('t');
            $M= M('Auth_authority');
            if(empty($type)){
                $map=array('mpid'=>array('egt','0' ),'status'=>'1','is_deleted'=>0,'show' => 1);
                $result=$M->field("id,title as name,mpid as pId")->where($map)->order('level,queue,id')->select();
                $this->ajaxReturn($result,'JSON');
            }else if($type=="queue"){
                //更新当前节点
                $ids    = I('ids');//当前节点及子节点
                $nids   = I('nids');//同级节点
                $ids    = explode(',', $ids);
                $nids   = explode(',', $nids);
                $data['id']=$ids[0];
                $data['mpid'] = I('pid');
                $data['level'] = I('level');
                
                $M->save($data);
                $data=array();

                //更新子节点
                if(count($ids)>1){
                    $data['mpid'] = $ids[0];
                    $data['show'] = 1;
                    unset($ids[0]);
                    $level = $level + 1;
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
                $data['title'] = I('name');
                $result=$M->save($data);
            }else if($type=='add'){
                $data['title'] = I('name');
                $data['mpid'] = I('pid');
                $data['level'] = I('level');
                $data['queue'] = I('queue');
                $data['type'] = '0' ;
                $result=$M->add($data);
                $this->ajaxReturn($result);
            }else if($type=='del'){
                $ids =I('ids');
                $ids    = explode(',', $ids);
                $map['id'] = array('in', $ids );
                $data['show'] = 0;
                $data['mpid'] =null;
                $result=$M->data($data)->where($map)->save();
            }
            $this->ajaxReturn($result!==false?'Success':'Fail');
        }
        else{
            $this->display("Auth/menu");
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
    protected function save(){
        $role_ids=I('role_ids');
        $map['id'] = array('in',$role_ids);
        $data['rules']=I('auth_ids');
        $M=M('auth_role');
        $res = $M->where($map)->save($data);
        $this->msgReturn(1);
    }
    public function nodes(){
        $id=I('id',0);
        $type=I('type');
        $data['name']=I('name');
        $data['pid']=I('pid');
        $data['level']=I('level');
        $M=M('auth_authority');
        if(empty($type)){
            if(empty($id))$id=0;
            $map=array('pid'=>$id,'status'=>'1','is_deleted'=>0);
            $result=$M->field("id,title as name,pid as pId")->where($map)->order('level,queue')->select();
            $this->ajaxReturn($result,'JSON');
        }else if($type=="m"){
            $map=array('type'=>'0','status'=>'1','is_deleted'=>0);
            $result=$M->field("id,name,pid as pId")->where($map)->order('level,queue')->select();
            $this->ajaxReturn($result,'JSON');
        }else if($type=="all"){
            //$dict=R(MODULE_NAME.'/Dictionary/get_black_words');
            //$dict = implode("','", $dict);
            //$dict ="'".$dict."'";
            //$map=array('status'=>'1','is_deleted'=>0,'mpid'=> null);
            $result=$M->field("id,pid as pid,title as name")->where("status='1' and is_deleted=0 ")->select();
            $this->ajaxReturn($result,'JSON');
        }else if($type=="edit"){
            $result=$M->where('id='.$id)->save($data);
        }else if($type=="queue"){
            $ids =I('ids');
            $ids    =   explode(',', $ids);
            $i=0;
            $result=$M->where('id='.$id)->save($data);
            foreach ($ids as $v) {
                $node['id']=$v;
                $node['queue']=$i+'0';
                $M->save($node);
            }

        }else if($type=='add'){
            $data['isnode']=($data['pid']==0?'0':'1');
            $data['type'] = '0' ;
            $result=$M->add($data);
        }else if($type=='del'){
            $ids =I('ids');
            $map['id'] = array('in', $ids );
            $result=$M->where($map)->delete();
        }
        $this->ajaxReturn($result!==false?'Success':'Fail');
    }

    //编辑权限归类
    public function editCat() {
        $this->display('Index:tree-edit');
    }
}