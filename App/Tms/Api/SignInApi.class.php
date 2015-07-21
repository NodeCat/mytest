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
        dump($img);
        $path = $this->uploadImg($img);
        dump($path);die();
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
        $url = "http://img.dachuwang.com/upload/" + $img['name'];
        $boundary = '--********--';
        $header = array(
            'Content-Type'    => 'multipart/form-data;boundary=' . $boundary,
            'Charset'         => 'UTF-8',
            'Connection'      => 'Keep-Alive',
        );
        $arg = file_get_contents($img['tmp_name']);
        dump($arg);
        $res = $this->post(
            $url,
            $arg, 
            $options = array('header' => $header),
        );
        dump($file);
        dump($res);
    }

    public function post($url, $arg, $options = array(), $urlencode = false, $file = array()){
        import("Common.Lib.HttpRequest");
        $httpRequest = new \HttpRequest($url);
        $httpRequest->set_method("POST");
        $httpRequest->post_fields = $arg;
        if ($file) {
            $httpRequest->add_post_file($file['name'], $file['dir']);
        }
        // timeout
        if(isset($options['timeout'])){
            $httpRequest->set_timeout($options['timeout']);
        }else{
            $httpRequest->set_timeout(8000);
        }
        if(isset($options['connect_timeout'])){
            $httpRequest->set_connect_timeout($options['connect_timeout']);
        }else{
            $httpRequest->set_connect_timeout(8000);
        }

        if(isset($options['header'])){
            foreach($options['header'] as $key => $value){
                $httpRequest->add_header($key,$value);
            }
        }
        $httpRequest->send();
        $response = $httpRequest->get_response_content();
        return $response;
    }
}