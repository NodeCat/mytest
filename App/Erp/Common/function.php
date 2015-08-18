<?php
function getField($table,$fields,$condition=null){
  $data=M($table)->where($condition)->getField($fields);
  return $data;
}
function match($table,$field,$fields){
  $id=I('q');
  $map[$field] = array('like','%'.$id.'%');
  $data = getField($$table,$fields,$map);
  return json_encode($data);
}

function validator($vo){
        $str ='{';
        $str .= $vo['null']=='NO'?'required:true,':'';
        $str .= strpos($vo['type'],'char')!==false?'maxlength:'.substr($vo['type'],strpos($vo['type'],"(")+1,-1).',':'';
        if(strpos($vo['type'],'int')!==false){
            $str .= 'digits:true,';
            switch(strtoupper(substr($vo['type'],0,3)))
            {
                case 'TIN'://TINYINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,255]':'range:[-128,127]';
                  break;  
               case 'SMA'://SMALLINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,65535]':'range:[-32768,32767]';
                  break; 
                case 'MED'://MEDIUMINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,16777215]':'range:[-8388608,8388607]';
                  break; 
                case 'INT'://INT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,4294967295]':'range:[-2147483648,2147483647]';
                  break; 
                case 'BIG'://BIGINT
                    $str .= strpos($vo['type'],'unsigned')!==false?'range:[0,18446744073709551615]':'range:[-9223372036854775808,9223372036854775807]';
                  break; 
            }
        }
        $str .= strpos($vo['type'],'decimal')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'float')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'double')!==false?'number:true,':'';
        $str .= strpos($vo['type'],'year')!==false?'range:[0,9999],digits:true,':'';
        $str .= strpos($vo['type'],'timestamp')!==false?'dateISO:true,':'';
        $str .= strpos($vo['type'],'datetime')!==false?'dateISO:true,':'';
        $str .= strpos($vo['type'],'time')!==false?'date:true,':'';
        $str.='}';
        return $str;
    }
    function control_type($vo){
        if($vo['key']==='PRI')$type='hidden';
        elseif(strpos($vo['type'],'char')!==false)$type='text';
        elseif(strpos($vo['type'],'text')!==false)$type='area';
        elseif(strpos($vo['type'],'enum')!==false)$type='select';
        elseif($vo['type']=='datetime' || $vo['type']=='timestamp')$type='datetime';
        elseif($vo['type']=='date')             $type='date';
        elseif($vo['type']=='time')             $type='time';
        elseif(strpos($vo['type'],'bit')!==false)$type='checkbox';
        //else if(strpos($vo['type'],'set')!==false)$type='checkboxs';
        else if(
                strpos($vo['type'],'int')!==false 
            ||  strpos($vo['type'],'decimal')!==false 
            ||  strpos($vo['type'],'float')!==false 
            ||  strpos($vo['type'],'double')!==false
            )
            $type='digit';
        return $type;
    }
function where_array_to_str($where = array(), $relation = 'AND'){
    if(empty($where)){
        return false;
    }
    
    foreach($params['where'] as $condition => $val){
        $where_str .= $condition.' = '.$val.' '.$relation.' ';
    }
    
    $where_str = substr($where_str, 0, strlen($where_str) - 4);
    
    return $where_str;
}
//英文转中文
function en_to_cn($str){
    $filter = array(
        'qualified' => '合格',
        'unqualified' => '残次',
        'freeze' => '冻结',
        'in' => '收货',
        'on' => '上架',
        'move_location' => '库存移动',
        'move' => '库存移动',
        'inventory' => '盘点',
        'change_status' => '状态调整',
        'noinventory' => '未盘点',
        'inventorying' => '盘点中',
        'confirm' => '待审核',
        'closed' => '已作废',
        'fast' => '快速盘点',
        'again' => '复盘',
        'status' => '状态调整',
        'unite' => '组合',
        'split' => '拆分',
        'draft' => '草稿',
        'make' => '已完成',
        'pass' => '已生效',
        'reject' => '已驳回',
        'close' => '已作废',
        );
    return $filter[$str];
}
//中文转英文
function cn_to_en($str){
    $filter = array(
        '盘点' => 'inventory',
        '库存移动' => 'move',
        '收货' => 'in',
        '上架' => 'on',
        '库存移动' => 'move_location',
        );
    return $filter[$str];
}
//根据单号返回单据中文类型
function get_type_by_bill($bill_code){
    if(strstr($bill_code,'PD')){
        $type = '盘点单';
    }
    if(strstr($bill_code,'STOCK')){
        $type = '状态调整';
    }
    return $type;
}
//根据USER_AGENT判断是否是移动端
function is_mobile_terminal(){
    $mobile_ua = array(
        'iPhone',
        'Android',
        );
    foreach($mobile_ua as $ua){
        if(strstr($_SERVER['HTTP_USER_AGENT'], $ua)){
            return true;
        }
    }
    return false;
}
/**
 * getProname 根据suk code 获取sku 名称
 * @param Int $Prcode sku code
 * @param String $name 要字段
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getPronameByCode($name, $prcode) {
    $result = '';
    if($prcode){
        $infos = A('Pms','Logic')->get_SKU_by_pro_codes($prcode);
        if($infos['status'] == 0){
            if($infos['list']){
                $result = $infos['list']['0'][$name];
            }
        }
    }
    return $result;
    
}
/**
 * getProname 根据suk code 获取处理过的信息
 * @param Int $Prcode sku code
 * @param String $name 要字段
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getSkuInfoByCode($name, $prcode) {
    $result = '';
    if($prcode){
        $infos = A('Pms','Logic')->get_SKU_field_by_pro_codes($prcode);
        if($infos[$prcode]){
            $result = $infos[$prcode][$name];
        }
    }
    return $result;
    
}

function getSkuInfoByCodeArray($arr_pro_code) {
    $infos = A('Pms','Logic')->get_SKU_field_by_pro_codes($arr_pro_code);
    return $infos;
    
}
/**
 * getProname 根据 库位id 获取库位信息
 * @param Int $location_id 仓库id
 * @param String $name 要字段
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getLocationNameById($name, $location_id) {
    $result = '';
    if($location_id){
        $infos = M('location')->where('id='.$location_id)->getField($name);
        $result = $infos;
    }
    return $result;
    
}
/**
 * getProname 根据表名 和 主键 要 获取的字段
 * @param String $tablename 表名
 * @param Int $id 主键
 * @param String $field 字段
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getTableFieldById($tablename, $field='', $id){
    $result = '';
    if($tablename && $id && $field){
        $infos = M($tablename)->where('id='.$id)->getField($field);
        $result = $infos;
    }elseif($tablename && $id && !$field){
        $infos = M($tablename)->where('id='.$id)->find();
        $result = $infos;
    }
    return $result;
    
}
/**
 * getLineNameByid 根据线路id得到线路信息
 * @param Int $id 线路id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getLineNameByid($id){
    $result = '';
    $lines = A('Wave','Logic')->line();
    if(isset($lines[$id])){
        $result = $lines[$id];
    }
    return $result;
    
}
/**
 * Ajax json 返回结果
 * @param Int ids 状态
 * @param Array $data 数据
 * @param String $meg 提示信息
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function echojson($status, $data, $msg){
    $return['status'] = $status;
    $return['msg']    = $msg;
    $return['data']   = $data;
    echo json_encode($return); exit;
}
/**
 * getSubByKey 将两个二维数组根据指定的字段来连接起来
 * @param Array $pArray 二维数组
 * @param String $pKey 指定的键
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getSubByKey($pArray, $pKey = "") {
    $result = array();
    if(is_array($pArray)){
        foreach($pArray as $key=>$value){
            foreach ($value as $keys => $values) {
                if($keys == $pKey){
                    array_push($result, $values);
                }
            }
        }
    }
    return $result;
    
}
/**
 * getStockQtyByWpcode 根据sku 和 仓库汇总库存量
 * @param String $pro_code sku code
 * @param Int $wh_id 仓库id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getStockQtyByWpcode($pro_code,$wh_id){

    $m = M('Stock');
    if(!$pro_code || !$wh_id){
        return 0;
    }

    $where = array();
    $where['wh_id'] = $wh_id;
    $where['pro_code'] = $pro_code;
    $where['status'] = 'qualified';
    $res = $m->where($where)->sum('stock_qty');

    if(!$res){
        return 0;
    }
    return $res;
}

/**
 * getDownOrderNum 根据sku和仓库id汇总下单数
 * @param String $pro_code sku code
 * @param Int $wh_id 仓库id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getDownOrderNum($pro_code,$delivery_date='',$delivery_ampm='',$wh_id=''){

    $m = M('stock_bill_out_detail');
    if(!$pro_code || !$wh_id){
        return 0;
    }
    $where = array();
    $where['d.wh_id'] = $wh_id;
    $where['d.pro_code'] = $pro_code;
    $where['d.is_deleted'] = 0;
    $where['b.is_deleted'] = 0;
    $where['b.status'] = 1;
    $where['b.type'] = 1;
    if($delivery_date){
        $where['b.delivery_date'] = $delivery_date;
    }
    if($delivery_ampm){
        $where['b.delivery_ampm'] = $delivery_ampm; 
    }

    $res = $m->table('stock_bill_out_detail as d')->join('left join stock_bill_out as b on d.pid=b.id')->where($where)->sum('order_qty');
    if(!$res){
        return 0;
    }
    return $res;
}
/**
 * getPurchaseNum 根据sku和仓库id得到需要采购的量
 * @param String $pro_code sku code
 * @param Int $wh_id 仓库id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getPurchaseNum($pro_code,$delivery_date='',$delivery_ampm='',$wh_id=''){

    $res = getDownOrderNum($pro_code,$delivery_date,$delivery_ampm,$wh_id)-getStockQtyByWpcode($pro_code,$wh_id);
    
    return $res;
}
/**
 * getProcessByCode 根据物料清单sku和子sku仓库id汇总原理采购量
 * @param String $pro_code sku code
 * @param Int $wh_id 仓库id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function getProcessByCode($pro_code,$wh_id,$delivery_date='',$delivery_ampm='',$c_pro_code){

    $m = M('erp_process_sku_relation');
    if(!$pro_code || !$c_pro_code){

        return 0;
    }
    $where = array();
    $where['c_pro_code'] = $c_pro_code;
    $where['p_pro_code'] = $pro_code;
    $ratio = $m->where($where)->getField('ratio');
    if(!$ratio){
        return 0;
    }else{
        $order_num = getPurchaseNum($pro_code,$delivery_date,$delivery_ampm,$wh_id)*$ratio-getStockQtyByWpcode($c_pro_code,$wh_id);
    }
    return $order_num;
}

/**
 * array_sum 数组之和
 * @param Array $array 处理的数组
 * @param key $key 仓库id
 * @author liuguangping@dachuwang.com
 * @since 2015-06-13
 */
function arraySum($pArray,$pKey=''){

    $result = 0;
    if(is_array($pArray) && $pKey){
        foreach($pArray as $key=>$value){
            foreach ($value as $keys => $values) {
                if($keys == $pKey){
                    $result += $values;
                }
            }
        }
    }else{
        foreach ($pArray as $key => $value) {
            $result += $value;
        }
    }

    return $result;
}

/**
 * formatMoney 小数点保留几位后面抹掉
 * @param Float $number 处理的数据
 * @param Int $dat 保留几位
 * @param Int $is_end_str null 返回格式化的数据 不等于null 返回小数点后面的数
 * @author liuguangping@dachuwang.com
 * @since 2015-07-17
 */
function formatMoney($number = 0, $dot = 2 ,$is_end_str = null)
{   
    $pos            = strrpos($number, '.');
    $enstr          = substr($number,$pos+1);
    if ($is_end_str !== null) {
        if ($pos === FALSE) {
            // 末尾没有小数点
            return 1;
        }
        return $enstr;
    }
    $return_str     = 0;
    if (strrpos($number,'.') !== FALSE) {
      $end_str      = str_pad($enstr,$dot,'0');
      $return_str   = intval($number) . '.' . substr_replace($end_str, '', $dot);
    } else {
      $return_str   = intval($number) . '.' . str_pad('0',$dot,'0');
    }
    return $return_str;

}

