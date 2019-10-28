<?php
/**
 * Created by PhpStorm.
 * User: dd
 * Date: 2019/3/19
 */

namespace app\api\controller;

use app\api\model\SmsModel;
use think\Controller;
use app\api\model\NotifyCallBackLogModel;
use app\api\model\LoseNotifyCallBackLogModel;
use app\admin\model\SystemConfigModel;
use app\api\model\OrderModel;
use think\Request;
use think\Db;


class Ordernotify extends Controller
{
    /**
     * {
     * //   "Channel"   :  "工作室标识",
     * //   "PayCardNo"   :  "打款卡号",
     * //   "PayMoney"     :  "打款金额",
     * //   "PayCardUser": "打款人姓名"
     * //   "PayCardType": "打款银行名称",
     * //   "PayTime":     "打款时间"
     * //   "PayComment":  "打款备注或打款留言",
     * //   "RecvCardNo":   "收款账号",
     * //   "RecvCardType": "收款银行名称",
     * //   "RecvCardMark": "收款银行简称",
     * //   "RecvCardBalance": "收款卡号余额"
     * //   "PayHash":      "唯一码"
     * // }
     * 封装回调数据  根据打款人姓名和金额匹配订单
     * @param Request $request
     * @return bool
     */
//    public function index1(Request $request)
//    {
//        try{
//            $message = $request->param();
//            if(!isset($message['PayHash'])||empty($message['PayHash'])){
//                return apiJsonReturn('10001','missing_parameters_PayHash');
//            }
//            if(!isset($message['PayCardUser'])||empty($message['PayCardUser'])){
//                return apiJsonReturn('10002','missing_parameters_PayCardUser');
//            }
//            if(!isset($message['PayCardUser'])||empty($message['PayCardUser'])){
//                return apiJsonReturn('10003','missing_parameters_PayCardUser');
//            }
//            if(!isset($message['Channel'])||empty($message['Channel'])){
//                return apiJsonReturn('10004','missing_parameters_Channel');
//            }
//
//            //hash（卡号＋时间＋金额 ＋流水号 如果有的话）
//            $notifyCallBackLogModel = new NotifyCallBackLogModel();
//            $notifyCallBackLog = $notifyCallBackLogModel::where('PayHash','=',$message['PayHash'])->find();
//            // 有回调记录并且已经成功匹配
//            if(!empty($notifyCallBackLog)&&$notifyCallBackLog['status']=='1'){
//                NotifyCallBackLogModel::theSameNotifyLog($message['PayHash'],$notifyCallBackLog['times']);
//                return apiJsonReturn('10000','order_notify_callback_has_been_successful');
//            }
//
//            //匹配订单 start
//            $orderModel = new OrderModel();
//            $isPayOrderRes = $orderModel::matchOrderForBankPay($message);
//            if(!isset($isPayOrderRes['code'])){
//                return apiJsonReturn('17777','order_notify_callback_exception');
//            }
//            //匹配成功 >>>>>>>>>start
//            if($isPayOrderRes['code']=='10000'){
//                $matchOrderData  = $isPayOrderRes['data'];
//                // 回调商户 start
//                $money = $message['PayMoney']*1005;
//                $money = number_format($money, 2);
//                $notifyToMainStationData = $orderModel::doNotifyToMainStationData($matchOrderData, $money);  //=========>>>>>封装数据
//                $notifyCallbackResult = cUrlGetData($matchOrderData['notify_url'], $notifyToMainStationData, ['Content-Type:application/json']);//请求回调
//
//                if ($notifyCallbackResult == 'success') {
//                    //本地修改订单状态
//                    $localNotifyRes = $orderModel::doNotifySuccess($matchOrderData, $money);
//                    if(!isset($localNotifyRes['code'])){
//                        return apiJsonReturn('17777','local_order_notify_callback_exception');
//                    }
//                    if($localNotifyRes['code']=='10000'){
//                        return apiJsonReturn('10000','success');
//                    }
//                }
//                // 回调商户 end
//            }
//            //匹配成功 >>>>>>>>>end
//
//            //匹配不成功>>>>>>> start
//             NotifyCallBackLogModel::doNoMatchNotifyLog($message);
//            //匹配不成功>>>>>>>> end
//
//            return apiJsonReturn('10000','success_no_match_order');
//        }catch (\Exception $exception){
//            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'order_notify_exception');
//            return apiJsonReturn('19999','order_notify_callback_error');
//        }catch (\Error $error){
//            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'order_notify_error');
//            return apiJsonReturn('29999','order_notify_callback_error');
//        }
//    }

    /**
     * {
     * "Uuid"    :    "机器信息",    // 机器组号-server机器号-client机器号-com口
     * "Type"    :    2        //转账结果返回值
     * "Body"    :
     * {
     * "OrderNum"    :    "DI4178325232",        //订单号
     * “ChangeAmount”: “+300”                //金额变动(实际打款金额)
     * “CurrentBalance “：”12314“.            //银行卡余额
     * “PayType”: 1-支付宝 2-微信 4-银行卡 8-云闪付    //支付类型
     * "Statu"        :    0失败 1成功 2未知 3 处理中    //转账结果
     * }
     * }
     * @return bool
     */
    public function putTransferMsg()
    {
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );
        try {
            if (!isset( $message['Uuid'] ) || empty( $message['Uuid'] )) {
                return apiJsonReturn( '10001', "missing_parameters_Uuid" );
            }
            if (!isset( $message['channel'] ) || empty( $message['channel'] )) {
                return apiJsonReturn( '10001', "missing_parameters_channel" );
            }
            if (!isset( $message['Body'] ) || empty( $message['Body'] )) {
                return apiJsonReturn( '10001', "missing_parameters_Body" );
            }
            $body = $message['Body'];
            if (!isset( $body['IsUpdate'] )) {
                return apiJsonReturn( '10001', "missing_parameters_IsUpdate" );
            }
            if (!isset( $body['CardList'] ) || empty( $body['CardList'] )) {
                return apiJsonReturn( '10001', "missing_parameters_CardList" );
            }
            if (empty( $body['CardList'] ) || !is_array( $body['CardList'] )) {
                return apiJsonReturn( '10001', "CardList_error" );
            }
            //检验cardlist
            $deviceValidate = new DeviceValidate();
            //循环检查
            $infoData = [];
            $totalDataCount = 0;
            $successCount = 0;
            $errorCount = 0;
            $errorData = array();
            //循环更新 start
            foreach ($body['CardList'] as $key => $val) {
                $totalDataCount++;
                //验证更新
                $validateResult = $deviceValidate->check( $val );
                if ($validateResult) {
                    //判断是否存在
                    $where['card'] = $val['BankCardNo'];
                    $deviceModel = new DeviceModel();
                    $insertData['device_id'] = $message['Uuid'];
                    $insertData['name'] = $val['AccountName'];     //张三
                    $insertData['bank_mark'] = $val['BankShortName'];    //CCB
                    $insertData['bank_name'] = $val['OpenAccountBranch'];  //中国建设银行
                    $insertData['phone'] = $val['PhoneNum'];  //电话号码
                    $insertData['status'] = $val['Statu'];  //0空闲 1工作中
                    $insertData['update_time'] = time();  //更新时间
                    if (isset( $message['channel'] )) {
                        $insertData['channel'] = $message['channel']; //工作室标识
                    }
                    $res = $deviceModel::field( 'id' )->where( $where )->find();
                    //存在 更新
                    if ($res) {
                        $successCount++;
                        $deviceModel::where( $where )->update( $insertData );
                    } else {
                        $successCount++;
                        //不存在 插入
                        $insertData['card'] = $val['BankCardNo'];
                        $insertData['create_time'] = time();
                        $deviceModel::create( $insertData );
                    }
                } else {
                    //验证不通过
                    $errorCount++;
                    $errorData[$val['BankCardNo']] = $deviceValidate->getError();
                }
            }
            //循环更新 end
            $returnData['successCount'] = $successCount;
            $returnData['errorCount'] = $errorCount;
            $returnData['errorData'] = $errorData;
            return apiJsonReturn( '10000', "接收成功", $returnData );

        } catch (\Exception $exception) {
            logs( json_encode( ['message' => $message, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'create_order_exception' );
            return apiJsonReturn( '20009', "上传服务器异常" . $exception->getMessage() );
        } catch (\Error $error) {
            logs( json_encode( ['message' => $message, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'create_order_exception' );
            return apiJsonReturn( '20099', "上传服务器错误" . $error->getMessage() );
        }

    }

    public function putTransferOld()
    {
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );
        try {

            if (!isset( $message['RecvCardMark'] ) || empty( $message['RecvCardMark'] )) {//收款银行简称
                return apiJsonReturn( '10001', 'missing_parameters_RecvCardMark' );
            }
            if (!isset( $message['RecvCardNo'] ) || empty( $message['RecvCardNo'] )) {//收款账号
                return apiJsonReturn( '10001', 'missing_parameters_RecvCardNo' );
            }

            if (!isset( $message['RecvCardType'] ) || empty( $message['RecvCardType'] )) {//收款银行名称
                return apiJsonReturn( '10001', 'missing_parameters_RecvCardType' );
            }
            if (!isset( $message['Channel'] ) || empty( $message['Channel'] )) {//渠道号
                return apiJsonReturn( '10001', 'missing_parameters_Channel' );
            }
            if (!isset( $message['InfoData'] ) || empty( $message['InfoData'] )) {//具体协议
                return apiJsonReturn( '10001', 'missing_parameters_InfoData' );
            }
            $notifyCallBackLogModel = new NotifyCallBackLogModel();
            //循环处理数据 start
            $infoData = [];

            $totalDataCount = 0;
            foreach ($message['InfoData'] as $i) {
                //根据hash去重
                $LogCount = $notifyCallBackLogModel->getLogCount( $i['PayHash'] );
                if ($LogCount == 0) {
                    //去除金额中的逗号
                    $PayMoney = str_replace( ',', '', $i['PayMoney'] );
                    $infoData[] = [
                        'PayCardNo' => $i['PayCardNo'],//打款卡号
                        'PayMoney' => $PayMoney,//打款金额
                        'PayCardUser' => $i['PayCardUser'],//打款人姓名
                        'PayCardType' => $i['PayCardType'],//打款银行名称
                        'PayTime' => strtotime( $i['PayTime'] ),//打款时间
                        'PayComment' => $i['PayComment'],//打款备注或打款留言
                        'PayHash' => $i['PayHash'],//唯一码
                        'RecvCardBalance' => $i['RecvCardBalance'],  //余额
                        'Channel' => $message['Channel'],//工作室标识
                        'RecvCardNo' => $message['RecvCardNo'],//收款账号
                        'RecvCardType' => $message['RecvCardType'],//收款银行名称
                        'RecvCardMark' => $message['RecvCardMark'],//收款银行简称
                        'createTime' => time(),//添加时间
                    ];
                    if (mb_substr( $i['PayComment'], 0, 5 ) == "银联入账:") {
                        $infoData[]['PayCardUser'] = mb_substr( $i['PayComment'], 5 );
                    }
                }
                $totalDataCount++;
            }
            //循环处理数据 end
            if (empty( $infoData )) {
                return apiJsonReturn( '20002', 'missing_parameters_infoData' );
            }

            $saveResult = $notifyCallBackLogModel->saveAll( $infoData );
            if (!$saveResult) {
                logs( json_encode( ['message' => $message, 'saveData' => $infoData] ), 'notify_callback_save_data_error' );
                return apiJsonReturn( '20002', 'missing_parameters_infoData' );
            }
            $orderModel = new OrderModel();
            //循环匹配入库
            $errorCount = 0;
            $successCount = 0;
            $repeatCallbackTimes = 0;
            //循环回调 start
            foreach ($infoData as $key => $val) {
                //hash（卡号＋时间＋金额 ＋流水号 如果有的话）
                $notifyCallBackLogModel = new NotifyCallBackLogModel();
                $notifyCallBackLog = $notifyCallBackLogModel::where( 'PayHash', '=', $val['PayHash'] )->find();
                // 有回调记录并且已经成功匹配
                if ($notifyCallBackLog['times'] >= 2) {
                    //times+1
                    NotifyCallBackLogModel::theSameNotifyLog( $val['PayHash'], $notifyCallBackLog['times'] );
                    $repeatCallbackTimes++;
                } else {
                    logs( json_encode( ['message' => $message, 'data' => $val] ), 'order_notify_match_log' );
                    //无回调记录
                    //匹配订单
                    $matchOrderRes = $orderModel::matchOrderForBankPay( $val );
                    if (!isset( $matchOrderRes['code'] )) {
                        $errorCount++;
                    }
//                        var_dump($matchOrderRes);
//                    die();
                    if ($matchOrderRes['code'] == '10000') {
//                        echo  1111;die();
                        $matchOrderData = $matchOrderRes['data'];
                        // 回调商户（总后台） >>>>>>>>>>>>>>>start
                        $money = $val['PayMoney'] * 1.005;
                        $amount = str_replace( ',', '', $money );
                        $paymoney = number_format( $amount, 2 );
                        $notifyToMainStationData = $orderModel::doNotifyToMainStationData( $matchOrderData, $paymoney );  //=========>>>>>封装数据
                        $notifyCallbackResult = cUrlGetData( $matchOrderData['notify_url'], $notifyToMainStationData, ['Content-Type:application/json'] );//请求回调
                        //如果订单是 merchant_id 是 dd 以回调结果为主  不是dd
                        if ($matchOrderData['merchant_id'] != 'dd') {
                            $notifyCallbackResult = "success";
                        }
                        // 回调商户 >>>>>>>>>>>end
                        //总后台返回success  修改本地状态
                        if ($notifyCallbackResult == 'success') {
                            //本地修改订单状态
//                            $localNotifyRes = $orderModel::doNotifySuccess($matchOrderData, $money);
//                            dump($localNotifyRes);die();
                            if (!isset( $localNotifyRes['code'] )) {
                                $errorCount++;
                            } else {
                                if ($localNotifyRes['code'] == '10000') {
                                    $status = 1;
                                    $updateCallbackData['status'] = 1;
                                    $updateCallbackData['orderNo'] = $matchOrderData['order_no'];
                                    $updateCallbackData['matchTime'] = time();
                                    //查询记录是否存在  存在status =1  不存在
                                    NotifyCallBackLogModel::updateNotifyLog( $val['PayHash'], $updateCallbackData );
                                    $successCount++;
                                }
                            }
                            // 本地修改订单状态 end
                        } else {
                            //是商户下单报异常
                            logs( json_encode( ['message' => $notifyToMainStationData, 'notifyCallbackResult' => $notifyCallbackResult] ), 'notify_callback_result_exception' );

                        }
                    }
                    //匹配成功 >>>>>>>>>end
                    //没有匹配成功给的单进入调单表 20001
                    else {
                        $losenotifyCallBackLogModel = new LoseNotifyCallBackLogModel();
                        $losenotifyCallBackLogModel->insert( $val );
                    }

                }
            }
            //循环回调 end
            if (isset( $totalDataCount ) && is_numeric( $totalDataCount )) {
                $returnData['totalDataCount'] = $totalDataCount;
            }

            $returnData['repeatCallbackTimes'] = $repeatCallbackTimes;
            $returnData['successCount'] = $successCount;
            $returnData['errorCount'] = $errorCount;
            if ($errorCount > 0) {
                logs( json_encode( ['message' => $message, 'data' => $returnData] ), 'order_notify_error_type_1' );
            }
            return apiJsonReturn( '10000', 'success', $returnData );

        } catch (\Exception $exception) {
            logs( json_encode( ['message' => $message, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'order_notify_exception' );
            return apiJsonReturn( '28888', 'order_notify_callback_exception' );
        } catch (\Error $error) {
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'order_notify_error' );
            return apiJsonReturn( '29999', 'order_notify_callback_error' );
        }

    }

    //短信模式回调方法 废弃
    public function frankMessageCallbacktest()
    {//address
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );//获取 调用信息
        if (!isset( $message['phone'] ) || empty( $message['phone'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_phone_no' );
        }
        if (!isset( $message['body'] ) || empty( $message['body'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_shotmessage_body' );
        }
        if (!isset( $message['channel'] ) || empty( $message['channel'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_shotmessage_body' );
        }
        if (!isset( $message['address'] ) || empty( $message['address'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_address' );
        }
        //获取 可用address 如果address 不在可用 中 则视为垃圾短信
        $db = new Db;
        $card = $db::table( 's_bank_phone' )->field( 'id' )->where( 'bank_phone', '=', $message['address'] )->find();
        //1接收到插件回调的短信 第一时间存入数据库，数据库存入成功直接返回success
        $smsModel = new SmsModel();
        //去除短信的左右空格
        $sms = trim( $message['body'] );
//        $smsres = $smsModel->findsms($sms);
//        if($smsres){
//            return apiJsonReturn('10002','此条短信已存在');
//        }
        $addResult = $smsModel->newaddSms( $sms, $message['phone'], $message['channel'], $message['address'], $message['sign'], $message['version'], 1, 6 );
        return $addResult;
        //3判断匹配结果，如果是垃圾短信直接 修改短信使用状态为1级别为3
        //3.1如果匹配为正常入账短信，则去匹配订单 匹配依据为 卡号，金额，订单状态为0，短信到账时间，匹配不到订单 修改短信使用状态为1，级别为2
        //3.2如果匹配为正常入账短信，则去匹配订单 匹配依据为 卡号，金额，订单状态为0，短信到账时间，匹配到订单 修改短信使用状态为1，级别为1
        //4匹配到订单后第一时间回调给总服务器
        //5修改订单状态以及相关字段  （封装单独的函数）
    }

    public function getSmsOld()
    {
        //2触发读取数据库短信 循环正则匹配
        $smsModel = new SmsModel();
        $smsJsonData = $smsModel->getSmsSelect();
        $smsArrayData = json_decode( $smsJsonData, true );
        if (json_decode( $smsJsonData, true )['code'] != 10000) {
            return $smsJsonData;
        }
        $smsArrayData = $smsArrayData['data'];
        foreach ($smsArrayData as $key => $value) {
//有可用的短信，使用正则匹配短信
            $pregJsonResult = pregMessages( $value['sms'] );
            $pregArrayResult = json_decode( $pregJsonResult, true );
            if ($pregArrayResult['code'] == 10000) {
                //短信格式匹配成功，进行订单匹配
                $result = $this->findOrder( $pregArrayResult['data'], $value['phone'], $value['sms'], $value['sms_md5'] );
//                return $result;
                if ($result['code'] == 10000) {
                    $this->updateSms( $value['id'], 1, $result['data'], $result['msg'] );
                } elseif ($result['code'] == 18888) {
                    $this->updateSms( $value['id'], 1, $result['data'], $result['msg'] );
                } elseif ($result['code'] == 19999) {
                    $this->updateSms( $value['id'], 2, '', $result['msg'] );
                } elseif ($result['code'] == 19997) {
                    $this->updateSms( $value['id'], 4, $result['data'], $result['msg'] );
                } elseif ($result['code'] == 100000) {
                    $this->updateSms( $value['id'], 5, $result['data'], $result['msg'] );
                } elseif ($result['code'] == 100001) {
                    $this->updateSms( $value['id'], 5, $result['data'], $result['msg'] );
                }
            } else {
                //短信格式匹配失败视为垃圾短信，如果是垃圾短信直接 修改短信使用状态为1，级别为3
                $this->updateSms( $value['id'], 3, '', '垃圾短信' );
            }
        }
    }


    //从数据库中读取未使用过的短信
    public function getSms()
    {
        try {
            //2触发读取数据库短信 循环正则匹配
            $smsModel = new SmsModel();
            $smsJsonData = $smsModel->getSmsSelect();
            $smsArrayData = json_decode( $smsJsonData, true );
            if (json_decode( $smsJsonData, true )['code'] != 10000) {
                return $smsJsonData;
            }
            $smsArrayData = $smsArrayData['data'];
            foreach ($smsArrayData as $key => $value) {
//有可用的短信，使用正则匹配短信
                $pos = strpos( $value['sms'], '直销银行转账' ); //短信内如果有直销银行转账字样 直接判断为垃圾短信
                if ($pos !== false) {//
                    $this->updateSms( $value['id'], 3, '', '垃圾短信' );
                    continue;
                }
                $pregJsonResult = pregMessages( $value['sms'] );
                $pregArrayResult = json_decode( $pregJsonResult, true );
                if ($pregArrayResult['code'] == 10000) {
                    //短信格式匹配成功，进行订单匹配
                    $result = $this->findOrder( $pregArrayResult['data'], $value['phone'], $value['sms'], $value['sms_md5'] );
//                return $result;
                    if ($result['code'] == 10000) {
                        $this->updateSms( $value['id'], 1, $result['data'], $result['msg'] );
                    } elseif ($result['code'] == 18888) {
                        $this->updateSms( $value['id'], 1, $result['data'], $result['msg'] );
                    } elseif ($result['code'] == 19999) {
                        $this->updateSms( $value['id'], 2, '', $result['msg'] );
                    } elseif ($result['code'] == 19997) {
                        $this->updateSms( $value['id'], 4, $result['data'], $result['msg'] );
                    } elseif ($result['code'] == 100000) {
                        $this->updateSms( $value['id'], 5, $result['data'], $result['msg'] );
                    } elseif ($result['code'] == 100001) {
                        $this->updateSms( $value['id'], 5, $result['data'], $result['msg'] );
                    }
                } else {
                    //短信格式匹配失败视为垃圾短信，如果是垃圾短信直接 修改短信使用状态为1，级别为3
                    $this->updateSms( $value['id'], 3, '', '垃圾短信' );
                }
            }
        } catch (\Exception $exception) {
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'getSms_exception' );
            return apiJsonReturn( '20009', "上传服务器异常" . $exception->getMessage() );
        } catch (\Error $error) {
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'getSms_error' );
            return apiJsonReturn( '20099', "上传服务器错误" . $error->getMessage() );
        }

    }

    public function findOrder($smsData, $phone, $sms, $sms_md5)
    {
//        echo 5;die();
        //使用phone获取银行卡号
        $db = new Db;
        $card = $db::table( 's_device' )->field( 'id,channel,card' )->where( 'phone', '=', $phone )->find();
        //1将短信里的文字时间转换成时间戳
        $time = smsTimeStroTime( $smsData['time'] );
        $amount = $smsData['money'];
        $orderModel = new OrderModel();
        $orderResult = $orderModel->matchOrderForSms( $amount, $time, $card['card'] );
        if ($orderResult['code'] == '10000') {
            //1,回调总后台数据
            $notifyCallbackResult = "";
            if ($orderResult['data']['notify_url'] != "test.com") {
                $notifyToMainStationData = $orderModel::doNotifyToMainStationData( $orderResult['data'], $amount );
                for ($x = 0; $x < 5; $x++) {
                    $notifyCallbackResult = cUrlGetData( $orderResult['data']['notify_url'], $notifyToMainStationData, ['Content-Type:application/json'] );//请求回调
                    if ($notifyCallbackResult == "success") {
                        break;
                    }
                }
            } else {
                $notifyCallbackResult = "测试订单，无需回调";
            }


            //2成功匹配到订单后修改订单状态

            for ($x = 0; $x < 5; $x++) {
                $updateOrderResult = $orderModel::doNotifySuccess( $orderResult['data'], $amount, 1, $sms, $sms_md5, $notifyCallbackResult );
                if ($updateOrderResult['code'] == 10000) {
                    break;
                }
            }
            if ($updateOrderResult['code'] == 10000) {
                return arrayReturn( '10000', $orderResult['data']['order_no'], '完成且回调，总后台返回' . $notifyCallbackResult );
            } else {
                return arrayReturn( '18888', $orderResult['data']['order_no'], '本地更新失败，返回信息：' . $updateOrderResult['code'] . ',错误信息' . $updateOrderResult['msg'] . '总后台返回' . $notifyCallbackResult );
            }

        } else {
            $username = '';
            if (isset( $smsData['username'] ) && $smsData['username'] != '') {
                $username = $smsData['username'];
            }
            //使用 允许范围 内的金额 匹配一次订单
            $orderResult = $orderModel->matchOrderForSmsWithScopeMoney( $amount, $time, $card['card'], $username );
            if ($orderResult['code'] == '10000') {
                //1,回调总后台数据
                $notifyCallbackResult = "";
                if ($orderResult['data']['notify_url'] != "test.com") {
                    $notifyToMainStationData = $orderModel::doNotifyToMainStationData( $orderResult['data'], $amount );
                    for ($x = 0; $x < 5; $x++) {
                        $notifyCallbackResult = cUrlGetData( $orderResult['data']['notify_url'], $notifyToMainStationData, ['Content-Type:application/json'] );//请求回调
                        if ($notifyCallbackResult == "success") {
                            break;
                        }
                    }
                } else {
                    $notifyCallbackResult = "测试订单，无需回调";
                }
                //2成功匹配到订单后修改订单状态
                for ($x = 0; $x < 5; $x++) {
                    $updateOrderResult = $orderModel::doNotifySuccess( $orderResult['data'], $amount, 3, $sms, $sms_md5, $notifyCallbackResult );
                    if ($updateOrderResult['code'] == 10000) {
                        break;
                    }
                }
                //3推送到potato


                $title = "@xiaoli 注意！！通用后台找到修改金额（在可回调范围内,并且付款人姓名相符,已经自动回调）的订单,";

                $result = sendMessageToPotato( $orderResult['data']['order_no'], $username, $amount, $card['card'], $smsData['time'], $sms, $orderResult['data']['payable_amount'], $title );


                if ($updateOrderResult['code'] == 10000) {

                    return arrayReturn( '100000', $orderResult['data']['order_no'], '修改金额订单 完成且回调，总后台返回' . $notifyCallbackResult . '机器人返回：' . $result );
                } else {
                    return arrayReturn( '100001', $orderResult['data']['order_no'], '修改金额订单 本地更新失败，返回信息：' . $updateOrderResult['code'] . '总后台返回' . $notifyCallbackResult . '机器人返回：' . $result );
                }

            } else {
                //不适用金额进行一次匹配将匹配的结果推送potato
                $orderResults = $orderModel->matchOrderForSmsWithoutMoney( $amount, $time, $card['card'] );
                if ($orderResults['code'] == '10000') {
                    //如果匹配到了订单使用potato推送
                    for ($x = 0; $x <= 5; $x++) {
                        $username = '';
                        if (isset( $smsData['username'] ) && $smsData['username'] != '') {
                            $username = $smsData['username'];
                        }
                        $title = "@xiaoli 注意！！通用后台系统找到(可能因为用户修改金额)掉单的，";
                        $result = json_decode( sendMessageToPotato( $orderResults['data']['order_no'], $username, $amount, $card['card'], $smsData['time'], $sms, $orderResults['data']['payable_amount'], $title ), true );
                        if ($result['ok'] == true) {
                            break;
                        }
                        sleep( 1 );
                    }
                    return arrayReturn( '19997', $orderResults['data']['order_no'], json_encode( $result ) );
                }
            }


            return arrayReturn( '19999', '', $orderResult['msg'] );
        }
    }

    //修改短信使用情况
    public function updateSms($id, $level, $orderno, $msg)
    {
        $smsModel = new SmsModel();
        $smsJsonData = $smsModel->updateSms( $id, $level, $orderno, $msg );
    }

    public function pregdemo1()
    {
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );//获取 调用信息
        $pregJsonResult = pregMessagesdemo( $message['sms'] );
        return $pregJsonResult;
    }

    public function jiancetime()
    {
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );//获取 调用信息
        $pregJsonResult = smsTimeStroTime( $message['time'] );
        return $pregJsonResult;
    }

    //短信模式回调方法
    public function frankMessageCallbackWithToken()
    {//address
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );//获取 调用信息
        if (!isset( $message['phone'] ) || empty( $message['phone'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_5' );
        }
        if (!isset( $message['body'] ) || empty( $message['body'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_4' );
        }
        if (!isset( $message['channel'] ) || empty( $message['channel'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_3' );
        }
        if (!isset( $message['address'] ) || empty( $message['address'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_2' );
        }
        if (!isset( $message['sign'] ) || empty( $message['sign'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_1' );
        }
        if (!isset( $message['time'] ) || empty( $message['time'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_6' );
        }
        if (!isset( $message['version'] ) || empty( $message['version'] )) {//收款银行简称
            return apiJsonReturn( '10001', 'missing_7' );
        }
        $db = new Db();
        $token = $db::table( 's_device_token' )->where( 'channel', '=', $message['channel'] )->find()['token'];
        $sign = $this->sing( $message, $token );
        if ($sign != $message['sign']) {
            return apiJsonReturn( '10001', 'error' );
        } else {
            //获取 可用address 如果address 不在可用 中 则视为垃圾短信
            $smsModel = new SmsModel();
            //去除短信的左右空格
            $sms = trim( $message['body'] );
            $card = $db::table( 's_bank_phone' )->field( 'id' )->where( 'bank_phone', '=', $message['address'] )->find();
            if (!$card) {
                $addResult = $smsModel->newaddSms( $sms, $message['phone'], $message['channel'], $message['address'], $message['sign'], $message['version'], 1, 6 );
                return $addResult;
            }
            $addResult = $smsModel->newaddSms( $sms, $message['phone'], $message['channel'], $message['address'], $message['sign'], $message['version'], 0, 0 );
            return $addResult;
        }
    }

    //sign
    public function sing($message, $token)
    {
        // sign  = md5(phone+channel+address+time+token+body)
        $sign = md5( $message['phone'] . $message['channel'] . $message['address'] . $message['time'] . $token . $message['body'] );
        return $sign;
    }

    //getMcsing
    public function getMcsing($message, $token)
    {
        // sign  = md5(phone+channel+address+time+token+body)
        $sign = md5( $message['trx_type'] . $message['bank_order_no'] . $message['transaction_amount'] . $message['account_no'] . $message['transaction_status'] . $token );
        return $sign;
    }

    public function McOrderCallBack()
    {
        $db = new Db();
        $data = @file_get_contents( 'php://input' );
        $message = json_decode( $data, true );//获取
        if (!isset( $message['order_no'] ) || empty( $message['order_no'] )) {//收款银行简称
            $returnmessage['msg'] = "miss order_no";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1001";
            return json_encode( $returnmessage );
        }
        if (!isset( $message['bank_order_no'] ) || empty( $message['bank_order_no'] )) {//收款银行简称
            $returnmessage['msg'] = "miss bank_order_no";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1002";
            return json_encode( $returnmessage );
        }
        if (!isset( $message['transaction_amount'] ) || empty( $message['transaction_amount'] )) {//收款银行简称
            $returnmessage['msg'] = "miss transaction_amount";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1003";
            return json_encode( $returnmessage );
        }
        if (!isset( $message['account_no'] ) || empty( $message['account_no'] )) {//收款银行简称
            $returnmessage['msg'] = "miss account_no";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1004";
            return json_encode( $returnmessage );
        }
        if (!isset( $message['transaction_status'] )) {//收款银行简称
            $returnmessage['msg'] = "miss transaction_status";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1005";
            return json_encode( $returnmessage );
        }
        if (!isset( $message['sign'] ) || empty( $message['sign'] )) {//收款银行简称
            $returnmessage['msg'] = "miss sign";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1006";
            return json_encode( $returnmessage );
        }//post_balance
        if (!isset( $message['post_balance'] )) {//余额
            $returnmessage['msg'] = "miss post_balance";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1007";
            return json_encode( $returnmessage );
        }//post_balance
        $token = "j9dfmeDV3wX6nbAZY4MTJqcLFU2CGB8x";
        $sign = $this->getMcsing( $message, $token );
        if ($sign != $message['sign']) {
            $returnmessage['msg'] = "sign error";
            $returnmessage['status'] = 0;
            $returnmessage['error_code'] = "1007";
            return json_encode( $returnmessage );
        } else {
            //更新设备的余额
            $update['post_balance'] = $message['post_balance'] / 100;
            $db::table( 's_device' )->where( 'account_no', '=', $message['account_no'] )->update( $update );
            //判断是否有重复的订单
            $order = $db::table( 's_mcorder' )->where( 'bank_order_no', '=', $message['bank_order_no'] )->field( 'id' )->find();
            if ($order) {
                //如果有重复的订单号 将之前的订单删除 使用最新的订单
                $db::table( 's_mcorder' )->where( 'id', '=', $order['id'] )->delete();
            }
            //使用支付宝号找到代理账号
            $where['account_no'] = $message['account_no'];
            $channel = $db::table( 's_device' )->where( $where )->field( 'proxy_id' )->find()['proxy_id'];
            if ($channel) {
                $message['channel'] = $channel;
                //使用代理账号得到代理的费率
                $userwhere['user_name'] = $channel;
                $Rate = $db::table( 's_user' )->where( $userwhere )->field( 'Rate' )->find()['Rate'];
                $profit = ($message['transaction_amount'] * $Rate) / 10000;
                $message['profit'] = $profit;
            } else {
                $message['channel'] = 'no proxy';
                $message['profit'] = 0;
            }
            //失败的订单不计算利润
            if ($message['transaction_status'] == 0) {
                $message['profit'] = 0;
            }
            $message['add_time'] = time();
            $aa = $message['transaction_amount'] / 100;
            $message['transaction_amount'] = $aa;
            $result = $db::table( 's_mcorder' )->insert( $message );
            if ($result) {
                $returnmessage['status'] = 1;
                return json_encode( $returnmessage );
            } else {
                $returnmessage['msg'] = "server error";
                $returnmessage['status'] = 0;
                $returnmessage['error_code'] = "1007";
                return json_encode( $returnmessage );
            }

        }
    }

    //sign
    public function demosing()
    {
        // sign  = md5(phone+channel+address+time+token+body)
        $sign = md5( "15104615605" . "lg" . "188888888885" . "156337813" . "d9fce7705d4c6cf255f813caae68b00d" . "测试数据1" );
        echo $sign;

    }

    public function getip()
    {
        if (!empty( $_SERVER['HTTP_CLIENT_IP'] )) {
            $ip_address = "是否来自共享互联网" . $_SERVER['HTTP_CLIENT_IP'];
        } //ip是否来自代理

        elseif (!empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )) {
            $ip_address = "是否来自代理" . $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
//
////ip是否来自远程地址
//
        else {
            $ip_address = "是否来自远程地址" . $_SERVER['REMOTE_ADDR'];
        }

        echo $ip_address;
    }


}
