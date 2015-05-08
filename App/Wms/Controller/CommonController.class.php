<?php
namespace Wms\Controller;
use Think\Controller;

class CommonController extends AuthController {
    public function index() {
        $map ='';
        $this->before($map,'index');
        $this->lists();
    }

    //如果需要自定义列表显示，请重写_before_index函数
    public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
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

    public function search($query = '') {
        $condition = I('query');
        $this->filter_list($condition, '1');
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
        $this->after($map,'search');
        return $map;
    }

    protected function filter_list(&$data,$type = '0') {
        if(empty($this->filter)) {
            $file = strtolower(CONTROLLER_NAME);
            $filter = C($file.'.filter');
        }
        else {
            $filter = $this->filter;
        }
        if(empty($filter)) return ;

        if($type == '1') {
            $table = strtolower(CONTROLLER_NAME);
            foreach ($filter as $key => $val) {
                $val = array_flip($val);
                $filter[$table.'.'.$key] = $val ;
                unset($filter[$key]);
            }
        }
        if(is_array(current($data))){
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

    public function get_list($field = '') {
        $M = D(CONTROLLER_NAME);
        $table = $M->tableName;
        
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $data = $M->scope('default')->getField($field,true);
        return $data;
    }
    protected function lists() {
        $M = D(CONTROLLER_NAME);
        $table = $M->tableName;
        
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $this->pk = $M->getPK();

        $setting = get_setting($table);
        $this->columns = $setting['list'];
        $this->query = $setting['query'];

        $map = $this->search($this->query);

        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        $M->scope('default')->page($p.','.$page_size);
        if(!empty($map)) {
            $M->where($map);
        }
        $this->before($M,'lists');
        $M2 = clone $M;
        $data = $M->select();
        $count  = $M2->count();
        $this->filter_list($data);
        $this->after($data,'lists');
        $this->data = $data;
        
        $this->page($count,$map);
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
            $map['is_deleted'] = 0; 
            $res = $M->where($map)->find($id);
            $this->before($res,'edit');
	        if(!empty($res) && is_array($res)){
                $this->filter_list($res);
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
            $this->before($M, 'add');
            if(ACTION_NAME === 'add') {
                dump($M->data());exit();
                $res = $M->add();

            }
            else {
                $pk = $M->getPk();
                $map[$pk] = I($pk); 
                $res = $M->where($map)->save();
            }
            if($res > 0) {
                $this->after($res, 'add');
                $this->after($res, 'save');
                $this->msgReturn(1);
            }
            else{

                $this->msgReturn(0,$M->getError());
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
        
        $map[$pk]   =   array('in',$ids);
        $data['is_deleted'] = 1;
        $res = $M->where($map)->save($data);
        $this->msgReturn($res);
    }

    public function setting(){
        $table = get_tablename();
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
            R('Code/build_config',array(MODULE_NAME,$table));
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
    function file_download($file, $name, $mime_type='') {
    if(!is_readable($file)) die('File not found or inaccessible!');

    $size = filesize($file);
    $name = rawurldecode($name);

    /* Figure out the MIME type (if not specified) */
    $known_mime_types = get_known_mime_types();

    if($mime_type==''){
        $file_extension = strtolower(substr(strrchr($file,"."),1));

        if(array_key_exists($file_extension, $known_mime_types)){
            $mime_type=$known_mime_types[$file_extension];
        } else {
            $mime_type="application/force-download";
        }
    }

    @ob_end_clean(); //turn off output buffering to decrease cpu usage

    // required for IE, otherwise Content-Disposition may be ignored
    if(ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'Off');
    }

    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header("Content-Transfer-Encoding: binary");
    header('Accept-Ranges: bytes');

    /* The three lines below basically make the download non-cacheable */
    header("Cache-control: private");
    header('Pragma: private');
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    // multipart-download and download resuming support
    if(isset($_SERVER['HTTP_RANGE'])) {
        list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
        list($range) = explode(",",$range,2);
        list($range, $range_end) = explode("-", $range);
        $range=intval($range);

        if(!$range_end) {
            $range_end=$size-1;
        } else {
            $range_end=intval($range_end);
        }

        $new_length = $range_end-$range+1;

        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $new_length");
        header("Content-Range: bytes $range-$range_end/$size");
    } else {
        $new_length=$size;
        header("Content-Length: ".$size);
    }

    /* output the file itself */
    $chunksize = 1*(1024*1024); // 1MB, can be tweaked if needed
    $bytes_send = 0;

    if ($file = fopen($file, 'r')) {
        if(isset($_SERVER['HTTP_RANGE'])) {
            fseek($file, $range);
        }

        while(!feof($file) && (!connection_aborted()) && ($bytes_send<$new_length)) {
            $buffer = fread($file, $chunksize);
            print($buffer); //echo($buffer); // is also possible
            flush();
            $bytes_send += strlen($buffer);
        }

        fclose($file);
    } else {
        die('Error - can not open file.');
    }

    die();
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
        $msg = empty($msg)?($res > 0 ?'操作成功':'操作失败'):$msg;
        if(IS_AJAX){
            $this->ajaxReturn(array('status'=>$res,'msg'=>$msg,'data' => $data));
        }
        else if($result){ 
                $this->success('操作成功');
            }
            else{
                $this->error('操作失败');
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