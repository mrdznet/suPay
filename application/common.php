<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use app\admin\model\Helper;
use \GatewayWorker\Lib\Gateway;
// 应用公共文件
function postCurl($url,$data=[]){
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$return = json_decode(curl_exec($ch),true);
	return $return;
}

/**
 * 取出字符串空格
 * @param $str
 * @return mixed
 */
function deleteStringSpace($str)
{
	$qian=array(" ","　","\t","\n","\r");
	$hou=array("","","","","");
	return str_replace($qian,$hou,$str);
}
/**
 * socket 推送消息
 * @param $str
 * @return mixed
 */
function socketServerToClient($uid,$data){
    $message['action'] = $data;
    $message['time'] = mmicrotime();
    return Gateway::sendToClient($uid,json_encode($message));
}
/**
 * 毫秒时间戳
 * @param $str
 * @return mixed
 */
function mmicrotime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}
/**
 * 接口获取参数
 * @param $str
 * @return mixed
 */
function apiGetParameter()
{
    $data    = @file_get_contents('php://input');
    $message = json_decode($data, true);//获取 调用信息
    return $message;
}


/**
 * 日志写入
 * @param $data : 数据
 * @param $fileName : 写入哪个日志
 * @return mixed
 */
function logs($data = null,$fileName = null)
{
    if (is_null($data) || is_null($fileName)) {
        $out_arr['code'] = '400004';
        return $out_arr;
    }

    $path = RUNTIME_PATH . 'log/' . $fileName;

    if (!is_dir($path)) {
        $mkdir_re = mkdir($path, 0777, TRUE);
        if (!$mkdir_re) {
            $this->logs($data, $fileName);
        }
    }

    $filePath = $path . "/" . date("Y-m-d", time());

    $time = date("Y-m-d H:i:s", time());
    $re = file_put_contents($filePath, $time . " " . var_export($data, TRUE) . "\r\n\r\n", FILE_APPEND);

    if (!$re) {
        $this->logs($data, $fileName);
    } else {
        $out_arr['code'] = '000000';
        return $out_arr;
    }
}
/**
 * json_return
 * @param null $code
 * @param null $msg
 * @param null $data
 * @param null $apiMsg
 * @param null $status
 * @return bool
 */
function apiJsonReturn($code=null,$msg=null,$data=null){


    if($data==null){
        $dataNow['code']=$code;
        $dataNow['msg']=$msg;

    }else{
        $dataNow['code']=$code;
        $dataNow['msg']=$msg;
        $dataNow['data']=$data;

    }
    $dataNow=json_encode($dataNow);
    return $dataNow;
}


/**
 * 写入日志
 * @param $message string 日志信息
 * @param $level string error：错误类型 debug：调试类型 info：信息类型 all：所有信息
 * @return bool
 */
function logMessage($message,  $fileprefix = "", $level = 'info') {
    if($fileprefix=='') $fileprefix=pathinfo(__FILE__)['filename'];
    $logLevel = array('error' => 1, 'debug' => 2, 'info' => 3, 'all' => 4);
    $logDateFormat = 'Y-m-d';
    $basepath="/home/logs";
    $logConfig =[
        'on'=>true,
        'level'=>4,
        'path'=>'/',
        'dateFormat'=> 'Y-m-d H:i:s',
    ];

    //根据配置日志等级决定是否写日志
    //if (!@$logConfig['on'] || !is_numeric(@$logConfig['level']) || !@$logConfig['level'] || (@$logLevel[$level] > @$logConfig['level']) return false;

    //指定日志存放文件夹
    $folderPath = isset($logConfig['path']) ? $basepath . $logConfig['path'] : $basepath . '/log/';
    if (!file_exists($folderPath))
        return false;

    //指定日志存放文件
    $dateFormat = $logConfig['dateFormat'] ? $logConfig['dateFormat'] : $logDateFormat;
    $filePath = $folderPath . $fileprefix . '.log';
    if (@filesize($filePath) > 2097152) { //2M
        $newfilePath = $folderPath . $fileprefix . "_" . date("Y-m-d_H.i.s") . ".log"; //所有日志都保存，不用以前的直接覆盖了
        @rename($filePath, $newfilePath);
        $fp = fopen($filePath, 'w');
        fwrite($fp, "");
    } else {
        $fp = fopen($filePath, "a+");
    }
    //strtolower($level) . '--' .
    $msg = '----------' . date($dateFormat) . '----------' . "\n" . var_export($message, TRUE) . "\n";

    //判断打开文件流是否成功
    //$fp = fopen($filePath, 'a');
    //写入日志
    flock($fp, LOCK_EX);
    fwrite($fp, $msg);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}


/**
 * 根据数字 获取保留两位小数得浮点数
 * @param $money
 * @param int $decimals
 * @return float|int   11000.05
 */
function getFloatMoney($money,$decimals=2)
{
    if(!empty($money)&&is_numeric($money)){
        $res = number_format($money,$decimals, '.', '');
        $floatMoney = (float)($res);
        return $floatMoney;
    }
    return 1.00;
}
function pregMessages($str){
    $str = deleteStringSpace($str);
    $pattern = [ //各个银行短信正则标准
        //中国农业银行*/
        //【中国农业银行】说揭锌ㄓ?07月11日21:48向您尾号5274账户完成代付交易人民币12.00，余额12.54。
        '/^【中国农业银行】(?<usernames>\S+)\?\;(?<username>\S+)支付宝转账于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        '/^【中国农业银行】(?<username>\S+)\?(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】Ц侗ψ?;胡引支付宝转账于07月11日22:22向您尾号6272账户完成代付交易人民币100.00，余额2312.00。
        // 【中国农业银行】孟龙于07月18日12:02向您尾号8778账户完成转存交易人民币500.00，余额13500.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成转存交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】支付宝（中国）网络技术有限公司于06月24日20:13向您尾号8472账户完成代付交易人民币10.00，余额11.02。
        '/^【中国农业银行】支付宝（中国）网络技术有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】杨宏于07月13日23:32向您尾号6273账户完成网银转账交易人民币19999.00，余额22664.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成网银转账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】财付通支付科技有限公司于06月24日21:53向您尾号8472账户完成银联入账交易人民币10.00，余额10.02
        '/^【中国农业银行】财付通支付科技有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】财付通支付科技有限公司于07月16日03:22向您尾号2872账户完成代付交易人民币50.00，余额1360.00。
        '/^【中国农业银行】财付通支付科技有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号5176账户07月16日03:25完成银联入账交易人民币50.00，余额10593.04。"}
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号2176账户07月16日03:06完成代付交易人民币10000.00，余额10749.00。
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】支付宝（中国）网络技术有限公司于07月19日15:48向您尾号3864账户完成银联入账交易人民币12.00，余额12.00。
        '/^【中国农业银行】支付宝（中国）网络技术有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号7473账户07月19日20:40完成工资交易人民币1000.00，余额4012.00。
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成工资交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】罗春美于07月19日20:13向您尾号1816账户完成银联入账交易人民币1000.00，余额2012.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】施宏伟于07月19日21:07向您尾号5971账户完成施宏伟交易人民币60.00，余额20336.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成(?<username2>\S+)交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//交通开始---------------------------------------------------------------------------------------------------------------------------------------------------------------------
     //交易提醒：账号：*1935，金额：21000.00，类型：网银转入，附言：转账 ，余额：21100.00，时间：08月17日17：46。
     //交易提醒：账号：*3780，金额：50.00，   类型：网银转入，附言：560861772，余额：9731.28，时间：07月30日07：21。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：(?<fuyan>\S+)，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账 ，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*4226，金额：199.00，类型：网银转入，附言：转账汇款，余额：13849.00，时间：07月29日01：37。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账汇款，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*7552，金额：5000.00，类型：网银转入，附言：跨行转出，余额：7150.00，时间：07月29日00：05。
        //交易提醒：账号：*4640，金额：15000.00，类型：网银转入，附言：跨行转出 ，余额：30167.00，时间：08月14日22：05。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：跨行转出，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：跨行转出 ，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',

        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)支付宝网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',

        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)财付通公司网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //您尾号*3904的卡于07月17日23:11网络支付转入12.00元,交易后余额为1230.00元。【交通银行】
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)网络支付转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：手机转账，余额：24.00，时间：07月08日22：09。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号卡的于07月09日07:25手机银行交行转入99.00元，交易后余额为162.00元。【交通银行】
        '/^您尾号(?<card>\S+)卡的于(?<time>\S+)手机银行交行转入(?<money>\S+)元，交易后余额为(?<remains>\S+)元。\【交通银行\】/',
        //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：网银转账，余额：24.00，时间：07月08日22：09。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：网银转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号*1911的卡于07月10日02:27支付宝网银转入99.00元，交易后余额为9034.50元。
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)支付宝网银转入(?<money>\S+)元，交易后余额为(?<remains>\S+)元。/',
        //您尾号*1176的卡于07月10日10:08财付通公司网银转入100.00元，交易后余额为7328.00元。
        //交易提醒：账号：*3917，金额：1000.00，类型：网银转入，附言：转账，余额：11096.00，时间：07月10日21：33。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*5956，金额：12.00，类型：网银转入，附言：手机银行转账，余额：48.00，时间：07月11日16：22。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机银行转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号*8394的卡于07月12日17:37手机银行交行转入1999.00元,交易后余额为4469.00元。【交通银行】
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)手机银行交行转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //交通end----------------------------------------------------------------------------------------------------------------------------------------
        //建设开始-------------------------------------------------------------------------------------------------------------------------------------------------------------
//        '/(?<time>\S+)分向您尾号(\S+)的储蓄卡账户电子汇入收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)支付机构提现收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)支付机构提现收入人民币(\S+)元，活期余额(\S+)元\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)转账存入收入人民币(\S+)元,(\S+)\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户转账收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户转账存入收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户银联入账收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]：云闪付转账/',
//        //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]
//        '/^(\S+)日(\S+)分向您尾号(\S+)的储蓄卡账户手机银行转账收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
        //建行结束---------------------------------------------------------------------------------------------------------------------------------------------
        //浦发开始--------------------------------------------------------------------------------------------------------------------------------------
        //浦发
        //您尾号2315卡人民币活期11:36存入500.00[支付宝-严林杰支付宝转]，可用余额10473.08。【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝转\]，可用余额(?<remains>\S+)。【浦发银行】/',
        //您尾号1630卡人民币活期10:39存入9,000.00[支付宝-孙建支付宝转账]，可用余额15530.01。【浦发银行】,
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝转账\]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[互联汇入](?<username>\S+)，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[银联入账:余额宝]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[转账到银行卡]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[(?<username>\S+)支付宝转账]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[银联入账:(?<username>\S+)]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝]，可用余额(?<remains>\S+)。【浦发银行】/',
        //您尾号1418卡人民币活期23:13存入20.00[转入黄孟4620]【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[转入(?<username>\S+)]【浦发银行】/',
        //您尾号6924卡人民币活期13:59存入10.00[财付通-转账到银行卡]，可用余额10.00。【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[财付通-转账到银行卡\]，可用余额(?<remains>\S+)。\【浦发银行\】/',
//    //浦发结束------------------------------------------------------------------------------------------------------------------------------------------------
//   //民生开始--------------------------------------------------------------------------------------------------------------------------------------
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】/',
        //账户*5620于07月31日15:26存入￥200.00元，可用余额6692.00元。１５１９４００１４６３。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(?<unnomessage>\S+)。【民生银行】/',
        //账户*2579于07月29日22:39存入￥39999.00元，付方支付宝（中国）网络技术有限公司，可用余额45199.20元。马德松支付宝转账-马德松支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方支付宝（中国）网络技术有限公司，可用余额(?<remains>\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】/',
        //	账户*8650于07月29日00:05存入￥110.00元，可用余额8897.86元。卢方桐支付宝转账-卢方桐支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
       //账户*0587于08月16日01:21存入￥500.00元，可用余额18885.00元。董帅帅支付宝转账-董帅帅支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(\S+)-(\S+)-支付宝（中国）网络技术有限公司。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账：转账到银行卡。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。手机转账。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。存款。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账到银行卡-财付通支付科技有限公司。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。跨行转出。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。手机银行转账。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。NonResident。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<remains>\S+)元。存款。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<remains>\S+)元。跨行转出。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账汇款。\【民生银行\】/',
        // 账户*1027于07月18日15:49存入￥12.00元，可用余额5448.00元。银联入账：张亚伟支付宝转账。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账：(?<username>\S+)支付宝转账。【民生银行】/',
        //民生结束-------------------------------------------------------------------------------------------------------------------------
        //招商开始-------------------------------------------------------------------------------------------------------------------------
//        '/^您账户(?<card>\S+)于(?<time>\S+)收到人民币(?<money>\S+)\（\S+。[招商银行]\S+/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)收款人民币(?<money>\S+)，备注：(?<username>\S+)\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。快抽话费 cmbt.cn\/yo 。\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)收到本行转入人民币(?<money>\S+)，付方(?<username>\S+)，账号尾号(\S+)，备注：转账\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分 cmbt.cn\/jfqd 。\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)银联入账人民币(?<money>\S+)元\（财付通支付科技有限公司\/转账到银行卡\）\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分cmbt.cn\/jfqd。/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。福利 cmbt.cn\/ali07 。\[招商银行]/',
//        //您账户3755于07月11日01:17二维码收款（蒋欣），人民币400.00元[招商银行]
//        '/^您账户(?<card>\S+)于(?<time>\S+)二维码收款\（(?<username>\S+)\），人民币(?<money>\S+)元\[招商银行]/',
        //招商结束----------------------------------------------------------------------------------------------------------------------
        //广发开始-----------------------------------------------------------------------------------------------------------------------
        //【广发银行】您尾号2959卡09日17:08收入人民币12.00元(网银入账)。账户余额:13.00元。
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。账户余额:(?<remains>\S+)元。/',

//        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(转账存入\)。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元。账户余额:(?<remains>\S+)元。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。账户余额:(?<remains>\S+)元。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司）。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-(?<remains>\S+)\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\（银联入账-(?<remains>\S+)\）。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元（网银入账）。/',
        //【广发银行】您尾号3576卡19日14:29收入人民币100.00元(网银入账)。
        //广发结束--------------------------------------------------------------------------------------------------------------------------
        //兴业开始--------------------------------------------------------------------------------------------------------------------------
        //16日13:40账户*9341*汇款汇入收入1000.00元，余额63498.00元。对方户名:房龙二（跨行转出）[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（跨行转出）\[兴业银行\]。/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（跨行转出）\[兴业银行\]/',
        //16日15:48账户*9341*汇款汇入收入500.00元，余额80608.00元。对方户名:张祥（转账）[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（转账）\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（转账）\[兴业银行\]。/',
        //16日15:56账户*2815*跨行代付收入1000.00元，余额6753.05元[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*跨行代付收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*跨行代付收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]。/',
        //16日15:27账户*2815*网联付款收入152.00元，余额5753.05元[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*网联付款收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*网联付款收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]。/',
        //16日15:18账户*2815*汇款汇入收入1500.00元，余额5601.05元。对方户名:胡文斌[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)\[兴业银行\]。/',

        //兴业结束--------------------------------------------------------------------------------------------------------------------------
    ];

    $successMatch = array();
    foreach ($pattern as $key => $value){
        preg_match($value,$str,$match);
        if(!empty($match)){
            $match['money'] = str_replace(',', '', $match['money']) ;//去除金额中的逗号
            $successMatch = $match;
            return apiJsonReturn('10000','匹配成功', $successMatch);
        }
    }
    if(empty($successMatch)){
        return apiJsonReturn('10001','匹配失败');
    }
}

function pregMessagesdemo($str){
    $str = deleteStringSpace($str);
    $pattern = [ //各个银行短信正则标准
        //中国农业银行*/
        //【中国农业银行】说揭锌ㄓ?07月11日21:48向您尾号5274账户完成代付交易人民币12.00，余额12.54。
        '/^【中国农业银行】(?<usernames>\S+)\?\;(?<username>\S+)支付宝转账于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        '/^【中国农业银行】(?<username>\S+)\?(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】Ц侗ψ?;胡引支付宝转账于07月11日22:22向您尾号6272账户完成代付交易人民币100.00，余额2312.00。
        // 【中国农业银行】孟龙于07月18日12:02向您尾号8778账户完成转存交易人民币500.00，余额13500.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成转存交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】支付宝（中国）网络技术有限公司于06月24日20:13向您尾号8472账户完成代付交易人民币10.00，余额11.02。
        '/^【中国农业银行】支付宝（中国）网络技术有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】杨宏于07月13日23:32向您尾号6273账户完成网银转账交易人民币19999.00，余额22664.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成网银转账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】财付通支付科技有限公司于06月24日21:53向您尾号8472账户完成银联入账交易人民币10.00，余额10.02
        '/^【中国农业银行】财付通支付科技有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】财付通支付科技有限公司于07月16日03:22向您尾号2872账户完成代付交易人民币50.00，余额1360.00。
        '/^【中国农业银行】财付通支付科技有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号5176账户07月16日03:25完成银联入账交易人民币50.00，余额10593.04。"}
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号2176账户07月16日03:06完成代付交易人民币10000.00，余额10749.00。
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成代付交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】支付宝（中国）网络技术有限公司于07月19日15:48向您尾号3864账户完成银联入账交易人民币12.00，余额12.00。
        '/^【中国农业银行】支付宝（中国）网络技术有限公司于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//【中国农业银行】您尾号7473账户07月19日20:40完成工资交易人民币1000.00，余额4012.00。
        '/^【中国农业银行】您尾号(?<card>\S+)账户(?<time>\S+)完成工资交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】罗春美于07月19日20:13向您尾号1816账户完成银联入账交易人民币1000.00，余额2012.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
        //【中国农业银行】施宏伟于07月19日21:07向您尾号5971账户完成施宏伟交易人民币60.00，余额20336.00。
        '/^【中国农业银行】(?<username>\S+)于(?<time>\S+)向您尾号(?<card>\S+)账户完成(?<username2>\S+)交易人民币(?<money>\S+)，余额(?<remains>\S+)。/',
//交通开始---------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //交易提醒：账号：*1935，金额：21000.00，类型：网银转入，附言：转账 ，余额：21100.00，时间：08月17日17：46。
        //交易提醒：账号：*3780，金额：50.00，   类型：网银转入，附言：560861772，余额：9731.28，时间：07月30日07：21。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：(?<fuyan>\S+)，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账 ，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*4226，金额：199.00，类型：网银转入，附言：转账汇款，余额：13849.00，时间：07月29日01：37。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账汇款，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*7552，金额：5000.00，类型：网银转入，附言：跨行转出，余额：7150.00，时间：07月29日00：05。
        //交易提醒：账号：*4640，金额：15000.00，类型：网银转入，附言：跨行转出 ，余额：30167.00，时间：08月14日22：05。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：跨行转出，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：跨行转出 ，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',

        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)支付宝网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',

        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)财付通公司网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)网银转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //您尾号*3904的卡于07月17日23:11网络支付转入12.00元,交易后余额为1230.00元。【交通银行】
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)网络支付转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：手机转账，余额：24.00，时间：07月08日22：09。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号卡的于07月09日07:25手机银行交行转入99.00元，交易后余额为162.00元。【交通银行】
        '/^您尾号(?<card>\S+)卡的于(?<time>\S+)手机银行交行转入(?<money>\S+)元，交易后余额为(?<remains>\S+)元。\【交通银行\】/',
        //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：网银转账，余额：24.00，时间：07月08日22：09。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：网银转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号*1911的卡于07月10日02:27支付宝网银转入99.00元，交易后余额为9034.50元。
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)支付宝网银转入(?<money>\S+)元，交易后余额为(?<remains>\S+)元。/',
        //您尾号*1176的卡于07月10日10:08财付通公司网银转入100.00元，交易后余额为7328.00元。
        //交易提醒：账号：*3917，金额：1000.00，类型：网银转入，附言：转账，余额：11096.00，时间：07月10日21：33。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //交易提醒：账号：*5956，金额：12.00，类型：网银转入，附言：手机银行转账，余额：48.00，时间：07月11日16：22。
        '/^交易提醒：账号：\*(?<card>\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机银行转账，余额：(?<remains>\S+)，时间：(?<time>\S+)。/',
        //您尾号*8394的卡于07月12日17:37手机银行交行转入1999.00元,交易后余额为4469.00元。【交通银行】
        '/^您尾号\*(?<card>\S+)的卡于(?<time>\S+)手机银行交行转入(?<money>\S+)元,交易后余额为(?<remains>\S+)元。【交通银行】/',
        //交通end----------------------------------------------------------------------------------------------------------------------------------------
        //建设开始-------------------------------------------------------------------------------------------------------------------------------------------------------------
//        '/(?<time>\S+)分向您尾号(\S+)的储蓄卡账户电子汇入收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)支付机构提现收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)支付机构提现收入人民币(\S+)元，活期余额(\S+)元\[建设银行]/',
//        '/^您尾号(\S+)的储蓄卡账户(?<time>\S+)转账存入收入人民币(\S+)元,(\S+)\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户转账收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户转账存入收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
//        '/^(?<time>\S+)向您尾号(\S+)的储蓄卡账户银联入账收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]：云闪付转账/',
//        //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]
//        '/^(\S+)日(\S+)分向您尾号(\S+)的储蓄卡账户手机银行转账收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
        //建行结束---------------------------------------------------------------------------------------------------------------------------------------------
        //浦发开始--------------------------------------------------------------------------------------------------------------------------------------
        //浦发
        //您尾号2315卡人民币活期11:36存入500.00[支付宝-严林杰支付宝转]，可用余额10473.08。【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝转\]，可用余额(?<remains>\S+)。【浦发银行】/',
        //您尾号1630卡人民币活期10:39存入9,000.00[支付宝-孙建支付宝转账]，可用余额15530.01。【浦发银行】,
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝转账\]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[互联汇入](?<username>\S+)，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[银联入账:余额宝]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[转账到银行卡]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[(?<username>\S+)支付宝转账]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[银联入账:(?<username>\S+)]，可用余额(?<remains>\S+)。【浦发银行】/',
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝]，可用余额(?<remains>\S+)。【浦发银行】/',
        //您尾号1418卡人民币活期23:13存入20.00[转入黄孟4620]【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[转入(?<username>\S+)]【浦发银行】/',
        //您尾号6924卡人民币活期13:59存入10.00[财付通-转账到银行卡]，可用余额10.00。【浦发银行】
        '/^您尾号(?<card>\S+)卡人民币活期(?<time>\S+)存入(?<money>\S+)\[财付通-转账到银行卡\]，可用余额(?<remains>\S+)。\【浦发银行\】/',
//    //浦发结束------------------------------------------------------------------------------------------------------------------------------------------------
//   //民生开始--------------------------------------------------------------------------------------------------------------------------------------
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】/',
        //账户*5620于07月31日15:26存入￥200.00元，可用余额6692.00元。１５１９４００１４６３。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(?<unnomessage>\S+)。【民生银行】/',
        //账户*2579于07月29日22:39存入￥39999.00元，付方支付宝（中国）网络技术有限公司，可用余额45199.20元。马德松支付宝转账-马德松支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方支付宝（中国）网络技术有限公司，可用余额(?<remains>\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】/',
        //	账户*8650于07月29日00:05存入￥110.00元，可用余额8897.86元。卢方桐支付宝转账-卢方桐支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
        //账户*0587于08月16日01:21存入￥500.00元，可用余额18885.00元。董帅帅支付宝转账-董帅帅支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。(\S+)-(\S+)-支付宝（中国）网络技术有限公司。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账：转账到银行卡。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。手机转账。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。存款。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账到银行卡-财付通支付科技有限公司。【民生银行】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。跨行转出。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。手机银行转账。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。NonResident。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<remains>\S+)元。存款。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<remains>\S+)元。跨行转出。\【民生银行\】/',
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。转账汇款。\【民生银行\】/',
        // 账户*1027于07月18日15:49存入￥12.00元，可用余额5448.00元。银联入账：张亚伟支付宝转账。【民生银行】
        '/^账户\*(?<card>\S+)于(?<time>\S+)存入￥(?<money>\S+)元，可用余额(?<remains>\S+)元。银联入账：(?<username>\S+)支付宝转账。【民生银行】/',
        //民生结束-------------------------------------------------------------------------------------------------------------------------
        //招商开始-------------------------------------------------------------------------------------------------------------------------
//        '/^您账户(?<card>\S+)于(?<time>\S+)收到人民币(?<money>\S+)\（\S+。[招商银行]\S+/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)收款人民币(?<money>\S+)，备注：(?<username>\S+)\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。快抽话费 cmbt.cn\/yo 。\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)收到本行转入人民币(?<money>\S+)，付方(?<username>\S+)，账号尾号(\S+)，备注：转账\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分 cmbt.cn\/jfqd 。\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)银联入账人民币(?<money>\S+)元\（财付通支付科技有限公司\/转账到银行卡\）\[招商银行]/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分cmbt.cn\/jfqd。/',
//        '/^您账户(?<card>\S+)于(?<time>\S+)他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。福利 cmbt.cn\/ali07 。\[招商银行]/',
//        //您账户3755于07月11日01:17二维码收款（蒋欣），人民币400.00元[招商银行]
//        '/^您账户(?<card>\S+)于(?<time>\S+)二维码收款\（(?<username>\S+)\），人民币(?<money>\S+)元\[招商银行]/',
        //招商结束----------------------------------------------------------------------------------------------------------------------
        //广发开始-----------------------------------------------------------------------------------------------------------------------
        //【广发银行】您尾号2959卡09日17:08收入人民币12.00元(网银入账)。账户余额:13.00元。
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。账户余额:(?<remains>\S+)元。/',

//        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(转账存入\)。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元。账户余额:(?<remains>\S+)元。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。账户余额:(?<remains>\S+)元。/',   //张笛新增
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司）。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(银联入账-(?<remains>\S+)\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\（银联入账-(?<remains>\S+)\）。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元\(网银入账\)。/',
        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)收入人民币(?<money>\S+)元（网银入账）。/',
        //【广发银行】您尾号3576卡19日14:29收入人民币100.00元(网银入账)。
        //广发结束--------------------------------------------------------------------------------------------------------------------------
        //兴业开始--------------------------------------------------------------------------------------------------------------------------
        //16日13:40账户*9341*汇款汇入收入1000.00元，余额63498.00元。对方户名:房龙二（跨行转出）[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（跨行转出）\[兴业银行\]。/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（跨行转出）\[兴业银行\]/',
        //16日15:48账户*9341*汇款汇入收入500.00元，余额80608.00元。对方户名:张祥（转账）[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（转账）\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)（转账）\[兴业银行\]。/',
        //16日15:56账户*2815*跨行代付收入1000.00元，余额6753.05元[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*跨行代付收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*跨行代付收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]。/',
        //16日15:27账户*2815*网联付款收入152.00元，余额5753.05元[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*网联付款收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*网联付款收入(?<money>\S+)元，余额(?<remains>\S+)元\[兴业银行\]。/',
        //16日15:18账户*2815*汇款汇入收入1500.00元，余额5601.05元。对方户名:胡文斌[兴业银行]
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)\[兴业银行\]/',
        '/^(?<time>\S+)账户\*(?<card>\S+)\*汇款汇入收入(?<money>\S+)元，余额(?<remains>\S+)元。对方户名:(?<username>\S+)\[兴业银行\]。/',

        //兴业结束--------------------------------------------------------------------------------------------------------------------------
    ];

    $successMatch = array();
    foreach ($pattern as $key => $value){
        preg_match($value,$str,$match);
        if(!empty($match)){
            $match['money'] = str_replace(',', '', $match['money']) ;//去除金额中的逗号
            $successMatch = $match;
            return apiJsonReturn('10000','匹配成功', $successMatch);
        }
    }
    if(empty($successMatch)){
        return apiJsonReturn('10001','匹配失败');
    }
}

function sendMessageToPotato($orderno,$playername,$amount,$card,$smstime,$body,$payable_amount,$title){
    $potatoMessage =$title.
                   "后台地址：http://47.112.223.187:8899/admin,
                   单号：".$orderno."，
                   付款者姓名：".$playername.",
                   订单金额：".$payable_amount.",
                   付款金额：".$amount.",
                   收款卡号：".$card.",
                   短信到账时间：".$smstime.",
                   短信内容：".$body.",
                   请进一步核实，如确定请去后台进行手动回调";
    $sendData = [
        'chat_type' => 3,
        'chat_id' => 10958036,
        'text' => $potatoMessage,
    ];
    $headers = ['Content-Type:application/json'];
    $result = Helper::cUrlGetData('http://18.138.140.45:8000/10101207:CWZIsDhF13uH3CBTusCGpTMd/sendTextMessage', json_encode($sendData), $headers);
    return $result;
}
