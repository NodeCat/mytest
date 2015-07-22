<?php
namespace Fms\Controller;

use Think\Controller;

class BillController extends \Wms\Controller\CommonController
{
	protected $query;
	protected $columns = array (
		
		'billing_num' => '账单号',
		'expire_time' => '结账截至日期',
		'start_time' => '账单起始时间（包括）',
		'end_time' => '账单结束日期（不包括该值）',
		'shop_name' => '店铺名称',
		'mobile' => '店铺手机号',
		'bd_name' => 'bd 名称',
		'bd_mobile' => 'bd 手机号',
		'expire_status' => '逾期未付标记位',
		'status' => '账单状态'
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
				'11'=>array('value'=>'11','title'=>'未打款','class'=>'warning'),
				'13'=> array('value'=>'13','title'=>'同意打款','class'=>'info'),//已审核
				'14'=> array('value'=>'14','title'=>'已打款','class'=>'danger'),
				'04'=> array('value'=>'04','title'=>'已收款','class'=>'success')
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
            'stock_purchase.created_time' =>    array (    
			'title' => '账单时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => '',   
		),
            
        );
    }

    public function view()
    {
    	$A = A('Common/Order', 'Logic');
    	$map['id'] = I('id');
    	$data = $A->billDetail($map);
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

        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        
        if(!empty($map)) {
            $M->where($map);//用界面上的查询条件覆盖scope中定义的
        }
        $this->before($M,'lists');//列表显示前的业务处理

        $M2 = clone $M;//深度拷贝，M2用来统计数量, M 用来select数据。
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $A = A('Common/Order', 'Logic');
        
        $data = $A->billList();
        $this->pk = 'id';
        $this->assign('data', $data['list']); 
        $maps = $this->condition;
        $template= IS_AJAX ? 'Common@Table/list':'Common@Table/index';
        $this->page($count,$maps,$template);
    }
}