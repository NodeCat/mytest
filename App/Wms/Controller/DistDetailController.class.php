<?php
namespace Wms\Controller;
use Think\Controller;
class DistDetailController extends CommonController {
	protected $columns = array (
            'order_id' => '订单ID',
            'line' => '订单线路',
            'address' => '送货地址',
            'time' => '送货时间',
            'name' => '货品名称',
            'attrs' => '规格',
            'qty' => '数量',
    );
    protected $query   = array (
            'company_id' => array(
                    'title' => '所属系统',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'Company.id,name',
            ),
            'type' => array(
                    'title' => '订单类型',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
                            '1' => '未发运',
                            '2' => '已发运',
                    ),
            ),
            'line' => array(
                    'title' => '线路',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => '',
            ),
            'wh_id' => array(
                    'title' => '所属仓库',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'warehouse.id,name',
            ),
            'date' => array(
                    'title' => '日期',
                    'query_type' => 'eq',
                    'control_type' => 'datetime',
                    'value' => 'created_time',
            ),
            'time' => array(
            	       'title' => '时段',
                   'query_type' => 'eq',
                   'control_type' => 'select',
                   'value' => array(
            	           '3' => '全天',
                       '1' => '上午',
                       '2' => '下午',
                   ),
            )
    );	
    public function before_index() {
        $this->table = array(
                'toolbar'   => false,//是否显示表格上方的工具栏,添加、导入等
                'searchbar' => true, //是否显示搜索栏
                'checkbox'  => true, //是否显示表格中的浮选款
                'status'    => false,
                'toolbar_tr'=> false,
                'statusbar' => false
        );
        
        //分配线路
        $D = D('Distribution', 'Logic');
        $line = $D->format_line();
        $this->query['line']['value'] = $line;
    }
    //显示数据列表
    protected function lists($template='') {
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
        //$map = $this->search($this->query);//获取界面上传过来的查询条件

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
            'Location'
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
        
        //$data = $M->select();//真正的数据查询在这里生效
        //echo $M->getLastSql();die;
        //$count  = $M2->page()->limit()->count();//获取查询总数
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $Dis = D('Distribution', 'Logic');
        if (IS_POST) {
            $search_info = $Dis->search_test(I('post.query'));
            //$search_info = $Dis->order_lists(I('post.query'));
        } else {
            $search_info['list'] = array();
        }
        //获取搜索结果
        //$search_info = $Dis->search();
        //dump($search_info['list']);exit();
        $this->assign('data', $search_info['list']);        
        $maps = $this->condition;
        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);
    }
}