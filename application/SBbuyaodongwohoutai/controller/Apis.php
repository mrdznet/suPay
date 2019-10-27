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
namespace app\SBbuyaodongwohoutai\controller;

use think\Controller;
use think\Db;
use think\log;
use app\SBbuyaodongwohoutai\model\Merchant;
use app\SBbuyaodongwohoutai\model\Device;
use app\SBbuyaodongwohoutai\model\OrderModel;
use app\SBbuyaodongwohoutai\model\SystemConfigModel;
use app\SBbuyaodongwohoutai\model\AccountUrlModel;
use app\SBbuyaodongwohoutai\model\Helper;
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
