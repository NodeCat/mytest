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
        $dest = "../" . $img["name"];
        move_uploaded_file($img["tmp_name"],$dest);
        dump(file_get_contents($dest));die();
        if (empty($suborder_id) || empty($img)) {
            $re = array(
                'status' => -1,
                'msg'    => '订单ID或签名不能为空'
            );
            $this->ajaxReturn($re);
        }
        $path = $this->uploadImg($img);
        if ($path) {
            $map['suborder_id'] = $suborder_id;
            $map['sign_img'] = $path;
            //保存签名到配送单详情 和 回调给订单
            $A = A('Wms/Dist', 'Logic');
            $cA = A('Common/Order', 'Logic');
            $ts = $A->saveSignature($map);
            $hs = $cA->saveSignature($map);
            if ($ts['status'] === 0 && $hs['status'] === 0) {
                $re = array(
                    'status' => 0,
                    'msg'    => '保存成功'
                );
            } else {
                $re = array(
                    'status' => -1,
                    'msg'    => '保存失败，请稍候再试'
                );
            }
        } else {
            $re = array(
                'status' => -1,
                'msg'    => '图片上传失败'
            );
        }
    }

    public function uploadImg($img) {
        //读取临时文件
        $fileStr = file_get_contents($img['tmp_name']);
        $path = '/tms/sign/1.png';
        $paramsQuery = http_build_query($params);
        
        $url = 'http://img.dachuwang.com/upload' . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        //$info = curl_getinfo($ch);
        //$errorMsg = curl_error ( $ch );
        //$errorNumber = curl_errno ( $ch );
        curl_close ( $ch );

        //清除临时文件
        exec('rm -rf '.$info['file']['tmp_name']);
        return $ret;
    }
}