<?php
namespace Erp\Controller;
use Think\Controller;

class CommonController extends \Common\Controller\AuthController {

    //默认显示函数列表
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
            'status'    => false, //是否显示状态字段
            'toolbar_tr'=> true, //是否显示表格内的“操作”列的按钮
            'statusbar' => false //是否显示状态栏
        );
        
        $auth = $this->auth;
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => isset($auth['view']),'new'=>'true'), 
            array('name'=>'edit', 'show' => isset($auth['edit']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => isset($auth['delete']),'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($auth['view']),'new'=>'false'), 
            array('name'=>'edit', 'show' => isset($auth['view']),'new'=>'false'), 
            array('name'=>'delete' ,'show' => isset($auth['delete']),'new'=>'false'),
            array('name'=>'import' ,'show' => isset($auth['import']),'new'=>'false'),
            array('name'=>'export' ,'show' => isset($auth['export']),'new'=>'false'),
            array('name'=>'print' ,'show' => isset($auth['print']),'new'=>'false'),
            array('name'=>'setting' ,'show' => isset($auth['setting']),'new'=>'false'),
        );
        $this->status =array(
            array(
                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($auth['forbid'])), 
                array('name'=>'resume', 'title'=>'启用', 'show' => isset($auth['resume']))
            ),
        );
    }

    //查询处理函数，根据form上的查询条件返回$map
    protected function search($query = '') {
        $this->before($query,'search');//查询前的处理函数
        $condition = I('query'); //列表页查询框都是动态生成的，名字都是query['abc']
        $condition = queryFilter($condition); //去空处理
        $table = get_tablename(CONTROLLER_NAME);
        $get = I('path.');unset($get['p']);//获取链接中附加的查询条件，状态栏中的按钮url被附带了查询参数
        //将参数并入$condition
        $get_len = count($get);
        for ($i = 0;$i < $get_len;++$i) {
            if(array_key_exists($get[$i], $query) && !array_key_exists($get[$i], $condition)) {
                $condition[$get[$i]] = $get[++$i];
            }
        }
        $this->condition = $condition;
        !empty($condition) && $this->filter_list($condition, '1');//反向转义，反向转filter
        if(!empty($condition)){
            foreach ($query as $key => $v) {//query是查询条件生成的数组，从query中取出当前提交的查询条件。因此，如果提交了query定义之外的查询条件，是会被过滤掉的
                if(!array_key_exists($key, $condition) && !array_key_exists($key.'_1', $condition)) {
                    continue;
                }
                //查询匹配方式
                switch ($v['query_type']) {
                    case 'eq'://相等
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'in':
                        $map[$key]=array($v['query_type'],$condition[$key]);
                        break;
                    case 'like':
                        $map[$key]=array($v['query_type'],'%'.$condition[$key].'%');
                        break;
                    case 'between'://区间匹配
                        //边界值+1
                        if(check_data_is_valid($condition[$key]) && check_data_is_valid($condition[$key.'_1'])){
                            $condition[$key.'_1'] = date('Y-m-d',strtotime($condition[$key.'_1']) + 86400);
                        }elseif(is_numeric($condition[$key.'_1'])){
                            $condition[$key.'_1'] = $condition[$key.'_1'] + 1;
                        }
                        if(empty($condition[$key]) && !empty($condition[$key.'_1'])) {
                            $map[$key]=array('lt',$condition[$key.'_1']);
                        }
                        elseif(!empty($condition[$key]) && empty($condition[$key.'_1'])) {
                            $map[$key]=array('gt',$condition[$key]);
                        }
                        else {
                            $map[$key]=array($v['query_type'],$condition[$key].','.$condition[$key.'_1']);
                        }
                        break;
                }
            }
        }
        $condition = I('q');//对状态栏的特殊处理,状态栏中的各种状态按钮实际上是附加了各种status=1 这样的查询条件
         if(!empty($condition)){
            $para=explode('&', urldecode($condition));
            foreach ($para as $key => $v) {
                $cond=explode('=', $v);
                if(count($cond)===2)
                    $map[$table.'.'.$cond[0]]=$cond[1];
            }
        }
        
        $this->after($map,'search');//查询条件生成以后，这里可以往$map中加入新的查询条件
        return $map;
    }

    /* 过滤条件
    // protect $filter = array(
    //     'status'=> array(
    //         '1' => '草稿',
    //         '2' => '已完成'
    //     ), 
    // );
    */
    //过滤函数，比如数据表中status值是1，2，3，列表页面中显示的是草稿、审核、已完成
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
        //反向转换
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
        //二维数组
        if(is_array(reset($data))){
            foreach ($data as $key => $val) {
                foreach ($filter as $k => $v) {
                    if(!empty($v[$data[$key][$k]])) {
                        $data[$key][$k] = $v[$data[$key][$k]];
                    }
                }
            }
        }
        else{//一维数组
            foreach ($filter as $k => $v) {
                if(!empty($v[$data[$k]])) {
                    $data[$k] = $v[$data[$k]];
                }
            }
        }
    }

    //默认获取key,value键值对的方法，主要是在下拉框中显示时的数据源
    public function get_list($controller,$field = '') {
        $M = D($controller);
        $table = $M->tableName;
        
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $map['is_deleted'] = 0 ;
        //$map['status'] = '1';
        $data = $M->where($map)->getField($field,true);
        return $data;
    }

    //显示数据列表
    protected function lists($template='') {
        //先根据控制器名称获取对应的表名
        $M = D(CONTROLLER_NAME);
        $table = $M->tableName;
        if(empty($table)) {
            $table = strtolower(CONTROLLER_NAME);
        }
        $this->pk = $M->getPK();
        $setting = get_setting($table);//获取该表对应的显示和查询字段

        //如果当前控制器中定义了字段，则优先采用控制器中的定义，为的是项目上线以后，这种配置在文件中生效，放在数据库中可能会丢
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
        $map = $this->search($this->query);//获取界面上传过来的查询条件

        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        $M->scope('default');//默认查询，default中定义了一些预置的查询条件
        $controllers = array(
            'Warehouse',
            'StockIn',
            'StockOut',
            'Inventory',
            'Stock',
            'StockMoveDetail',
            'Adjustment',
            //'Purchase',
            'LocationArea',
            'Location',
            'Distribution',
        );

        $controllers_muilt = array(
            'Purchase'
        );
        if(in_array(CONTROLLER_NAME, $controllers) && empty($map['warehouse.id'])) {
            $map['warehouse.id'] = array('eq',session('user.wh_id'));
        }
        
        if(in_array(CONTROLLER_NAME, $controllers_muilt) && empty($map['warehouse.id'])) {
            $map['warehouse.id'] = array('in',session('user.rule'));
        }
        if(!empty($map)) {
            $M->where($map);//用界面上的查询条件覆盖scope中定义的
        }
        $this->before($M,'lists');//列表显示前的业务处理

        $M2 = clone $M;//深度拷贝，M2用来统计数量, M 用来select数据。
        $M->page($p.','.$page_size);//设置分页
        
        $data = $M->select();//真正的数据查询在这里生效
        $count  = $M2->page()->limit()->count();//获取查询总数
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $this->data = $data;
        $maps = $this->condition;
        $this->page($count,$maps,$template);
    }

    //引用之前处理模版，隐藏掉不需要的信息
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
            array('name'=>'refer', 'show' => isset($this->auth['refer']),'new'=>'false'), 
        );
        $this->status_type='0';
    }

    public function refer(){
        $this->before($map,'refer');
        $this->lists();
    }

    //查看详情
    public function view() {
        $this->edit();
    }

    //添加
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
            $map[$table.'.'.'is_deleted'] = 0; //预置条件
            $map[$table.'.'.$pk] = $id;
            $res = $M->scope('default')->where($map)->limit(1)->find();//edit也会走scope,但是不会filter
	        if(!empty($res) && is_array($res)){//如果查询成功
                $this->before($res,'edit');//可以在这里写入编辑前的业务
                if(ACTION_NAME == 'view') {
                    $this->filter_list($res);//如果是查看，需要filter
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

    //保存数据，添加时的保存和编辑时的保存都会调用这个函数
    protected function save() {
        $M = D(CONTROLLER_NAME);
        if($M->create()){
            $this->before($M, 'save');//before_save无论添加或编辑都会被调用
            if(ACTION_NAME === 'add') {//添加
                $this->before($M, 'add');//添加前的逻辑处理
                $res = $M->add();
            }
            else {//编辑
                $pk = $M->getPk();
                $map[$pk] = I($pk); 
                $res = $M->where($map)->save();
            }
            if($res > 0) {
                if(ACTION_NAME === 'add') {//添加成功后
                    $this->after($res, 'add');
                }
                else {
                    $res = $map[$pk];
                }
                $this->after($res, 'save');//保存成功后
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

    //删除
    public function delete() {
        $M      =   D(CONTROLLER_NAME);
        $pk     =   $M->getPK();
    	$ids    =   I($pk);//要删除的主键列表，以逗号分割
        $ids    =   explode(',', $ids);
        $ids    =   array_filter($ids);
        $ids    =   array_unique($ids);
        $this->before($ids,'delete');//删除前
        $map[$pk]   =   array('in',$ids);
        $data['is_deleted'] = 1;
        $res = $M->where($map)->save($data);//逻辑删除
        if($res == true) {
            $this->after($ids,'delete');//删除后
        }
        $this->msgReturn($res);
    }

    //设置要显示的字段和是否是查询条件，开发时使用，线上禁用
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
    //excel导入
    public function import() {
        $file = $this->upload();
        if(empty($this->columns)) {
            $table = get_tablename(CONTROLLER_NAME);
            $setting = get_setting($table);
            $columns = $setting['list'];
        }
        else {
            $columns = $this->columns;
        }
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
        $this->after($data,'import');//导入后
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

    //上传，导入时的前置页面
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
    //导出
    public function export() {
        import("Common.Lib.PHPExcel");
        import("Common.Lib.PHPExcel.IOFactory");
        $Excel = new \PHPExcel(); 
        $i = 1;
        if(empty($this->columns)) {
            $table = get_tablename(CONTROLLER_NAME);
            $res = get_setting($table);
            $columns = $res['list'];
        }
        else {
            $columns = $this->columns;
        }
        
        $ary  =  array("", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $Sheet = $this->get_excel_sheet($Excel);
        foreach ($columns as $key  => $value) { 
            $Sheet->setCellValue($ary[$i/27].$ary[$i%27].'1', $value);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setSize(14);
            $Sheet->getStyle($ary[$i/27].$ary[$i%27].'1')->getFont()->setBold(true);
            ++$i;
        }

        if(empty($this->query)){
            $this->assign('query',$setting['query']);
        }
        else {
            $this->assign('query',$this->query);
        }
        $map = $this->search($this->query);//获取界面上传过来的查询条件

        $M  =  D(CONTROLLER_NAME);
        $result = $M->scope('default')->where($map)->select();
        $this->filter_list($result);
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
   
    //访问不存在的方法时
    public function _empty($action){
       $this->error('unknown',U('index'));
    }
    //前置函数钩子
    protected function before(&$data, $func_name = '') {
    	$func = 'before_' . (empty($func_name) ? ACTION_NAME : $func_name);
		if(method_exists($this, $func)){
            $this->$func($data);
        }
    }
    //后置函数钩子
    protected function after(&$res, $func_name = '') {
    	$func = 'after_' . (empty($func_name) ? ACTION_NAME : $func_name);
		if(method_exists($this, $func)){
            $this->$func($res);
        }
    }
    //统一的返回方法
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
    //旧版的分页函数，主要是为了兼容旧代码
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
    //分页函数，具体请参考手册
    protected function page($count, $map='',$template=''){
        $p              = I("p", 1);
        $page_size      = C('PAGE_SIZE');
        if(IS_AJAX) {
            if(ACTION_NAME == 'refer') {
                $target = "#modal-refer .modal-body";
            }
            else {
                $target = "#table-content";
            }
        }
        else {
            $target = "#table-content";
        }
        //$target = $target;
        $pagesId = 'page';
        import("Common.Lib.Page");
        $Page = new \Common\Lib\Page($count, $page_size, $map,$target, $pagesId);
        $this->page     = $Page->show();
        $this->pageinfo = $Page->nowPage.'/'.$Page->totalPages;
        $this->jump_url = $Page->jump_url;
        if(empty($template)){//这里根据是否ajax显示不同的模版
           $template= IS_AJAX ? 'Table:list':'Table:index';
        }
        $this->display($template);
    }
    
}
