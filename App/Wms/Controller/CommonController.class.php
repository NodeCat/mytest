<?php
namespace Wms\Controller;
use Think\Controller;

class CommonController extends AuthController {
    public function index() {
        $this->before($map,'index');
        $this->lists();
    }
    //如果需要自定义列表显示，请重写_before_index函数
    public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => false
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => !isset($auth['view']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => !isset($auth['delete']),'new'=>'false'),
            array('name'=>'import' ,'show' => !isset($auth['import']),'new'=>'false'),
            array('name'=>'export' ,'show' => !isset($auth['export']),'new'=>'false'),
            array('name'=>'print' ,'show' => !isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => !isset($auth['setting']),'new'=>'false'),
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => !isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => !isset($auth['resume']))
            ),
        );
        $this->status_type='0';
    }

    public function search($query = '') {
        $this->before($query,'search');
        $condition = I('query');
        $condition = queryFilter($condition);
        !empty($condition) && $this->filter_list($condition, '1');
        if(!empty($condition)){
            foreach ($query as $key => $v) {
                switch ($v['query_type']) {
                    case 'eq':
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'like':
                        $map[$key]=array($v['query_type'],'%'.$condition[$key].'%');
                        break;
                    case 'between':
                        $map[$key]=array($v['query_type'],$condition[$key].','.$condition[$key.'_1']);
                        break;
                }
            }
            $map = queryFilter($map);
        }
        else{
            $condition = I('q');
             if(!empty($condition)){
                $para=explode('&', urldecode($condition));
                $table = get_tablename();
                foreach ($para as $key => $v) {
                    $cond=explode('=', $v);
                    if(count($cond)===2)
                        $map[$table.'.'.$cond[0]]=$cond[1];
                }
            }
        }
        
        $this->after($map,'search');
        
        return $map;
    }

    protected function filter_list(&$data,$type = '0') {
        if(!is_array($data)) return;
        if(empty($this->filter)) {
            $file = strtolower(CONTROLLER_NAME);
            $filter = C($file.'.filter');
        }
        else {
            $filter = $this->filter;
        }
        if($type == '1') {
            $table = strtolower(CONTROLLER_NAME);
            foreach ($filter as $key => $val) {
                $val = array_flip($val);
                $filter[$table.'.'.$key] = $val ;
                unset($filter[$key]);
            }
        }
        else {
        }
        if(is_array(reset($data))){
            foreach ($data as $key => $val) {
                foreach ($filter as $k => $v) {
                    if(!empty($v[$data[$key][$k]])) {
                        $data[$key][$k] = $v[$data[$key][$k]];
                    }
                }
            }
        }
        else{
            foreach ($filter as $k => $v) {
                if(!empty($v[$data[$k]])) {
                    $data[$k] = $v[$data[$k]];
                }
            }
        }
    }

    public function get_list($controller,$field = '') {
        $M = D($controller);
        $table = $M->tableName;
        
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $data = $M->getField($field,true);
        return $data;
    }
    protected function lists($template='') {
        $M = D(CONTROLLER_NAME);
        $table = $M->tableName;
        
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $this->pk = $M->getPK();
        $setting = get_setting($table);
        if(empty($this->columns)) {
            $this->assign('columns',$setting['list']);
        }
        else {
            $this->assign('columns',$this->columns);
        }
        if(empty($this->query)){
            $this->assign('query',$setting['query']);
        }
        else {
            $this->assign('query',$this->query);
        }
        $map = $this->search($this->query);

        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        $M->scope('default');
        if(!empty($map)) {
            $M->where($map);
        }
        $this->before($M,'lists');

        $M2 = clone $M;
        $M->page($p.','.$page_size);
        $data = $M->select();
        $count  = $M2->page()->limit()->count();
        $this->after($data,'lists');
        $this->filter_list($data);
        $this->data = $data;
        $this->page($count,$map,$template);
    }

    public function _before_refer() {
        $this->refer=I('refer');
        $this->table = array(
            'toolbar'   => FALSE,
            'searchbar' => true, 
            'checkbox'  => FALSE, 
            'status'    => FALSE, 
            'toolbar_tr'=> true
        );
        $this->toolbar_tr =array(
            array('name'=>'refer', 'show' => !isset($auth['refer']),'new'=>'false'), 
        );
        $this->status_type='0';
    }

    public function refer(){
        $this->before($map,'refer');
        $this->lists();
    }

    public function view() {
        $this->edit();
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
            $M = D(CONTROLLER_NAME);
    		$pk = $M->getPk();
            $table = $M->tableName;
            if(empty($table)) {
                $table = strtolower(CONTROLLER_NAME);
            }
	    	$id=I($pk);
	      	if(empty($id)){
	            $this->msgReturn(0,'param_error');
			}
            $map[$table.'.'.'is_deleted'] = 0; 
            $map[$table.'.'.$pk] = $id;
            $res = $M->scope('default')->where($map)->limit(1)->find();
	        if(!empty($res) && is_array($res)){
                $this->before($res,'edit');
                //$this->filter_list($res);
	            $this->data = $res;
	        }
	        else{
                $msg = ' '.$M->getError().' '.$M->_sql();
	            $this->msgReturn(0,'没有找到该记录，请检查表关联或者纪录状态'.$msg);
	        }
	        $this->pk = $pk;
			$this->display();
		}
    }

    protected function save() {
        $M = D(CONTROLLER_NAME);
        if($M->create()){
            $this->before($M, 'save');
            if(ACTION_NAME === 'add') {
                $this->before($M, 'add');
                $res = $M->add();
            }
            else {
                $pk = $M->getPk();
                $map[$pk] = I($pk); 
                $res = $M->where($map)->save();
            }
            if($res > 0) {
                if(ACTION_NAME === 'add') {
                    $this->after($res, 'add');
                }
                else {
                    $res = $map[$pk];
                }
                $this->after($res, 'save');
                $this->msgReturn(1);
            }
            else{
                $msg = '保存失败，错误和sql：'.$M->getError().' '.$M->_sql();
                $this->msgReturn(0,$msg);
            }
        }
        else {
            $this->msgReturn(0,$M->getError());
        }

    }

    public function delete() {
        $M      =   D(CONTROLLER_NAME);
        $pk     =   $M->getPK();
    	$ids    =   I($pk);
        $ids    =   explode(',', $ids);
        $ids    =   array_filter($ids);
        $ids    =   array_unique($ids);
        $this->before($ids,'delete');
        $map[$pk]   =   array('in',$ids);
        $data['is_deleted'] = 1;
        $res = $M->where($map)->save($data);
        if($res == true) {
            $this->after($ids,'delete');
        }
        $this->msgReturn($res);
    }

    public function setting(){
        $table = get_tablename();
        if(IS_POST){
            $M =M('module_column');
            $data=I("query");
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
            $A= A('Code');
            $A->build_config(MODULE_NAME,$table);
            $this->msgReturn(1);
        }
        else{
            $M =M('module_column');
            $map['module']=$table;
            $this->data=$M->where($map)->order('list_order')->select();
            $this->display('Code:setting');

        }
       
    }
    public function import() {
        $info = $this->upload();
        $data = get_setting(CONTROLLER_NAME);
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $sheet = $this->get_excel_sheet();


    }

    public function export() {
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel(); 
        $i = 1;
        $res = get_setting(CONTROLLER_NAME);
        $columns = $res['list'];
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }
        $M  =  M(CONTROLLER_NAME);
        $result = $M->select();
        for($j  = 0;$j<count($result) ; ++$j){
            $i  = 1;
            foreach ($columns as $key  => $value){
                $Sheet->setCellValue($ary[$i/27].$ary[$i%27].($j+2), $result[$j][$key]);
                ++$i;
            }
        }
        
        if(ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        date_default_timezone_set("Asia/Shanghai"); 
        header("Content-Type: application/force-download");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition:attachment;filename = ".time().".xlsx");
        header('Cache-Control: max-age=0');
        header("Pragma:no-cache");
        header("Expires:0");
        header("Content-Length: ");
        $objWriter  =  \PHPExcel_IOFactory::createWriter($Excel, 'Excel2007'); 
        $objWriter->save('php://output');
        
    }
    protected function get_excel_sheet(&$Excel) {
        $Excel->getProperties()
        ->setCreator("Dachuwang")
        ->setLastModifiedBy("Dachuwang")
        ->setTitle("Dachuwang")
        ->setSubject("Dachuwang")
        ->setDescription("Dachuwang")
        ->setKeywords("Dachuwang")
        ->setCategory("Dachuwang");
        $Excel->setActiveSheetIndex(0);
        $Sheet  =  $Excel->getActiveSheet();  
                
        $Sheet->getDefaultColumnDimension()->setAutoSize(true);
        $Sheet->getDefaultStyle()->getFont()->setName('Arial');
        $Sheet->getDefaultStyle()->getFont()->setSize(13);
        return $Sheet;
    }

    public function upload(){
        import('ORG.Net.UploadFile');
        $upload             = new UploadFile();
        $upload->maxSize    = C('MAX_UPLOAD_FILE_SIZE');
        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg','xls','doc');
        $upload->savePath   = __PUBLIC__.'/Upload/';
        if(!$upload->upload()) {
            $this->error($upload->getErrorMsg());
            return null;
        }else{
            return $info;
            $info = $upload->getUploadFileInfo();
            $this->import($info);
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
    protected function msgReturn($res, $msg='', $data = '', $url=''){
        $msg = empty($msg)?($res > 0 ?'操作成功':'操作失败'):$msg;
        if(IS_AJAX){
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data'=>$data,'url'=>$url));
        }
        else if($res){ 
                $this->success('操作成功',$url);
            }
            else{
                $this->error('操作失败',$url);
            }
        exit();
    }
    protected function mpage($M, $map='',$template){
        $p              = I("p", 1);
        $page_size      = C('PAGE_SIZE');

        $map_default['is_deleted'] = 0; 
        $M = $M->scope('default')->page($p.','.$page_size)->where($map)->where($map_default)->order($M->getPk().' desc');
        $data = $M->select();
        $this->data = $data;
        $count  = $M->scope('default')->where($map)->where($map_default)->count();
        $target = "table-content";
        $pagesId = 'page';
        import("Common.Lib.Page");
        $Page = new \Common\Lib\Page($count, $page_size, $map,$target, $pagesId);
        $this->page     = $Page->show();
        $this->pageinfo = $Page->nowPage.'/'.$Page->totalPages;
        $this->jump_url = $Page->jump_url;
        if(empty($template)){
           $template= IS_AJAX ? 'Table:list':'Table:index';
        }
        $this->display($template);
    }
    protected function page($count, $map='',$template=''){
        $p              = I("p", 1);
        $page_size      = C('PAGE_SIZE');
        $target = "table-content";
        $pagesId = 'page';
        import("Common.Lib.Page");
        $Page = new \Common\Lib\Page($count, $page_size, $map,$target, $pagesId);
        $this->page     = $Page->show();
        $this->pageinfo = $Page->nowPage.'/'.$Page->totalPages;
        $this->jump_url = $Page->jump_url;
        if(empty($template)){
           $template= IS_AJAX ? 'Table:list':'Table:index';
        }
        $this->display($template);
    }
    
}