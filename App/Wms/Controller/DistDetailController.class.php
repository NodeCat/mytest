<?php
namespace Wms\Controller;
use Think\Controller;
class DistDetailController extends CommonController {
	protected $columns = array (
            'order_id' => '订单ID',
	        'code' => '出库单号',
            'line' => '订单线路',
            'address' => '送货地址',
            //'deliver_date' => '送货时间',
            'user_id' => '客户id',
            'name' => '货品名称',
            'attrs' => '规格',
            'quantity' => '数量',
    );
    protected $query   = array (
            'stype' => array(
                    'title' => '出库单类型',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'stock_bill_out_type.id,name',
            ),
            'type' => array(
                    'title' => '订单类型',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
                            '1' => '普通订单',
                            '2' => '冻品订单',
                            '3' => '水果爆款订单',
                            '4' => '水果订单',
                    ),
            ),
            'line' => array(
                    'title' => '线路',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => '',
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
        $line = $D->format_line(-1, session('user.wh_id'));
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

        $p              = I("p",1);
        $page_size      = C('PAGE_SIZE');
        
        if(!empty($map)) {
            $M->where($map);//用界面上的查询条件覆盖scope中定义的
        }
        $this->before($M,'lists');//列表显示前的业务处理

        $M2 = clone $M;//深度拷贝，M2用来统计数量, M 用来select数据。
        $this->after($data,'lists');//查询后的业务处理，传入了结果集
        $this->filter_list($data);//对结果集进行过滤转换
        $Dis = D('Distribution', 'Logic');
        if (IS_POST) {
            $search_info = $Dis->order_lists(I('post.query'));
        } else {
            //默认为空
            $search_info['list'] = array();
        }
        //获取搜索结果
        if (isset($search_info['status']) && $search_info['status'] == false) {
            if (IS_AJAX) {
                $this->msgReturn(false, $search_info['msg']);
            }
        }
        $this->assign('data', $search_info['list']); 
        $maps = $this->condition;
        $template= IS_AJAX ? 'list':'index';
        $this->page($count,$maps,$template);
    }
}