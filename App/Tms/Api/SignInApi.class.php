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
        if (empty($suborder_id) || empty($img)) {
            $re = array(
                'status' => -1,
                'msg'    => '订单ID或签名不能为空'
            );
            $this->ajaxReturn($re);
        }
        $this->ajaxReturn(array('status' => 0));
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
        import("Common.Lib.HttpCurl");
        $this->request = new \HttpCurl();
        $url = "http://img.dachuwang.com/upload/" + $img['name'];
        $file = array(
            'name' => $img['tmp_name'],
            'dir'  => dirname($img['tmp_name']),
        );
        $boundary = '--********--';
        $header = array(
            'Content-Type'    => 'multipart/form-data;boundary=' . $boundary,
            'Charset'         => 'UTF-8',
            'Connection'      => 'Keep-Alive',
        );
        $res = $this->request->post(
            $url,
            $args, 
            $options = array('post_json' => false, 'header' => $header),
            $urlencode = false,
            $file
        );
        return $res;
    }
}