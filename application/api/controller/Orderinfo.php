<?php
/**
 * Created by PhpStorm.
 * User: dd
 * Date: 2019/3/19
 */

namespace app\api\controller;

use app\admin\model\Helper;
use think\Controller;
use think\Db;
use app\api\model\OrderModel;
use app\api\model\DeviceModel;
use app\admin\model\SystemConfigModel;
use app\api\model\AccountBindingModel;
use think\exception\ErrorException;
use think\Request;

class Orderinfo extends Controller
{

    /**
     * 正式下单接口(手机银行支付)
     * @return bool
     */
    public function createOrder()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
//            logs(json_encode(['message' => $message], 512), 'create_order');
            if (!isset($message['merchant_id']) || empty($message['merchant_id'])) {
                return apiJsonReturn('10001', "缺少必要参数:merchant_id");
            }
            if (!isset($message['order_no']) || empty($message['order_no'])) {
                return apiJsonReturn('10002', "缺少必要参数:order_no");
            }
            if (!isset($message['amount']) || empty($message['amount'])) {
                return apiJsonReturn('10003', "缺少必要参数:amount");
            }
            if (!isset($message['userid']) || empty($message['userid'])) {
//                return apiJsonReturn('100001', "缺少必要参数:userid");
                $message['userid'] = guidForSelf();
            }
            if (!isset($message['sig']) || empty($message['sig'])) {
                return apiJsonReturn('10004', "缺少必要参数:sig");
            }
            if (!isset($message['time']) || empty($message['time'])) {
                return apiJsonReturn('10005', "缺少必要参数:time");
            }
            $db = new Db();
            //验证商户
            $token = $db::table('s_merchant')->where('merchant_id','=',$message['merchant_id'])->find()['token'];
            $sig = md5($message['merchant_id'].$token.$message['order_no'].$message['amount'].$message['time']);

            if($sig!=$message['sig']){
                return apiJsonReturn('10006', "验签失败！");
            }
            $userId = $message['userid'];  //用户标识
            // 根据userId  未付款次数 限制下单 start
//            $prohibitedUserData = $db::table('s_prohibited_user')->where('user_id', '=', $userId)->find();
//            // 根据userId  查看是否有进行中的订单 如果有 直接返回订单号
//            $userWhere['payuserid'] = $userId;
//            $userWhere['order_status'] = 0;
//            $order = $db::table('s_order')->Field('order_no,bank_name,name,card')->where($userWhere)->find();
//            if($order){
//                $baseurl = request()->root(true);
//                $orderUrl = $baseurl."/api/bankpay/index?orderNo=" . $order['order_no'];
//                return apiJsonReturn1('10000', "下单成功", $orderUrl,$order['bank_name'],$order['name'],$order['card']);
//            }
//            if ($prohibitedUserData) {
//                $userNoPayTimes = $prohibitedUserData['no_pay_times'];
//                if ($userNoPayTimes >= 100) {
//                    $errorMsg = "此用户下单次数过于频繁，已禁止下单！";
//                    return apiJsonReturn('10007', $errorMsg);
//                }
//            }
            // 根据userId  未付款次数 限制下单 end
            $leftCanuseAmount = 50000-$message['amount'];
            $DeviceModel = new DeviceModel();
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['lock_time'] = 0;
            $deviceCount = $DeviceModel::where($deviceWhere)
                ->where('today_money','<=',$leftCanuseAmount)
                ->count();
            if($deviceCount==0){
                return apiJsonReturn('10009', "空闲设备不足，或者 所有设备今日收款金额加上此次收款金额大于50000下单失败");
            }
            //1、入库
            $insertOrderData['merchant_id'] = $message['merchant_id'];  //商户
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单id
            $insertOrderData['order_me'] = guidForSelf(); //本平台订单号
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['payersessionid'] = $userId;  //用户标识
            $insertOrderData['payuserid'] = $userId;  //用户标识
            if($message['payrealname'] == "张三"||$message['payrealname'] == "李四"){
                $message['payrealname'] = "";
            }
            $insertOrderData['player_name'] = $message['payrealname'];  //玩家姓名
            $insertOrderData['payable_amount'] = $message['amount'];  //应付金额
            $insertOrderData['payment'] = $message['payment']; //alipay
            $insertOrderData['add_time'] =time();//strtotime(date("Y-m-d H:i",time())) ;  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址 player_name payrealname

            $OrderModel = new OrderModel();
            $createOrderOne = $OrderModel->createOrderForBankPay($insertOrderData);
            if(!isset($createOrderOne['code'])||$createOrderOne['code']!='10000'){
                return apiJsonReturn('10008', $createOrderOne['msg']);
            }

            //2、分配设备
            $deviceMessage['amount'] = $insertOrderData['amount'];
            $deviceMessage['order_me'] = $insertOrderData['order_me'];

            $getDeviceQrCode = $DeviceModel::getBankDevice($userId,$message['merchant_id'],'',$message['amount']);
            if(!isset($getDeviceQrCode['code'])||$getDeviceQrCode['code']!="10000"){
                //修改订单为下单失败状态。
                $updateOrderStatus['order_status'] = 2;
                $OrderModel::where('order_no','=',$insertOrderData['order_no'])->update($updateOrderStatus);
                return apiJsonReturn($getDeviceQrCode['code'], $getDeviceQrCode['msg']);
            }

            //3、修改订单状态，下单成功状态
            $changOrderData['channel'] = $getDeviceQrCode['data']['channel'];
            $changOrderData['account'] = $getDeviceQrCode['data']['phone'];
            $changOrderData['card'] = $getDeviceQrCode['data']['card'];
            $changOrderData['name'] = $getDeviceQrCode['data']['name'];
            $changOrderData['bank_name'] = $getDeviceQrCode['data']['bank_name'];
            $changOrderData['bank_mark'] = $getDeviceQrCode['data']['bank_mark'];
            $changOrderStatus = $OrderModel::where('order_no', $insertOrderData['order_no'])->update($changOrderData);
            if(!$changOrderStatus){
                return apiJsonReturn('10010', '下单失败！');
            }

            if ($createOrderOne['code']=='10000' && $getDeviceQrCode['code']=='10000' && $changOrderStatus) {
//                $baseurl = request()->root(true);
                $baseurl = "https://a.tzpay.xyz";
                $orderUrl = $baseurl."/api/bankpay/index?orderNo=" . $insertOrderData['order_no'];
                return apiJsonReturn1('10000', "下单成功", $orderUrl,$changOrderData['bank_name'],$changOrderData['name'],$changOrderData['card']);
            } else {
                return apiJsonReturn('19999', "设备不足，下单失败");
            }
        } catch ( \Exception $exception) {
            logs(json_encode(['message'=>$message,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'create_order_exception');
            return apiJsonReturn('20011', "通道异常".$exception->getMessage());
        }catch ( \Error $error) {
            logs(json_encode(['message'=>$message,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'create_order_exception');
            return apiJsonReturn('20099', "通道异常".$error->getMessage());
        }
    }

    /**
     * 手机支付下单
     * @param $message
     * @return bool
     */
    public function docreateOrder($message)
    {
//        $data = @file_get_contents('php://input');
//        $message = json_decode($data, true);
        try {
//            logs(json_encode(['message' => $message], 512), 'create_order');
            if (!isset($message['merchant_id']) || empty($message['merchant_id'])) {
                return apiJsonReturn('10001', "缺少必要参数:merchant_id");
            }
            if (!isset($message['order_no']) || empty($message['order_no'])) {
                return apiJsonReturn('10002', "缺少必要参数:order_no");
            }
            if (!isset($message['amount']) || empty($message['amount'])) {
                return apiJsonReturn('10003', "缺少必要参数:amount");
            }
            if (!isset($message['userid']) || empty($message['userid'])) {
//                return apiJsonReturn('100001', "缺少必要参数:userid");
                $message['userid'] = guidForSelf();
            }
            if (!isset($message['sig']) || empty($message['sig'])) {
                return apiJsonReturn('10004', "缺少必要参数:sig");
            }
            if (!isset($message['time']) || empty($message['time'])) {
                return apiJsonReturn('10005', "缺少必要参数:time");
            }
            $db = new Db();
            //验证商户
            $token = $db::table('s_merchant')->where('merchant_id','=',$message['merchant_id'])->find()['token'];
            $sig = md5($message['merchant_id'].$token.$message['order_no'].$message['amount'].$message['time']);

            if($sig!=$message['sig']){
                return apiJsonReturn('10006', "验签失败！");
            }
            $userId = $message['userid'];  //用户标识
            // 根据userId  未付款次数 限制下单 start
//            $prohibitedUserData = $db::table('s_prohibited_user')->where('user_id', '=', $userId)->find();
//            if ($prohibitedUserData) {
//                $userNoPayTimes = $prohibitedUserData['no_pay_times'];
//                if ($userNoPayTimes >= 100) {
//                    $errorMsg = "此用户下单次数过于频繁，已禁止下单！";
//                    return apiJsonReturn('10007', $errorMsg);
//                }
//            }
            // 根据userId  未付款次数 限制下单 end

            $DeviceModel = new DeviceModel();
            $deviceWhere['is_online'] = 1;
//            $deviceWhere['is_prohibit'] = 1;
            $deviceCount = $DeviceModel::where($deviceWhere)->count();
            if($deviceCount==0){
                return apiJsonReturn('10009', "设备不足，下单失败");
            }
            //1、入库
            $insertOrderData['merchant_id'] = $message['merchant_id'];  //商户
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单id
            $insertOrderData['order_me'] = guidForSelf(); //本平台订单号
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['payersessionid'] = $userId;  //用户标识
            $insertOrderData['payuserid'] = $userId;  //用户标识
            if($message['payrealname'] == "张三"||$message['payrealname'] == "李四"){
                $message['payrealname'] = "";
            }
            $insertOrderData['player_name'] = $message['payrealname'];  //玩家姓名
            $insertOrderData['payable_amount'] = $message['amount'];  //应付金额
            $insertOrderData['payment'] = $message['payment']; //alipay
            $insertOrderData['add_time'] =time();//strtotime(date("Y-m-d H:i",time())) ;  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址

            $OrderModel = new OrderModel();
            $createOrderOne = $OrderModel->createOrderForBankPay($insertOrderData);
            if(!isset($createOrderOne['code'])||$createOrderOne['code']!='10000'){
                return apiJsonReturn('10008', $createOrderOne['msg']);
            }

            //2、分配设备
            $deviceMessage['amount'] = $insertOrderData['amount'];
            $deviceMessage['order_me'] = $insertOrderData['order_me'];

            $getDeviceQrCode = $DeviceModel::getBankDeviceTEST($userId,'',$message['card']);
            if(!isset($getDeviceQrCode['code'])||$getDeviceQrCode['code']!="10000"){
                //修改订单为下单失败状态。
                $updateOrderStatus['order_status'] = 2;

                $OrderModel::where('order_me','=',$insertOrderData['order_me'])->update($updateOrderStatus);
                return apiJsonReturn($getDeviceQrCode['code'], $getDeviceQrCode['msg']);
            }

            //3、修改订单状态，下单成功状态
            $changOrderData['channel'] = $getDeviceQrCode['data']['channel'];
            $changOrderData['account'] = $getDeviceQrCode['data']['phone'];
            $changOrderData['card'] = $getDeviceQrCode['data']['card'];
            $changOrderData['name'] = $getDeviceQrCode['data']['name'];
            $changOrderData['bank_name'] = $getDeviceQrCode['data']['bank_name'];
            $changOrderData['bank_mark'] = $getDeviceQrCode['data']['bank_mark'];
            $changOrderStatus = $OrderModel::where('order_me', $insertOrderData['order_me'])->update($changOrderData);
            if(!$changOrderStatus){
                return apiJsonReturn('10010', '下单失败！');
            }

            if ($createOrderOne['code']=='10000' && $getDeviceQrCode['code']=='10000' && $changOrderStatus) {
//                $baseurl = request()->root(true);
                $baseurl = "https://a.tzpay.xyz";
                $orderUrl = $baseurl."/api/bankpay/index?orderNo=" . $insertOrderData['order_no'];
                return apiJsonReturn('10000', "下单成功订单号：".$insertOrderData['order_no'], $orderUrl);
            } else {
                return apiJsonReturn('19999', "设备不足，下单失败");
            }
        } catch ( \Exception $exception) {
            logs(json_encode(['message'=>$message,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'create_order_exception');
            return apiJsonReturn('20009', "通道异常".$exception->getMessage());
        }catch ( \Error $error) {
            logs(json_encode(['message'=>$message,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'create_order_exception');
            return apiJsonReturn('20099', "通道异常".$error->getMessage());
        }
    }

    /**
     * 下单调试
     */
    public function createOrderTest()
    {
        $data = @file_get_contents('php://input');
        $messages = json_decode($data, true);
        if($messages['card'] != ""){
            $message['card'] = $messages['card'];
        }
        $orderNo = guidForSelf()."T";
        $message['order_no'] = $orderNo;
        $message['merchant_id'] = "test01";
        $token = "0e698a8ffc1a0af622c7b4db3cb750cc";  //test01
        $message['amount'] = 0.1;  //test01
        if($messages['amount'] != ""){
            $message['amount'] = $messages['amount'];
        }
        $message['userid'] = "xdm";  //test01
        $message['payrealname'] = "测试订单";  //test01
        $message['payable_amount'] = 1;  //test01
        $message['time'] = time();  //test01
        $message['notify_url'] = "test.com";  //test01
        $sig = md5($message['merchant_id'] . $token . $message['order_no'] . $message['amount'] . $message['time']);
        $message['sig'] = $sig;  //test01
        $message['payment'] = 'bankPay';

        $result = $this->doCreateOrder($message);
        $result = json_decode($result, true);
        if($result['code'] == 10000){
            return apiJsonReturn('100000',$result['msg'],$result['data']);
        }else{
            return apiJsonReturn('100001',$result['msg']);

        }
        exit;

    }

    /**
     * 后台测试下单接口（付呗、聚合、陌陌当前是陌陌）
     * @return string
     */
    public function createOrderForOur()
    {
        try {
            $data = @file_get_contents('php://input');
            $message = json_decode($data, true);

//            logs(json_encode(['message' => $message], 512), 'create_order');
            if (!isset($message['merchant_id']) || empty($message['merchant_id'])) {
                $message['merchant_id'] = 'test01';
//                return apiJsonReturn('10001', "缺少必要参数:merchant_id");
            }
            if (!isset($message['order_no']) || empty($message['order_no'])) {
                $message['order_no'] = guidForSelfTest();
//                return apiJsonReturn('10002', "缺少必要参数:order_no");
            }
            if (!isset($message['amount']) || empty($message['amount'])) {
                $message['amount'] = "100";
//                return apiJsonReturn('10003', "缺少必要参数:amount");
            }
            if (!isset($message['userid']) || empty($message['userid'])) {
//                return apiJsonReturn('100001', "缺少必要参数:userid");
                $message['userid'] = guidForSelf();
            }
            if (!isset($message['card']) || empty($message['card'])) {
                $message['phone'] = ' ';
//                return apiJsonReturn('100001', "缺少必要参数:amount");
            }
            if (!isset($message['userid']) || empty($message['userid'])) {
                $message['userid'] = md5(guidForSelfTest());
                $userId = $message['userid'];
//                return apiJsonReturn('100001', "缺少必要参数:userid");
            }

            logs(json_encode(['message' => $message], 512), 'create_order_for_our');

            $DeviceModel = new DeviceModel();
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $DeviceModel = new DeviceModel();

            $deviceCount = $DeviceModel::where($deviceWhere)->count();
            if($deviceCount==0){
                return apiJsonReturn('10009', "设备不足，下单失败");
            }
            //查询此user_id是否3600存在下单成功、存在继续使用此收款链接
//            $orderLogWhere['payersessionid'] = $userId;
//            $limitTime = SystemConfigModel::getPayLimitTime();
//            $lockTime = time()-$limitTime;
//            $orderModel = new OrderModel();
//            $orderLog = $orderModel::where($orderLogWhere)->where('add_time','>',$lockTime)->where('order_status','<>',1)->find();
//            if(!empty($orderLog)){
//                $orderUrl = "http://120.79.7.89/api/bank_pay/index?orderNo=" . $orderLog['order_no'];
//                return apiJsonReturn('10000', "下单成功", $orderUrl);
//            }
            //查询此user_id是否3600存在下单成功 未付款订单

            //1、入库
            $insertOrderData['merchant_id'] = $message['merchant_id'];  //商户
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单id
            $insertOrderData['order_me'] = guidForSelf(); //本平台订单号
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['payersessionid'] = $userId;  //用户标识
            $insertOrderData['payable_amount'] = $message['amount'];  //应付金额
            $insertOrderData['payment'] = $message['payment']; //alipay
            $insertOrderData['add_time'] = time();  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址

            $OrderModel = new OrderModel();
            $createOrderOne = $OrderModel->createOrderForBankPay($insertOrderData);
            if(!isset($createOrderOne['code'])||$createOrderOne['code']!='10000'){
                return apiJsonReturn('10008', $createOrderOne['msg']);
            }

            //2、分配设备
            $deviceMessage['amount'] = $insertOrderData['amount'];
            $deviceMessage['order_me'] = $insertOrderData['order_me'];

            $DeviceModel = new DeviceModel();
            $getDeviceQrCode = $DeviceModel::getBankDevice();
            if(!isset($getDeviceQrCode['code'])||$getDeviceQrCode['code']!="10000"){
                //下单失败修改订单状态
                $updateOrderStatus['order_status'] = 2;
                $OrderModel::where('order_me','=',$insertOrderData['order_me'])->update($updateOrderStatus);
                return apiJsonReturn($getDeviceQrCode['code'], $getDeviceQrCode['msg']);
            }

            //3、修改订单状态，下单成功状态
            $changOrderData['channel'] = $getDeviceQrCode['data']['channel'];
            $changOrderData['account'] = $getDeviceQrCode['data']['phone'];
            $changOrderData['card'] = $getDeviceQrCode['data']['card'];
            $changOrderData['bank_name'] = $getDeviceQrCode['data']['bank_name'];
            $changOrderData['bank_mark'] = $getDeviceQrCode['data']['bank_mark'];
            $changOrderStatus = $OrderModel::where('order_me', $insertOrderData['order_me'])->update($changOrderData);
            if(!$changOrderStatus){
                return apiJsonReturn('10010', '下单失败！');
            }

            if ($createOrderOne['code']=='10000' && $getDeviceQrCode['code']=='10000' && $changOrderStatus) {

                $orderUrl = "https://a.tzpay.xyz/api/bank_pay/index?orderNo=" . $insertOrderData['order_no'];
                $apiReturnData['code'] = '100000';
                $apiReturnData['msg'] = "下单成功";
                $apiReturnData['data'] = $orderUrl;
                $apiReturnData['phone'] = $changOrderData['account'];
                return json_encode($apiReturnData);
//                return apiJsonReturn('10000', "下单成功", $orderUrl);
            } else {
                $apiReturnData['code'] = '19999';
                $apiReturnData['msg'] = "下单失败";
                $apiReturnData['phone'] = $changOrderData['account'];
                return apiJsonReturn('19999', "设备不足，下单失败");
            }
        } catch ( \Exception $e) {
            return apiJsonReturn('20009', "渠道异常".$e->getMessage());
        }catch ( \Error $error) {
            return apiJsonReturn('20099', "渠道异常".$error->getMessage());
        }
    }
    public function demo(){
        echo "当前的服务器时间时间戳是：".time();
    }
    public function getMessage ($sms = null){
        $data    = @file_get_contents('php://input');
        $message = json_decode($data, true);//获取 调用信息
        $match   = Helper::pregMessageForDiaodan($message['body']);
        if($match){
            return Helper::apiJsonReturn('1000', "匹配成功",$match);
        }
    }

    //导出上一日回调成功给的订单
    public function exportOrder(){//导出Excel
        $xlsName  = "（按照回调时间）前日通用群回调成功订单列表";
        $xlsCell  = array(
            array('order_no','订单号'),
            array('add_time','下单时间'),
            array('actual_amount','支付金额'),
            array('pay_time','支付时间'),
            array('order_status','回调方式'),//1自动回调 5手动回调
            array('channel','工作室'),//1自动回调 5手动回调

        );
        $db      = new Db;
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');
        $xlsData  = $db::table('s_order')
//            ->where("(order_status = 1 or order_status = 5) and ((pay_time>".$yesterday." and pay_time<".$today.") or pay_time = 0) ")
            ->where("(order_status = 1 or order_status = 5 or order_status = 3)")
            ->where('pay_time','>=',$yesterday)
            ->where('pay_time','<',$today)
            ->where('merchant_id','<>',"test01")
            ->Field('add_time,order_no,actual_amount,pay_time,order_status,channel')->order("pay_time asc")->select();//pay_time,
        $excelDta = [];
        foreach($xlsData as $key=>$value){
            $excelDta[$key]['order_no'] = $value['order_no'];
            $excelDta[$key]['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
            $excelDta[$key]['actual_amount'] = $value['actual_amount'];
            $excelDta[$key]['channel'] = $value['channel'];
            $excelDta[$key]['pay_time'] = date("Y-m-d H:i:s",$value['pay_time']);
            if($value['pay_time'] == 0){
                $excelDta[$key]['pay_time'] = "手动回调订单没有准确支付时间";
            }
            if($value['order_status'] == 1){
                $excelDta[$key]['order_status']="自动回调";
            }elseif ($value['order_status'] == 5){
                $excelDta[$key]['order_status'] = "手动回调";
            }elseif ($value['order_status'] == 3){
                $excelDta[$key]['order_status'] = "修改金额（在可回调范围内）自动回调";
            }
        }
        Helper::exportExcel($xlsName,$xlsCell,$excelDta);


    }
    public function StatisticalDropRate(Request $request){
        $message = $request->param();
        if(!isset($message['time']) || $message['time'] ==""){
            return "请指定查询日期";
        }
        if(!isset($message['type']) || $message['type'] ==""){
            return "请指定查询方式";
        }
        $order = new \app\admin\model\OrderModel();
        $today = strtotime($message['time']);
        $endtime =$today+86400;
        //1当天的下单总量
        $Where = "order_status!=2 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $createOrderCount = $order->getCOUNT($Where);//getCOUNT
        //2未支付总量
        $Where = "order_status=4 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $errorCount = $order->getCOUNT($Where);
        //3正常回调量
        $Where = "order_status=1 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $successCount = $order->getCOUNT($Where);
        //4修改金额（在范围内）回调量
        $Where = "order_status=3 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $AmendmentAmountCallbackCount = $order->getCOUNT($Where);
        $TotalAutomaticCallback = $successCount+$AmendmentAmountCallbackCount;
        //5手动回调量
        $Where = "order_status=5 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $ManualCallbackCount = $order->getCOUNT($Where);
        //6手动回调（修改金额）总量
        $Where = "order_status=5 and actual_amount!= payable_amount and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $ManualCallbackAmendmentAmountCount = $order->getCOUNT($Where);
        //7待支付总量
        $Where = "order_status=0 and ".$message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $ToBePaidCount = $order->getCOUNT($Where);
        //总回调数量 自动会掉+手动回调
        $allCallBackCount = $TotalAutomaticCallback+$ManualCallbackCount;
        //成功率
        $SuccessRate = round((($allCallBackCount/$createOrderCount)*100),2)."%";
        //包含修改金额
        $LossRrateWithAmendmentAmount = round((($ManualCallbackCount/$allCallBackCount)*100),2)."%";
        //不包含修改金额
        $LossRrateWithOutAmendmentAmount = round(((($ManualCallbackCount-$ManualCallbackAmendmentAmountCount)/$allCallBackCount)*100),2)."%";
        //计算当日收款总额
        $Where = $message['type'].">=".$today." and ".$message['type']." <".$endtime;
        $sumActualAmount = $order->getSum($Where);//sum('actual_amount')
        $str =date("Y-m-d",$today)."下单总量：".$createOrderCount.",
               未支付总量：".$errorCount.",
               待支付总量：".$ToBePaidCount.",
               正常自动回调总量：".$successCount.",
               修改金额（在范围内）自动回调量".$AmendmentAmountCallbackCount.",
               总自动回调量：".$TotalAutomaticCallback.",
               掉单手动回调量：".$ManualCallbackCount.",
               手动回调（修改金额）总量:".$ManualCallbackAmendmentAmountCount.",
               回调总额:".$sumActualAmount."
               成功率(百分之):".$SuccessRate.",
               掉单率(百分之)：
               包含修改金额：".$LossRrateWithAmendmentAmount.",
               不包含修改金额:".$LossRrateWithOutAmendmentAmount;
//        $aa = $errorCount+$ToBePaidCount+$TotalAutomaticCallback+$ManualCallbackCount;
//        //echo $str;echo "<br>";
//        $sendData = [
//            'chat_type' => 1,
//            'chat_id' => 23611962,
//            'text' => $str,
//        ];
//        $headers = ['Content-Type:application/json'];
//        $result = Helper::cUrlGetData('http://18.138.140.45:8000/10101207:CWZIsDhF13uH3CBTusCGpTMd/sendTextMessage', json_encode($sendData), $headers);
        echo "<pre>";
        print_r($str);
    }

    public function aa(){
        $db = new Db();
        $sms = $db::table('s_sms')->where("ISNULL(sms_md5)")->select();
        $num = 0;
        foreach ($sms as $key=>$val){
            $where['id'] = $val['id'];
            $update['sms_md5'] = md5($val['sms']);
            $res = $db::table('s_sms')->where($where)->update($update);
            if($res == 1){
                $num+=1;
            }
        }
        dump($num);
    }
    public function bb(){
        $db = new Db();
        $sms = $db::table('s_sms')->query("select count(id),sms_md5 from s_sms GROUP BY sms_md5 ORDER BY count(id) desc");
        $num = 0;
        echo $db::table('s_sms')->getLastSql();
        foreach ($sms as $key=>$val){
           if($val['count(id)']>1){
               $where['sms_md5'] = $val['sms_md5'];
               $aa = $db::table('s_sms')->field('id,sms_md5')->where($where)->select();
               dump($aa);
               $num+=1;
           }
        }
        dump($num);
    }
    public function ee(){
        $Dailycollectionquota = SystemConfigModel::getDailycollectionquota();
        $db = new Db();
//        $Dailycollectionquota = $db::table('s_system_config')->field('config_data')->where('config_name','=','Daily_collection_quota')->find()['config_data'];
        dump($Dailycollectionquota);
        dump((int)$Dailycollectionquota);
        dump(intval($Dailycollectionquota));
        echo intval($Dailycollectionquota);
    }
}



//

