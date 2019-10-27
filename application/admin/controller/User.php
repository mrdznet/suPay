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


use app\admin\model\RoleModel;
use app\admin\model\UserModel;
use think\Db;
class User extends Base
{
    //用户列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['user_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new UserModel();
            $selectResult = $user->getUsersByWhere($where, $offset, $limit);

            $status = config('user_status');

            // 拼装参数
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['status'] = $status[$vo['status']];

                if( 1 == $vo['id'] ){
                    $selectResult[$key]['operate'] = '';
                    continue;
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id'],''));
            }

            $return['total'] = $user->getAllUsers($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
//代理列表
    public function proxy()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['user_name'] = ['like', '%' . $param['searchText'] . '%'];

            }
            $where['role_id'] = 13;
            $user = new UserModel();
            $selectResult = $user->getUsersByWhere($where, $offset, $limit);

            $status = config('user_status');

            // 拼装参数
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['status'] = $status[$vo['status']];
                //拥有人头数量 user_name
                $whereTotalcard['proxy_id'] = $vo['user_name'];
                $db = new Db();

                $Totalcard = $db::table('s_device')->where($whereTotalcard)->count();
                $selectResult[$key]['Totalcard'] = $Totalcard;
                // 名下所有卡内余额
                $post_balancesum = $db::table('s_device')->where($whereTotalcard)->sum('post_balance');
                $selectResult[$key]['post_balancesum'] = $post_balancesum;

                if( 1 == $vo['id'] ){
                    $selectResult[$key]['operate'] = '';
                    continue;
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id'],$vo['user_name']));
            }

            $return['total'] = $user->getAllUsers($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $db = new Db();
        $sumpost_balance = $db::table('s_device')->sum('post_balance');
        $this->assign('sumpost_balance',$sumpost_balance);
        return $this->fetch();
    }

    // 添加用户
    public function userAdd()
    {
        if(request()->isPost()){

            $param = input('post.');

            $param['password'] = md5($param['password'] . config('salt'));
            $param['head'] = '/static/admin/images/profile_small.jpg'; // 默认头像

            $user = new UserModel();
            $flag = $user->insertUser($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $role = new RoleModel();
        $this->assign([
            'role' => $role->getRole(),
            'status' => config('user_status')
        ]);

        return $this->fetch();
    }
    // 添加用户
    public function proxyAdd()
    {
        if(request()->isPost()){

            $param = input('post.');

            $param['password'] = md5($param['password'] . config('salt'));
            $param['head'] = '/static/admin/images/profile_small.jpg'; // 默认头像

            $user = new UserModel();
            $flag = $user->insertUser($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }


        $role = new RoleModel();
        $this->assign([
            'role' => $role->getRoleproxy(),
            'status' => config('user_status')
        ]);

        return $this->fetch();
    }


    // 编辑用户
    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isPost()){

            $param = input('post.');

            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5($param['password'] . config('salt'));
            }
            $flag = $user->editUser($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $role = new RoleModel();
        $merchantId = session('username');
        if($merchantId == 'nimdaistrator'){
           $channel = $role->getRole();
        }else{
            $channel = $role->getRoleproxy();
        }
        $this->assign([
            'user' => $user->getOneUser($id),
            'status' => config('user_status'),
            'role' => $channel
        ]);
        return $this->fetch();
    }

    // 删除用户
    public function userDel()
    {
        $id = input('param.id');

        $role = new UserModel();
        $flag = $role->delUser($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id,$username)
    {
        return [
            '设备' => [
                'auth' => 'user/useredit',
                'href' => url('bank/bankList', ['username' => $username]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '编辑' => [
                'auth' => 'user/useredit',
                'href' => url('user/userEdit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'user/userdel',
                'href' => "javascript:userDel(" .$id .")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ],
            '增加设备' => [
                'auth' => 'bank/createbank',
                'href' => url('bank/createbank', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '跑量详情' => [
                'auth' => 'index/indexPage',
                'href' => url('index/indexPage', ['username' => $username]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ]

        ];
    }
    //potato 验证
    public function potato_sendcode(){
        $user = $_POST['user'];
        setcode($user);
        $return = array('code' => $_COOKIE['code'], );
        $this->ajaxReturn($return);
    }
}
