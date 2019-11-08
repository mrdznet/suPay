<?php

namespace app\api\model;

use app\api\model\DeviceModel;
use think\Db;
use think\Model;
use app\admin\model\SystemConfigModel;
use app\admin\model\Merchant;

class OrderModel extends Model
{
    // 确定链接表名
    protected $name = 'order';

    /**
     * 订单入库
     * @param $message
     * @return array
     */
    public function createOrderForBankPay($message)
    {
        try{
            $orderModel = new self();
            $result = $orderModel::create($message);
            if($result){
                return arrayReturn('10000','','添加成功');
            }else{
                return arrayReturn(2,'',$this->getError());
            }
        }
        catch(\Exception $e){
            return arrayReturn(-2,'',$e->getMessage());
        }
    }

    /**
     * 手机银行回调匹配订单
     * @param $message
     * @return array|bool
     */
    public static function matchOrderForBankPay($message)
    {
        try{
            //打款人姓名
            if(!isset($message['PayCardUser'])||empty($message['PayCardUser'])){
                return arrayReturn('10001','','missing_parameters_PayCardUser');
            }
            //打款金额
            if(!isset($message['PayMoney'])||empty($message['PayMoney'])){
                return arrayReturn('10002','','missing_parameters_PayMoney');
            }
            //收款账号
            if(!isset($message['RecvCardNo'])||empty($message['RecvCardNo'])){
                return arrayReturn('10003','','missing_parameters_RecvCardNo');
            }
            if(!isset($message['Channel'])||empty($message['Channel'])){
                return arrayReturn('10001','','missing_parameters_Channel');
            }
            if(!isset($message['PayTime'])||empty($message['PayTime'])){
                $message['PayTime'] =time();
//                return arrayReturn('10001','','missing_parameters_PayTime');
            }
            $limitTime = SystemConfigModel::getPayLimitTime();
            $message['PayTime'] = $message['PayTime']+30;
            $lockTime = $message['PayTime']-$limitTime;
            $orderModel = new OrderModel();
            $orderWhere['channel'] = $message['Channel'];
            //判断付款方式 如果PayCardUser为"支付宝（中国）网络技术有限公司" 则截取 PayComment当作支付者姓名
            $orderWhere['player_name'] = $message['PayCardUser'];
            if($message['PayCardUser'] == "支付宝（中国）网络技术有限公司"){
                $orderWhere['player_name'] = substr($message['PayComment'],0,strrpos($message['PayComment'],"支付宝转账"));
            }
            $orderWhere['amount'] = $message['PayMoney'];
            $orderWhere['card'] = $message['RecvCardNo'];
            $orderWhere['order_status'] = 0;//只匹配未付款的订单
            $orderData = $orderModel::where($orderWhere)->where('add_time','>',$lockTime)->find();
            //未匹配到订单
            if(!$orderData){
                $lastSql = $orderModel->getLastSql();
                logs(json_encode(['orderWhere'=>$orderWhere,'orderData'=>$orderData,'lastSql'=>$lastSql]),'match_order_fail_log');
                return arrayReturn('20001','','未匹配到订单'.$lastSql);
            }
            //匹配到了订单

            return arrayReturn('10000',$orderData,'匹配到订单');
        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'match_order_for_bank_pay_exception');
            return arrayReturn('19999','','order_notify_callback_error_exception');

        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'match_order_for_bank_pay_error');
            return arrayReturn('29999','','order_notify_callback_error');
        }
    }

    /**
     * 回调成功本地修改订单状态 更新设备收款额
     * @param $successOrderData
     * @param $notifyMoney
     * @return array
     */
    public static function doNotifySuccess($successOrderData, $notifyMoney,$otder_status,$sms,$sms_md5,$notifyCallbackResult)
    {
        //开启事务
        $db = new Db();
        $db::startTrans();
        $updateOrderRes = false;
        $updateBankDeviceRes = false;
        $bindres = false;
        try {
            $orderModel = new self();
            $deviceModel = new DeviceModel();
            //1、修改订单状态
            $updateOrderRes = $orderModel::where('order_no', $successOrderData['order_no'])
                ->update(
                    [
                        'order_status' => $otder_status,
                        'actual_amount' => $notifyMoney,
                        'time_update' => time(),
                        'pay_time' => time(),
                        'sms' => $sms,
                        'sms_md5' => $sms_md5,
                        'notifyCallbackResult' => $notifyCallbackResult
                    ]
                );
            //2、添加设备收款额度
            $updateBankDeviceRes = false;
            if($successOrderData['order_status'] ==0){
                $updateBankDeviceRes = $deviceModel->where('card', $successOrderData['card'])
                    ->update(
                        [
                            'lock_time'=>0,
                            'total_money' => Db::raw("total_money + $notifyMoney"),
                            'today_money' => Db::raw("today_money+ $notifyMoney"),
                            'remains' => Db::raw("remains+ $notifyMoney"),
                        ]
                    );
            }elseif ($successOrderData['order_status'] ==4){
                $updateBankDeviceRes = $deviceModel->where('card', $successOrderData['card'])
                    ->update(
                        [
                            'total_money' => Db::raw("total_money + $notifyMoney"),
                            'today_money' => Db::raw("today_money+ $notifyMoney"),
                            'remains' => Db::raw("remains+ $notifyMoney"),
                        ]
                    );
            }

            //3、
            $insertData['receivables'] =$successOrderData['card'];
            $insertData['payuserid'] =$successOrderData['payuserid'];
            $count = $db::table('s_receivables_bind')->where($insertData)->find();
            if(!$count){
                $bindres = $db::table('s_receivables_bind')->insert($insertData);
            }else{
                $bindres = $db::table('s_receivables_bind')->where('id','=',$count['id'])
                    ->update([
                        'number_of_use' => Db::raw("number_of_use+1"),//'total_sum+'.$amounts
                    ]);
            }
            if ($updateOrderRes && $updateBankDeviceRes && $bindres) {
                //所有操作完成提交事务
                $db::commit();
                return arrayReturn('10000',$successOrderData['order_no'],'do_local_notify_success');

            } else {
                $db::rollback();
                return arrayReturn('19999','','local_transaction_failed');
            }
        } catch (\Exception $exception){
            $db::rollback();
            return arrayReturn('19999','',$exception->getMessage());

        }catch (\Error $error){
            $db::rollback();
            return arrayReturn('29999','',$error->getMessage());
        }
    }

    /**
     * 封装回调总后台订单数据
     * @param $orderData
     * @param $notifyMoney
     * @return bool|string
     */
    public static function doNotifyToMainStationData($orderData, $notifyMoney)
    {
        try{
            $notifyData["status"] = "10000";   // 支付成功
            $notifyData["time"] = time();   //订单支付时间;
            $notifyData["trade_amount"] = $orderData['amount'];   //订单金额;
//            $notifyData["receipt_amount"] = $notifyMoney*1.005;  //实际支付金额;
            $notifyData["receipt_amount"] = $notifyMoney;  //实际支付金额;
            $notifyData["order_no"] = $orderData['order_no'];   //商户订单号;
            $merchantModel = new Merchant();
            $token = $merchantModel::field('token')->where('merchant_id', '=', $orderData['merchant_id'])->find()['token'];
            $notifyData["sig"] = md5($orderData['merchant_id'] . $token . $notifyData["trade_amount"] . $notifyData["receipt_amount"] . $notifyData["time"]);
            return json_encode($notifyData);

        }catch (\Exception $exception){
            logs(json_encode(['orderData'=>$orderData,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'notify_to_main_station_exception');
            return apiJsonReturn('19999','order_notify_callback_error_exception');

        }catch (\Error $error){
            logs(json_encode(['orderData'=>$orderData,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'notify_to_main_station_error');
            return apiJsonReturn('29999','order_notify_callback_error');
        }
    }

    /**
     * 短信回调匹配订单   当前锁金额
     * @param $amount
     * @param $time
     * @param $card
     * @return array
     */
    public function matchOrderForSms($amount,$time,$card)
    {
        $lastSql = "";
        try{
            $orderWhere['payable_amount'] = $amount;
            $orderWhere['card'] = $card;
            $orderData = $this->where($orderWhere)
                ->where('add_time','>',$time-600)
                ->where('add_time','<=',$time+60)
                ->where('order_status = 0 or order_status = 4')
                ->where('is_come','=',1)
                ->order('id desc')
                ->find();
            //未匹配到订单

            $lastSql = $this->getLastSql();
            if(!$orderData){
                return arrayReturn('20001','','未匹配到订单'.$lastSql);
            }
            //匹配到了订单
            return arrayReturn('10000',$orderData, $lastSql);
        }catch (\Exception $exception){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$exception->getFile(),'lastSql'=>$lastSql,'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'matchOrderForSms_exception');
            return arrayReturn('19999','',"错误1".$lastSql);
        }catch (\Error $error){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$error->getFile(),'lastSql'=>$lastSql,'line'=>$error->getLine(),'error'=>$error->getMessage()]),'matchOrderForSms_error');
            return arrayReturn('19999','',"错误2".$lastSql);
        }
    }

    /**
     * 短信回调匹配订单  不适用金额
     * @param $amount
     * @param $time
     * @param $card
     * @return array
     */
    public function matchOrderForSmsWithScopeMoney($amount,$time,$card,$username)
    {
        if($username == ''){
            return arrayReturn('20001','',  '姓名为空 不做范围匹配');
        }
        $Scope =  SystemConfigModel::ModifiedAmountCallableScope();
        $ScopeStart = $amount-$Scope;
        $ScopeEnd = $amount+$Scope;
        $lastSql = "";
        try{
            $orderWhere['card'] = $card;
            $orderWhere['player_name'] = $username;
            $orderData = $this->where($orderWhere)
                ->where('add_time','>',$time-600)
                ->where('add_time','<=',$time+60)
                ->where('order_status = 0 or order_status = 4')
                ->where('is_come','=',1)
                ->where('payable_amount','>=',$ScopeStart)
                ->where('payable_amount','<=',$ScopeEnd)
                ->order('id desc')
                ->find();
            //未匹配到订单

            $lastSql = $this->getLastSql();
            if(!$orderData){
                return arrayReturn('20001','','未匹配到订单'.$lastSql);
            }
            //匹配到了订单
            return arrayReturn('10000',$orderData, $lastSql);
        }catch (\Exception $exception){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$exception->getFile(),'lastSql'=>$lastSql,'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'matchOrderForSms_exception');
            return arrayReturn('19999','',"错误1".$lastSql);
        }catch (\Error $error){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$error->getFile(),'lastSql'=>$lastSql,'line'=>$error->getLine(),'error'=>$error->getMessage()]),'matchOrderForSms_error');
            return arrayReturn('19999','',"错误2".$lastSql);
        }
    }
    /**
     * 短信回调匹配订单  不适用金额
     * @param $amount
     * @param $time
     * @param $card
     * @return array
     */
    public function matchOrderForSmsWithoutMoney($amount,$time,$card)
    {
        $lastSql = "";
        try{
            $orderWhere['card'] = $card;
            $orderData = $this->where($orderWhere)
                ->where('add_time','>',$time-600)
                ->where('add_time','<=',$time+60)
                ->where('is_come','=',1)
                ->where('order_status = 0 or order_status = 4')
                ->order('id desc')
                ->find();
            //未匹配到订单
            $lastSql = $this->getLastSql();
            if(!$orderData){
                return arrayReturn('20001','','未匹配到订单'.$lastSql);
            }
            //匹配到了订单
            return arrayReturn('10000',$orderData, $lastSql);
        }catch (\Exception $exception){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$exception->getFile(),'lastSql'=>$lastSql,'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'matchOrderForSms_exception');
            return arrayReturn('19999','',"错误1".$lastSql);
        }catch (\Error $error){
            logs(json_encode(['amount'=>$amount,'time'=>$time,'card'=>$card,'file'=>$error->getFile(),'lastSql'=>$lastSql,'line'=>$error->getLine(),'error'=>$error->getMessage()]),'matchOrderForSms_error');
            return arrayReturn('19999','',"错误2".$lastSql);
        }
    }



}
