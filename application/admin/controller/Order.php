<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 18:57
 */

namespace app\admin\controller;

use app\admin\model\Helper;
use think\Db;
use app\admin\model\OrderModel;
use app\admin\model\FuCheckOrderModel;
use think\Request;

class Order extends Base
{
    // 渠道支付宝列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['order_no'] = ['=', deleteStringSpace($param['searchText'])];
            }
            if (!empty($param['searchStartTime'])) {
                $where['add_time'] = ['>', $param['searchStartTime']];
            }
            if (!empty($param['searchEndTime'])) {
                $where['add_time'] = ['=', $param['searchEndTime']];
            }
            if (!empty($param['amount'])) {
                $where['amount'] = ['=', $param['amount']];
            }
            if (!empty($param['payable_amount'])) {
                $where['payable_amount'] = ['=', $param['payable_amount']];
            }
            if (!empty($param['phone'])) {
                $where['account'] = ['=', deleteStringSpace($param['phone'])];
            }
            if (!empty($param['card'])) {
                $where['card'] = ['=', deleteStringSpace($param['card'])];
            }
            if (!empty($param['name'])) {
                $where['name'] = ['=', deleteStringSpace($param['name'])];
            }
            if (!empty($param['player_name'])) {
                $where['player_name'] = ['=', deleteStringSpace($param['player_name'])];
            }
            if (!empty($param['order_status'])) {
                $where['order_status'] = ['=', $param['order_status']];
            }
            if (!empty($param['payuserid'])) {
                $where['payuserid'] = ['=', $param['payuserid']];
            }
            if (!empty($param['channel'])) {
                $where['channel'] = ['=', $param['channel']];
            }
            if (!empty($param['sms'])) {
                $where['sms'] = ['=', $param['sms']];
            }
            $merchantId = session('username');
            if($merchantId!='nimdaistrator'&&$merchantId!='studio_kf'){//1bA8
                $where['channel'] = ['=',  $merchantId ];
            }
            $Order = new OrderModel();
            $selectResult = $Order->getOrderListByWhere($where, $offset, $limit);

            foreach ($selectResult as $key => $vo) {
//                $selectResult[$key]['time_update'] = date('Y-m-d H:i:s',$selectResult[$key]['time_update']);
                $selectResult[$key]['add_time'] = date('Y-m-d H:i:s', $selectResult[$key]['add_time']);
                if ($selectResult[$key]['pay_time'] != 0) {
                    $selectResult[$key]['pay_time'] = date('Y-m-d H:i:s', $selectResult[$key]['pay_time']);
                }
                if ($selectResult[$key]['time_update'] != 0) {
                    $selectResult[$key]['time_update'] = date('Y-m-d H:i:s', $selectResult[$key]['time_update']);
                }

                if ($selectResult[$key]['order_status'] == '0') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-info">订单受理中</span>';
                    $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
                }
                if ($selectResult[$key]['order_status'] == '1') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-primary">完成且回调</span>';
                }
                if ($selectResult[$key]['order_status'] == '2') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-important">下单失败</span>';
                }
                if ($selectResult[$key]['order_status'] == '3') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-warning">修改金额（允许范围内）自动回调</span>';
                }
                if ($selectResult[$key]['order_status'] == '4') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-danger">超时未统计</span>';
                    $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
                }
                if ($selectResult[$key]['order_status'] == '5') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-success">手动回调</span>';
                }
                if ($selectResult[$key]['is_come'] == '0') {
                    $selectResult[$key]['is_come'] = '<span  class="label label-info">用户未提交</span>';
                }
                if ($selectResult[$key]['is_come'] == '1') {
                    $selectResult[$key]['is_come'] = '<span  class="label label-success">用户已提交</span>';
                }


            }

            $return['total'] = $Order->getOrderListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        $Order = new OrderModel();
        $day = date("Y-m-d",strtotime("-1 days"));
        $this->assign('day',$day);
        $time = strtotime($day);
        $count = $Order->getOrderCount($time);//getOrdersecoundCount
        $successcount = $Order->getOrdersecoundCount($time);//getOrdersecoundCount
        $successcount15 = $Order->getOrdersuccessCount($time);
        $this->assign('translatecount',$count);
        $this->assign('successcount',$successcount);
        $this->assign('successcount15',$successcount15);
        return $this->fetch();
    }

    // mc订单列表
    public function mcorder()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            //order_no  bank_order_no account_name account_no trx_name trx_account_no transaction_status channel
            if (!empty($param['order_no'])) {
                $where['order_no'] = ['=', deleteStringSpace($param['order_no'])];
            }
            if (!empty($param['bank_order_no'])) {
                $where['bank_order_no'] = ['=', $param['bank_order_no']];
            }
            if (!empty($param['account_name'])) {
                $where['account_name'] = ['=', $param['account_name']];
            }
            if (!empty($param['account_no'])) {
                $where['account_no'] = ['=', $param['account_no']];
            }
            if (!empty($param['trx_name'])) {
                $where['trx_name'] = ['=', $param['trx_name']];
            }
            if (!empty($param['trx_account_no'])) {
                $where['trx_account_no'] = ['=', deleteStringSpace($param['trx_account_no'])];
            }
            if ($param['transaction_status'] != '') {
                $where['transaction_status'] = ['=', $param['transaction_status']];
            }
            if (!empty($param['channel'])) {
                $where['channel'] = ['=', $param['channel']];
            }
            $merchantId = session('username');
            if($merchantId!='nimdaistrator'&&$merchantId!='agent_total'){//1bA8
                $where['channel'] = ['=',  $merchantId ];
            }
            $Order = new OrderModel();
            $selectResult = $Order->getMcOrderListByWhere($where, $offset, $limit);

            foreach ($selectResult as $key => $vo) {
//                $selectResult[$key]['time_update'] = date('Y-m-d H:i:s',$selectResult[$key]['time_update']);
                $selectResult[$key]['add_time'] = date('Y-m-d H:i:s', $selectResult[$key]['add_time']);
                if ($selectResult[$key]['finish_time'] != 0) {
                    $selectResult[$key]['finish_time'] = date('Y-m-d H:i:s', $selectResult[$key]['finish_time']);
                }
                if ($selectResult[$key]['submit_time'] != 0) {
                    $selectResult[$key]['submit_time'] = date('Y-m-d H:i:s', $selectResult[$key]['submit_time']);
                }
                if ($selectResult[$key]['transaction_status'] == '0') {
                    $selectResult[$key]['transaction_status'] = '<span  class="label label-danger">订单失败</span>';
                }
                if ($selectResult[$key]['transaction_status'] == '1') {
                    $selectResult[$key]['transaction_status'] = '<span  class="label label-primary">订单完成</span>';
                }
                if ($selectResult[$key]['transaction_status'] == '2') {
                    $selectResult[$key]['transaction_status'] = '<span  class="label label-important">未知</span>';
                }
                if ($selectResult[$key]['account_type'] == '1') {
                    $selectResult[$key]['account_type'] = 'PC网银';
                }
                if ($selectResult[$key]['account_type'] == '2') {
                    $selectResult[$key]['account_type'] = '支付宝APP';
                }
//                if ($selectResult[$key]['transaction_status'] == '3') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-warning">修改金额（允许范围内）自动回调</span>';
//                }
//                if ($selectResult[$key]['transaction_status'] == '4') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-danger">超时未统计</span>';
//                }
//                if ($selectResult[$key]['transaction_status'] == '5') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-success">手动回调</span>';
//                }
            }

            $return['total'] = $Order->getmcOrderListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
        return $this->fetch();
    }

    // 提现订单列表
    public function tixianorder()
    {
        if (request()->isAjax()) {
            $param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;
            $where = [];
            //order_no  bank_order_no account_name account_no trx_name trx_account_no transaction_status channel
            if (!empty($param['user_name'])) {
                $where['user_name'] = ['=', $param['user_name']];
            }
            $merchantId = session('username');
            if($merchantId!='nimdaistrator'&&$merchantId!='agent_total'){//1bA8
                $where['user_name'] = ['=',  $merchantId ];
            }
            $Order = new OrderModel();
            $selectResult = $Order->gettxOrderListByWhere($where, $offset, $limit);
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['create_time'] = date('Y-m-d H:i:s', $selectResult[$key]['create_time']);
//                if ($selectResult[$key]['transaction_status'] == '0') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-danger">订单失败</span>';
//                }
//                if ($selectResult[$key]['transaction_status'] == '1') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-primary">订单完成</span>';
//                }
//                if ($selectResult[$key]['transaction_status'] == '2') {
//                    $selectResult[$key]['transaction_status'] = '<span  class="label label-important">未知</span>';
//                }
            }
            $return['total'] = $Order->gettxOrderListCount($where);  // 总数据
            $return['rows'] = $selectResult;
            return json($return);
        }
        return $this->fetch();
    }


    /**
     * 掉单表
     */
    public function loseOrder()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['account'] = ['=', $param['searchText']];
            }
            if (!empty($param['payableAmount'])) {
                $where['actual_amount'] = ['=', $param['payableAmount']];
            }
            $db = new Db();
            $selectResult = $db::table('s_lose_orders')->where($where)->limit($offset, $limit)->order('id desc')->select();
            foreach ($selectResult as $key => $vo) {
                $selectResult[$key]['create_time'] = date('Y-m-d H:i:s', $selectResult[$key]['create_time']);
            }

            $return['total'] = $db::table('s_lose_orders')->where($where)->count();  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    public function orderEdit()
    {
        $id = input('param.id');

        $this->assign(
            [
                'order' => Db::table('s_order')->where('id', $id)->find()
            ]
        );
        return $this->fetch();
    }

    public function notify()
    {

        $id = input('param.id');
        $order = Db::table('s_order')->where('id', $id)->find();
        $amount = input('param.actual_amount');
        $remains = input('param.remains');
        $sms = input('param.sms');
        if(empty($amount) ||$amount == ''){
            return json(msg(-1, '', '回调金额不能为空'));
        }
        $money = $amount;
        $result = action('devices/manualCallBackForMoMo', [$order, $money]);
        if ($result == 'success') {
            $this->success('回调成功', 'Order/index');
        } else {
            echo $result;
            exit;

//            return msg(-2, '', '回调失败');
        }
    }

    public function testCreateOrder()
    {
//        $db = new Db();
        if (request()->isPost()) {
            $param = input('post.');
            if ($param['amount'] == '') {//
                return json(msg(-1, '', '请填写下单金额'));
            }
            if (is_numeric($param['amount']) != 1) {//
                return json(msg(-1, '', '请输入数值'));
            }
            if ($param['card'] == '') {//
                return json(msg(-1, '', '请填写指定设备号'));
            }
            $url = "http://47.112.223.187:8899/api/orderinfo/createOrderTest";
            $amount = json_encode(['amount' => $param['amount'], 'card' => $param['card']]);
            $headers = ['Content-Type:application/json'];
            $callback_result = cUrlGetData($url, $amount, $headers);
            $callback_result = json_decode($callback_result, true);
            if ($callback_result['code'] != '100000') {
                return json(msgs(-1,$callback_result['code'], $callback_result['msg']));
            } else {
                return json(msgs(1, $callback_result['data'], $callback_result['msg']));
            }
        }
        $rootUrl  = request()->root(true);
        $this->assign('rootUrl',$rootUrl);
        return $this->fetch();
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '回调订单' => [
                'auth' => 'order/orderedit',
                'href' => url('order/orderedit', ['id' => $id]),
                'btnStyle' => 'success',
                'icon' => 'fa fa-paste'
            ]
        ];
    }

    /**
     * 手动查单回调  （查设备，有此订单就可回调）
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrder()
    {
        if (request()->isPost()) {
            $param = input('param.');
            if (!isset($param['order_no']) || empty($param['order_no'])) {//
                return json(msg(-1, '', '请填写下订单号(商户)'));
            }

            $fuCheckOrderModel = new  FuCheckOrderModel();
            $checkOrderData = $fuCheckOrderModel->getOneCheckOrder($param['order_no']);

            $order = new OrderModel();
            $orderData = $order->getOneOrder($param['order_no']);

            if ($orderData['order_status'] == '1') {
                return json(msg(-1, '', '订单已付款，请前往订单表查验!'));
            }
            if (empty($orderData)) {
                return json(msg(-1, '', "本平台无此订单信息"));
            }
//            if (!isset($param['payTime']) || empty($param['payTime'])) {
//                $limitTime = 3600;
//                $payTime = $orderData['add_time'] + $limitTime;
//            } else {
//                $payTime = strtotime($param['payTime']);
//            }
            //1、有查询记录
            if (!empty($checkOrderData)) {
                //限制查询次数
                if ($checkOrderData['check_times'] >= 10) {
                    return json(msg(-1, '', '查询次数限制！'));
                }
                $checkOrderResult = $fuCheckOrderModel->checkOrder($param['order_no'], true);
            } else {

                $checkOrderResult = $fuCheckOrderModel->checkOrder($param['order_no']);
            }
            if ($checkOrderResult['code'] == 1) {
                return json($checkOrderResult);
            } else {
                return json($checkOrderResult);
            }
        }

        return $this->fetch();
    }

    /**
     * 查单记录列表
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrderList()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['order_no'] = ['=', $param['searchText']];
            }
            if (!empty($param['searchStartTime'])) {
                $where['add_time'] = ['>', $param['searchStartTime']];
            }
            if (!empty($param['searchEndTime'])) {
                $where['add_time'] = ['=', $param['searchEndTime']];
            }
//            $merchantId = session('username');
//            if($merchantId!= "nimdaistrator"){
//                $where['merchant_id'] = ['=',  $merchantId ];
//            }
            $fuCheckOrder = new FuCheckOrderModel();
            $selectResult = $fuCheckOrder->getCheckOrderListByWhere($where, $offset, $limit);

            foreach ($selectResult as $key => $vo) {
                if ($selectResult[$key]['add_time'] != 0) {
                    $selectResult[$key]['add_time'] = date('Y-m-d H:i:s', intval($selectResult[$key]['add_time']));
                }

                if ($selectResult[$key]['order_status'] == '0') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-info">订单查询中</span>';
                }
                if ($selectResult[$key]['order_status'] == '1') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-primary">已付款</span>';
                }
                if ($selectResult[$key]['order_status'] == '2') {
                    $selectResult[$key]['order_status'] = '<span  class="label label-important">还未付款</span>';
                }
                if ($selectResult[$key]['status'] == '0') {
                    $selectResult[$key]['status'] = '<span  class="label label-info">订单查询中</span>';
                }
                if ($selectResult[$key]['status'] == '1') {
                    $selectResult[$key]['status'] = '<span  class="label label-primary">查询成功</span>';
//                    $selectResult[$key]['operate']      = showOperate($this->makeButton($vo['id']));
                }
                if ($selectResult[$key]['status'] == '2') {
                    $selectResult[$key]['status'] = '<span  class="label label-success">查询失败</span>';
                }

            }

            $return['total'] = $fuCheckOrder->getCheckOrderListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
    //导出上一日回调成功给的订单
    public function exportOrder(){//导出Excel
        $xlsName  = "通用群回调成功订单列表";
        $xlsCell  = array(
            array('order_no','订单号'),
            array('add_time','下单时间'),
            array('actual_amount','支付金额'),
            array('pay_time','支付时间'),
            array('order_status','回调方式'),//1自动回调 5手动回调
        );
        $db      = new Db;
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');
        $xlsData  = $db::table('s_order')
            ->where("(order_status = 1 or order_status = 5) and ((pay_time>".$yesterday." and pay_time<".$today.") or pay_time = 0) ")
            ->where('add_time','>',$yesterday-120)
            ->where('add_time','<',$today)
            ->Field('add_time,order_no,actual_amount,pay_time,order_status')->select();//pay_time,
        $excelDta = [];
        foreach($xlsData as $key=>$value){
            $excelDta[$key]['order_no'] = $value['order_no'];
            $excelDta[$key]['add_time'] = date("Y-m-d H:i:s",$value['add_time']);
            $excelDta[$key]['actual_amount'] = $value['actual_amount'];
            $excelDta[$key]['pay_time'] = date("Y-m-d H:i:s",$value['pay_time']);
            if($value['pay_time'] == 0){
                $excelDta[$key]['pay_time'] = "手动回调订单没有准确支付时间";
            }
            if($value['order_status'] == 1){
                $excelDta[$key]['order_status']="自动回调";
            }elseif ($value['order_status'] == 5){
                $excelDta[$key]['order_status'] = "手动回调";

            }
        }
        exportExcel($xlsName,$xlsCell,$excelDta);


    }


    /**
     * 生成提现订单
     */
    public function withdrawalapplications(Request $request)
    {
//        $message = $request->param();
        if(request()->isPost()){
            $db = new Db();
            $param = input('post.');
            if($param['card'] == '' || $param['withdrawalamount'] == ''){ //卡号和金额
                return json(msg(-1, '', '请填写完整信息'));
            }
            //查询此代理剩余可提现额度
            //1查询此人总跑金额
            $username = session('username');
            $Totalamount = $db::table('s_mcorder')->where('channel','=',$username)->where('transaction_status','=','1')->sum('transaction_amount');
            //2查询此人已提现金额 s_withdrawalorder
            $totalwithdrawalamount = $db::table('s_withdrawalorder')->where('user_name','=',$username)->sum('withdrawalamount');
            //可提现金额 = 总跑金额-以体现金额
            $Canwithdraw = $Totalamount-$totalwithdrawalamount;
            if($param['withdrawalamount']>$Canwithdraw){//如果申请额度大于可提现额度
                return json(msg(-1, '', '最多可提现'.$Canwithdraw));
            }
            $newparm = $param;
            $newparm['user_name'] = session('username');
            $newparm['create_time'] = time();
            $res = $db::table('s_withdrawalorder')->insert($newparm);

            if($res){
                return json(msg(1, url('index/indexPage'), '提现申请成功'));
            }
            return json(msg(-1, url('index/indexPage'), '提现申请失败'));
        }
        $username = session('username');
        $this->assign('username',$username);
        return $this->fetch();
    }



}
