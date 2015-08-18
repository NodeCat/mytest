<?php
namespace Tms\Logic;

class DistLogic {

    /**
     * [billOut 从WMS获取出库单列表并关联订单信息]
     * @param  array  $params [出库单ID或配送单ID，排序方式]
     * @return [type]         [description]
     */
    public function billOut($params = array()) {
        $res = A('Wms/StockOut', 'Logic')->bill_out_list($params);
        //配送单关联订单信息
        if($res['status'] === 0) {
            $bill_out_lists = $res['list'];
            $order_ids = array();
            foreach ($bill_out_lists as $bill) {
                $order_ids[] = $bill['refer_code'];
            }
            $map['order_ids'] = $order_ids;
            $map['itemsPerPage'] = count($order_ids);
            $cA = A('Common/Order','Logic');
            $orders = $cA->order($map);
            //配送单关联订单信息
            foreach ($bill_out_lists as &$bill) {
                foreach ($orders as $value) {
                    if($bill['refer_code'] == $value['id']) {
                        $bill['order_info'] = $value;
                    }
                }
            }
            $res = array(
                'orders'     => $bill_out_lists,
                'orderCount' => count($orders),
            );
            return $res;
        }
        return false;
    }

    /**
     * [getPayStatusByCode 根据支付状态码获取中文状态]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function getPayStatusByCode($code)
    {
        switch ($code) {
            case -1:
                $s = '货到付款';
                break;
            case 0:
                $s = '货到付款';
                break;
            case 1:
                $s = '已付款';
                break;
            default:
                $s = '';
                break;
        }
        return $s;
    }
    
    /** 
 	** 抹零处理函数，用来得到抹零后的结果
 	** 说明：抹零规则，0.5以下抹去，0.5〜0.9保留，第二位小数四舍五入
 	** @param float $price
 	** @return float $price抹零处理的结果
 	**/
    public function wipeZero($price =0.0) 
    {
        if ($price + 0.5 < ceil($price)) {
            $price = floor($price);
        }
        $price = round($price, 1);
        return $price;
    }
    /**
     * [getCatesByType 按类型获取如车型、平台、任务类型等数据]
     * @return [type] [description]
     */
    public function getCatesByType($type)
    {
        $map['type'] = $type;
        $map['is_deleted'] = 0;
        $M = M('category');
        $res = $M->field('id,name,type')->where($map)->select();
        return $res;
    }

    /**
     * [getTaskTypeById 根据分类ID获取分类名称]
     * @return [type] [description]
     */
    public function getCateNameById($id)
    {
        if (empty($id)) {
            return '';
        }
        $map['is_deleted'] = 0;
        $map['id'] = $id;
        $M = M('category');
        $res = $M->field('name')->where($map)->find();
        return $res['name'];
    }

    /**
     * [getDriverInfoById 根据ID获取一条司机信息]
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function getDriverInfoById($uid)
    {
        if (empty($uid)) {
            return array();
        }
        $map['is_deleted'] = 0;
        $map['id'] = $uid;
        $M = M('tms_user');
        $res = $M->field('id,username,mobile,car_type,car_from')->where($map)->find();
        $res['car_type'] = $this->getCateNameById($res['car_type']);
        $res['car_from'] = $this->getCateNameById($res['car_from']);
        return $res;
    }

    /**
     * [getStatusCnByCode 根据状态码获取任务状态]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function getStatusCnByCode($code)
    {
        $status = '';
        switch ($code) {
            case '1':
                $status = '待部门审批';
                break;
            case '2':
                $status = '待物流审批';
                break;
            case '3':
                $status = '待派车';
                break;
            case '4':
                $status = '配送中';
                break;
            case '5':
                $status = '已完成';
                break;
            case '6':
                $status = '未通过';
                break;
        }
        return $status;
    }

    /**
     * [getAllTaskStatus 获取所有的任务状态]
     * @return [type] [description]
     */
    public function getAllTaskStatus()
    {
        $task_type = array(
            '1' => '待部门审批',
            '2' => '待物流审批',
            '3' => '待派车',
            '4' => '配送中',
            '5' => '已完成',
            '6' => '未通过',
        );
        return $task_type;
    }
    
    /**
     * [get_delivery_fee 根据车型、公里数获取运费价格最便宜的运力平台及运费]
     * @param  [int] $car_type     [车型]
     * @param  [int] $mile         [公里数]
     * @return [array] $result        [运力平台及运费价格]
     */
    public function get_delivery_fee($car_type = 0, $mile = 0) 
    {
        $result = array();
        $map['car_type']     = $car_type;
        $map['min_mile']     = array('lt',$mile);
        $map['max_mile']     = array('egt',$mile);
        $map['is_deleted']   = 0;
        $M = M('tms_delivery_fee');
        $res = $M->where($map)->order('price asc')->find();
        if (!empty($res)) {
            $result['price']        = $res['price'];
            $result['car_platform'] = $this->getCateNameById($res['car_platform']);
        }
        return $result;
    }

    /**
     * [getAuditShowStatus 根据权限、状态、审批步骤判断审批按钮是否显示]
     * @param  [type] $auth   [权限集]
     * @param  [type] $status [当前任务状态]
     * @param  [type] $type   [物流审批还是部门审批]
     * @return [type]         [description]
     */
    public function getAuditShowStatus($auth, $status, $type = 1)
    {
        //部门审批
        if ($type === 1) {
            if (!empty($auth['departAudit']) && $status == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            //物流审批
            if (!empty($auth['logisAudit']) && $status == 2) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * [getOneUser 根据uid获取一条用户信息]
     * @param  [type] $uid [用户ID]
     * @return [type]      [description]
     */
    public function getOneUser($uid)
    {
        $uM = M('user');
        $map['id'] = $uid;
        $map['is_deleted'] = 0;
        $res = $uM->where($map)->find();
        return $res;
    }

    /**
     * [getSignCode 生成用户签到验证码]
     * @return [type] [description]
     */
    public function getSignCode()
    {
        $date  = date('Y-m-d', time());
        $wh_id = session('user.wh_id');
        $s4 = substr(md5($date . '-' . $wh_id), 0, 4);
        $code = substr(base_convert($s4, 16, 10), -4);
        if (!S(md5($code))) {
           S(md5($code), $wh_id, 86400); 
        }
        return $code;
    }
}