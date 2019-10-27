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
namespace app\api\controller;


use think\Controller;
use app\admin\model\RoleModel;
use think\Db;

class Alipay extends Controller
{
    //此接口给提供给设备绑定支付宝id
    public function bindAlipayFriend()
    {
        $data            = @file_get_contents('php://input');
        $message         = json_decode($data, true);
        $db              = new Db();
        $device_id       = $message['device_id'];
        $userid          = $message['userid'];
        $where['userid'] = $userid;
        $userres         = $db::table('s_bind_alipay_friend')->where($where)->find();
        if ($userid == "" || $device_id == "") {
            $returnmessage['code'] = 9998;
            $returnmessage['msg']  = "Parameter Completion";
            return json_encode($returnmessage, true);
        }
        if ($userres) {
            $returnmessage['code'] = 9997;
            $returnmessage['msg']  = "userid Already exist";
            return json_encode($returnmessage, true);
        }
        $insertData['device_id'] = $device_id;
        $insertData['userid']    = $userid;
        $res                     = $db::table('s_bind_alipay_friend')->insert($insertData);
        if ($res) {
            $returnmessage['code'] = 100000;
            $returnmessage['msg']  = "success";
        } else {
            $returnmessage['code'] = 9999;
            $returnmessage['msg']  = "failed";
        }
        return json_encode($returnmessage, true);
    }
}
