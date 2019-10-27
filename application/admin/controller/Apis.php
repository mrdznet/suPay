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

use think\Controller;
use think\Db;
use think\log;
use app\admin\model\Merchant;
use app\admin\model\Device;
use app\admin\model\OrderModel;
use app\admin\model\SystemConfigModel;
use app\admin\model\AccountUrlModel;
use app\admin\model\Helper;
use think\Request;
class Apis extends Controller
{

    /**
     * 是否需要新的收款连接
     *
     * @return string
     */
    public function isNeedQrcode()
    {
        $need = ['time' => $this->echotime(), 'timeout' => 60];
        return json_encode($need);
    }

    public function echotime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
    public function servertoclient(){
        $data    = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息
        $res = socketServerToClient($message['clientid'],$message['msg']);
        echo $res;
    }
    public function demodemo1(){
      echo time();


    }

}
