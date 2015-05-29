<?php
namespace Wms\Controller;
use Think\Controller;
class StockOutController extends CommonController {
    
    protected $filter = array(
                    'type'=>array(
                        '1'=>'普通订单',
                        '2'=>'采购退货',
                        '3'=>'库内样品出库',
                        ),
                    'line_name'=>array(
                        '1'=>'海淀黄庄北',
                        '2'=>'知春路锦秋国际'
                        ),
                    'status'=>array(
                        '1'=>'待生产',
                        '2'=>'已出库'
                        ),
                   'process_type'=>array(
                        '1'=>'正常单',
                        '2'=>'取消单'
                        ),
                    'refused_type'=>array(
                        '1'=>'空',
                        '2'=>'缺货'
                        ),
                    'op_time'=>array(
                        '0'=>'全天',
                        '1'=>'上午',
                        '2'=>'下午'
                        )
                    );

    protected $columns = array (  
        'code' => '出库单号',
        'type' => '出库单类型',
        'wave_code' => '波次号',
        'packing_code' => '装车号',
        'total_qty' => '总件数',
        'line_name' => '线路片区',
        'status' => '出库单状态',
        'process_type' => '处理类型',
        'refused_type' => '拒绝标识',
        'delivery_time' => '送货时间',
        'order_time' => '下单时间'
	);
	protected $query = array (   
		 'stock_bill_out.id' =>    array (     
			'title' => '货品号',     
			'query_type' => 'in',     
			'control_type' => 'text',     
			'value' => '',   
		),  
		
		'stock_bill_out.type' =>    array (     
			'title' => '出库单类型',     
			'query_type' => 'eq',     
			'control_type' => 'select',     
			'value' => array(
                        '1'=>'普通订单',
                        '2'=>'采购退货',
                        '3'=>'库内样品出库',
                        ),    
		),
		'stock_bill_out.refused_type' =>    array (     
			'title' => '拒绝标识',     
			'query_type' => 'eq',     
			'control_type' => 'select',     
			'value' => array(
                        '1'=>'空',
                        '2'=>'缺货'
                        ),   
		),   
		
		'stock_bill_out.line_name' => array (     
			'title' => '路线片区',     
			'query_type' => 'eq',     
			'control_type' => 'getField',     
			'value' => 'StockOut.line_name,line_name ln' 
			),
		'stock_bill_out.process_type' => array (     
			'title' => '处理类型',     
			'query_type' => 'eq',     
			'control_type' => 'select',     
			'value' => array(
                        '1'=>'正常单',
                        '2'=>'取消单'
                        ),   
		),
        
	);

    protected function before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
            'status'    => false, 
            'toolbar_tr'=> true,
            'statusbar' => true
        );
        $this->toolbar_tr =array(
                    'view'=>array('name'=>'view', 'show' => !isset($auth['view']),'new'=>'true'),
                    'edit'=>array('name'=>'edit', 'show' => !isset($auth['edit']),'new'=>'true'),
                    'delete'=>array('name'=>'delete' ,'icon'=>'remove', 'show' => !isset($auth['audit']),'new'=>'true','domain'=>"1"),
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => ! isset($auth['add']),'new'=>'true'),
            );
        $this->search_addon = true;
    }

    protected function before_lists(){
        
        $pill = array(
			'status'=> array(
				'1'=>array('value'=>'1','title'=>'待生产','class'=>'warning'),
				'2'=>array('value'=>'2','title'=>'已出库','class'=>'primary'),
			)
		);
		$stock_out = M('stock_bill_out');
		$map['is_deleted'] = 0;
		$res = $stock_out->field('status,count(status) as qty')->where($map)->group('status')->select();
		foreach ($res as $val) {
            if(array_key_exists($val['status'], $pill['status'])) {
			    $pill['status'][$val['status']]['count'] = $val['qty'];
                $pill['status']['total'] += $val['qty'];
		    }
        }

        foreach($pill['status'] as $k => $val){
			if(empty($val['count'])){
				$pill['status'][$k]['count'] = 0;
			}
		}

		$this->pill = $pill;

    }
   
    protected function after_lists(&$data) {
        foreach($data as &$val) {
            $val['delivery_time'] = date('Y-m-d', $val['op_date']) . $this->filter['op_time'][$val['op_time']];
        }
    }

    protected function before_add(&$M) {
        $post = I('post.');
        $n = count($post['pros']['pro_code']);
		if($n < 2) {
            $this->msgReturn(0,'请至少填写一个货品');
		}
        $data = $M->data();         
        $M->code = get_sn('out',$post['wh_id']);
        $M->status = 1;
        $M->process_type = 1;
        $M->refused_type = 1;
    }
    
    protected function before_save() {
        if(ACTION_NAME == 'edit') {
            $post = I('post.');
            //dump($post); 
        }
    }

    protected function after_save($id) {
        $post = I('pros');
        $stock_bill_detail = M('stock_bill_out_detail'); 
        $column['pid'] = $id;
        $column['status'] = 1;
        
        $n = count($post['pro_code']);
		if($n < 2) {
			$this->msgReturn(1,'','',U('view','id='.$id));
		}
        
        for($i = $n-1;$i > 0;$i--) {
            if(empty($post['pro_code'][$i])) {
				continue;
			}
            $column['pro_code'] = $post['pro_code'][$i];
            $column['pro_name'] = $post['pro_name'][$i];
            $column['pro_attrs'] = $post['pro_attrs'][$i];
            $column['order_qty'] = $post['order_qty'][$i];
            $column['delivery_qty'] = ($post['delivery_qty'][$i])? $post['delivery_qty'][$i] : $post['order_qty'][$i];
            
            $data = $stock_bill_detail->create($column);
            if(! empty($post['id'][$i])) {
                $map['id'] = $post['id'][$i];
                $res = $stock_bill_detail->where($map)->save($data);  
            }else {
                $stock_bill_detail->add($data);
            }
        }
        unset($map);
		$field="sum(order_qty) as total_qty";
		$map['pid'] = $id;
		$data = $stock_bill_detail->field($field)->where($map)->group('pid')->find();
		$where['id'] = $id;
		$stock_bill_out = M('stock_bill_out');
		$stock_bill_out->where($where)->save($data);

		$this->msgReturn(1,'','',U('view','id='.$id));
        
    }

    protected function before_edit(&$data) {
       $stock_out = M('stock_bill_out');
       $stock_detail = M('stock_bill_out_detail');
       $warehouse = M('warehouse');

       $map['pid'] = $data['id'];
       $pros = $stock_detail->where($map)->select();
       
       foreach ($pros as $key => $val) {
            $pros[$key]['pro_names'] = '['.$val['pro_code'] .'] '. $val['pro_name'] .'（'. $val['pro_attrs'].'）';
	   }

       $data['wh_name'] = $warehouse->where($data['wh_id'])->getField('name');
       $data['delivery_time'] = date('Y-m-d', $data['op_date']) . $this->filter['op_time'][$data['op_time']];

       $filter = array('status' => array('1'=>'待生产','2'=>'已出库'),
                       'type' => array('1'=>'普通订单','2'=>'采购退货','3'=>'库内样品出库'),
                       'process_type' => array('1'=>'正常单','2'=>'取消单'),
                );
       $this->filter_list($data, 0, $filter);

       $filter = array('status'=>array('1'=>'待出库', '2'=>'已出库'));
       $this->filter_list($pros, 0, $filter);
       
       $this->pros = $pros;

    }

    public function stockOut() {
        $ids = I('ids');
        $ids_arr = explode(",",$ids);
        $stock_out = M('stock_bill_out');
        $stock_detail = M('stock_bill_out_detail');
        
        
        //$flag标识判断出库单是否出库成功
        $state = 'succ';
        
        foreach($ids_arr as $id) {
           
            //$flag标识判断此次出库是否成功
            $flag = 'succ';
            
            //查找出库单信息
            $map['id'] = $id;
            $stock_info = $stock_out->field('wh_id,code,total_qty,status')->where($map)->find();
            //如果出库单的总数为0，不能出库
            if(empty($stock_info['total_qty'])) {
                $state = 'failed';
                break; 
            }
            //如果是状态是已出库的出库单，则不能再次出库 
            if($stock_info['status'] == 2) {
                $state = 'failed';
                break;
            }

            //查找出库单明细
            unset($map);
            $map['pid'] = $id;
            $detail_info = $stock_detail->where($map)->field('pro_code,delivery_qty')->select();
            
            $data['wh_id'] = $stock_info['wh_id'];
            $data['refer_code'] = $stock_info['code'];
            foreach($detail_info as $val) {
                $data['pro_code'] = $val['pro_code'];
                $data['pro_qty'] = $val['delivery_qty'];
                
                $check_stock = A('Stock', 'Logic')->outStockBySkuFIFOCheck($data);
                if($check_stock['status'] == 0) {
                    $flag = 'failed';
                    $state = 'failed';
                    break;
                }
            }
            if($flag == 'succ') {
                //销库存
                foreach($detail_info as $val) {
                    $data['pro_code'] = $val['pro_code'];
                    $data['pro_qty'] = $val['delivery_qty'];
                    $res = A('Stock', 'Logic')->outStockBySkuFIFO($data);
                    /*if($res['status'] == 0) {
                        $flag = 'failed';
                        $state = 'failed';
                    }*/
                }
            }

            if($flag == 'failed') {
                $list['refused_type'] = 2;
            }else {
                $list['status'] = 2;
                $list['refused_type'] = 1;
            }
            
            unset($map);
            $map['id'] = $id;
            $stock_out->where($map)->save($list);
        }

        if($state == 'failed') {
            $return['status'] = 0;
            $return['msg'] = '出库失败';
            $this->ajaxReturn($return);
        }else {
            $return['status'] = 1;
            $return['msg'] = '出库成功';
            $this->ajaxReturn($return); 
        }
        
    }

    protected function after_search(&$map) {
        if(! empty($map['stock_bill_out.id'])) {
            $condition['pro_code'] = $map['stock_bill_out.id'][1];
            $ids = M('stock_bill_out_detail')->field('pid')->where($condition)->select();
            $arr = array_column($ids, 'pid');
            $str = implode(",", $arr);
            $map['stock_bill_out.id'][1] = $str;
        }
    }

}
