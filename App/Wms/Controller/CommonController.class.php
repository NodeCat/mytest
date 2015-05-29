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
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
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

    protected function search($query = '') {
        $this->before($query,'search');
        $condition = I('query');
        $condition = queryFilter($condition);
        $table = get_tablename(CONTROLLER_NAME);
        $get = I('get.');unset($get['p']);
        foreach ($get as $key => $value) {
            $param[$table.'.'.$key] = $value;
            if(!array_key_exists($key, $condition)) {
                $condition[$table.'.'.$key] = $value;
            }
        }
        $this->condition = $condition;
        !empty($condition) && $this->filter_list($condition, '1');
        if(!empty($condition)){
            foreach ($query as $key => $v) {
                if(!array_key_exists($key, $condition)) {
                    continue;
                }
                switch ($v['query_type']) {
                    case 'eq':
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'in':
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
        }
        $condition = I('q');
         if(!empty($condition)){
            $para=explode('&', urldecode($condition));
            foreach ($para as $key => $v) {
                $cond=explode('=', $v);
                if(count($cond)===2)
                    $map[$table.'.'.$cond[0]]=$cond[1];
            }
        }
        
        
        $this->after($map,'search');
        //dump($map);
        return $map;
    }

    protected function filter_list(&$data,$type = '0',$filter = '') {
        if(!is_array($data)) return;
        if(empty($filter)){
            if(empty($this->filter)) {
                $file = strtolower(CONTROLLER_NAME);
                $filter = C($file.'.filter');
            }
            else {
                $filter = $this->filter;
            }
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
        $maps = $this->condition;
        $this->page($count,$maps,$template);
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
                if(ACTION_NAME == 'view') {
                    $this->filter_list($data);
                }
	            $this->data = $res;
	        }
	        else{
                $msg = ' '.$M->getError().' '.$M->_sql();
	            $this->msgReturn(0,'没有找到该记录，请检查表关联或者纪录状态'.$msg);
	        }
	        $this->pk = $pk;
			$this->display(ACTION_NAME);
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
        $file = $this->upload();

        $table = get_tablename(CONTROLLER_NAME);
        $setting = get_setting($table);
        $columns = $setting['list'];
        foreach ($columns as $key => $val) {
            $list[$val] = $key ;
        }

        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        import("PHPExcel.PHPExcel.Reader.Excel5");  

        $objReader  =  \PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel  =  $objReader->load($file);   
        $sheet  =  $objPHPExcel->getSheet(0);
        $rows  =  $sheet->getHighestRow(); // 取得总行数
        $cols  =  $sheet->getHighestColumn(); // 取得总列数

        //获取关联表中的数据，比如类别表，获取name,id结构的键值对
        //这样excel中字段值是类别1，那么就被转换成类别1的id

        //读取第一行,将第一行的标题中的中文文字转成数据库中的字段
        for($i=1,$j = 'A';$j<= $cols;$j++,$i++){
            $val = trim(iconv('utf-8','gbk',$objPHPExcel->getActiveSheet()->getCell("$j1")->getValue()));
            if($val == "") {
               continue;
            }
            else {
                if(array_key_exists($val, $list)) {
                    $columns[$i] = $list[$val];
                }
            }
        }
        //遍历数据
        for($i = 2;$i<= $rows;$i++) {
            for($k=1,$j = 'A';$j<= $cols;$j++,$k++){
                $val = trim(iconv('utf-8','gbk',$objPHPExcel->getActiveSheet()->getCell("$j$i")->getValue()));
                if(array_key_exists($k, $columns)) {
                    $data[$i][$columns[$k]] = $val;    
                }
                
            }
        }
        $this->after($data,'import');
        unset($rows);
        $i = 2;
        $M = D(CONTROLLER_NAME);
        foreach ($data as $row) {
            $row = $M->create($row);
            $res = $M->add($row);
            if($res < 1) {
                $fail[] = $i;
                $fail_msg = $M->getError();
            }
            else {
                $success[] = $i;
            }
            ++$i;
        }
        $i = count($fail);
        $j = count($success);
        $msg = '成功：'.$j.'条。';
        if($i > 0) {
            $msg .= ' 失败：'.$i.'条。最后一条导入失败的错误信息是：'.$fail_msg.'。其中导入失败的行数：'.implode(',', $fail).'。';
        }
        $this->msgReturn(1,'导入完成。'.$msg);
    }

    protected function upload(){
        if(IS_POST) {
            $upload_path = RUNTIME_PATH;
            $config = array(
                'maxSize'    =>    10241024,//C('MAX_UPLOAD_FILE_SIZE'),
                'rootPath'   =>    $upload_path,
                'savePath'   =>    '',
                'saveName'   =>    array('uniqid',''),
                'exts'       =>    array('jpg', 'gif', 'png', 'jpeg','xls','doc','xlsx','docx'),
                'autoSub'    =>    false,
                'subName'    =>    array('date','Ymd'),
            );
            $upload = new \Think\Upload($config);
            $info   =   $upload->upload();
            if(!$info) {
                $this->msgReturn(0,$upload->getError());
            }else{
                foreach ($info as $v) {
                    $files[]=$upload_path.$v['savepath'].$v['savename'];
                }
                return $files;
            }
        }
        else {
            $this->display('Index:upload');
            exit();
        }
    }
    public function export() {
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel(); 
        $i = 1;
        $table = get_tablename(CONTROLLER_NAME);
        $res = get_setting($table);
        $columns = $res['list'];
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }
        $M  =  D(CONTROLLER_NAME);
        $result = $M->scope('default')->select();
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
   
    public function _empty($action){
       $this->error('unknown',U('index'));
    }
    protected function before(&$data, $func_name = '') {
    	$func = 'before_' . (empty($func_name) ? ACTION_NAME : $func_name);
		if(method_exists($this, $func)){
            $this->$func($data);
        }
    }
    protected function after(&$res, $func_name = '') {
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
                $this->success($msg,$url);
            }
            else{
                $this->error($msg,$url);
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
