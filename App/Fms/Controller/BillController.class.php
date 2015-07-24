<?php
namespace Fms\Controller;

use Think\Controller;

class BillController extends \Wms\Controller\CommonController
{
	protected $query;
	protected $columns = array (
		
		'billing_num' => '账单号',
		'theory_start' => '帐期',
        'billing_cycle' => '帐期长度',
        'pay_date' => '结算日期',
        'expire_status' => '逾期',
		'shop_name' => '店铺名称',
		'mobile' => '店铺手机号',
		'bd_name' => 'bd 名称',
		'bd_mobile' => 'bd 手机号',
		'status' => '账单状态'
    );
    protected $filter = array (
        'expire_status' => array(
            '0' => '',
            '1' => '逾期未付'
        )
    );
    

	public function before_index() {
        $this->table = array(
                'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
                'searchbar' => true, //是否显示搜索栏
                'checkbox'  => true, //是否显示表格中的浮选款
                'status'    => false,
                'toolbar_tr'=> true,
                'statusbar' => true
        );
        
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'), 
        );

        $this->pill = array(
			'status'=> array(
				'11'=>array('value'=>'2','title'=>'未打款','class'=>'warning'),
				'13'=> array('value'=>'3','title'=>'同意打款','class'=>'info'),//已审核
				'14'=> array('value'=>'4','title'=>'已打款','class'=>'danger'),
				'04'=> array('value'=>'5','title'=>'已收款','class'=>'success')
			)
		);

        //查询条件
        $A = A('Common/Order', 'Logic');
        $query = $A->billQuery();
        $this->query = array(
        	'billing_cycle' => array(
        		'title' => '账期',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['billing_cycle']
            ),
            'area' => array(
        		'title' => '地区',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['area']
            ),
            'bd' => array(
        		'title' => 'bd列表',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['bd']
            ),
            'billing_status' => array(
        		'title' => '账单状态',
        		'query_type' => 'eq',
                'control_type' => 'select',
                'value' => $query['billing_status']
            ),
            'start_time' =>    array (    
			'title' => '账单时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => '',   
		),
            
        );
    }

    protected function search($query = '') {
        $this->before($query,'search');//查询前的处理函数
        $condition = I('query'); //列表页查询框都是动态生成的，名字都是query['abc']
        $condition = queryFilter($condition); //去空处理
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
                $map[$key] = $condition[$key];
                switch ($v['query_type']) {
                    case 'between'://区间匹配
                        $map['end_time'] = $condition[$key.'_1'];
                    break;
                }
                continue;
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
                        if(check_data_is_valid($condition[$key]) || check_data_is_valid($condition[$key.'_1'])){
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
                    $map[$cond[0]]=$cond[1];
            }
        }
        
        $this->after($map,'search');//查询条件生成以后，这里可以往$map中加入新的查询条件
        return $map;
    }

    public function view()
    {
    	$A = A('Common/Order', 'Logic');
    	$map['id'] = I('id');
    	$data = $A->billDetail($map);
        $data['state'] = $data['billing_info']['status_code'];
    	$this->data = $data;
    	$remarks = $A->billRemarkList($map);
    	$this->remarks = $remarks['list'];
    	$this->display();
    }

    public function detail()
    {
    	$A = A('Common/Order', 'Logic');
    	$map['id'] = I('id');
    	$map['date'] = I('date');
    	$data = $A->billOrders($map);
    	$this->data = $data['list'];
    	$this->display();
    }

    public function pay()
    {
        $A = A('Common/Order', 'Logic');
        $map['id'] = I('id');
        //{"id": xx, "author_id":xx, "author_name":xx, "role_name": xx, "role_id":xx,"payment":xx}
        $map['author_id'] = session('user.uid');
        $map['author_name'] = session('user.username');
        $map['role_id'] = session('user.role');
        $map['role_name'] = session('user.role');

        $data = $A->billDetail($map);
        if ($data['billing_info']['status_code'] == '4' ) {
            $map['payment'] = '1';
        } else {
            $map['payment'] = '0';
        }

        $res = $A->billPay($map);

        $url = $res['status'] == '0' ? U('view',array('id'=>$map['id'])) : '';
        $status = $res['status'] == '0' ? 1 : 0;

        $this->msgReturn($status, $res['msg'].$res['status'], '', $url);
    }

    public function remark()
    {
        $A = A('Common/Order', 'Logic');
        $map['id'] = I('id');
        $data = $A->billDetail($map);
        $this->data = $data;
        $remarks = $A->billRemarkList($map);
        $this->remarks = $remarks['list'];
        $this->display();
    }

    //显示数据列表
    protected function lists($template='')
    {
        //先根据控制器名称获取对应的表名
        $M = M();

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

        $map = $this->search($this->query);
        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        $map['currentPage'] = $p;
        $map['itemsPerPage'] = $page_size;

        if(!empty($map)) {
            $M->where($map);//用界面上的查询条件覆盖scope中定义的
        }
        $this->before($M,'lists');//列表显示前的业务处理

        $M2 = clone $M;//深度拷贝，M2用来统计数量, M 用来select数据。
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $A = A('Common/Order', 'Logic');
        
        $data = $A->billList($map);
        foreach ($data['list'] as &$val) {
            $val['theory_start'] = $val['theory_start'] . ' - ' . $val['theory_end'];
        }
        $this->pk = 'id';
        $this->assign('data', $data['list']); 
        $maps = $this->condition;
        $template= IS_AJAX ? 'Common@Table/list':'Common@Table/index';
        $this->page($data['total'],$maps,$template);
    }
}