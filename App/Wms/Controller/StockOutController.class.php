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
        'op_date' => '送货时间',
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
        
	/*	'stock_bill_in.created_time' => array (     
			'title' => '送货时间',     
			'query_type' => 'between',     
			'control_type' => 'datetime',     
			'value' => '',   
		), */
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
            array('name'=>'view','link'=>'view','icon'=>'zoom-in','title'=>'查看', 'show' => true,'new'=>'true'), 
        );
        $this->search_addon = true;
    }

    public function before_lists(){
    	
        $pill = array(
			'status'=> array(
				'1'=>array('value'=>'1','title'=>'待生产','class'=>'warning'),
				'2'=>array('value'=>'2','title'=>'已出库','class'=>'primary'),
			)
		);
		$stock_out = M('stock_bill_out');
		$map['is_deleted'] = 0;
		$res = $stock_out->field('status,count(status) as qty')->where($map)->group('status')->select();
		foreach ($res as $key => $val) {
            //if(array_key_exists($key, $pill)){
			    $pill['status'][$val['status']]['count'] = $val['qty'];
		    //}
        }
		$this->pill = $pill;

    }
    
    public function before_edit(&$data) {
       $stock_out = M('stock_bill_out');
       $stock_detail = M('stock_bill_out_detail');
       $warehouse = M('warehouse');

       $map['pid'] = $data['id'];
       $pros = $stock_detail->where($map)->select();
        
       $data['wh_name'] = $warehouse->where($data['wh_id'])->getField('name');

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
        
        
        //$flag标识判断所有的出库是否成功
        $state = 'succ';
        
        foreach($ids_arr as $id) {
           
            //$flag标识判断此次出库是否成功
            $flag = 'succ';
            
            //查找出库单信息
            $map['id'] = $id;
            $stock_info = $stock_out->field('wh_id,code')->where($map)->find();
            //查找出库单明细
            unset($map);
            $map['pid'] = $id;
            //只查没有出库成功的货品
            $map['status'] = 1;
            $detail_info = $stock_detail->where($map)->field('pro_code,order_qty')->select();
            
            $data['wh_id'] = $stock_info['wh_id'];
            $data['refer_code'] = $stock_info['code'];
            foreach($detail_info as $val) {
                $data['pro_code'] = $val['pro_code'];
                $data['pro_qty'] = $val['order_qty'];
                
                $res = A('Stock', 'Logic')->outStockBySkuFIFO($data);
                if($res['status'] == 1) {
                    $status = 2;      
                }else {
                    $status = 1;
                    $flag = 'failed';
                    $state = 'failed';
                }
                $list['status'] = $status;
                $condition['pid'] = $id;
                $condition['pro_code'] = $val['pro_code'];
                $stock_detail->where($condition)->save($list);
            }
           
            if($flag == 'failed') {
                $refused['refused_type'] = 2;
            }else {
                $refused['status'] = 2;
                $refused['refused_type'] = 1;
            }
            
            unset($map);
            $map['id'] = $id;
            $stock_out->where($map)->save($refused);
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
