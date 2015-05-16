<?php
namespace Wms\Controller;
use Think\Controller;
class LoginController extends Controller {
    protected function _initialize(){
        if(!check_maintain()){
            destory_session();
            layout(FALSE);
            $this->display('index:closed');
            exit();
        }
    }

    public function index($username = null, $password = null, $verify = null){
        if(IS_POST){
            /* 检测验证码  */
            if(!check_verify($verify)){
                $this->error('验证码输入错误！');
            }
            $this->username=$username;
            $User = D('User','Api');
            $uid = $User->login($username, $password);
            if(0 < $uid){
                //TODO:跳转到登录前页面
                set_session($uid);
                $url = I('post.url');
                if(empty($url)) {
                    $this->success('登录成功！', 'Index/index',3);
                }
                else {
                    $url = urldecode($url);
                    if($url == 'Login/index') {
                        $url = "Index/index";
                    }
                    $this->success('登录成功！跳转至登录前界面',$url,3);
                }
            } else { //登录失败
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
            $url = I('get.url');
            if(is_login()){
                if(empty($url)) {
                    $this->redirect('Index/index');
                }
                else{
                    $url = urldecode($url);
                    if($url == 'Login/index') {
                        $url = "Index/index";
                    }
                    redirect($url);
                }
            }else{
                if(!empty($url)) {
                    $this->url = urlencode($url);   
                }
                $this->display();
            }
        }
    }

    public function logout(){
        if(is_login()){
            destory_session();
            $this->success('退出成功！', U('index'),3);
        } else {
            $this->redirect('Login/index');
        }
    }


    public function verify(){
        $config = array(
            'imageW' => 260, 
            'imageH' => 60, 
            'useCurve'=> false,
            'fontSize'=> 28
            );
        $verify = new \Think\Verify($config);
        $verify->entry(1);
    }

    public function signup($username = '', $password = '', $email = '', $mobile ='', $nickname ='', $verify = ''){
        if(C('USER_ALLOW_REGISTER') == 0){
            $this->error('注册已关闭');
        }
        if(IS_POST){ //注册用户
            /* 检测验证码 */
            if(!check_verify($verify)){
                $this->error('验证码输入错误！');
            }
            $User = A('User','Api');
            $uid = $User->register($username, $password, $nickname, $email, $mobile);
            if(0 < $uid){ //注册成功
                //TODO: 发送验证邮件
                $this->success('注册成功！','index', 3);
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }

        } else { //显示注册表单
            $this->display();
        }
    }

    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在20个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在4-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            case -12: $error = '真实姓名必须填写！'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }

    public function changepwd(){
      if ( !is_login() ) {
         $this->error( '您还没有登陆',U('User/login') );
      }
      
      if(IS_POST){
         $old        =  I('password');
         $new        =  I('new_password');
         $confirm    =  I('new_password_confirm');
         empty($old) && $this->msg = '请输入原密码';
         empty($new) && $this->msg = '请输入新密码';
         empty($confirm) && $this->msg = '请输入确认密码';

         if($new!==$confirm){
            $this->msg = '两次密码输入不一致';
         }
         if(!isset($this->msg)){
            $data['password'] = $new;
            $uid = is_login();
            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $old, $data);
            if($res['status']){
                $this->success('修改密码成功！');
            }else{
                $this->error($this->showRegError($res['info']));
            }
         }
      }
      $this->display();

   }

}