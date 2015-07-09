<?php
namespace Wms\Controller;
use Think\Controller;
class ProcessRatioController extends CommonController {
    //列表显示定义
	protected $columns = array (
	      'company_id' => '所属系统',
          'p_pro_code' => '父SKU',
	      'p_pro_name' => '父产品名称',
	      'p_pro_norms' => '父产品规格',
          'c_pro_code' => '子SKU',
	      'c_pro_name' => '子产品名称',
	      'c_pro_norms' => '子产品规格',
          'ratio' => '比例',
    );
	//搜索字段定义
	protected $query   = array (
	       'erp_process_sku_relation.p_pro_code' => array(
		       'title' => '父SKU',
	           'query_type' => 'eq',
	           'control_type' => 'text',
	           'value' => 'p_pro_code',
	        ),
	        'erp_process_sku_relation.c_pro_code' => array(
	                'title' => '子SKU',
	                'query_type' => 'eq',
	                'control_type' => 'text',
	                'value' => 'c_pro_code',
	        ),
	        'erp_process_sku_relation.company_id' => array(
	                'title' => '所属系统',
	                'query_type' => 'eq',
	                'control_type' => 'getField',
	                'value' => 'company.id,name',
	        ),
    );
	
	/**
	 * 定义页面格局
	 */
	protected function before_index() {
	    $this->table = array(
	            'toolbar'   => true,//是否显示表格上方的工具栏,添加、导入等
	            'searchbar' => true, //是否显示搜索栏
	            'checkbox'  => true, //是否显示表格中的浮选款
	            'status'    => false, //是否显示列表栏状态
	            'toolbar_tr'=> true, //是否显示列表栏操作
	            'statusbar' => false, //是否显示状态栏
	    );
	    $this->toolbar =array(
	            array('name'=>'add', 'show' => true,'new'=>'true'),
	            array('name'=>'edit', 'show' => false,'new'=>'false'),
	            array('name'=>'delete' ,'show' => false,'new'=>'false'),
	            array('name'=>'import' ,'show' => false,'new'=>'false'),
	            array('name'=>'export' ,'show' => false,'new'=>'false'),
	            array('name'=>'print' ,'show' => false,'new'=>'false'),
	            array('name'=>'setting' ,'show' => false,'new'=>'false'),
	    );
	    $this->status =array(
	            array(
	                array('name'=>'forbid', 'title'=>'禁用', 'show' => isset($this->auth['forbid'])),
	                array('name'=>'resume', 'title'=>'启用', 'show' => isset($this->auth['resume']))
	            ),
	    );
	    $this->toolbar_tr =array(
	            array('name'=>'view','link'=>'view','title'=>'查看', 'show' => true,'new'=>'true'),
	            array('name'=>'edit','link'=>'edit','title'=>'编辑', 'show' => true,'new'=>'false'),
	            array('name'=>'delete','link'=>'delete','title'=>'删除', 'show'=>true,'new'=>'true','target'=>'_blank'),
	    );
	}
	
	/**
	 * 查看详情
	 */
	public function before_edit(&$data) {
	    //获取所有比例关系
	    $p_code = $data['p_pro_code'];
	    $M = M('erp_process_sku_relation');
	    $map['p_pro_code'] = $p_code;
	    $ratio = $M->where($map)->select();
	    
	    unset($map);
	    $map['id'] = $data['created_user'];
	    $user = M('user');
	    $name = $user->where($map)->find();
	    $data['created_user'] = $name['nickname'];
	    $code = array();
	    $code[] = $data['p_pro_code'];
	    foreach ($ratio as $val) {
	        $code[] = $val['c_pro_code'];
	    }
	    //调用Pms接口查询sku信息
	    $pms = D('Pms', 'Logic');
	    $sku_info = $pms->get_SKU_field_by_pro_codes($code);
	    foreach ($sku_info as $key => $value) {
	        if ($key == $data['p_pro_code']) {
	            $data['p_name'] = $value['name'];
	            $data['p_attrs'] = $value['pro_attrs_str'];
	            $data['uom_name'] = $value['uom_name'];
	        } else {
	            //查询所有子sku
	            foreach ($ratio as $v) {
	                if ($key == $v['c_pro_code']) {
	                    $data['c_pros'][] = array(
	                    	'c_code' => $v['c_pro_code'],
	                        'c_name' => $value['name'],
	                        'c_attrs' => $value['pro_attrs_str'],
	                        'c_ratio' => $v['ratio'],
	                        'c_uom_name' => $value['uom_name'],
	                    );
	                }
	            }
	        }
	    }
	}
	
	/**
	 * 添加比例关系（重写父类add方法）
	 */
	public function add() {
	    if (IS_POST) {
        	    //数据处理
        	    $post = I('post.');
        	    if (empty($post['company_id']) || empty($post['p_pro_code_hidden'])) {
        	        $this->msgReturn(0, '必须填写所属系统和父SKU');
        	    }
        	     
        	    if (count($post['pros']) < 2) {
        	        $this->msgReturn(0, '请添加一个子SKU');
        	    }
        	    
        	    //去除隐藏域
        	    unset($post['pros'][0]);

        	    //叠加同类子SKU
        	    $new_pros = array();
        	    foreach ($post['pros'] as $key=>$v) {
        	        if (!isset($new_pros[$v['pro_code']])) {
        	            $new_pros[$v['pro_code']] = $v;
        	        } else {
        	            $new_pros[$v['pro_code']]['pro_qty'] += $v['pro_qty'];
        	        }
        	    }
        	    $post['pros'] = $new_pros;
        	    $M = D('ProcessRatio');
        	    //创建物料清单
        	    $info = array();
        	    foreach ($post['pros'] as $key => $value) {
        	        if (empty($value['pro_code'])) {
        	            $this->msgReturn(0, '请选择子SKU');
        	            return;
        	        }
        	        if ($value['pro_qty'] < 1) {
        	            $this->msgReturn(0, '数量不可小于1');
        	            return;
        	        }
        	        
        	        //子SKU父SKU不可相同
        	        if ($value['pro_code'] == $post['p_pro_code_hidden']) {
        	            $this->msgReturn(0, '创建规则错误');
        	        }
        	        $map['p_pro_code'] = $post['p_pro_code_hidden'];
        	        $map['c_pro_code'] = $value['pro_code'];
        	        $map['ratio'] = $value['pro_qty'];
        	        $map['company_id'] = $post['company_id'];
        	        $affected = $M->where($map)->find();
        	        if (!empty($affected)) {
        	            //此比例关系已存在
        	            continue;
        	        }
        	        $info[$key]['p_pro_code'] = $post['p_pro_code_hidden'];
        	        $info[$key]['c_pro_code'] = $value['pro_code'];
        	        $info[$key]['ratio'] = $value['pro_qty'];
        	        $info[$key]['company_id'] = $post['company_id'];
        	        $info[$key]['created_user'] = session()['user']['uid'];
        	        $info[$key]['updated_user'] = session()['user']['uid'];
        	        $info[$key]['created_time'] = get_time();
        	        $info[$key]['updated_time'] = get_time();
        	    }
        	    //批量写入
        	    
        	    foreach ($info as $val) {
        	        if ($M->create($val)) {
        	            $M->add();
        	        }
	       }
	       $this->msgReturn(true, '', '', U('index'));
	    } else {
	        $this->display();
	    }
	}
	/**
	 * 列表信息处理
	 */
	protected function after_lists(&$data) {
	    if (empty($data)) {
	        return;
	    }
	    
	    $company = M('company');
	    $company_info = $company->select();
	    $pms = D('Pms', 'Logic');
	    $code = array();
	    //获取所有sku编号
	    foreach ($data as $value) {
            $code[] = $value['p_pro_code'];
            $code[] = $value['c_pro_code'];
	    }
	    //调用PMS接口获取产品信息
	    $code_info = $pms->get_SKU_field_by_pro_codes($code, count($code));
	    foreach ($data as $key => &$val) {
	        foreach ($code_info as $k => $v) {
	            if ($val['p_pro_code'] == $k) {
	                $val['p_pro_name'] = $v['name'];
	                $val['p_pro_norms'] = $v['pro_attrs_str'];
	            } elseif($val['c_pro_code'] == $k) {
	                $val['c_pro_name'] = $v['name'];
	                $val['c_pro_norms'] = $v['pro_attrs_str'];
	            }
	        }
	        foreach ($company_info as $v) {
	            if ($val['company_id'] == $v['id']) {
	                $val['company_id'] = $v['name'];
	            }
	        }
	        $new_data[$key] = $val['p_pro_code'];
	    }
	    
	    asort($new_data);
	    //排序
	    $arr = array();
	    foreach ($new_data as $kk => $v) {
	        $arr[] = $data[$kk];
	    }
	    $data = $arr;
	}
	
	/**
	 * 编辑处理 （非数据处理 比例关系是否符合编辑条件处理）
	 */
	public function before_save($M) {
	    if (empty($M->p_pro_code)) {
	        $this->msgReturn(false, '请输入父SKU编号');
	    }
	    if (empty($M->c_pro_code)) {
	        $this->msgReturn(false, '请输入子SKU编号');
	    }
	    if (empty($M->company_id)) {
	        $this->msgReturn(false, '请选择所属系统');
	    }
	    if (empty($M->ratio)) {
	        $this->msgReturn(false, '请输入比例关系');
	    }
	    $process_ratio = M('erp_process');
        $sql = "select id from erp_process where status in ('pass', 'make')
                and real_qty < plan_qty
                and p_pro_code = " . $M->p_pro_code . " limit 1";
        $affected = $process_ratio->query($sql);
        if (!empty($affected)) {
            //比例关系正在使用
            $this->msgReturn(false, '比例关系正在使用中请勿编辑');
        }
        return;
	} 
	
	/**
	 * 删除处理(非数据处理 比例关系是否符合删除条件处理)
	 */
	public function before_delete($data) {
	    if (empty($data)) {
	        return;
	    }
	    $process = M('erp_process');
	    $process_ratio = M('erp_process_sku_relation');
	    foreach ($data as $value) {
	        $map['id'] = $value;
	        $p_code = $process_ratio->where($map)->find();
	        unset($map);
	        $sql = "select id from erp_process where status in ('pass', 'make') 
	                and real_qty < plan_qty 
	                and p_pro_code = " . $p_code['p_pro_code'] . " limit 1";
	        $affected = $process->query($sql);
	        if (!empty($affected)) {
	            //比例关系正在使用
	            $this->msgReturn(false, '比例关系正在使用中请勿删除');
	        }
	    }
	    return;
	}
}
