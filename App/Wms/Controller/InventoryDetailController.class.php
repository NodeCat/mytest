<?php
namespace Wms\Controller;
use Think\Controller;
class InventoryDetailController extends CommonController {
    protected $columns = array('id' => '',
            'location_code' => '库位',
            'pro_code' => '货品标识',
            'pro_name' => '货品名称',
            'theoretical_qty' => '原数量',
            'pro_qty' => '实盘量',
            'diff_qty' => '差异量',
            'uom_name' => '计量单位',
            );
    //设置列表页选项
    protected function before_index() {
        $id = I('id');
        //根据inventory_detail 的id 查询对应的inventory信息
        $map['id'] = $id;
        $inventory_info = M('stock_inventory')
        ->where($map)
        ->field('stock_inventory.status')
        ->find();

        //如果不是已关闭的盘点单 可以修改实盘量
        $this->toolbar_tr_is_edit = 0;    
        if(isset($this->auth['edit']) && $inventory_info['status'] != 'closed' && $inventory_info != 'confirmed'){
            $this->toolbar_tr_is_edit = 1;
        }

        $this->table = array(
            'toolbar'   => true,
            'searchbar' => true, 
            'checkbox'  => true, 
            'status'    => false, 
            'toolbar_tr'=> true,
        );
        $this->toolbar_tr =array(
            array('name'=>'view', 'show' => false,'new'=>'true'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false')
        );
        $this->toolbar =array(
            array('name'=>'add', 'show' => isset($this->auth['add']),'new'=>'false'), 
            array('name'=>'edit', 'show' => false,'new'=>'false'), 
            array('name'=>'delete' ,'show' => false,'new'=>'false'),
            array('name'=>'import' ,'show' => false,'new'=>'false'),
            array('name'=>'export' ,'show' => false,'new'=>'false'),
            array('name'=>'print' ,'show' => false,'new'=>'false'),
            array('name'=>'setting' ,'show' => false,'new'=>'false'),
        );
    }

    public function index() {
        $tmpl = IS_AJAX ? 'Table:list':'index';
        //$this->before($map,'index');
        $this->before_index();
        $this->lists($tmpl);
    }

    //lists方法执行前，执行该方法
    protected function before_lists(&$M){
        //根据inventory_id 查询对应code
        $inventory_id = I('id');
        $map['id'] = $inventory_id;
        $inventory_code = M('stock_inventory')->where($map)->getField('code');
        unset($map);
        $map['inventory_code'] = $inventory_code;
        $M->where($map)->order('stock_inventory_detail.id');
    }

    //lists方法执行后，执行该方法
    protected function after_lists(&$data){
        //整理数据项
        foreach($data as $key => $data_detail){
            if($data_detail['pro_qty'] || $data_detail['status'] == 'done'){
                $data[$key]['pro_qty'] = (empty($data[$key]['pro_qty'])) ? 0 : $data[$key]['pro_qty'];
                $data[$key]['diff_qty'] = formatMoney($data_detail['pro_qty'] - $data_detail['theoretical_qty'], 2);
            }
        }
        //添加pro_name字段
        $data = A('Pms','Logic')->add_fields($data,'pro_name');

        //根据盘点code 查询盘点单信息
        $map['code'] = $data[0]['inventory_code'];
        $inventory_info = M('stock_inventory')->where($map)->find();

        //添加 创建人
        $map['id'] = $inventory_info['created_user'];
        $inventory_info['created_user_nickname'] = M('user')->where($map)->getField('nickname');
        unset($map);

        //添加 盘点人
        $map['id'] = $inventory_info['updated_user'];
        $inventory_info['updated_user_nickname'] = M('user')->where($map)->getField('nickname');
        unset($map);

        //添加盘点结束时间
        $map['inventory_code'] = $data[0]['inventory_code'];
        $inventory_info['end_time'] = M('stock_inventory_detail')->where($map)->order('updated_time desc')->getField('updated_time');

        $this->inventory_info = $inventory_info;

    }
    //更新盘点单详情
    public function upd_detail(){
        $inventoryInfo = I('post.');
        
        unset($inventoryInfo['p']);
        
        if (empty($inventoryInfo)) {
            $this->msgReturn(false, '请先录入盘点量');
        }
        
        $inventoryCode = ''; //盘点单号
       
        foreach ($inventoryInfo as $id => $qty) {
            if (empty($id) || empty($qty)) {
                $this->msgReturn(false, '参数有误');
            }
            if (strlen(formatMoney($qty, 2, 1)) > 2) {
                $this->msgReturn(0,'实盘量只能精确到两位小数点');
            }
            $proQty = formatMoney($qty, 2);
            
            $map['id'] = $id;
            //获取盘点单号
            if (empty($inventoryCode)) {
                $inventoryDetailInfo = M('stock_inventory_detail')->where($map)->find();
                
                $inventoryCode = $inventoryDetailInfo['inventory_code'];
                //获取盘点单
                $where['code'] = $inventoryCode;
                $inventoryInfoStatus = M('stock_inventory')->where($where)->getField('status');
                if (empty($inventoryInfoStatus)) {
                    $this->msgReturn(false, '参数有误');
                }
                if ($inventoryInfoStatus == 'confirmed' || $inventoryInfoStatus == 'close') {
                    $this->msgReturn(false, '已经确认或者关闭的盘点单');
                }
            }
            
            //变更盘点详情状态
            $data['pro_qty'] = $proQty;
            $data['status'] = 'done';
            M('stock_inventory_detail')->where($map)->save($data);
            unset($data);
            unset($map);
        }
        
        //是否有差异
        $map['inventory_code'] = $inventoryCode;
        $inventoryDetailInfo = M('stock_inventory_detail')->field('SUM(pro_qty) as pro_qty,SUM(theoretical_qty) as theoretical_qty')->where($map)->find();
        
        $inventoryDetailInfoStatus = M('stock_inventory_detail')->where($map)->getField('status', true);
        $inventoryDetailInfoStatus = array_unique($inventoryDetailInfoStatus);
        
        //更新盘点单状态为待确认 如果有差异，则更新盘点单的是否有差异
        $map['code'] = $inventoryCode;
        if (count($inventoryDetailInfoStatus) == 1 && $inventoryDetailInfoStatus[0] == 'done') { 
            $data['status'] = 'confirm'; //待确认
        } else {
            $data['status'] = 'inventorying'; //盘点中
        }
        if($inventoryDetailInfo['pro_qty'] != $inventoryDetailInfo['theoretical_qty']){
            $data['is_diff'] = 1;
        }
        
        M('stock_inventory')->where($map)->save($data);
        unset($map);
        
        $this->msgReturn(true, '录入成功');
    }
}