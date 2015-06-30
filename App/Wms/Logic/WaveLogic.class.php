<?php
// +----------------------------------------------------------------------
// | DaChuWang [ Let people eat at ease ]
// +----------------------------------------------------------------------
// | Copyright (c) 20015 http://dachuwang.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liuguangping <liuguangpingtest@163.com>
// +----------------------------------------------------------------------
namespace Wms\Logic;
class WaveLogic{
	/**
	 * 根据出库单格式化出库单数据 
	 *  
	 * @param String $ids 出库单id
	 * @param Int $company_id 来自哪里：1大厨2大果
	 * @author liuguangping@dachuwang.com
	 * @return Array $data;
	 * 
	 */
	public function getWaveDate($ids, $company_id = 1){
		if(!$ids) return FALSE;
		$idsArr = explode(',', $ids);
		$data = array();
		$m = M('stock_wave');
		$sumResult = A('StockOut','Logic')->sumStockBillOut($idsArr);
		$data['wave_type']   = 2;
		$data['order_count'] = count($idsArr); //订单数
		$data['line_count']  = $sumResult['skuCount'];//sku码
		$data['total_count'] = $sumResult['totalCount'];//商品总数
		$data['company_id']    = $company_id;//pm和王爽说大厨与大果是不会在同一个仓库
		return $data;
	}
	
	/**
	 * 根据出库单列表和波次id加入波次详细表
	 *  
	 * @param String $ids 出库单id
	 * @param Int $wave_id 波次id
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
	public function addWaveDetail($ids, $wave_id){
		if(!$ids) return FALSE;
		$idsArr = explode(',', $ids);
		$WaveDetailArr = array();
		$M      = M('stock_wave_detail');
		foreach ($idsArr as $key => $value) {
			
			$WaveDetailArr[$key]['bill_out_id'] = $value;
			$WaveDetailArr[$key]['pid'] = $wave_id;
			$WaveDetailArr[$key]['created_time'] = get_time();
			$WaveDetailArr[$key]['created_user'] = session('user.uid');
			$WaveDetailArr[$key]['updated_user'] = session('user.uid');
			$WaveDetailArr[$key]['updated_time'] = get_time();
		}
		$result = $M->addAll($WaveDetailArr)?TRUE:FALSE;
		return $result;
	}
	
	/**
	 * 根据出库单列表修改出库单状态
	 *  
	 * @param String $ids 出库单id
	 * @param Int $wave_id 波次id
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
	public function updateBillOutStatus($ids, $wave_id){
		if(!$ids) return FALSE;
		$idsArr     = explode(',', $ids);
		$map        = array();
		$map['id']  = array('in',$idsArr);
		$Model      = M('stock_bill_out');
		$data['status'] = '3';
		$data['wave_id'] = $wave_id;
		$result      = $Model->data($data)->where($map)->save()?TRUE:FALSE;

		//通知hop订单状态改变
		$bill_out_infos = M('stock_bill_out')->where($map)->select();
		unset($map);

		foreach($bill_out_infos as $bill_out_info){
			$map['suborder_id'] = $bill_out_info['refer_code'];
			$map['status'] = '11';
			$map['cur']['name'] = session('user.username');
			$re = A('Common/Order','Logic')->set_status($map);
			unset($map);
		}

		return $result;
	}
	/**
	 * 根据仓库ID获取线路列表
	 *  
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
    public function line(){
        $map['wh_id'] = session('user.wh_id');
        $map['status'] = '1';
        $map['itemsPerPage'] = 1000;
        $A = A('Common/Order','Logic');
        $lines = $A->line($map);
        $lines_arr = array();
        foreach ($lines as $key => $value) {
            $lines_arr[$value['id']] = $value['name'];
        }
        return $lines_arr;
    }
    /**
	 * 根据仓库Id判断波次是否可以删除和拣货
	 * 
	 * @param String $ids 出库单id 
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
    public function hasIsAuth($ids = '',$status = '201,900'){
    	if(!$ids) return FALSE;
		$map = array();
		$map['status'] =  array('in', $status);
		$map['id'] = array('in', $ids);
		$m = M('stock_wave');
		$result = $m->where($map)->select();
		if($result) return FALSE;
		return TRUE;
    }
    
    /**
	 * 根据仓库Id判断波次开始拣货把状态至为分拣中
	 * 
	 * @param String $ids 出库单id 
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
    public function execPack($ids = ''){
    	if(!$ids) return FALSE;
		$map = array();
		$map['id'] = array('in', $ids);
		$status = array();
		$status['status'] = 201;
		if($this->updateStatus($map, $status)){
			return TRUE;
		}else{
			return FALSE;
		}
    }
    /**
	 * 根据仓库Id判断波次开始拣货把状态至为分拣中
	 * 
	 * @param String $ids 出库单id 
	 * @author liuguangping@dachuwang.com
	 * @return Boolean $result;
	 * 
	 */
    public function delWave($ids = ''){
    	if(!$ids) return FALSE;
		$map = array();
		$map['id'] = array('in',$ids);
		$status = array();
		$status['is_deleted'] = 1;
		if($this->updateStatus($map, $status)){
			//删除波次详细数据
			$del = array();
			$data = array();
			$M = M('stock_wave_detail');
			$billOut = M('stock_bill_out');
			$del['pid'] = array('in',$ids);
			$data['is_deleted'] = 1;
			if($M->where($del)->save($data)){
				//还原出库单stock_bill_out 的出库单状态status 为待生产 1 
				$bill_outWhere = array();
				$bill_outWhere['pid'] = $del['pid'];
				$wave_detail_arr = $M->field('bill_out_id')->where($bill_outWhere)->select();
				
				if($wave_detail_arr){
					$billOutW = array();
					$billSave = array();
					$bill_outArr = getSubByKey($wave_detail_arr, 'bill_out_id');
					if($bill_outArr){
						$idsStr = implode(',', $bill_outArr);
						$billOutW['id'] = array('in',$idsStr);
						$billSave['status'] = 1;
						$billSave['wave_id'] = 0;
						if(!$billOut->where($billOutW)->save($billSave)){
							$this->updateStatus($del, array('is_deleted'=>0),'stock_wave_detail');
							$this->updateStatus($map, array('is_deleted'=>0));
							return FALSE;
						}
					}
					
				}
				return TRUE;
			}else{
				$this->updateStatus($map, array('is_deleted'=>0));
				return FALSE;
			}
			return TRUE;
		}else{
			return FALSE;
		}
    }
    /**
	 * 根据条件修改拣货表
	 * 
	 * @param Array $map 条件
	 * @param Int $status 状态 
	 * @author liuguangping@dachuwang.com
	 * @return Boolean;
	 * 
	 */
    public function updateStatus($map = array(), $data = array(), $tableName = 'stock_wave'){
    	if(empty($data)) return FALSE;
		if(empty($map)) return FALSE;
		$m = M($tableName);
		if($m->where($map)->save($data)){
			return TRUE;
		}else{
			return FALSE;
		}
    }
    public function getStatusCn($id){
    	$array = array(
            '1'=>'待生产',
            '2'=>'已出库',
            '3'=>'波次中',
            '4'=>'待拣货',
            '5'=>'待复核',
            '6'=>'己复核' 
           );
    	return $array[$id];
    }
    //查看出库单中所有sku是否满足数量需求
    public function hasEnough($ids){
    	$idsArr = explode(',', $ids);
    	if(!$idsArr) return FALSE;
    	foreach($idsArr as $key=>$value){
    		$is_enough = A('Stock','Logic')->checkStockIsEnoughByOrderId($value);
    		if(!$is_enough) return FALSE;
    	}
    	return TRUE;
    }
    
    public function updateStauts($tablename, $data, $map){
        $result = TRUE;

        if(!$map || !$tablename || !$data){
            $result = FALSE;
        }

        $m = M($tablename);
        if(!$m->where($map)->save($data)){
            $result = FALSE;
        }
        return $result;
    }
        
}
/* End of file WaveLogic.class.php */
/* Location: ./Application/Logic/WaveLogic.class.php */
