<?php
namespace Wms\Controller;
use Think\Controller;
class DistributionController extends CommonController {
    
    protected $filter = array(
    	    'status' => array(
    	        '1' => '未发运',
    	        '2' => '已发运',
        )
    );
    protected $columns = array (
            //'id' => '',
            'dist_code' => '配送单号',
            'total_price' => '应收总金额',
            'order_count' => '总单数',
            'line_count' => '总行数',
            'sku_count' => '总件数',
            'created_user' => '创建者',
            'created_time' => '创建时间',
            'status' => '状态',
            //'remarks' => '备注',
            //'deal_price' => '实收总金额',
            //'site_src' => '1大厨网，2 大果网',
            //'line_id' => '线路ID',
            //'deliver_time' => '配送时间',
            //'total_distance' => '预估总里程数',
            //'begin_time' => '配送开始时间',
            //'end_time' => '配送结束时间',
            //'updated_id' => '更新人id',
            //'updated_time' => '更新时间',
            //'is_deleted' => '0未删除 >0已删除',
            //'is_printed' => '是否打印：1已打印，0未打印',
            //'city_id' => '城市ID',
    );
    protected $query   = array (
            'stock_wave_distribution.company_id' => array(
                    'title' => '所属系统',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'Company.id,name',
            ),
            'stock_wave_distribution.status' => array(
                    'title' => '状态',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => array(
                        '1' => '未发运',
                        '2' => '已发运',
                    ),
            ),
            'stock_wave_distribution.order_id' => array(
                    'title' => '订单ID',
                    'query_type' => 'eq',
                    'control_type' => 'text',
                    'value' => '',
            ),
            'stock_wave_distribution.wh_id' => array(
                    'title' => '所属仓库',
                    'query_type' => 'eq',
                    'control_type' => 'getField',
                    'value' => 'warehouse.id,name',
            ),
            'stock_wave_distribution.created_time' => array(
                    'title' => '创建时间',
                    'query_type' => 'between',
                    'control_type' => 'datetime',
                    'value' => 'created_time',
            ),
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
                'view'=>array('name'=>'view', 'show' => true/*isset($this->auth['view'])*/,'new'=>'true'), //查看按钮
                'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true'), //编辑按钮
        );
        $this->status =array(
                array(
                        array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
                        array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
                ),
        );
        /*$this->toolbar = array(
        	         array('name'=>'add', 'show' => trueisset($auth['view']),'new'=>'true', 'link' => 'DistDetail/index'),
        );*/
    }
    
    public function index() {
        $this->before_index();
        $this->lists('index');
    }

    
    /**
     * 生成配送单
     */
    public function add() {
        if (!IS_POST) {
            $this->msgReturn(false, '未知错误');
        }
        $post = I('post.');
        if (empty($post)) {
            $this->msgReturn(false, '参数错误');
        }
        
        $D = D('Distribution', 'Logic');
        $result = $D->add_distributioin($post);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
       
        $this->msgReturn(true, '已创建配送单', '', '', U('index'));
    }
    
    /**
     * 配送单导出
     */
    public function exportdis() {
        
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=distribution.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $get = I('get.id');
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单id
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        $order = D('Order', 'Logic');
        $result = $order->getOrderInfoByOrderIdArr($order_ids);
        if (empty($result)) {
            $this->msgReturn(false, '获取订单失败');
        }
        $string = '';
        foreach ($result as $value) {
            $pay_type = '';
            $pay_status = '';
            switch ($value['pay_type']) {
            	   case 0:
            	       $pay_type = '货到付款';
            	       break;
            	   case 1:
            	       $pay_type = '微信支付';
            	       break;
            }
            switch ($value['pay_status']) {
            	   case -1:
            	       $pay_status = '支付失败';
            	       break;
            	   case 0:
            	       $pay_status = '未支付';
            	       break;
            	   case 1:
            	       $pay_status = '已支付';
            	       break;
            }
            $string .= '订单ID,' . $value['id'] . ',订单编号,' . $value['order_number'] . ', ,' . ',线路,' . $value['line'] . "\n";
            $string .= $value['city_name'] . ',' . $value['address'] . "\n";
            $string .= $value['shop_name'] . ',' . $value['realname'] . ',' . 'tel:' . $value['mobile'] . ',下单时间,' . $value['created_time'] . "\n";
            $string .= '销售,' . $value['bd']['name'] . ',销售电话,' . 'tel:' . $value['bd']['mobile'] . ',配送时间,' . $value['deliver_date'] . $value['deliver_time'] . "\n";
            $string .= '------------------------------------------------------------------------------' . "\n";
            $string .= '货号,产品名称,订货数量,订货单位,结算单价,结算单位,实收数量,实收金额' . "\n";
            foreach ($value['detail'] as $val) {
                $string .= $val['product_id'] . ',' . $value['name'] . ',' . $value['quantity'] . ', ,' . $value['price'] . '元,' . $value['unit_id'] . ', , ,' . "\n";
            }
            $string .= "\n\n\n\n\n\n\n\n\n\n";
            $string .= '订单备注' . "\n";
            $string .= '------------------------------------------------------------------------------' . "\n";
            $string .= '订单总价,' . $value['total_price'] . ', , , , , , , ,实收总金额' . "\n";
            $string .= '活动优惠,-0' . "\n";
            $string .= '微信支付,-0' . "\n";
            $string .= '运费,+0' . "\n";
            $string .= '\n';
            $string .= '应付总价,' . $value['total_price'] . "\n";
            $string .= '支付状态,' . $pay_status . ',支付方式,' . $pay_type . "\n";
            $string .= '客户签字' . "\n";
            $string .= "\n";
            $string .= '客户(白联),存根(粉联)' . "\n";
            $string .= ' , , , , , , ,售后电话,' . $value['am']['mobile'] . "\n";
        }
        echo iconv("UTF-8","GB2312",$string);
    }
    /**
     * 配送单详情
     */
    public function view() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.id');
        if (empty($get)) {
            $this->msgReturn(false, '参数有误');
        }
        
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单id
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        
        //拉取订单
        $Order = D('Order', 'Logic');
        $result = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
        $result = $result['list'];
        $data = array();
        $data['dist_code'] = $dis['dist_code']; //编号
        $data['is_printed'] = $dis['is_printed']; //是否打印
        //创建者
        $user = M('user');
        $map['id'] = $dis['created_user'];
        $user_info = $user->field('nickname')->where($map)->find();
        $data['creator'] = $user_info['nickname'];
        $data['created_time'] = $dis['created_time']; //创建时间
        $data['order_count'] = $dis['order_count']; //总单数
        $data['sku_count'] = $dis['sku_count']; //sku总数
        $data['total_price'] = $dis['total_price']; //总价格
        $data['line_count'] = $dis['line_count']; //总行数
        $this->assign('data', $data);
        $this->assign('orderList', $result);
        $this->display();
    }
    
    /**
     * 配送单打印
     */
    public function printpage() {
        if (!IS_GET) {
            $this->msgReturn(false, '未知错误');
        }
        $get = I('get.id');
        if (empty($get)) {
            $this->msgReturn(false, '参数有误');
        }
        
        //获取配送单信息
        $M = M('stock_wave_distribution');
        $map['id'] = $get;
        $dis = $M->where($map)->find();
        unset($map);
        
        //获取订单id
        $D = D('Distribution', 'Logic');
        $order_ids = $D->get_order_ids_by_dis_id($get);
        
        //拉取订单
        $Order = D('Order', 'Logic');
        $result = $Order->getOrderInfoByOrderIdArr($order_ids);
        if ($result['status'] == false) {
            $this->msgReturn(false, $result['msg']);
        }
        $result = $result['list'];
        $items = array();
        $items['dist_code'] = $dis['dist_code'];
        $items['line_name'] = $D->format_line($dis['line_id']);
        
        $user_ids = array();
        foreach ($result as $value) {
            $user_ids[$value['user_id']] = null;
        }
        $items['user_count'] = count($user_ids); //总客户数量
        $items['orders_length'] = $dis['order_count'];  //总订单数
        $items['sku_count'] = $dis['sku_count']; //sku总数
        //获取仓库名称
        $ware = M('warehouse');
        $map['id'] = $dis['wh_id'];
        $ware_info = $ware->field('name')->where($map)->find();
        $items['warehouse_name'] = $ware_info['name'];
        $items['deliver_date'] = $dis['deliver_date']; //发车时间
        $items['deliver_time'] = $dis['deliver_time']; //时段
        $items['orders'] = $result; //订单列表
        $items['barcode'] = 'http://api.pda.dachuwang.com/barcode/get?text=PD1506080001'; //条码
        
        $merge = array();
        foreach ($result as $val) {
            $merge = array_merge($merge, $val['detail']);
        }
        foreach ($merge as $key=>$v) {
            if (!isset($merge[$v['product_id']])) {
                $merge[$v['product_id']] = $v;
            } else {
                $merge[$v['product_id']]['quantity'] += $v['quantity'];
            }
            unset($merge[$key]);
        }
        $items['sku_list'] = $merge;
        unset($map);
        //更新打印状态
        if ($dis['is_printed'] <= 0) {
            $data['is_printed'] = 1;
            $map['id'] = $get;
            if ($M->create($data)) {
                $M->where($map)->save();
            }
        }
        $this->assign('attrs', $items);
        $this->display();
    }
}