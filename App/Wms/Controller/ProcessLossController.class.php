<?php
/**
 * Created by PhpStorm.
 * User: san77
 * Date: 15/8/6
 * Time: 上午11:08
 */
namespace Wms\Controller;

use Think\Controller;

class ProcessLossController extends CommonController
{
    protected $columns = array (
        'id' => '',
        'p_pro_code' => '父SKU',
        'c_pro_code' => '子SKU',
        'ratio' => '比例',
        'task' => '原损耗数',
        'c_pro_num' => '原料加工数',
        'p_pro_num' => '成品加工数',
        'aa' => '损耗率',
        'over_taskc' => '原料损耗成本',
        'over_taskd' => '成本损耗成本',
    );
    protected $query   = array (
        'erp_process.created_time' => array (
            'title' => '加工时间',
            'query_type' => 'between',
            'control_type' => 'datetime',
            'value' => '',
        )
    );
    //设置列表页选项
    protected function before_index() {
        $this->table = array(
            'searchbar' => true,
            'checkbox'  => true,
            'status'    => false,
        );
    }

    public function _before_index() {
        $this->table = array(
            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
            'searchbar' => true, //是否显示搜索栏
            'checkbox'  => true, //是否显示表格中的浮选款
        );
    }

    /**
     * 列表字段处理
     * @param unknown $data
     */
    public function after_lists(&$data) {
        $pro_code = array();
        //格式化状态
        foreach ($data as $key => &$value) {
            $pro_code[] = $value['c_pro_code'];
            $value['c_pro_num'] = sprintf('%.2f',$value['p_pro_num']*$value['ratio']);
        }

        $code = implode(',', $pro_code);

        $model = M('stock');
        $where['is_deleted'] = 0;

        $model->select();
        console($pro_code);
    }

    public function after_search(&$map) {
        if (array_key_exists('erp_process.p_pro_code', $map)) {
            $where['p_pro_code'] = $map['erp_process.p_pro_code'][1];
            $processDetail = M('erp_process_detail')->where($where)->select();
            if (empty($processDetail)) {
                unset($map['erp_process.p_pro_code']);
                return;
            }
            $ids = array();
            foreach ($processDetail as $value) {
                $ids[] = $value['pid'];
            }
            unset($map['erp_process.p_pro_code']);
            if (!empty($ids)) {
                $map['erp_process.id'] = array('in', $ids);
            } else {
                $map['erp_process.id'] = array('eq', null);
            }
        }
        if (array_key_exists('erp_process.created_user', $map)) {
            $where['nickname'] = $map['erp_process.created_user'][1];
            $result = M('user')->where($where)->select();
            if (empty($result)) {
                unset($map['erp_process.created_user']);
            }
            $uids = array();
            foreach ($result as $value) {
                $uids[] = $value['id'];
            }
            unset($map['erp_process.created_user']);
            if (!empty($uids)) {
                $map['erp_process.created_user'] = array('in', $uids);
            } else {
                $map['erp_process.created_user'] = array('eq', null);
            }
        }
    }
}