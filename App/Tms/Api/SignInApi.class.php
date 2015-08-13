<?php
namespace Tms\Api;
use Think\Controller;

/**
 * TMS订单签收接口
 */
class SignInApi extends CommApi
{
    public function signature() 
    {
        $suborder_id = I('post.order_id/d',0);
        $bill_out_id = I('post.bill_out_id/d',0);
        $img = $_FILES['image'];
        if ($suborder_id == 0 || !$img || $img['error'] != 0) {
            $re = array(
                'status' => -1,
                'msg'    => '参数错误或图片传输失败'
            );
            $this->ajaxReturn($re);
        }
        $save_dir = TEMP_PATH;
        is_dir($save_dir) || mkdir($save_dir, 0777, true);
        $info = pathinfo($img['name']);
        $filename = uniqid() . '.' . $info['extension'];
        $file = $save_dir . '/' . $filename;
        if (!move_uploaded_file($img['tmp_name'], $file)) {
            $re = array(
                'status' => -1,
                'msg'    => '图片保存失败'
            );
            $this->ajaxReturn($re);
        }
        chmod($file,0755);
        $res = $this->curl_upload_pic($file);
        is_file($file) && unlink($file);
        if (empty($res['files'][0]['url'])) {
            $re = array(
                'status' => -1,
                'msg'    => '图片上传失败'
            );
            $this->ajaxReturn($re);
        }
        $sign_path = $res['files'][0]['url'];
        $map['suborder_id'] = $suborder_id;
        $map['sign_img'] = $sign_path;

        //签名图片回调给订单
        $cA = A('Common/Order', 'Logic');
        $hs = $cA->saveSignature($map);
        $ts = array('status' => 0);
        //保存签名到配送单详情
        if ($bill_out_id) {
            $A = A('Wms/Distribution', 'Logic');
            $ts = $A->saveSignature($map);
        }
        if ($hs['status'] === 0 && $ts['status'] === 0) {
            $re = array(
                'status' => 0,
                'msg'    => '签名保存成功'
            );
        } else {
            $re = array(
                'status' => -1,
                'msg'    => '签名保存失败，请稍候再试'
            );
        }
        $this->ajaxReturn($re);
    }

    /**
     * [curl_upload_pic curl上传一个图片到图片服务器]
     * @param  [type] $file [文件名]
     * @return [type]       [文件在服务器保存信息]
     */
    public function curl_upload_pic($file)
    {
        $url = C('IMG_UPLOAD_PATH') . 'upload?bucket=tms';
        $fields['files'] = '@'.$file;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * [skuStatis 统计SKU售卖信息]
     * @param [type] [发货时间段:start_time,endtime,仓库ID:warehouse_id]
     */
    public function skuStatis()
    {
        $params = I('json.');
        $start_time   = isset($params['start_time']) ? $params['start_time'] : 0;
        $end_time     = isset($params['end_time']) ? $params['end_time'] : 0;
        $warehouse_id = isset($params['warehouse_id']) ? $params['warehouse_id'] : 0;
        if (empty($start_time) || empty($end_time)) {
            $res = array(
                'status' => -1,
                'msg'    => '参数错误'
            );
            $this->ajaxReturn($res);
        }
        //时间段
        $start_date = date('Y-m-d H:i:s',$start_time);
        $end_date   = date('Y-m-d H:i:s',$end_time);
        $map['bo.delivery_date'] = array('between', array($start_date, $end_date));
        //仓库ID
        if ($warehouse_id) {
            $map['bo.wh_id'] = $warehouse_id;
        }
        $M = M('tms_sign_in_detail');
        //返回字段：SKU号，实际销售额，实际销售件数，拒收sku数
        $field = 'bod.pro_code as sku_number,';
        $field .= 'sum(sid.real_sign_qty * sid.price_unit) as actual_sale_amount,';
        $field .= 'sum(sid.real_sign_qty) as actual_sale_count,';
        $field .= 'sum(CAST(bod.delivery_qty - sid.real_sign_qty as SIGNED)) as reject_sku_counts';
        $list = $M->alias('sid')->field($field)
            ->join('stock_bill_out_detail bod on bod.id = sid.bill_out_detail_id')
            ->join('join stock_bill_out bo on bo.id = bod.pid')
            ->where($map)
            ->group('bod.pro_code')
            ->select();
        $res = array(
            'status' => 0,
            'list'   => $list
        );
        $this->ajaxReturn($res);
    }
    /**
     * [get_sign_data 获取订单的签收信息]
     * @param [int] [suborder_id 订单id]
     */
    public function get_sign_data()
    {
        $order_id = I('json.suborder_id');
        if (empty($order_id) || intval($order_id) <= 0) {
            $res = array(
                'status' => -1,
                'msg'    => '需要提供子订单id',
            );
            $this->ajaxReturn($res);
        }
        $map['refer_code'] = $order_id;
        $map['is_deleted'] = 0;
        $bill_out = M('stock_bill_out')->where($map)->find();
        if (empty($bill_out)) {
            $res = array(
                'status' => -1,
                'msg'    => '查不到该订单对应的出库单',
            );
            $this->ajaxReturn($res);
        }
        unset($map);
        $map['bill_out_id'] = $bill_out['id'];
        $map['is_deleted']  = 0;
        $sign_data = M('stock_wave_distribution_detail')->where($map)->find();
        if (empty($sign_data)) {
            $res = array(
                'status' => -1,
                'msg'    => '查不到该订单的签收信息',
            );
            $this->ajaxReturn($res);
        }
        $data = array(
            'status'         => $sign_data['status'],
            'sign_driver'    => $sign_data['sign_driver'],
            'sign_time'      => $sign_data['sign_time'],
            'sign_msg'       => $sign_data['sign_msg'],
            'reject_reason'  => $sign_data['reject_reason'],
            'receivable_sum' => $sign_data['receivable_sum'],
            'real_sum'       => $sign_data['real_sum'],
            'wipe_zero'      => $sign_data['wipe_zero'],
            'deposit'        => $sign_data['deposit'],
        );
        $res = array(
            'status' => 0,
            'list'   => $data,
        );
        $this->ajaxReturn($res);
    }
}