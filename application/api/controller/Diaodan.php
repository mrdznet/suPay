<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/15
 * Time: 19:53
 */

namespace app\api\controller;

use app\api\model\OrderModel;
use think\Db;
use think\Controller;
use think\Request;
use app\api\model\SmsModel;
use app\api\model\DeviceModel;
use app\api\model\NotifyCallBackLogModel;

class Diaodan extends Controller
{
    public function test(Request $request)
    {
        $db = new Db();
        $diaodanData = $db::table('s_diaodan')->select();
        $totalCount = count($diaodanData);
        $repeatOrder= [];
        $repeatOrderCount = 0;
        $errorMoneyOrder= [];
        $errorMoneyOrderCount = 0;
        $errorNameOrder= [];
        $errorNameOrderCount = 0;
        $otherOrder= [];
        $otherCount = 0;
        if(!empty($diaodanData)){
            foreach ($diaodanData as $key =>$val){
                //1、查找订单
                $diaoOne = $db::table('s_order')->where('order_no','=',$val['orderNo'])->find();
                if(empty($diaoOne)){
                    //空订单 代表同一账户重复下单
                    $repeatOrder[] = $val['orderNo'];
                    $repeatOrderCount ++;
                }else{



                    //判断金额是否匹配
                    if($diaoOne['amount']!=$val['money']){
                        $errorMoneyOrder[] = $val['orderNo'];
                        $errorMoneyOrderCount++;
                    }
                    //判断姓名是否填写或者是数字
                    if(empty($diaoOne['player_name'])||is_numeric($diaoOne['player_name'])){
                        $errorNameOrder[] = $val['orderNo'];
                        $errorNameOrderCount++;
                    }
                    if(!empty($diaoOne['player_name'])&&!is_numeric($diaoOne['player_name'])&&$diaoOne['amount']!=$val['money']){
                        $otherOrder[] = $val['orderNo'];
                        $otherCount++;
                    }
                }
            }
        }
        $nullName = $db::table('s_order')->where('player_name','=',"")->count();
        echo "总数量".$totalCount;
        echo "</br>";
        echo "有重复下单数量".$repeatOrderCount;
        echo "</br>";
        echo "有下单与付款金额不一致数量".$errorMoneyOrderCount;
        echo "</br>";
        echo "有付款名字不一致数量".$errorNameOrderCount;
        echo "</br>";
        echo "其余数量".$otherCount;
        var_dump($errorNameOrder);exit;

    }

    public function isChinese()
    {
        $str = "新·疆域";
        $res = isAllChinese($str);
        var_dump($res);
    }

    public function testMoney()
    {
        $money = "11000.50";
//        $res = number_format($money,2);
//        $english_format_number = number_format($number, 2, '.', '');
        $res = number_format($money,2, '.', '');
//        $res = (float)($res);
        var_dump($res);exit;
    }

    public function test1(Request $request)
    {
        $db = new Db();
//        $totalCount= $db::table('s_loseorder_notify_callback_log')->where('PayMoney','<',"6000")->count();

        $totalData = $db::table('s_loseorder_notify_callback_log')->where('PayMoney','<',"6000")->select();
        $timeTotalData = $db::table('s_loseorder_notify_callback_log')->where('PayMoney','<',"6000")->where('PayTime','>=',1561046400)->select();
        $totalCount = count($totalData);
        $emptyNameCount =  0;
        $weiPayCount =  0;
        $aliPayCount =  0;

        foreach($totalData as $k =>$v){
            //1561046400
            //PayCardUser
            if(empty($v['PayCardUser'])){
                $emptyNameCount++;
            }
            if($v['PayCardUser']=="财付通支付科技有限公司"){
                $weiPayCount++;
            }
            if($v['PayCardUser']=="支付宝（中国）网络技术有限公司"){
                $aliPayCount++;
            }

        }
        //总的 start
        echo "2019-06-20 03:01:22之后总的未匹配的订单分析 ";
        echo "</br>";
        echo "总数量".$totalCount;
        echo "</br>";
        echo "空名字".$emptyNameCount;
        echo "</br>";
        echo "微信支付导致回调失败数量".$weiPayCount;
        echo "</br>";
        echo "支付宝支付失败数量".$aliPayCount;
        echo "</br>";
        echo "-------------------------------";
        echo "</br>";
        //总的 send


        //1561046400之后的 start
        $timeTotalCount = count($timeTotalData);
        $timeEmptyNameCount =  0;
        $timeWeiPayCount =  0;
        $timeAliPayCount =  0;
        foreach($timeTotalData as $key =>$val){
            //1561046400
            //PayCardUser
            if(empty($val['PayCardUser'])){
                $timeEmptyNameCount++;
            }
            if($val['PayCardUser']=="财付通支付科技有限公司"){
                $timeWeiPayCount++;
            }
            if($val['PayCardUser']=="支付宝（中国）网络技术有限公司"){
                $timeAliPayCount++;
            }

        }

        echo "2019-06-21 00:00:00之后未匹配的订单分析";
        echo "</br>";
        echo "总数量".$timeTotalCount;
        echo "</br>";
        echo "空名字".$timeEmptyNameCount;
        echo "</br>";
        echo "微信支付导致回调失败数量".$timeWeiPayCount;
        echo "</br>";
        echo "支付宝支付失败数量".$timeAliPayCount;
    }

    public function test2(Request $request)
    {
        $db = new Db();
//        $totalCount= $db::table('s_loseorder_notify_callback_log')->where('PayMoney','<',"6000")->count();

        $totalData = $db::table('s_order_notify_callback_log')->where('PayMoney','<',"6000")->where('status','=','0')->select();
//        $timeTotalData = $db::table('s_notify_callback_log')->where('PayMoney','<',"6000")->where('PayTime','>=',1561046400)->select();
        $sql = $db::table('s_order_notify_callback_log')->getLastSql();
        echo $sql;
        $totalCount = count($totalData);
        $emptyNameCount =  0;
        $weiPayCount =  0;
        $aliPayCount =  0;

        foreach($totalData as $k =>$v){
            //1561046400
            //PayCardUser
            if(empty($v['PayCardUser'])){
                $emptyNameCount++;
            }
            if($v['PayCardUser']=="财付通支付科技有限公司"){
                $weiPayCount++;
            }
            if($v['PayCardUser']=="支付宝（中国）网络技术有限公司"){
                $aliPayCount++;
            }

        }
        //总的 start
        echo "后总的未匹配的订单分析 ";
        echo "</br>";
        echo "总数量".$totalCount;
        echo "</br>";
        echo "空名字（当前是银联）".$emptyNameCount;
        echo "</br>";
        echo "微信支付导致回调失败数量".$weiPayCount;
        echo "</br>";
        echo "支付宝支付失败数量".$aliPayCount;
        echo "</br>";
        echo "-------------------------------";
        echo "</br>";
        //总的 send


//        //1561046400之后的 start
//        $timeTotalCount = count($timeTotalData);
//        $timeEmptyNameCount =  0;
//        $timeWeiPayCount =  0;
//        $timeAliPayCount =  0;
//        foreach($timeTotalData as $key =>$val){
//            //1561046400
//            //PayCardUser
//            if(empty($val['PayCardUser'])){
//                $timeEmptyNameCount++;
//            }
//            if($val['PayCardUser']=="财付通支付科技有限公司"){
//                $timeWeiPayCount++;
//            }
//            if($val['PayCardUser']=="支付宝（中国）网络技术有限公司"){
//                $timeAliPayCount++;
//            }
//
//        }
//
//        echo "2019-06-21 00:00:00之后未匹配的订单分析";
//        echo "</br>";
//        echo "总数量".$timeTotalCount;
//        echo "</br>";
//        echo "空名字".$timeEmptyNameCount;
//        echo "</br>";
//        echo "微信支付导致回调失败数量".$timeWeiPayCount;
//        echo "</br>";
//        echo "支付宝支付失败数量".$timeAliPayCount;
    }

    public function successOrder(Request $request)
    {
        $db = new Db();
//        $totalCount= $db::table('s_loseorder_notify_callback_log')->where('PayMoney','<',"6000")->count();

        $totalData = $db::table('s_order_notify_callback_log')->where('PayMoney','<',"6000")->where('status','=','1')->select();
//        $timeTotalData = $db::table('s_notify_callback_log')->where('PayMoney','<',"6000")->where('PayTime','>=',1561046400)->select();
        $sql = $db::table('s_order_notify_callback_log')->getLastSql();
        echo $sql;
        $totalCount = count($totalData);
//        $emptyNameCount =  0;
        $otherCount =  0;
        $weiPayCount =  0;
        $aliPayCount =  0;

        foreach($totalData as $k =>$v){
            //1561046400
            //PayCardUser
//            if(empty($v['PayCardUser'])){
//                $emptyNameCount++;
//            }
            if($v['PayCardUser']=="财付通支付科技有限公司"){
                $weiPayCount++;
            }else if($v['PayCardUser']=="支付宝（中国）网络技术有限公司"){
                $aliPayCount++;
            }else{
                $otherCount++;
            }

        }
        //总的 start
        echo "总匹配的订单分析 ";
        echo "</br>";
        echo "总数量成功回调数(以下包含在其中）".$totalCount;
        echo "</br>";
//        echo "空名字（当前是银联）".$emptyNameCount;
        echo "</br>";
        echo "微信支付导致回调成功数量".$weiPayCount;
        echo "</br>";
        echo "支付宝支付成功数量".$aliPayCount;
        echo "</br>";
        echo "其他成功回调数量".$otherCount;
        echo "</br>";
        echo "-------------------------------";
        echo "</br>";
        //总的 send


//        //1561046400之后的 start
//        $timeTotalCount = count($timeTotalData);
//        $timeEmptyNameCount =  0;
//        $timeWeiPayCount =  0;
//        $timeAliPayCount =  0;
//        foreach($timeTotalData as $key =>$val){
//            //1561046400
//            //PayCardUser
//            if(empty($val['PayCardUser'])){
//                $timeEmptyNameCount++;
//            }
//            if($val['PayCardUser']=="财付通支付科技有限公司"){
//                $timeWeiPayCount++;
//            }
//            if($val['PayCardUser']=="支付宝（中国）网络技术有限公司"){
//                $timeAliPayCount++;
//            }
//
//        }
//
//        echo "2019-06-21 00:00:00之后未匹配的订单分析";
//        echo "</br>";
//        echo "总数量".$timeTotalCount;
//        echo "</br>";
//        echo "空名字".$timeEmptyNameCount;
//        echo "</br>";
//        echo "微信支付导致回调失败数量".$timeWeiPayCount;
//        echo "</br>";
//        echo "支付宝支付失败数量".$timeAliPayCount;
    }

    /**
     * 统计匹配错误的金额
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function successAliPay()
    {
        $smsModel = new SmsModel();
        $deviceModel = new DeviceModel();
//        $orderModel = new OrderModel();
//        $smsJsonData = $smsModel->getPregSms();
        $status2SmsData = $smsModel->where('use_state','=',1)->where('level','=',2)->select();
        $lastSql = $smsModel->getLastSql();
        dump($lastSql);
        $studio_am = 0;
        $studio_ts = 0;
        $studio_other = 0;
        $cardList_ts = array();
        foreach ($status2SmsData as $key =>$val){
            $pregJsonResult = pregMessages($val['sms']);

            $pregArrayResult = json_decode($pregJsonResult,true);

            if(isset($pregArrayResult['data']['money'])){

                if(isset($val['channel'])&&!empty($val['channel'])){
                    if($val['channel'] == "studio_am"){
                        $studio_am = $studio_am+$pregArrayResult['data']['money'];
                    }
                    if($val['channel'] == "studio_ts"){
                        $key = $val['phone'];
                        if(!isset($cardList_ts[$key])){
                            $cardList_ts[$key] = 0;
                        }else{
                            $cardList_ts[$key]  += $pregArrayResult['data']['money'];
                        }
                        $studio_ts = $studio_ts+$pregArrayResult['data']['money'];
                    }
                }else{
                    //根据手机号去s_device 表查询
                    $channel = $deviceModel->where('phone','=',$val['phone'])->find()['channel'];
                    if(!empty($channel)){
                        if($channel == "studio_am"){
                            $studio_am = $studio_am+$pregArrayResult['data']['money'];
                        }
                        if($channel == "studio_ts"){
                            $key = $val['phone'];
                            if(!isset( $cardList_ts[$key])){
                                $cardList_ts[$key] = 0;
                            }else{
                                $cardList_ts[$key]  += $pregArrayResult['data']['money'];
                            }
                            $studio_ts = $studio_ts+$pregArrayResult['data']['money'];
                        }
                    }else{
                        $studio_other = $studio_other+$pregArrayResult['data']['money'];
                    }
                }
            }

        }
        echo "statdio_am".$studio_am;
        echo "<pre/>";
        echo "statdio_ts".$studio_ts;
        echo "<pre/>";
        echo "studio_other".$studio_other;
        echo "<pre/>";
        var_dump($cardList_ts);
    }

    public function successTotalOrder()
    {
//        $array = array(
//            '18337425095' => '483',
//            '13043743581' => '62',
//            '18237156304' => '105545',
//            '15538712238' => '59233',
//            '15136440293' => '44905',
//            '18703883553' => '76330.99',
//            '15138474305' => '70149.78',
//            '13523401530' => '47582',
//            '13283747605' => '3562',
//            '18337164261' => '579.98',
//            '13183028910' => '350',
//            '13938795237' => '900',
//            '13525549893' => '9001',
//            '13783682455' => '1572',
//            '18838160541' => '12622',
//            '17527137782' => '130',
//            '15837164749' => '6723',
//            '18338998126' => '1972',
//            '17729747569' => '4172',
//            '15103748601' => '48010',
//            '13782373580' => '11542',
//            '15936341553' => '23300.78',
//            '18436211172' => '2412',
//            '18339830623' => '2412',
//            '18860371667' => '6592',
//            '15136850396' => '612.25',
//            '17839137036' => '1062',
//            '15136705271' => '3392',
//            '17337345929' => '3076',
//            '17734895162' => '13722',
//            '18153090235' => '4662',
//            '19939749676' => '1242',
//            '18864657019' => '11134',
//            '15836585997' => '212',
//            '17724881738' => '1362',
//            '17737349832' => '2582',
//            '17730892685' => '3482',
//            '17337342938' => '4963',
//            '15225978044' => '8112'
//        );
$array = array(
    '18337425095'=>
        '483',
    '13043743581'=>
        '62',
    '18237156304'=>
        '105545',
    '15538712238'=>
        '59233',
    '15136440293'=>
        '44905',
    '18703883553'=>
        '76330.99',
    '15138474305'=>
        '70149.78',
    '13523401530'=>
        '47582',
    '13283747605'=>
        '3562',
    '18337164261'=>
        '579.98',
    '13183028910'=>
        '350',
    '13938795237'=>
        '900',
    '13525549893'=>
        '9001',
    '13783682455'=>
        '1572',
    '18838160541'=>
        '12622',
    '17527137782'=>
        '130',
    '15837164749'=>
        '6723',
    '18338998126'=>
        '1972',
    '17729747569'=>
        '4172',
    '15103748601'=>
        '48010',
    '13782373580'=>
        '11542',
    '15936341553'=>
        '23300.78',
    '18436211172'=>
        '2412',
    '18339830623'=>
        '2412',
    '18860371667'=>
        '6592',
    '15136850396'=>
        '612.25',
    '17839137036'=>
        '1062',
    '15136705271'=>
        '3392',
    '17337345929'=>
        '3076',
    '17734895162'=>
        '13722',
    '18153090235'=>
        '4662',
    '19939749676'=>
        '1242',
    '18864657019'=>
        '11134',
    '15836585997'=>
        '212',
    '17724881738'=>
        '1362',
    '17737349832'=>
        '2582',
    '17730892685'=>
        '3482',
    '17337342938'=>
        '4963',
    '15225978044'=>
        '8112',

);
        $orderModel = new OrderModel();
        $statusTsorderSum= $orderModel->field('sum(payable_amount)AS successMoney')
            ->where('order_status','=',1)
            ->where('channel','=','studio_ts')
            ->group('channel')
            ->select();
        $status1OrderData = $orderModel->field('sum(payable_amount)AS successMoney,account as phone')
            ->where('order_status','=',1)
            ->where('channel','=','studio_ts')
            ->group('account')
            ->select();
        $lastSql = $orderModel->getLastSql();
        dump($lastSql);
        $studio_am = 0;
        $studio_ts = 0;
        $studio_other = 0;
        $totalArray = array();
        foreach ($status1OrderData as $key =>$val){
            if(!isset($totalArray[$val['phone']])){
                $totalArray[$val['phone']] = $val['successMoney'];
            }
            if(isset($array[$val['phone']])){
                $totalArray[$val['phone']] = $totalArray[$val['phone']]+$array[$val['phone']];
            }

            $studio_ts = $studio_ts+$totalArray[$val['phone']];

        }
        $status2SmsTsSum = 0;   //未匹配订单总额
        foreach ($array as $value){
            $status2SmsTsSum = $status2SmsTsSum+$value;
        }
        echo "<pre/>";
        echo "statdio_ts";
        echo "<pre/>";
        dump($statusTsorderSum);
        echo "<pre/>";
        echo "status2SmsTsSum";
        echo "<pre/>";
        dump($status2SmsTsSum);
        echo "<pre/>";
        echo "statdio_ts_total";
        echo "<pre/>";
        dump($studio_ts);
        echo "<pre/>";
        echo "totalArray";
        echo "<pre/>";
        dump($totalArray);
        echo "<pre/>";
        echo "status1OrderData";
        echo "<pre/>";
        dump($status1OrderData);exit;
    }
}