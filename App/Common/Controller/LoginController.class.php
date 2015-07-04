<?php
namespace Common\Controller;
use Think\Controller;
class LoginController extends Controller {
    protected function _initialize(){
        if(!check_maintain()){
            destory_session();
            layout(FALSE);
            $this->display('Common@Index/closed');
            exit();
        }
    }

    public function index($username = null, $password = null, $verify = null){

        if(IS_POST){
            /* 检测验证码  */
            /*if(!check_verify($verify)){
                $this->error('验证码输入错误！');
            }*/
            $this->username=$username;
            $User = D('Common/User','Api');
            $uid = $User->login($username, $password);
            if(0 < $uid){
                set_session($uid);
                $url = I('post.url');
                if(empty($url)) {
                    $this->success('登录成功！', '/Index/index',3);
                }
                else {
                    $url = urldecode($url);
                    if($url == '/Login/index' || strpos($url, '/Login/wh') !== false) {
                        $url = "/Index/index";
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
                    if($url == '/Login/index' || strpos($url, '/Login/wh') !== false) {
                        $url = "/Index/index";
                    }
                    $this->redirect($url);
                }
            }else{
                
                if(!empty($url)) {
                    $this->url = urlencode($url);   
                }
                $this->display('Common@Login/index');
            }
        }
    }

    public function logout(){
        if(is_login()){
            destory_session();
        }   
        $this->redirect('/Login/index');
    }

    public function wh() {
        $id = I('get.id/d',0);
        $A = A('Warehouse');
        $whs = $A->get_list('Warehouse','id,name');
        if(array_key_exists($id, $whs)) {
            $auth = session('user');
            $auth['wh_id'] = $id;
            session('user',$auth); 
            session('user_auth_sign', data_auth_sign($auth));
        }
        $this->success('切换成功');
    }

    public function verify(){
        $config = array(
            'imageW' => 260, 
            'imageH' => 60, 
            'useCurve'=> false,
            'fontSize'=> 28
            );
        $verify = new \Think\Verify($config);
        ob_clean();
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
            $User = A('Common/User','Api');
            $uid = $User->register($username, $password, $nickname, $email, $mobile);
            if(0 < $uid){ //注册成功
                //TODO: 发送验证邮件
                $this->success('注册成功！','index', 3);
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }

        } else { //显示注册表单
            $this->display('Common@Login/signup');
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

         $Api = A('Common/User','Api');
         $res = $Api->verifyUser(session('user.uid'),$old);
         if(!$res){
            $this->msg = '原密码输入错误';
         }
         unset($Api);
         unset($res);

         if($new!==$confirm){
            $this->msg = '两次密码输入不一致';
         }
         if(!isset($this->msg)){
            $data['password'] = $new;
            $uid = is_login();
            //$Api = new UserApi();
            $Api = A('User','Api');
            $res = $Api->updateInfo($uid, $old, $data);
            if($res['status']){
                $this->success('修改密码成功！');
                return true;
            }else{
                $this->error($this->showRegError($res['info']));
            }
         }
      }
      $this->display('Login/changepwd',false);

   }

}