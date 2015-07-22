<?php
namespace Tms\Api;
use Think\Controller;

/**
 * 配送单接口
 */
class SignInApi extends CommApi
{
    public function signature() 
    {
        $suborder_id = I('post.order_id/d',0);
        $img = $_FILES['image'];
        if ($suborder_id == 0 || !$img || $img['error'] != 0) {
            $re = array(
                'status' => -1,
                'msg'    => '参数错误或图片传输失败'
            );
            $this->ajaxReturn($re);
        }

        $save_dir = './upload';
        is_dir($save_dir) || mkdir($save_dir, 0755, true);
        $filename = basename($img['name']);
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
        if (!$res['url']) {
            $re = array(
                'status' => -1,
                'msg'    => '图片上传失败'
            );
            $this->ajaxReturn($re);
        }
        $sign_path = $res['saved_path'];
        $map['suborder_id'] = $suborder_id;
        $map['sign_img'] = $sign_path;
        //保存签名到配送单详情 和 回调给订单
        $A = A('Wms/Dist', 'Logic');
        $cA = A('Common/Order', 'Logic');
        $ts = $A->saveSignature($map);
        $hs = $cA->saveSignature($map);
        if ($ts['status'] === 0 && $hs['status'] === 0) {
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

   public function curl_upload_pic($file)
   {
        $url = "http://img.dachuwang.com/upload?bucket=shop";
        $fields['files'] = '@'.$file;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}