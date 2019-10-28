<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/25
 * Time: 11:38
 */
namespace app\SBbuyaodongwohoutai\controller;


use app\SBbuyaodongwohoutai\model\RoleModel;
use app\SBbuyaodongwohoutai\model\Merchant as MerchantModel;

class Merchant extends Base
{
    //商户列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['merchant_id'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $MerchantModel = new MerchantModel();
            $selectResult = $MerchantModel->getMerchantByWhere($where, $offset, $limit);


            // 拼装参数
            foreach($selectResult as $key=>$vo){

//                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
//                $selectResult[$key]['status'] = $status[$vo['status']];


                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $MerchantModel->getAllUsers($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 添加商户  merchant  & user
    public function merchantAdd()
    {
        if(request()->isPost()){

            $param = input('post.');

//            $param['password'] = md5($param['password'] . config('salt'));
            $md5Token = create_code().create_code();
            $param['token'] = md5($md5Token);
            $MerchantModel = new MerchantModel();
            $flag = $MerchantModel->insertMerchant($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

//        $role = new RoleModel();
//        $this->assign([
//            'role' => $role->getRole(),
//            'status' => config('user_status')
//        ]);

        return $this->fetch();
    }

    // 编辑商户 merchant
    public function merchantEdit()
    {
        $MerchantModel = new MerchantModel();

        if(request()->isPost()){

            $param = input('post.');

            $flag = $MerchantModel->editMerchant($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $MerchantModel = new MerchantModel();

        $this->assign([
            'merchant' => $MerchantModel->getOneMerchant($id)
        ]);
        return $this->fetch();
    }

    // 删除用户
    public function merchantDel()
    {
        $id = input('param.id');

        $MerchantModel = new MerchantModel();
        $flag = $MerchantModel->delMerchant($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '编辑' => [
                'auth' => 'merchant/merchantedit',
                'href' => url('merchant/merchantedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'merchant/merchantdel',
                'href' => "javascript:merchantDel(" .$id .")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
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
