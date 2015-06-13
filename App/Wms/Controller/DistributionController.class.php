<?php
namespace Wms\Controller;
use Think\Controller;
class DistributionController extends CommonController {
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
            'order_distribution.company_id' => array(
                    'title' => '所属系统',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => 'warehouse.id,name',
            ),
            'order_distribution.city_id' => array(
                    'title' => '所属城市',
                    'query_type' => 'eq',
                    'control_type' => 'select',
                    'value' => 'city_id',
            ),
            'order_distribution.order_id' => array(
                    'title' => '订单ID',
                    'query_type' => 'eq',
                    'control_type' => 'text',
                    'value' => '',
            ),
            'order_distribution.rmarks' => array(
                    'title' => '关键词',
                    'query_type' => 'like',
                    'control_type' => 'text',
                    'value' => 'rmarks',
            ),
            'order_distribution.created_time' => array(
                    'title' => '创建时间',
                    'query_type' => 'between',
                    'control_type' => 'datetime',
                    'value' => 'created_time',
            ),
    );
    
    public function _before_index() {
        $this->table = array(
                'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
                'searchbar' => true, //是否显示搜索栏
                'checkbox'  => true, //是否显示表格中的浮选款
                'status'    => false,
                'toolbar_tr'=> true,
                'statusbar' => true
        );
        $this->toolbar_tr =array(
                'view'=>array('name'=>'view', 'show' => isset($this->auth['view']),'new'=>'true'),
                'edit'=>array('name'=>'edit', 'show' => isset($this->auth['edit']),'new'=>'true','domain'=>"draft,confirm,reject"),
                'pass'=>array('name'=>'pass' ,'show' => isset($this->auth['pass']),'new'=>'true','domain'=>"draft,confirm"),
                'reject'=>array('name'=>'reject' ,'show' => isset($this->auth['reject']),'new'=>'true','domain'=>"draft,confirm"),
                'close'=>array('name'=>'close' ,'show' => isset($this->auth['close']),'new'=>'true','domain'=>"draft,confirm,pass,reject")
        );
        $this->status =array(
                array(
                        array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
                        array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
                ),
        );
        /*$this->toolbar = array(
        	        array(''),
        );*/
    }
    
    
}