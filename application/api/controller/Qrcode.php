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
use app\admin\model\OrderModel;
use app\admin\model\SystemConfigModel;

class Qrcode extends Controller
{
	public function dump()
	{
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$this->assign('userAgent', $userAgent);
		return $this->fetch();
	}

    /**
     * 付款链接跳转  废弃
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCode()
    {
        $qrUrl = isset($_GET['qr_url']) ? $_GET['qr_url'] : 'http://www.baidu.com';
        $qrUrl1 = '"' . $qrUrl . '"';
        $amount = isset($_GET['amount']) ? $_GET['amount'] : '111111';//$_GET['amount'];
        $request = Request::instance();
        $ip = $request->ip();
        $this->assign('amount', $amount);
        $this->assign('qrUrl', $qrUrl1);
        $updateData['payerloginid'] = $ip;
        OrderModel::where('qr_url', '=', $qrUrl)
            ->where('amount', '=', $amount)
            ->where('order_status', '=', '0')
            ->order('id asc')
            ->limit(1)
            ->update($updateData);
        //获取收款人姓名 使用付款链接
        $db = new Db;
        $name = $db::table('s_device')->field('realName')->where('ali_qr', '=', $qrUrl)->find()['realName'];
//        $name = $db::table('s_device')->getLastSql();
        $this->assign('name', $name);
        return $this->fetch();//('Location: '.$qrUrl);exit;
    }


    /**
     * 收款二维码展示页面  未用
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function orderIndex(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        //收款链接有误
        if (!isset($message['orderUrl']) || empty($message['orderUrl'])) {
            echo "收款链接有误！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,payerloginid,add_time,card')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
            exit;
        }

        //计算倒计时
        $now = time();
        $orderPayLimitTime = SystemConfigModel::getPayLimitTime();
        $endTime = $orderData['add_time'] + $orderPayLimitTime;
        $countdownTime = bcsub($endTime, $now);
        if ($countdownTime < 0) {
            echo "订单超时，请重新下单！";
            exit;
        }
        if (empty($orderData)) {
            echo "订单不存在";
            exit;
        }

        //修改订单收款ip
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //银行卡信息
        $cardData = $db::table('s_bank_device')->field('bankMark,name,bank_card,bank_name')->where('bank_card', '=', $orderData['card'])->find();

        if (empty($cardData) || empty($cardData['bankMark']) || empty($cardData['name']) || empty($cardData['bank_name'])) {
            echo "收款信息异常，请重新下单";
            exit;
        }
        //修改订单ip
//		$ip = $request->ip();
//		$updateData['payerloginid'] = $ip;
//		$db::table('s_fu_order')->where('order_no','=',$message['orderNo'])->update($updateData);

        $this->assign('payableAmountShow', $orderData['payable_amount']);
        //拼接链接金额   //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        $this->assign('payableAmount', $payableAmount);

        //银行名称
        $bankName = '"' . $cardData['bank_name'] . '"';
        $this->assign('bankName', $bankName);


        //收款银行卡号
        $cardNo = '"' . $orderData['card'] . '"';
        $this->assign('cardNo', $cardNo);

        //银行简称
        $bankMark = '"' . $cardData['bankMark'] . '"';
        $this->assign('bankMark', $bankMark);

        //银行卡收款name
        $receiverName = '"' . $cardData['name'] . '"';
        $this->assign('receiverName', $receiverName);

        $this->assign('countdownTime', $countdownTime);

        $orderUrl = '"' . $message['orderUrl'] . '"';
        $this->assign('orderUrl', $orderUrl);
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();
    }

    /**
     * 收款二维码展示页面  未用
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */

    public function orderIndexNew(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        //收款链接有误
        if (!isset($message['orderUrl']) || empty($message['orderUrl'])) {
            echo "收款链接有误！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,payerloginid,add_time,card')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
            exit;
        }

        //计算倒计时
        $now = time();
        $orderPayLimitTime = SystemConfigModel::getPayLimitTime();
        $endTime = $orderData['add_time'] + $orderPayLimitTime;
        $countdownTime = bcsub($endTime, $now);
        if ($countdownTime < 0) {
            echo "订单超时，请重新下单！";
            exit;
        }
        if (empty($orderData)) {
            echo "订单不存在";
            exit;
        }

        //修改订单收款ip
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //银行卡信息
        $cardData = $db::table('s_bank_device')->field('bankMark,name,bank_card,bank_name')->where('bank_card', '=', $orderData['card'])->find();

        if (empty($cardData) || empty($cardData['bankMark']) || empty($cardData['name']) || empty($cardData['bank_name'])) {
            echo "收款信息异常，请重新下单";
            exit;
        }
        //修改订单ip
//		$ip = $request->ip();
//		$updateData['payerloginid'] = $ip;
//		$db::table('s_fu_order')->where('order_no','=',$message['orderNo'])->update($updateData);

        $this->assign('payableAmountShow', $orderData['payable_amount']);
        //拼接链接金额   //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        $this->assign('payableAmount', $payableAmount);

        //银行名称
        $bankName = '"' . $cardData['bank_name'] . '"';
        $this->assign('bankName', $bankName);


        //收款银行卡号
        $cardNo = '"' . $orderData['card'] . '"';
        $this->assign('cardNo', $cardNo);

        //银行简称
        $bankMark = '"' . $cardData['bankMark'] . '"';
        $this->assign('bankMark', $bankMark);

        //银行卡收款name
        $receiverName = '"' . $cardData['name'] . '"';
        $this->assign('receiverName', $receiverName);

        $this->assign('countdownTime', $countdownTime);


        $orderUrl = '"' . $message['orderUrl'] . '"';
        $this->assign('orderUrl', $orderUrl);
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();
    }

    /**
     * 当前为跳转支付宝转账页面  未用
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function aliPay(Request $request)
    {
        $message = $request->param();

        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单信息不全！";
            exit;
        }

        $db = new Db();
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,userId,order_no')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "未支付订单不存在，请重新下单";
            exit;
        }
        //获取收款人姓名 使用付款链接
        $db = new Db;
        //修改订单收款ip
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        //userId
        $userId = '"' . $orderData['userId'] . '"';
        $this->assign('orderNo', $orderData['order_no']);
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        $this->assign('payableAmount', $payableAmount);
        $this->assign('userId', $userId);
        return $this->fetch();
    }

    /**
     * 跳转支付宝转账页面（金额转账收款）  未用
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cardPay(Request $request)
    {
        $message = $request->param();

        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单信息不全！";
            exit;
        }

        $db = new Db();
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        //订单信息  取出 应付金额、订单号、转账银行卡
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,order_no,card')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "未支付订单不存在，请重新下单";
            exit;
        }
        //获取收款人姓名 使用付款链接
        $db = new Db;
        //修改订单收款ip
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //银行卡信息
        $cardData = $db::table('s_bank_device')->field('bankMark,name,bank_card,bank_name')->where('bank_card', '=', $orderData['card'])->find();

        if (empty($cardData) || empty($cardData['bankMark']) || empty($cardData['name']) || empty($cardData['bank_name'])) {
            echo "收款信息异常，请重新下单";
            exit;
        }
        //订单号
        $this->assign('orderNo', $message['orderNo']);
        //展示金额不加引号
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        //拼接链接金额   //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        $this->assign('payableAmount', $payableAmount);

        //银行名称
        $bankName = '"' . $cardData['bank_name'] . '"';
        $this->assign('bankName', $bankName);

        //收款银行卡号
        $cardNo = '"' . $orderData['card'] . '"';
        $this->assign('cardNo', $cardNo);

        //银行简称
        $bankMark = '"' . $cardData['bankMark'] . '"';
        $this->assign('bankMark', $bankMark);

        //银行卡收款name
        $receiverName = '"' . $cardData['name'] . '"';
        $this->assign('receiverName', $receiverName);
        return $this->fetch();
    }

    public function cardPayOldaaa(Request $request)
    {
        $message = $request->param();

        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单信息不全！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,card,order_no')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "未支付订单不存在，请重新下单";
            exit;
        }
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        //银行卡信息
        $cardData = $db::table('s_bank')->where('bank_card', '=', $orderData['card'])->find();
        if (empty($cardData)) {
            echo "收款银行卡暂时不存在，请重新下单";
            exit;
        }
        //修改订单收款ip
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        //银行卡号
        $cardNo = '"' . $cardData['bank_card'] . '"';
        //银行简称
        $bankMark = '"' . $cardData['bank_jc'] . '"';
        //银行卡用户名
        $receiverName = '"' . $cardData['name'] . '"';
        $this->assign('orderNo', $orderData['order_no']);
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        $this->assign('payableAmount', $payableAmount);
        $this->assign('cardNo', $cardNo);
        $this->assign('bankMark', $bankMark);
        $this->assign('receiverName', $receiverName);
        return $this->fetch();
    }

    /**
     * 当前测试使用区分安卓与其他  （ios拉不起安卓）
     * @param Request $request
     */
    public function orderStepOne(Request $request)
    {
        $message = $request->param();
        if (!isset($message) || empty($message['orderNo'])) {
            echo "订单信息不全！";
            exit;

        }
        //安卓拉起
        $agent = strpos($_SERVER['HTTP_USER_AGENT'], 'Android');

        $dataOne = "http://129.204.132.45/api/qrcode/cardPayLa?orderNo=" . $message['orderNo'];
        if ($agent) {
            $dataOne = urlencode($dataOne);
            //$payUrl = "http://129.204.132.45/api/qrcode/payUrl?urlData=".$dataOne.'&orderNo='.$message['orderNo'];
            header("Location: http://129.204.132.45/api/qrcode/payUrl?urlData=" . $dataOne . '&orderNo=' . $message['orderNo']);
            exit;
        }
        //其他采用截图保存付款
        $orderUrl = "http://129.204.132.45/api/qrcode/orderIndex?orderUrl=" . $dataOne . '&orderNo=' . $message['orderNo'];
        header("Location: http://129.204.132.45/api/qrcode/orderIndex?orderUrl=" . $dataOne . '&orderNo=' . $message['orderNo']);
        exit;
    }

    /**
     * 跳转支付宝转账页面（金额转账收款）  未用
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cardPayLa(Request $request)
    {
        $message = $request->param();

        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单信息不全！";
            exit;
        }
        $db = new Db();
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        //订单信息  取出 应付金额、订单号、转账银行卡
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,order_no,card')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "未支付订单不存在，请重新下单";
            exit;
        }
        //获取收款人姓名 使用付款链接
        $db = new Db;
        //修改订单收款ip
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);
        //银行卡信息
        $cardData = $db::table('s_bank_device')->field('bankMark,name,bank_card,bank_name')->where('bank_card', '=', $orderData['card'])->find();

        if (empty($cardData) || empty($cardData['bankMark']) || empty($cardData['name']) || empty($cardData['bank_name'])) {
            echo "收款信息异常，请重新下单";
            exit;
        }
        //订单号
        $this->assign('orderNo', $message['orderNo']);
        //展示金额不加引号
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        //拼接链接金额   //转账金额
        $payableAmount = '"' . $orderData['payable_amount'] . '"';
        $this->assign('payableAmount', $payableAmount);

        //银行名称
        $bankName = '"' . $cardData['bank_name'] . '"';
        $this->assign('bankName', $bankName);

        //收款银行卡号
        $cardNo = '"' . $orderData['card'] . '"';
        $this->assign('cardNo', $cardNo);

        //银行简称
        $bankMark = '"' . $cardData['bankMark'] . '"';
        $this->assign('bankMark', $bankMark);

        //银行卡收款name
        $receiverName = '"' . $cardData['name'] . '"';
        $this->assign('receiverName', $receiverName);
        return $this->fetch();
    }

    /**
     * 中银收款二维码展示页面  当前
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function zhPayShowQr(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_momo_order')
            ->field('payable_amount,qr_url,add_time')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 3)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
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
        if (empty($orderData)) {
            echo "订单不存在";
            exit;
        }

        //修改订单收款ip
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);

        //展示金额
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        $this->assign('countdownTime', $countdownTime);
        $payUrl = '"' . $orderData['qr_url'] . '"';
        $this->assign('payUrl', $payUrl);
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();
    }

    /**
     * 测试使用
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function zhPayShowQrTestOne(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }

        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,qr_url,add_time')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
            exit;
        }
        header("Location: http://115.231.162.39:8000/api/qrcode/zhPayShowQrTest?orderNo=" . $message['orderNo']);
        exit;
    }

    /**
     * 收款二维码展示页面  测试
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function zhPayShowQrTest(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,qr_url,add_time')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
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
        if (empty($orderData)) {
            echo "订单不存在";
            exit;
        }

        //修改订单收款ip
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $db::table('s_fu_order')->where('order_no', '=', $message['orderNo'])->update($updateData);

        //展示金额
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        $this->assign('countdownTime', $countdownTime);
        $payUrl = '"' . $orderData['qr_url'] . '"';
        $this->assign('payUrl', $payUrl);
        $this->assign('orderNo', $message['orderNo']);

        $dataOne = $payUrl;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') || strpos($_SERVER['HTTP_USER_AGENT'], 'iOS)')) {
            $dataOne = urlencode($dataOne);
            //$payUrl = "http://129.204.132.45/api/qrcode/payUrl?urlData=".$dataOne.'&orderNo='.$message['orderNo'];
            header("Location: http://115.231.162.39:8000/api/qrcode/payUrl?urlData=" . $dataOne . '&orderNo=' . $message['orderNo']);
            exit;
        }
        return $this->fetch();
    }

    /**
     * 拉起支付宝使用  当前测试（拉不起）
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payUrl()
    {
        $orderNo = isset($_GET['orderNo']) ? $_GET['orderNo'] : 'xxx';
        $db = new Db();
        $orderData = $db::table('s_fu_order')
            ->field('payable_amount,qr_url,add_time')
            ->where('order_no', '=', $orderNo)
            ->where('order_status', '=', 0)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
            exit;
        }
        if (empty($orderNo)) {
            echo "订单号有误！";
            exit;
        }
        $urlData = urlencode($orderData['qr_url']);
        $urlData = '"' . $urlData . '"';
        $orderNo = '"' . $orderNo . '"';
        $this->assign('urlData', $urlData);
        $this->assign('orderNo', $orderNo);
        return $this->fetch();
    }

    /**
     * 聚合支付收款二维码展示页面
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function showQr(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        $db = new Db();
        $orderData = $db::table('s_momo_order')
            ->field('payable_amount,qr_url,add_time')
            ->where('order_no', '=', $message['orderNo'])
            ->where('order_status', '=', 3)
            ->find();
        if (empty($orderData)) {
            echo "请重新下单";
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
        if (empty($orderData)) {
            echo "订单不存在";
            exit;
        }

        //修改订单收款ip
        $ip = $request->ip();
        $updateData['payerloginid'] = $ip;
        $db::table('s_momo_order')->where('order_no', '=', $message['orderNo'])->update($updateData);

        //展示金额
        $this->assign('payableAmountShow', $orderData['payable_amount']);
        $this->assign('countdownTime', $countdownTime);
        $momoQr = "alipays://platformapi/startApp?appId=20000125&orderSuffix=".$orderData['qr_url'];
        $payUrl = '"' . $momoQr . '"';
        $this->assign('payUrl', $payUrl);
        $this->assign('orderNo', $message['orderNo']);
        return $this->fetch();
    }
}