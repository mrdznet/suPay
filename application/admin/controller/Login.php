<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use think\Db;
use app\admin\model\RoleModel;
use app\admin\model\UserModel;
use think\Controller;
use org\Verify;
use think\Cookie;
use think\Session;

class Login extends Controller
{
    // 登录页面
    public function index()
    {
        return $this->fetch('/login');
    }

    // 登录操作
    public function doLogin()
    {
        $userName = input("param.user_name");
        $password = input("param.password");
        $code = input("param.code");

        $result = $this->validate(compact('userName', 'password', "code"), 'AdminValidate');
        if(true !== $result){
            return json(msg(-1, '', $result));
        }

        $verify = new Verify();
        if (!$verify->check($code)) {
            return json(msg(-2, '', '验证码错误'));
        }

        $userModel = new UserModel();
        $hasUser = $userModel->checkUser($userName);

        if(empty($hasUser)){
            return json(msg(-3, '', '管理员不存在'));
        }

        if(md5($password . config('salt')) != $hasUser['password']){
            return json(msg(-4, '', '密码错误'));
        }

        if(1 != $hasUser['status']){
            return json(msg(-5, '', '该账号被禁用'));
        }

        session('username', $hasUser['user_name']);
        session('id', $hasUser['id']);
        session('head', $hasUser['head']);
        session('role', $hasUser['role_name']);  // 角色名
        session('role_id', $hasUser['role_id']);
        session('rule', $hasUser['rule']);

        // 更新管理员状态
        $param = [
            'login_times' => $hasUser['login_times'] + 1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time()
        ];

        $res = $userModel->updateStatus($param, $hasUser['id']);
        if(1 != $res['code']){
            return json(msg(-6, '', $res['msg']));
        }
        // ['code' => 1, 'data' => url('index/index'), 'msg' => '登录成功']
        return json(msg(1, url('index/index'), '登录成功'));
    }

    // 登录操作
    public function doLoginNew()
    {
        $userName = input("param.user_name");
        $password = input("param.password");

        $result = $this->validate(compact('userName', 'password'), 'AdminValidate');
        if(true !== $result){
            return json(msg(-1, '', $result));
        }

        $userModel = new UserModel();
        $hasUser = $userModel->checkUser($userName);

        if(empty($hasUser)){
            return json(msg(-3, '', '管理员不存在'));
        }

        if(md5($password . config('salt')) != $hasUser['password']){
            return json(msg(-4, '', '密码错误'));
        }

        if(1 != $hasUser['status']){
            return json(msg(-5, '', '该账号被禁用'));
        }
        //发送potato
//        $sendPotato = true;
        //使用 username 查找 potatoid
        $db = new Db();
        $token = $db::table('s_user')->where('user_name','=',$userName)->find();
        if($token){
            setcode($userName,$token['postao_id']);
            return json(msg(1, '', '请输入potato验证码！'));
        }else{
            return json(msg(1, '', '此用户没有权限，如需登录请联系管理员！'));
        }


        //获取potato验证码
    }

    //输入验证码确认登陆
    public function loginNewQuit()
    {
        $userName = input("param.user_name");
        $password = input("param.password");
        $potatoCode = input("param.potatoCode");

        $result = $this->validate(compact('userName', 'password'), 'AdminValidate');
        if(true !== $result){
            return json(msg(-1, '', $result));
        }
        //
//        if($potatoCode == ""){
//            return json(msg(-1, '', 'potato验证码不能为空！'));
//        }

        $userModel = new UserModel();
        $hasUser = $userModel->checkUser($userName);
        if(empty($hasUser)){
            return json(msg(-3, '', '管理员不存在'));
        }

        if(md5($password . config('salt')) != $hasUser['password']){
            return json(msg(-4, '', '密码错误'));
        }

        if(1 != $hasUser['status']){
            return json(msg(-5, '', '该账号被禁用'));
        }

        //验证potato验证码
        $realCode = Session::get('code');
//        return json(msg(-7, '', '系统验证码：'.$realCode.'输入的的验证码'.$potatoCode));
        if($realCode == ''){
            return json(msg(-7, '', '请先获取验证码！'));
        }
        if($potatoCode == ''){
            return json(msg(-7, '', '请输入验证码！'));
        }
            if($realCode!= $potatoCode ){
                return json(msg(-7, '', 'potato验证码错误！'));
            }


        session('username', $hasUser['user_name']);
        session('id', $hasUser['id']);
        session('head', $hasUser['head']);
        session('role', $hasUser['role_name']);  // 角色名
        session('role_id', $hasUser['role_id']);
        session('rule', $hasUser['rule']);

        // 更新管理员状态
        $param = [
            'login_times' => $hasUser['login_times'] + 1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time()
        ];

        $res = $userModel->updateStatus($param, $hasUser['user_name']);
        if(1 != $res['code']){
            return json(msg(-6, '', $res['msg']));
        }
        return json(msg(1, url('index/index'), '登录成功'));

    }

    // 验证码
    public function checkVerify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->length = 4;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }

    // 退出操作
    public function loginOut()
    {
        session('username', null);
        session('id', null);
        session('head', null);
        session('role', null);  // 角色名
        session('role_id', null);
        session('rule', null);
        session('code', null);

        $this->redirect(url('index'));
    }
}
