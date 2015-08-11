<?php
namespace Wms\Api;
use Think\Controller;
class UserApi extends Controller{
    /**
     * 构造方法，实例化操作模型
     */
    protected function _initialize(){
        $this->model = D('User');
        define('AUTH_KEY', C('AUTH_KEY'));
    }

    /**
     * 注册一个新用户
     * @param  string $username 用户名
     * @param  string $password 用户密码
     * @param  string $email    用户邮箱
     * @param  string $mobile   用户手机号码
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($username, $password, $nickname, $email, $mobile = ''){
        $data = array(
            'username' => $username,
            'password' => $password,
            'nickname' => $nickname,
            'email'    => $email,
            'mobile'   => $mobile,
        );

        //验证手机
        if(empty($data['mobile'])) unset($data['mobile']);

        /* 添加用户 */
        if($this->model->create($data)){
            $uid = $this->model->add();
            return $uid ? $uid : 0; //0-未知错误，大于0-注册成功
        } else {
            return $this->model->getError(); //错误详情见自动验证注释
        }
    }

    /**
     * 用户登录认证
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($username, $password, $type = 1){
        $map = array();
        switch ($type) {
            case 1:
                $map['username'] = $username;
                break;
            case 2:
                $map['email'] = $username;
                break;
            case 3:
                $map['mobile'] = $username;
                break;
            case 4:
                $map['id'] = $username;
                break;
            default:
                return 0; //参数错误
        }

        /* 获取用户数据 */
        $user = $this->model->where($map)->find();
        if(is_array($user) && $user['status']){
            /* 验证用户密码 */
            if(auth_md5($password, AUTH_KEY) !== $user['password']){
                //$this->updateLogin($user['id']); //更新用户登录信息
                return $user['id']; //登录成功，返回用户ID
            } else {
                return -2; //密码错误
            }
        } else {
            return -1; //用户不存在或被禁用
        }
    }

    /**
     * 获取用户信息
     * @param  string  $uid         用户ID或用户名
     * @param  boolean $is_username 是否使用用户名查询
     * @return array                用户信息
     */
    public function info($uid, $is_username = false){
        $map = array();
        if($is_username){ //通过用户名获取
            $map['username'] = $uid;
        } else {
            $map['id'] = $uid;
        }

        $user = $this->where($map)->field('id,username,email,mobile,status')->find();
        if(is_array($user) && $user['status'] = 1){
            return array($user['id'], $user['username'], $user['email'], $user['mobile']);
        } else {
            return -1; //用户不存在或被禁用
        }
    }

    /**
     * 检测用户名
     * @param  string  $field  用户名
     * @return integer         错误编号
     */
    public function checkUsername($username){
        return $this->model->checkField($username, 1);
    }

    /**
     * 检测邮箱
     * @param  string  $email  邮箱
     * @return integer         错误编号
     */
    public function checkEmail($email){
        return $this->model->checkField($email, 2);
    }

    /**
     * 检测手机
     * @param  string  $mobile  手机
     * @return integer         错误编号
     */
    public function checkMobile($mobile){
        return $this->model->checkField($mobile, 3);
    }

    /**
     * 更新用户信息
     * @param int $uid 用户id
     * @param string $password 密码，用来验证
     * @param array $data 修改的字段数组
     * @return true 修改成功，false 修改失败
     */
    public function updateInfo($uid, $password, $data){
        if(empty($uid) || empty($password) || empty($data)){
            $this->error = '参数错误！';
            $res = false;
        }

        //更新前检查用户密码
        if(!$this->verifyUser($uid, $password)){
            $this->error = '验证出错：密码不正确！';
            $res = false;
        }

        //更新用户信息
        $data = M('user')->create($data);

        if($data){
            $data['password'] = auth_md5($data['password'], AUTH_KEY);
            $res = M('user')->where(array('id'=>$uid))->save($data);
        }
        //$res = false;
        if($res !== false){
            $return['status'] = true;
        }else{
            $return['status'] = false;
            $return['msg'] = '修改密码错误';
        }
        return $return;
    }
    public function verifyUser($uid, $password_in){
        $map['id'] = $uid;
        $password = M('user')->where($map)->field('password')->find();
        unset($map);

        $password = $password['password'];
        if(auth_md5($password_in, AUTH_KEY) === $password){
            return true;
        }
        return false;
    }
}
