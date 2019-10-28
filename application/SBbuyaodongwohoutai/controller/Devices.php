<?php
/**
 * Created by sublime.
 * User: xdm
 * Date: 2018/12/12
 * Time: 14:00
 */

namespace app\SBbuyaodongwohoutai\controller;

use app\api\controller\Orderinfo;
use think\Db;
use think\Controller;
use app\SBbuyaodongwohoutai\model\Device;
use app\SBbuyaodongwohoutai\model\OrderModel; //订单model
use app\SBbuyaodongwohoutai\model\Merchant;
use app\SBbuyaodongwohoutai\model\Ali;
use app\SBbuyaodongwohoutai\model\OrderCallback;
use think\Request;


class Devices extends Controller
{

    //回调数据入库
    public function merchantCallbackData($orderdata)
    {
        $calldata = $orderdata;
        unset($calldata['id']);
//        unset($calldata['order_status']);
        $OrderCallback = new OrderCallback;
        $OrderCallback->data($calldata);
        $res = $OrderCallback->save();
        return $res;
    }

    //封装 商户回调数据
    public function callbackdata($message, $orderdata, $merchant_id)
    {
        $da["status"] = "10000";// 支付成功
        $da["time"] = $message['time'];// "订单支付时间";@todo 确认是否有
        $da["trade_amount"] = $orderdata['amount'];//"订单金额";
        $da["receipt_amount"] = $message['amount'];//"实际支付金额";
        $da["order_no"] = $orderdata['order_no'];//"商户订单号";
//        $da["trade_no" ]       = $message['transferNo']; //"支付宝订单号";
        $token = Merchant::field('token')->where('merchant_id', '=', $merchant_id)->find()['token'];
//        echo $token;
        $da["sig"] = md5($merchant_id . $token . $da["trade_amount"] . $message['amount'] . $da["time"]);
        return json_encode($da);
    }

    /**
     * 手动回调 momo
     * @param $orderdata
     * @param $amount
     * @return string
     */
    public function manualCallBackForMoMo($orderdata, $amount)
    {
        //手动回调
        $db = new Db();
        $headers = ['Content-Type:application/json'];
        $merchant_id = $orderdata['merchant_id'];
        $callbackdata = $this->callbackdata3($amount*1.005, $orderdata, $merchant_id);
        $notify_url = $orderdata['notify_url'];  //回调地址
        $callback_result = cUrlGetData($notify_url, $callbackdata, $headers);//请求回调
        if ($callback_result != 'success') {
            return "回调总后台失败";
            exit;
        }
        try {
            $db::startTrans();//开启事务
            $orderres = $db::table('s_order')->where('id', '=', $orderdata['id'])//'payable_amount'=>$message['amount'] 索金额备用
            ->update(
                [
                    'order_status' => 5,
                    'actual_amount' => $amount,
                    'pay_time' => time(),
                    'time_update' => time(),
                    'notify_user' => session('username')
//                'msgid'=>$message['msg_id'],
//                'mac_id'=>$message['name']
                ]
            );//修改订单状态
//            echo $db::table('s_order')->getLastSql();
//            $amounts =$match[4];
            $bankres = $db::table('s_device')->where('card', $orderdata['card'])
                ->update([
                    'total_money' => Db::raw("total_money+$amount"),//'total_sum+'.$amounts
                    'today_money' => Db::raw("today_money+$amount"),
                ]);//修改支付宝日额 和 总额
//收款成功 加入银行卡绑定表
//            echo $db::table('s_device')->getLastSql();
            $insertData['receivables'] =$orderdata['card'];
            $insertData['payuserid'] =$orderdata['payuserid'];
            $count = $db::table('s_receivables_bind')->where($insertData)->find();
            if(!$count){
                $bindres = $db::table('s_receivables_bind')->insert($insertData);
            }else{
                $bindres = $db::table('s_receivables_bind')->where('id','=',$count['id'])
                    ->update([
                        'number_of_use' => Db::raw("number_of_use+1"),//'total_sum+'.$amounts
                    ]);
            }
//            echo $db::table('s_receivables_bind')->getLastSql();

            if ($orderres == 1 && $bankres == 1 &&$bindres!=0) {
                //所有操作完成提交事务
                $db::commit();

                return "success";
            } else {
                $db::rollback();
                return "order update error";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return "try error";
        }

    }

    /**
     * 手动回调
     * @param $orderdata
     * @param $amount
     * @return string
     */
    public function manualCallBack($orderdata, $amount)
    {
        //手动回调
        $db = new Db();
        $headers = ['Content-Type:application/json'];
        $merchant_id = $orderdata['merchant_id'];
        $callbackdata = $this->callbackdata3($amount, $orderdata, $merchant_id);
        dump($callbackdata);die();
        $notify_url = $orderdata['notify_url'];  //回调地址
        $callback_result = cUrlGetData($notify_url, $callbackdata, $headers);//请求回调
        if ($callback_result != 'success') {
            return "callback error ";
            exit;
        }
        try {
            $db::startTrans();//开启事务
            $orderres = $db::table('s_order')->where('id', '=', $orderdata['id'])//'payable_amount'=>$message['amount'] 索金额备用
            ->update(
                [
                    'order_status' => 5,
                    'actual_amount' => $amount,
                    'time_update' => time(),
                    'notify_user' => session('username')
//                'msgid'=>$message['msg_id'],
//                'mac_id'=>$message['name']
                ]
            );//修改订单状态
//            echo $db::table('s_order')->getLastSql();
//            $amounts =$match[4];
//            $bankres = $db::table('s_bank_device')->where('bank_card', $orderdata['card'])
//                ->update([
//                    'total_money' => Db::raw("total_money+$amount"),//'total_sum+'.$amounts
//                    'today_money' => Db::raw("today_money+$amount"),
//                ]);//修改支付宝日额 和 总额

//           echo $db::table('s_bank_device')->getLastSql();exit;
//            echo $bankres;
            if ($orderres == 1) {
                //所有操作完成提交事务
                $db::commit();
//                $orderdata['actual_amount'] = $amount;
//                $orderdata['tid']           = 'ali001';
//                $orderdata['order_status']  = 1;
//                $notify_data                = json_encode($orderdata);
//                $main_url                   = 'http://129.204.132.45:8000/api/order/notify';
//                $res                        = cUrlGetData($main_url, $notify_data, $headers);
//                if ($callback_result == 'success') {
//                    $orderdata['order_status'] = 1; //回调状态  成功
//                } else {
//                    $orderdata['order_status'] = 0;//回调状态 失败
//                }
//                $orderdata['actual_amount'] = $amount;
////                    $orderdata['ali_order'] = $message['transferNo'];
//                $this->merchantCallbackData($orderdata);//回调数据入库
                return "success";
            } else {
                $db::rollback();
                return "order update error";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return "try error";
        }

    }

    public function callbackdata2($match, $orderdata, $merchant_id)
    {
        $da["status"] = "10000";// 支付成功
        $da["time"] = time();// "订单支付时间";@todo 确认是否有
        $da["trade_amount"] = $orderdata['amount'];//"订单金额";
        $da["receipt_amount"] = $match;//"实际支付金额";
        $da["order_no"] = $orderdata['order_no'];//"商户订单号";
        $token = Merchant::field('token')->where('merchant_id', '=', $merchant_id)->find()['token'];
        $da["sig"] = md5($merchant_id . $token . $da["trade_amount"] . $da["receipt_amount"] . $da["time"]);
        return json_encode($da);
    }

    public function callbackdata3($amount, $orderdata, $merchant_id)
    {
        $da["status"] = "10000";// 支付成功
        $da["time"] = time();// "订单支付时间";@todo 确认是否有
        $da["trade_amount"] = $orderdata['amount'];//"订单金额";
        $da["receipt_amount"] = $amount;//"实际支付金额";
        $da["order_no"] = $orderdata['order_no'];//"商户订单号";
//        $da["trade_no" ]       = $message['transferNo']; //"支付宝订单号";
        $token = Merchant::field('token')->where('merchant_id', '=', $merchant_id)->find()['token'];
//        echo $token;
        $da["sig"] = md5($merchant_id . $token . $da["trade_amount"] . $da["receipt_amount"] . $da["time"]);
        return json_encode($da);
    }

    /**
     * 付呗付款回调
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function zypaycallback()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息

        if (!isset($message['order_momo']) || empty($message['order_momo'])) {
            return "order_momo:不能为空";
        }
        if (!isset($message['channel']) || empty($message['channel'])) {
            return "channel:不能为空";
        }
        if (!isset($message['phone']) || empty($message['phone'])) {
            return "phone:不能为空";
        }

        $db = new Db;
        //根据付呗号匹配到订单 将状态改变
        //查找一条订单信息(防止重复回调，只查询3、4)  order_status => 3 || order_status =>4 ['order_momo' => $message['order_momo']];
        $order = $db::table('s_ju_order')
            ->where('order_momo', '=', $message['order_momo'])
            ->where('order_status', '>=', 3)
            ->where('order_status', '<=', 4)
            ->find();
        //无金额
        $amount = 0;
        if (isset($message['amount']) && !empty($message['amount'])) {
            $amount = $message['amount'];
        } else {
            $amount = $order['amount'];
        }
        if (empty($order)) {//匹配不到订单 直接存入掉单表
            $insertData = [
                'actual_amount' => $amount,//实际支付金额
                'studio_id' => $message['channel'],//渠道
                'account' => $message['phone'],
                'order_momo' => $message['order_momo'],
                'create_time' => time(),
            ];
            $db::table('s_lose_orders')->insert($insertData);
            return 'success';
        }

        //第一时间回调给商户
        $headers = ['Content-Type:application/json'];
        $callbackdata = $this->callbackdata2($amount, $order, $order['merchant_id']);
        $callback_result = cUrlGetData($order['notify_url'], $callbackdata, $headers);//请求回调
        $level = 1;
        if ($callback_result != 'success') {
            $level = 2;
        }

        try {
            //开启事务
            $db::startTrans();
            //1、修改订单状态
            $now = time();
            $orderRes = $db::table('s_ju_order')->where('order_momo', $message['order_momo'])
                ->update(
                    [
                        'order_status' => 1,
                        'actual_amount' => $amount,
                        'time_update' => time(),
                        'pay_time' => $now
                    ]
                );
            //2、添加设备收款额度
            $bankRes = $db::table('s_momo_device')->where('phone', $message['phone'])
                ->update(
                    [
                        'total_money' => Db::raw("total_money + $amount"),
                        'today_money' => Db::raw("today_money+$amount"),
                    ]
                );
            if ($orderRes && $bankRes) {
                //所有操作完成提交事务
                $db::commit();
                $this->merchantCallbackData($order);//回调数据入库
                return "success";
            } else {
                $db::rollback();
                return "error1";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return $e->getMessage();
        }
    }

    public function upPaymentQrcode()
    {
        $db = new Db();
        if (request()->isPost()) {
            $param = input('post.');
            if ($param['phonenumber'] == '' || $param['paymenturl'] == '' || $param['paymenturl'] == 'false') {//

                return json(msg(-1, '', '请填写完整信息'));
            }
            $paymentcode = $db::table('s_bank_device')->where('pay_url', '=', $param['paymenturl'])->find();
            if ($paymentcode && $paymentcode['id'] != $param['id']) {
                return json(msg(-1, '', '此收款二维码已经存在'));
            }

            $ress = $db::table('s_bank_device')->where('id', '=', $param['id'])->update(
                [
                    'pay_url' => $param['paymenturl'],
                ]
            );//修改订单状态

            if ($ress !== false) {
                return json(msg(1, url('Bankdevice/index'), '添加配置成功'));
            }
//            dump($param);
            return json(msg(-1, url('Bankdevice/index'), $param['id']));
        }
        $id = input('param.id');
        $bank = DB::table('s_bank_device')->where('id', $id)->find();
//        dump($bank);exit;
        $this->assign(
            [
                'bank' => $bank
            ]
        );
        return $this->fetch();
    }

    public function uploadImg()
    {
        if (request()->isAjax()) {

            $file = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
            if ($info) {
                $src = '/upload' . '/' . date('Ymd') . '/' . $info->getFilename();
                $paymenturl = $this->readCode($src);
                return json(msg(0, ['src' => $src, 'paymenturl' => $paymenturl], ''));
            } else {
                // 上传失败获取错误信息
                return json(msg(-1, '', $file->getError()));
            }
        }
    }

    public function readCode($src)
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../qrReader/lib/QrReader.php';
        $qrcode = new \QrReader(ROOT_PATH . 'public' . $src);  //图片路径
        $text = $qrcode->text(); //返回识别后的文本
        return $text;
    }

    public function aa()
    {
        header('Location: https://qr.95516.com/01041220/f9688f32-d447-4ab7-8583-7b82617dd8f3');
    }
    /**
     * 付呗付款回调
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderCallbackForMoMo()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息

        if (!isset($message['order_momo']) || empty($message['order_momo'])) {
            return "order_momo:不能为空";
        }
        if (!isset($message['channel']) || empty($message['channel'])) {
            return "channel:不能为空";
        }
        if (!isset($message['phone']) || empty($message['phone'])) {
            return "phone:不能为空";
        }

        $db = new Db;
        //根据付呗号匹配到订单 将状态改变
        //查找一条订单信息(防止重复回调，只查询3、4)  order_status => 3 || order_status =>4 ['order_momo' => $message['order_momo']];
        $order = $db::table('s_ju_order')
            ->where('order_momo', '=', $message['order_momo'])
            ->where('order_status', '>=', 3)
            ->where('order_status', '<=', 4)
            ->find();
        //无金额
        $amount = 0;
        if (isset($message['amount']) && !empty($message['amount'])) {
            $amount = $message['amount'];
        } else {
            $amount = $order['amount'];
        }
        if (empty($order)) {//匹配不到订单 直接存入掉单表
            $insertData = [
                'actual_amount' => $amount,//实际支付金额
                'studio_id' => $message['channel'],//渠道
                'account' => $message['phone'],
                'order_momo' => $message['order_momo'],
                'create_time' => time(),
            ];
            $db::table('s_lose_orders')->insert($insertData);
            return 'success';
        }

        //第一时间回调给商户
        $headers = ['Content-Type:application/json'];
        $callbackdata = $this->callbackdata2($amount, $order, $order['merchant_id']);
        $callback_result = cUrlGetData($order['notify_url'], $callbackdata, $headers);//请求回调
        $level = 1;
        if ($callback_result != 'success') {
            $level = 2;
        }

        try {
            //开启事务
            $db::startTrans();
            //1、修改订单状态
            $now = time();
            $orderRes = $db::table('s_ju_order')->where('order_momo', $message['order_momo'])
                ->update(
                    [
                        'order_status' => 1,
                        'actual_amount' => $amount,
                        'time_update' => time(),
                        'pay_time' => $now
                    ]
                );
            //2、添加设备收款额度
            $bankRes = $db::table('s_momo_device')->where('phone', $message['phone'])
                ->update(
                    [
                        'total_money' => Db::raw("total_money + $amount"),
                        'today_money' => Db::raw("today_money+$amount"),
                    ]
                );
            if ($orderRes && $bankRes) {
                //所有操作完成提交事务
                $db::commit();
                $this->merchantCallbackData($order);//回调数据入库
                return "success";
            } else {
                $db::rollback();
                return "error1";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return $e->getMessage();
        }
    }
    /**
     * 猫池回调
     */
    public function CatPoolCallbackOrderCallback(){
        $data    = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息
        $amounts = $message['amount'];
        $db      = new Db;
        //根据回调信息 匹配订单
        $where['card'] = $message['card'];//payable_amount
        $where['payable_amount'] = $message['amount'];//payable_amount
        $where['order_status'] = 0;//payable_amount
//        $where['time'] = $message['time'];//payable_amount
        $order   = $db::table('s_order')->where($where)->find();

//        $logResult = cUrlGetData($notify_url, $log_message, $headers);//写入日志
//		var_dump($logResult);
//        $notime = time() - 30;
//        if ($message['time'] < $notime) {//如果为垃圾短信或者金额为11.11 直接跳过
//            return "success";
//        }
        if (!$order) {//匹配不到订单 直接存入掉单表
            return 'success';
        }
        //第一时间回调给商户
        $callbackdata    = $this->callbackdata2($amounts, $order, $order['merchant_id']);
        $headers     = ['Content-Type:application/json'];
        $callback_result = cUrlGetData($order['notify_url'], $callbackdata, $headers);//请求回调
        try {
            $db::startTrans();//开启事务
            $orderres = $db::table('s_order')->where('id', $order['id'])
                ->update(
                    [
                        'order_status'  => 1,
                        'actual_amount' => $amounts,
                        'time_update'   => time(),
                        'pay_time'   => time(),
                    ]
                );//修改订单状态
//            echo $db::getLastSql();die();
            $deviceRes = $db::table('s_device')->where('card', $message['card'])
                ->update([
                    'total_money' => Db::raw("total_money+$amounts"),//'total_sum+'.$amounts
                    'today_money' => Db::raw("today_money+$amounts"),
                    'lock_time' =>0
                ]);//修改支付宝日额 和 总额
            if ($orderres == 1&&$deviceRes==1) {  //&& $bankres == 1
                //所有操作完成提交事务
                $db::commit();
//                $order['actual_amount'] = $amounts;
//                $order['tid']           = 'ali001';
//                $order['order_status']  = 0;
//                if ($callback_result == 'success') {
//                    $order['order_status'] = 1; //回调状态  成功
//                }
//                $notify_data = json_encode($order);
//                $main_url    = 'http://129.204.132.45:8000/api/order/notify';
//                cUrlGetData($main_url, $notify_data, $headers);
//                $this->merchantCallbackData($order);//回调数据入库
                return "success";
            } else {
                $db::rollback();
                return "error1";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return "error2";
        }

    }
//咖啡短信回调
    public function frankMessageCallback(){
        $data    = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息
//        logs(json_encode(['ShortMessage' => $message['body']], 512), 'ShortMessage');
        $db      = new Db;
        $headers     = ['Content-Type:application/json'];
        $notify_url  = "http://120.24.89.76:8899/Api/Alipay/preg_ShortMessage";
        $postdata = json_encode(['message'=>$message['body']]);
        $getDta = cUrlGetData($notify_url, $postdata, $headers);
        $arraydatas = json_decode($getDta,true);//写入日志
        $match = $arraydatas['amount'];//var_dump($match['amount'][4]);die();
        //使用手机号 获取银行卡信息
        $card = $db::table('s_device')->field('id,channel,card')->where('phone', '=', $message['phone'])->find();
        //查询订单的where条件
        $where = ['card' => $card['card'], 'order_status' => 0, 'payable_amount' => $match[4]];
        //查找一条订单信息
        $order   = $db::table('s_order')->where($where)->find();
        $level   = 1;//默认日志级别为1
        $amounts = 0;
        $loseorder = array();
        if (empty($match)) {
            $level = 3;//如果正则匹配返回为空 则为垃圾短信
        } else {
            if (!$order) {
                //没有匹配到订单的 使用人名去查找可能符合的单
                $level = 2;//如果未匹配到订单 或者 金额未11.11 则未掉单或者是测试单
                $loseorder = $this->findLoseOrder($message['body'],$card['card'],$message['time']);
                if($loseorder){
                    $level = 4;
                }
            }
            $amounts = $match[4];
        }
        $log_message = [//拼装日志数据
            'Project_id'  => 49,
            'Device_id'   => empty($card['channel']) ? "没有获取到device" : $card['channel'],
            'Level'       => $level,
            'Create_time' => time(),
            'Log_content' => '短信内容:' . $data
        ];
        if($order){
            $log_message['Log_content'].= "单号：".$order['order_no'];
        }
        if($loseorder){
            $log_message['Log_content'].= "单号：".$loseorder['order_no'];
            $log_message['Log_content'].= "，potato返回结果：".$loseorder['result'];
        }
        $log_message = json_encode($log_message);
        $headers     = ['Content-Type:application/json'];
        $notify_url  = "http://121.14.88.136/Home/Log/create_log";
        cUrlGetData($notify_url, $log_message, $headers);//写入日志

        $notime = time() - 30;
        if ($level == 3 || $message['time'] < $notime ||$level == 4) {//如果为垃圾短信或者金额为11.11 直接跳过
            return "success";
        }
        if (!$order) {
            return 'success';
        }
        //第一时间回调给商户
        for ($x=0; $x<=5; $x++) {
            $callbackamount = $amounts*1.005;//回调加上千五
            $callbackdata    = $this->callbackdata2($callbackamount, $order, $order['merchant_id']);
            $callback_result = cUrlGetData($order['notify_url'], $callbackdata, $headers);//请求回调
            if($callback_result == "success"){
                break;
            }
            sleep(1);
        }

        try {
            $db::startTrans();//开启事务
            $orderres = $db::table('s_order')->where('id', $order['id'])
                ->update(
                    [
                        'order_status'  => 1,
                        'actual_amount' => $amounts,
                        'time_update'   => time(),
                        'pay_time'   => time(),
                    ]
                );//修改订单状态
            $bankres = $db::table('s_device')->where('id', $card['id'])
                ->update([
                    'total_money' => Db::raw("total_money+$amounts"),//'total_sum+'.$amounts
                    'today_money' => Db::raw("today_money+$amounts"),
                    'lock_time'=>0
                ]);//修改支付宝日额 和 总额
//收款成功 加入银行卡绑定表
            $insertData['receivables'] =$order['card'];
            $insertData['payuserid'] =$order['payuserid'];
            $count = $db::table('s_receivables_bind')->where($insertData)->find();
            if(!$count){
                $bindres = $db::table('s_receivables_bind')->insert($insertData);
            }else{
                $bindres = $db::table('s_receivables_bind')->where('id','=',$count['id'])
                    ->update([
                        'number_of_use' => Db::raw("number_of_use+1"),//'total_sum+'.$amounts
                    ]);
            }
            if ($orderres == 1&&$bankres==1&&$bindres!=0) {  //&& $bankres == 1                //所有操作完成提交事务
                $db::commit();
                $orderCallback['actual_amount'] = $amounts;
                $orderCallback['order_no']           = $order['order_no'];
                $orderCallback['order_status']  = 0;
                if ($callback_result == 'success') {
                    $order['order_status'] = 1; //回调状态  成功
                }
//                $notify_data = json_encode($order);
//                $main_url    = 'http://129.204.132.45:8000/api/order/notify';
//                cUrlGetData($main_url, $notify_data, $headers);
                $this->merchantCallbackData($order);//回调数据入库
                return "success";
            } else {
                $db::rollback();
                return "error1";
            }
        } catch (\Exception $e) {
            $db::rollback();
            return "error2";
        }

    }

    public function demo (){
        $data    = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息
        $messagess = $message['sms'];
        $match   = preg_message1($messagess);
        dump($match);

    }
    public function export(Request $request){//导出Excel
        $message = $request->param();
        $xlsName  = $message['channel'].$message['time']."（回调时间）设备收款详情";
        $selecttime = strtotime($message['time']);
        $end_time = $selecttime+86400;
        $xlsCell  = array(
            array('name','姓名'),
            array('card','卡号'),
            array('channel','渠道号'),
            array('sumAmount','收款总额'),
        );
        $db      = new Db;
        $xlsData  = $db::table('s_order')
            ->where("(order_status = 1 or order_status = 5 or order_status = 3)")
            ->where('pay_time','>=',$selecttime)
            ->where('pay_time','<',$end_time)
            ->where('merchant_id','<>',"test01")
            ->where('channel','=',$message['channel'])->Field('name,card,channel,sum(actual_amount) as sumAmount')->group('card,name,channel')->select();
        $excelData = [];
        foreach ($xlsData as $key=>$val){
            $excelData[$key]['name'] = $val['name'];
            $excelData[$key]['channel'] = $val['channel'];
            $excelData[$key]['card'] = "'".$val['card']."'";
            $excelData[$key]['sumAmount'] = $val['sumAmount'];
        }
        exportExcel($xlsName,$xlsCell,$excelData);


    }
    public function exportall(Request $request){//导出Excel
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');
        $message = $request->param();
        $xlsName  = "通用群昨日设备收款详情";
//        $selecttime = strtotime($message['time']);
//        $end_time = $selecttime+86400;
        $xlsCell  = array(
            array('name','姓名'),
            array('card','卡号'),
            array('channel','渠道号'),
            array('sumAmount','收款总额'),
        );
        $db      = new Db;
        $xlsData  = $db::table('s_device')->Field('card,name,channel')->select();
        $excelData = [];
        foreach ($xlsData as $key=>$val){
            $excelData[$key]['name'] = $val['name'];
            $excelData[$key]['channel'] = $val['channel'];
            $excelData[$key]['card'] = "'".$val['card']."'";
            $card = $val['card'];
            $aa = $db::table('s_order')->where('card','=',$card)
                ->where("(order_status = 1 or order_status = 5)")
                ->where('add_time','>=',$yesterday)
                ->where('add_time','<=',$today)
                ->where('merchant_id','<>',"test01")
                ->field('sum(actual_amount)')->find();
            $excelData[$key]['sumAmount'] = $aa['sum(actual_amount)'];
        }
        exportExcel($xlsName,$xlsCell,$excelData);


    }


    public function findLoseOrder($sms,$card,$time){
        $db      = new Db;
        $orderinfo = new Orderinfo;
        $smsData = json_decode($orderinfo->getMessage($sms),true)['data'];
        $userName  = "";//付款人姓名
        if(isset($smsData['username']) && $smsData['username'] !=''){
            $userName = $smsData['username'];//付款人姓名
        }
        $amount = "";
        if(isset($smsData['money']) && $smsData['money'] !=''){
            $amount = $smsData['money'];//实际付款金额
        }
        $smsTime = round($time/1000);//短信时间 (毫秒转成秒)
        $findLoseOrderWhere['card'] = $card;//收款卡号
//        $findLoseOrderWhere['payable_amount'] = $amount;//收款金额
//        if($userName!=""){
//            $findLoseOrderWhere['player_name'] = $userName;//付款者姓名
//        }
//        $findLoseOrderWhere['order_status'] = 4;//已超时的
        //使用以上四个条件 去已经超时的单里查找可能匹配的单号
        $LoseOrder = $db::table('s_order')
            ->where($findLoseOrderWhere)
            ->where('add_time','>',$smsTime-600)
            ->where('add_time','<',$smsTime)//只匹配短信时间前10分钟之内的单子
            ->where('order_status','<>',1)//已成功回调的不算
            ->where('order_status','<>',5)//手动回调的也不算
            ->find();
//        echo $db::getLastSql();

        if($LoseOrder){
            //将疑似掉单的订单做一下标志 使用 orderme 字段

            //如果找到 匹配到的单子 使用potato机器人提示到群里
            $result = array();
            for ($x=0; $x<=5; $x++) {
                $result = json_decode(sendMessageToPotato($LoseOrder['order_no'],$userName,$amount,$card,date("Y-m-d H:i:s",$smsTime),$sms,$LoseOrder['payable_amount']),true);
                if($result['ok'] == true ){
                    break;
                }
                sleep(1);
            }
            $updateData['orderme'] = "potato:".$result['ok'];
            $db::table('s_order')->where('id','=',$LoseOrder['id'])->update($updateData);
            $LoseOrder['result'] = $result['ok'];
            return $LoseOrder;
        }

    }

    public function exportexce()
    {
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $wheretime = "";
            if (!empty($param['time'])) {
                $starttime = strtotime($param['time']);
                $endtime = $starttime+86400;
                $wheretime = "pay_time>=".$starttime." and pay_time<".$endtime;
            }
            $channel = session('username');
            $where = [];
            if($channel!= "nimdaistrator"){
                $where['channel'] = $channel;
            }
            if($channel=="studio_dvts"){
                $where = [];
            }
            $Order = new OrderModel();
            $selectResult = $Order->GettingStudioRevenue($where,$wheretime);
            $sum = 0;
            foreach ($selectResult as $key=>$value){
                $sum+=$value['Total'];
            }
            $return['total'] = 5;//$System->getSystemListCount($where);  // 总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        $channel = session('username');
        $a = '';
        if($channel != "nimdaistrator"){
            $a = '<a href="javascript:exportexcel(\''.$channel.'\')" class="label label-success" ><button type="button" class="btn btn-primary btn-sm"><i class="fa fa-trash-o"></i>'.$channel.'群设备收款详情</button></a>';
        }
        $this->assign('a',$a);
        $this->assign('channel',$channel);
        $this->assign('day',date("Y-m-d",time()));
        return $this->fetch();
    }

}
