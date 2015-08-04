<?php
namespace Tms\Logic;
/**
 *
 *  签收逻辑
 *
 *  @author  pengyanlei@dachuwang.com
 */

class SignInLogic 
{
   /**
     * [getReasonByCode 根据数字获取拒收原因]
     * @param  array  $codes [description]
     * @return [type]        [description]
     */
    public function getReasonByCode($codes = array())
    {
        $reason_cn = array(
            1 => '品相不符',
            2 => '缺斤少两',
            3 => '顾客原因',
        );
        if (empty($codes)) {
            return '';
        }
        $reasons = array();
        foreach ($codes as $val) {
            $reasons[] = $reason_cn[$val];
        }
        return json_encode($reasons);      
    }

    /**
     * [sendRejectMsg 拒收给采购和BD发短信]
     * @param  array  $data    [description]
     * @param  string $reasons [description]
     * @return [type]          [description]
     */
    public function sendRejectMsg($data = array(),$reasons = '')
    {
        if (empty($data) || empty($reasons)) {
            return array(
                'status' => -1,
                'msg'    => '参数错误'
            );
        }
        //需要发送的手机号
        $mobiles = array(
            '13241836114',
            '13834652468',
            '13601269285',
            '13701010714',
        );
        if ($data['bd']['mobile']) {
            $mobiles[] = $data['bd']['mobile'];
        }
        //产品列表
        foreach ($data['detail'] as $key => $pro) {
            if ($key == 0) {
                $products = $pro['name']; 
            } else {
                $products .= ',' . $pro['name'];
            }
        }
        //拒收原因
        $reasons = json_decode($reasons, 1);
        foreach ($reasons as $k => $reason) {
            if ($k == 0) {
                $reject_reason = $reason;
            } else {
                $reject_reason .= ',' . $reason;
            }
        }
        //组合内容
        $content = "伙伴们，订单号：{$data['id']}，商圈：{$data['line']}，店铺名称：{$data['shop_name']}，";
        $content .= "客户姓名：{$data['realname']} 将产品{$products}拒收，";
        $content .= "拒收原因：{$reject_reason}，电话：{$data['mobile']} 。";
        $content .= "请在方便的时候给客户打个电话，了解具体情况，便于各部门改进工作，如果需要请联系在线部做进一步客情维护。";
        $content .= "退订请回复TD";
        $map = array(
            'mobile'   => $mobiles,
            'content'  => $content,
            'sms_type' => 1,
            'delay'    => 0,
        );
        $res = A('Common/Order', 'Logic')->sendPushMsg($map);
        return $res;
    }

    /**
     * [sendDeliveryMsg 司机提货给客户发送短信]
     * @param  [type] $data [description]
     * @param  [type] $id   [description]
     * @return [type]       [description]
     */
    public function sendDeliveryMsg($data, $id)
    {
        if (empty($data) || empty($id)) {
            return array(
                'status' => -1,
                'msg'    => '参数错误'
            );
        }
        //要发送的手机号
        $mobiles = array();
        foreach ($data as $value) {
            $mobiles[] = $value['order_info']['mobile'];
        }
        $mobiles = array_unique($mobiles);
        //司机信息
        $driver_mobile = session('user.mobile');
        $driver_name   = mb_substr(session('user.username'), 0, 1);
        //组合短信内容
        $content = "亲爱的老板，您在大厨网订购的产品已从库房发出，正朝您赶来，请耐心等待。";
        $content .= "负责此次配送的为{$driver_name}师傅（电话{$driver_mobile}），如需帮助请致电：4008199491。";
        $content .= "退订请回复TD";
        $cA = A('Common/Order', 'Logic');
        $map = array(
            'mobile'   => $mobiles,
            'content'  => $content,
            'sms_type' => 1,
            'delay'    => 1200,
        );
        //如果队列中已经存在该配送单ID的消息，撤回
        if ($job_id = S(md5($id))) {
            $dmap = array('job_id' => $job_id);
            $pes = $cA->sendPullMsg($dmap);
        }
        //加入消息队列并缓存该job_id
        $res = $cA->sendPushMsg($map);
        S(md5($id), $res['job_id'], 1200);
        return $res;
    }

    /**
     * [sendParentAccountMsg 签收后，如果存在母账户，给母账户发消息]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function sendParentAccountMsg($data)
    {
        if (empty($data)) {
            return array(
                'status' => -1,
                'msg'    => '参数错误'
            );
        }
        $cA = A('Common/Order', 'Logic');
       //请求母账户信息
       $umap = array('customer_id' => $data['user_id']);
       $parent = $cA->getParentAccountByCoustomerId($umap);
       if (is_array($parent) 
            && $parent['data']['account_type'] == 1 
            && $parent['data']['account_type'] != $data['user_id']
        ) {
            //要发送的母账户手机号
            $mobile = $parent['data']['mobile'];
            //组合信息内容
            $content = "亲爱的老板，分店“{$data['shop_name']}”的产品已成功送达，完成签收，请您放心，";
            $content .= "更多产品及订单信息请登陆大厨网“个人中心”查询。客服电话：4008199491。";
            $content .= "退订请回复TD";
            $map = array(
                'mobile'   => $mobile,
                'content'  => $content,
                'sms_type' => 1,
                'delay'    => 0
            );
            $res = $cA->sendPushMsg($map);
            return $res;
           
       } else {
            return array(
               'status' => 0,
               'msg'    => '不存在母账户'
            );
       }
            
    }
}