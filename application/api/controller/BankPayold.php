<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/15
 * Time: 19:53
 */

namespace app\api\controller;

use think\Db;
use think\Controller;
use think\Request;
use app\api\model\DeviceModel;
use app\api\model\NotifyCallBackLogModel;
use app\admin\model\SystemConfigModel;

class BankPayold extends Controller
{
    public function test(Request $request)
    {
        $message = $request->param();
        //玩家提交发款者真实姓名
        if (!isset($message['orderNo'])||empty($message['orderNo'])) {
            echo "链接有误";
            exit;
        }
        $orderLogWhere['order_no'] = $message['orderNo'];
//            $limitTime = SystemConfigModel::getPayLimitTime();
//            $lockTime = time() - $limitTime;
        $db = new Db();
        $orderData = $db::table('s_order')->where($orderLogWhere)->find();
        if (empty($orderData)) {
            echo "订单不存在!!!!!";
            exit;
        }
        if ($orderData['order_status'] == '1') {
            echo "此订单已支付成功！！！！！";
            exit;
        }
        if(empty($orderData['card'])||empty($orderData['amount'])||empty($orderData['bank_name'])||empty($orderData['name'])){
            echo "此订单异常！！！！！";
            exit;
        }
        $this->assign('money', $orderData['amount']);  //下单金额
        $this->assign('card', $orderData['card']);  //收款银行卡号
        $this->assign('bankName', $orderData['bank_name']);    //收款银行卡名称
        $this->assign('name', $orderData['name']);    //收款者真实姓名
        $this->assign('player_name', $orderData['player_name']);    //付款者真实姓名
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();
    }

    /**
     * ajax 获取用户提交汇款人姓名
     * @param Request $request
     * @return \think\response\Json
     */
    public function ajaxPutName(Request $request)
    {
        $message = $request->param();
//        dump($message);die();
        try {
            if (!isset($message['orderNo']) || empty($message['orderNo'])) {

                return json(msg('101', '', '订单号不能为空'));
            }

            $orderLogWhere['order_no'] = $message['orderNo'];
            $db = new Db();
            $orderData = $db::table('s_order')->where($orderLogWhere)->find();
            if (empty($orderData)) {
                return json(msg('101', '', '订单不存在,请重新下单'));
//                return apiJsonReturn('101', '订单不存在,请重新下单');
            }

            if (!isset($message['player_name'])||empty($message['player_name'])) {
//                return json('', '102', '请输入汇款人姓名！');
//                return apiJsonReturn('102', '请输入汇款人姓名!');
                return json(msg('102', '', '请输入汇款人姓名！'));
            }
            /**
             * 中文名
             */
            if (!isAllChinese($message['player_name'])) {
//                return json('', '102', '请输入汇款人姓名！');
//                return apiJsonReturn('102', '请输入汇款人姓名!');
                return json(msg('103', '', '汇款人姓名格式不对！'));
            }
            //order
            $updateOrder = $db::table('s_order')->where($orderLogWhere)->find();
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['card'] = $orderData['card'];
            $deviceModel = new DeviceModel();
            $isCanUseDeviceData = $deviceModel::where($deviceWhere)->find();
//        if(empty($isCanUseDeviceData)){
//            unset($deviceWhere['card']);
//            $deviceData = $deviceModel::where($deviceWhere)->where('update_time','>',$time)->order('today_money,last_use_time asc')->find();
//            if(empty($deviceData)){
//                return apiJsonReturn('103','支付繁忙，请稍后重试！');
//            }
//            $changOrderData['channel'] = $deviceData['channel'];
//            $changOrderData['account'] = $deviceData['phone'];
//            $changOrderData['card'] = $deviceData['card'];
//            $changOrderData['bank_name'] = $deviceData['bank_name'];
//            $changOrderData['bank_mark'] = $deviceData['bank_mark'];
//            $changOrderStatus = $db::table('s_order')->where('order_no', $message['orderNo'])->update($changOrderData);
//            if(!$changOrderStatus){
//                return apiJsonReturn('104', '提交失败，请刷新页面重新提交！');
//            }
//        }
            $lastChangOrderWhere['order_no'] = $message['orderNo'];
            $lastChangOrderData['player_name'] = deleteStringSpace($message['player_name']);
            $doUpdate =true;
            if($orderData['player_name']==$lastChangOrderData['player_name']){
                $doUpdate = false;
            }

            if($doUpdate){
                $lastChangOrderStatus = $db::table('s_order')->where('order_no', $message['orderNo'])->update($lastChangOrderData);
            }else{
                $lastChangOrderStatus =true;
            }

            if(!$lastChangOrderStatus){
                $lastSql = $db::table('s_order')->getLastSql();
                $errorOrderData = $db::table('s_order')->where($orderLogWhere)->find();
                logs(json_encode(['message'=>$message,'lastSql'=>$lastSql,'lastChangOrderStatus'=>$lastChangOrderStatus,'orderData'=>$orderData,'isCanUseDeviceData'=>$isCanUseDeviceData]),'ajax_put_name_update_error');
                return json(msg('199', '', '提交失败,暂停充值！'));
//                return apiJsonReturn('199', '提交失败,暂停充值！');
            }else{
//                return apiJsonReturn('100', '提交成功,请放心充值！');
                return json(msg('100', '', '提交成功,请放心充值！'));
            }
        }catch (\Exception $exception){
            logs(json_encode(['message'=>$message,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'ajax_put_name_exception');
            return json(msg('199', '', '提交异常,暂停充值！'));

        }catch (\Error $error){
            logs(json_encode(['message'=>$message,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'ajax_put_name_error');
            return json(msg('199', '', '提交错误,暂停充值！'));

        }

    }

    /**
     * 订单页面
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $message = $request->param();
        //玩家提交发款者真实姓名
        if (!isset($message['orderNo'])||empty($message['orderNo'])) {
            echo "链接有误";
            exit;
        }
        $orderLogWhere['order_no'] = $message['orderNo'];
//            $limitTime = SystemConfigModel::getPayLimitTime();
//            $lockTime = time() - $limitTime;
        $db = new Db();
        $orderData = $db::table('s_order')->where($orderLogWhere)->find();
        if (empty($orderData)) {
            echo "订单不存在!!!!!";
            exit;
        }
        if ($orderData['order_status'] == '1') {
            echo "此订单已支付成功！！！！！";
            exit;
        }

        if ($orderData['order_status'] == '4') {
            echo "此订单已经超时，请重新下单！！！！！";
            exit;
        }
        //计算倒计时
        $now = time();
        $orderPayLimitTime = SystemConfigModel::getPayLimitTime();
        $orderPayLimitTime = $orderPayLimitTime - 60;
        $endTime = $orderData['add_time'] + $orderPayLimitTime;
        $countdownTime = bcsub($endTime, $now);
        if ($countdownTime < 0) {
            echo "订单超时，请重新下单！";
            exit;
        }
        if(empty($orderData['card'])||empty($orderData['amount'])||empty($orderData['bank_name'])||empty($orderData['name'])){
            echo "此订单异常！！！！！";
            exit;
        }
        $this->assign('money', $orderData['amount']);  //下单金额
        $this->assign('tomoney', $orderData['amount']*1.005);  //下单金额
        $this->assign('card', $orderData['card']);  //收款银行卡号
        $this->assign('bankName', $orderData['bank_name']);    //收款银行卡名称
        $this->assign('name', $orderData['name']);    //收款者真实姓名
        if($orderData['player_name'] == "张三" ||$orderData['player_name'] == "李四"){
            $orderData['player_name'] = "";
        }
        $this->assign('player_name', $orderData['player_name']);    //付款者真实姓名
        $this->assign('countdownTime', $countdownTime);    //付款者真实姓名
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();

    }

    /**
     * 浦发银行上传条数 SPDB  兴业银行 CIB
     */
    public function getCountByRecvCardNo()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try{
            if (!isset($message['RecvCardMark']) || empty($message['RecvCardMark'])) {
                return apiJsonReturn('10001', "missing_parameters_RecvCardMark");
            }
            if (!isset($message['RecvCardNo']) || empty($message['RecvCardNo'])) {
                return apiJsonReturn('10001', "missing_parameters_RecvCardNo");
            }
//            if (!isset($message['StartTime']) || empty($message['StartTime'])) {
//                return apiJsonReturn('10001', "missing_parameters_StartTime");
//            }
//            if (!isset($message['EndTime']) || empty($message['EndTime'])) {
//                return apiJsonReturn('10001', "missing_parameters_EndTime");
//            }
//            if(!is_numeric($message['StartTime'])||strlen($message['StartTime'])!=10){
//                return apiJsonReturn('10002', "parameters_StartTime_error");
//            }
//            if(!is_numeric($message['EndTime'])||strlen($message['EndTime'])!=10){
//                return apiJsonReturn('10002', "parameters_EndTime_error");
//            }
            $notifyCallBackLogModel = new NotifyCallBackLogModel();
            $count = $notifyCallBackLogModel::where('RecvCardMark','=',$message['RecvCardMark'])
                ->where('RecvCardNo','=',$message['RecvCardNo'])
                ->where('PayTime','>=',strtotime(date('Y-m-d',strtotime('-1 day'))))
                ->where('PayTime','<=',time())
                ->count();
            if(!is_numeric($count)){
                return apiJsonReturn('10000', "success",'0');
            }

            $return['upload_quantity'] = (int)$count;
            return apiJsonReturn('10000', "success", $return);

        }catch (\Exception $exception){
            logs(json_encode(['message'=>$message,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'get_count_forSPDB_exception');
            return apiJsonReturn('19999','order_notify_callback_error');
        }catch (\Error $error){
            logs(json_encode(['message'=>$message,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'get_count_forSPDB_error');
            return apiJsonReturn('29999','order_notify_callback_error');
        }
    }
}