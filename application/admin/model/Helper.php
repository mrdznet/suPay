<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2019/1/1
 * Time: 18:21
 */
namespace app\admin\model;

use think\Model;
use think\log;
use think\Request;
class Helper extends Model
{
	/**
	 * [createOrderLog 日志log]
	 * @param  [type] $mark        [备注]
	 * @param  [type] $log_content [内容]
	 * @param  string $keyp        [名]
	 * @return [type]              [description]
	 */
	public static function apiLog($mark, $log_content, $keyp = "") {
		$max_size = 30000000;
		if ($keyp == "") {
			$log_filename = RUNTIME_PATH . '/api/' . date('Ym-d') . ".log";
		} else {
			$log_filename = RUNTIME_PATH . '/api/' . $keyp . date('Ym-d') . ".log";
		}
		if (file_exists($log_filename) && (abs(filesize($log_filename)) > $max_size)) {
		    $keyp ='create_order';
			rename($log_filename, dirname($log_filename) . DS . date('Ym-d-His') . $keyp . ".log");
		}
		$t = microtime(true);
		$micro = sprintf("%06d", ($t - floor($t)) * 1000000);
		$d = new \DateTime (date('Y-m-d H:i:s.' . $micro, $t));
		if(is_array($log_content)){
			$log_content = json_encode($log_content);
		}
		file_put_contents($log_filename, '' . $d->format('Y-m-d H:i:s u') ." 【 ".Request::instance()->ip()." 】". " 【 Type—". $mark ." 】". $log_content . "--\r\n", FILE_APPEND);

	}


	/**
	 * 接口请求
	 * @param $url
	 * @param null $post_fields
	 * @param null $headers
	 * @return mixed
	 */
	public static function cUrlGetData($url, $post_fields = null, $headers = null) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		if ($post_fields && !empty($post_fields)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		}
		if ($headers && !empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		/*if (curl_errno($ch)) {
			echo 'Error:' . curl_error($ch);
		}*/
		curl_close($ch);
		return $data;
	}
    public static function pregMessageForDiaodan($str){
        $pattern = [ //各个银行短信正则标准
            //工商银行
            ////您尾号9757卡6月28日10:05网上银行收入(张威支付宝转帐)11元，余额11元。【工商银行】
            '/^您尾号(?<card>\S+)卡(?<time>\S+)网上银行收入\((?<username>\S+)支付宝转帐\)(?<money>\S+)元，余额(?<totalmoney>\S+)元。\【工商银行\】/',

            //建设银行
            //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]
            '/^(?<username>\S+)[\d]月[\d]日(?<time1>\S+)分向您尾号(?<card>\S+)的储蓄卡账户手机银行转账收入人民币(?<money>\S+)元,活期余额(?<totalmoney>\S+)元。\[建设银行]/',

            //招商
            //您账户6581于07月05日收到本行转入人民币30.00，付方王倩雯，账号尾号5155，备注：转账[招商银行]
            '/^您账户(?<card>\S+)于(?<time1>\S+)日收到本行转入人民币(?<money>\S+)，付方(?<username>\S+)，账号尾号(?<card1>\S+)，备注：转账\[招商银行\]/',
            //您账户8572于07月05日他行实时转入人民币10.00，付方霍明飞。领积分 cmbt.cn/jfqd 。[招商银行]
            '/^您账户(?<card>\S+)于(?<time1>\S+)日他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。[^\r\n]+。\[招商银行\]/',
            //您账户4168于07月10日他行实时转入人民币2000.00，付方曹凯。领积分cmbt.cn/jfqd。
            '/^您账户(?<card>\S+)于(?<time1>\S+)日他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。[^\r\n]+。/',
            //您账户3755于07月11日01:17二维码收款（蒋欣），人民币400.00元[招商银行]
            '/^您账户(?<card>\S+)日(?<time1>\S+)二维码收款\（(?<username>\S+)\），人民币(?<money>\S+)元\[招商银行]/',

            //new 招商 end
            //浦发
            //您尾号2692卡人民币活期12:34存入1,999.00[互联汇入]佟磊9560，可用余额4529.01。【浦发银行】
//        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[互联汇入](\S+)，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(?<card>\S+)卡人民币活期(?<time1>\S+)存入(?<money>\S+)\[互联汇入](?<username>\S+)[\d]{4}，可用余额(?<totalmoney>\S+)。\【浦发银行\】/',
            //您尾号2692卡人民币活期01:16存入799.00[支付宝-张迪支付宝转账]，可用余额5328.01。【浦发银行】
            '/^您尾号(?<card>\S+)卡人民币活期(?<time1>\S+)存入(?<money>\S+)\[支付宝-(?<username>\S+)支付宝转账]，可用余额(?<totalmoney>\S+)。\【浦发银行\】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[支付宝(?<username>\S+)支付宝转\]，可用余额(\S+)。【浦发银行】/',
            //您尾号6600卡人民币活期01:16存入799.00[银联入账:刘晓妮]，可用余额5328.01。【浦发银行】
            '/^您尾号(?<card>\S+)卡人民币活期(?<time1>\S+)存入(?<money>\S+)\[银联入账:(?<username>\S+)]，可用余额(?<totalmoney>\S+)。\【浦发银行\】/',
            //您尾号1418卡人民币活期23:13存入20.00[转入黄孟4620]【浦发银行】
            '/^您尾号(?<card>\S+)卡人民币活期(?<time1>\S+)存入(?<money>\S+)\[转入(?<username>\S+)[\d]{4}]\【浦发银行\】/',
            //new 浦发  end

            //光大银行
            //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款。[光大银行]
            '/^(?<username>\S+)向尾号(?<card>\S+)账户(?<card1>\S+)转入(?<money>\S+)元，余额为(?<totalmoney>\S+)元，摘要:网银跨行汇款。\[光大银行]/',
            '/^(?<username>\S+)向您尾号(?<card>\S+)的账户(?<time>\S+)转入(?<money>\S+)元，余额为(?<totalmoney>\S+)元，摘要:[^\r\n]+\[光大银行\]/',
            //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
            '/^(?<username>\S+)向尾号(?<card>\S+)账户(?<time>\S+)转入(?<money>\S+)元，余额为(?<totalmoney>\S+)元，摘要:[^\r\n]+\[光大银行\]/',
            //尾号5771账户09:10存入300元，余额8463元，摘要:刘凤娟支付宝转账 刘凤娟支付宝转账。瑞幸咖啡在手机银行等您！[光大银行]   2019/7/12  12：12
            '/^尾号(?<card>\S+)账户(?<time>\S+)存入(?<money>\S+)元，余额(?<totalmoney>\S+)元，摘要:(?<username>\S+)支付宝转账 (?<username1>\S+)支付宝转账。[^\r\n]+\[光大银行\]/',


            //光大结束 nes

            //广发
            '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)日(?<time1>\S+)收入人民币(?<money>\S+)元[\(（]银联入账-(?<username>\S+)[\)）]。/',
//        //【广发银行】您尾号6070卡10日02:50收入人民币200.00元(银联入账-许慧龙)。
//        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)日(?<time1>\S+)收入人民币(?<money>\S+)元\(银联入账-(?<username>\S+)\)。/',
//        //【广发银行】您尾号6913卡10日22:55收入人民币50.00元（银联入账-张晓雨）。   和上个就是括号的问题
//        '/^【广发银行】您尾号(?<card>\S+)卡(?<time>\S+)日(?<time1>\S+)收入人民币(?<money>\S+)元\（银联入账-(?<username>\S+)\）。/',

            //兴业银行 new
            //30日09:24账户*9861*汇款汇入收入1999.00元，余额8499.00元。对方户名:刘庚[兴业银行]
            '/^(?<time>\S+)日(?<time1>\S+)账户(?<card>\S+)汇款汇入收入(?<money>\S+)元，余额(?<totalmoney>\S+)元。对方户名:(?<username>\S+)（转账汇款）\[兴业银行]/',
            '/^(?<time>\S+)日(?<time1>\S+)账户(?<card>\S+)汇款汇入收入(?<money>\S+)元，余额(?<totalmoney>\S+)元。对方户名:(?<username>\S+)\[兴业银行]/',
            //兴业银行 new  end
            //中原银行
            //【中原银行】您尾号4259的卡06月26日16:42支付宝-支付宝（中国）网络技术有限公司转入；董艳伟支付宝转账；转账10元，可用余额10.54元。
            '/^【中原银行】您尾号(?<card>\S+)的卡(?<time>\S+)支付宝-支付宝\（中国\）网络技术有限公司转入；(?<username>\S+)支付宝转账；转账(?<money>\S+)元，可用余额(?<totalmoney>\S+)元。/',
            '/^【中原银行】您尾号(\S+)的卡(\S+)支付宝-支付宝（中国）网络技术有限公司转入；(\S+)支付宝转账；转账(?<money>\S+)元，可用余额(\S+)元。/',
            //【中原银行】您尾号4259的卡06月26日16:55跨行网银转入10元，可用余额40.54元，对方户名：董艳伟。
            '/^【中原银行】您尾号【中原银行】您尾号0980的卡07月05日01:14移动银行转入10元，可用余额10元，对方户名：姬翼鹏。的卡(?<time>\S+)日(?<time1>\S+)跨行网银转入(?<money>\S+)元，可用余额(?<totalmoney>\S+)元，对方户名：(?<username>\S+)。/',
            //【中原银行】您尾号0980的卡07月05日01:14移动银行转入10元，可用余额10元，对方户名：姬翼鹏。
            '/^【中原银行】您尾号(?<card>\S+)的卡(?<time>\S+)日(?<time1>\S+)移动银行转入(?<money>\S+)元，可用余额(?<totalmoney>\S+)元，对方户名：(?<username>\S+)/',

            //民生
            //账户*2549于06月30日10:06存入￥100.00元，可用余额210.00元。周世永支付宝转账-周世永支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
            '/^账户(?<card>\S+)于(?<time1>\S+)存入￥(?<money>\S+)元，可用余额(?<totalmoney>\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝\（中国\）网络技术有限公司。\【民生银行\】/',
            //账户*2598于07月12日07:48存入￥49999.00元，付方熊翼，可用余额61454.00元。跨行转出。【民生银行】
            '/^账户(?<card>\S+)于(?<time>\S+)日(?<time1>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<totalmoney>\S+)元。跨行转出。\【民生银行\】/',
            //账户*5192于07月12日02:54存入￥45000.00元，付方高小明，可用余额57023.00元。存款。【民生银行】
            '/^账户(?<card>\S+)于(?<time>\S+)日(?<time1>\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(?<totalmoney>\S+)元。存款。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，付方(\S+)，可用余额(\S+)元。存款。\【民生银行\】/',
            //民生  new  end

            //平安银行
            //您尾号9779的账户于7月10日网银转账转入人民币60.00元，活期存款账户余额人民币60.00元，付款人唐会香，详询95511-3【平安银行】
            '/^您尾号(?<card>\S+)的账户于(?<time>\S+)日网银转账转入人民币(?<money>\S+)元，活期存款账户余额人民币(?<totalmoney>\S+)元，付款人(?<username>\S+)，详询95511-3\【平安银行\】/',
            //平安银行 newend

            // 邮政银行
            //【邮储银行】19年07月05日16:20张亚伟账户8847向您尾号874账户他行来账金额12.00元，余额48.00元。
            '/^【邮储银行】(?<time>\S+)[\d](?<username>\S+)账户(?<card1>\S+)向您尾号(?<card>\S+)账户他行来账金额(?<money>\S+)元，余额(?<totalmoney>\S+)元/',
            // 邮政银行  new end


//        '/^(\S+)向您尾号(\S+)的账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款。[^\r\n]+\[光大银行\]/',
            '/^(?<username>\S+)向您尾号(?<card>\S+)的账户(?<time>\S+)转入(?<money>\S+)元，余额为(?<totalmoney>\S+)元，摘要:网银跨行汇款。[^\r\n]+\[光大银行\]/',
            '/^您尾号(\S+)于(\S+)日(\S+)付款业务转入人民币(?<money>\S+)元,存款账户余额人民币(\S+)元。详询95511-3【平安银行】/',
            //您尾号9779的账户于7月10日网银转账转入人民币60.00元，活期存款账户余额人民币60.00元，付款人唐会香，详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)日网银转账转入人民币(?<money>\S+)元，活期存款账户余额人民币(\S+)元，付款人(\S+)，详询95511-3\【平安银行\】/',
            //您尾号9779的账户于7月10日15:58付款业务转入人民币10.00元,存款账户余额人民币70.00元。详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)付款业务转入人民币(?<money>\S+)元，存款账户余额人民币(\S+)元，付款人(\S+)，详询95511-3\【平安银行\】/',
            //您尾号9779的账户于7月10日15:59银联入账转入人民币10.00元,存款账户余额人民币80.00元。详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)银联入账转入人民币(?<money>\S+)元,存款账户余额人民币(\S+)元。详询95511-3\【平安银行\】/',
            //您尾号9779的账户于7月10日15:59付款业务转入人民币10.00元,存款账户余额人民币90.00元。详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)付款业务转入人民币(?<money>\S+)元,存款账户余额人民币(\S+)元。详询95511-3\【平安银行\】/',
            //您尾号9779的账户于7月10日15:59银联入账转入人民币10.00元,存款账户余额人民币100.00元。详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)银联入账转入人民币(?<money>\S+)元,存款账户余额人民币(\S+)元。详询95511-3\【平安银行\】/',
            //您尾号9779的账户于7月10日16:01跨行转账转入人民币10.00元,存款账户余额人民币110.00元。详询95511-3【平安银行】
            '/^您尾号(\S+)的账户于(\S+)月(\S+)跨行转账转入人民币(?<money>\S+)元,存款账户余额人民币(\S+)元。详询95511-3\【平安银行\】/',
            //工商银行
            '/^您尾号(\S+)卡(\S+)日(\S+)工商银行收入\(他行汇入\)(?<money>\S+)元，余额(\S+)元。【工商银行】/',
            '/^您尾号(\S+)卡(\S+)网上银行收入\((\S+)支付宝转\)(?<money>\S+)元，余额(\S+)元。【工商银行】/',
            '/^您尾号(\S+)卡(\S+)日(\S+)快捷支付收入\(转账到银行卡财付通\)(?<money>\S+)元，余额(\S+)元。【工商银行】/',
            //您尾号7768卡7月5日09:00网上银行收入(转账到银行卡)10元，余额10元。【工商银行】
            '/^您尾号(\S+)卡(\S+)日(\S+)网上银行收入\(转账到银行卡\)(?<money>\S+)元，余额(\S+)元。【工商银行】/',
            //您尾号7768卡7月5日09:08手机银行收入(网转)10元，余额30元。【工商银行】
            '/^您尾号(\S+)卡(\S+)日(\S+)手机银行收入\(网转\)(?<money>\S+)元，余额(\S+)元。【工商银行】/',
            //您尾号9757卡6月28日10:05网上银行收入(张威支付宝转帐)11元，余额11元。【工商银行】
            '/^您尾号(\S+)卡(\S+)网上银行收入\((\S+)支付宝转帐\)(?<money>\S+)元，余额(\S+)元。\【工商银行\】/',

            //建设银行
            '/^(\S+)月(\S+)分向您尾号(\S+)的储蓄卡账户电子汇入收入人民币(?<money>\S+)元,活期余额(\S+)\[建设银行]/',
            '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)支付机构提现收入人民币(?<money>\S+)元,活期余额(\S+)元。\[建设银行]/',
            '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)支付机构提现收入人民币(?<money>\S+)元，活期余额(\S+)元\[建设银行]/',
            '/^您尾号(\S+)的储蓄卡账户(\S+)日(\S+)转账存入收入人民币(?<money>\S+)元,(\S+)\[建设银行]/',
            '/^(\S+)日(\S+)向您尾号(\S+)的储蓄卡账户转账收入人民币(?<money>\S+),活期余额(\S+)元。\[建设银行]/',
            '/^(\S+)日(\S+)向您尾号(\S+)的储蓄卡账户转账存入收入人民币(?<money>\S+),活期余额(\S+)元。\[建设银行]/',
            '/^(\S+)日(\S+)向您尾号(\S+)的储蓄卡账户银联入账收入人民币(?<money>\S+)元,活期余额(\S+)元。\[建设银行]：云闪付转账/',
            //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]
            '/^(\S+)日(\S+)分向您尾号(\S+)的储蓄卡账户手机银行转账收入人民币(?<money>\S+)元,活期余额(\S+)元。\[建设银行]/',
            //您尾号6384的储蓄卡账户7月14日21时39分银联入账收入人民币10.00元,活期余额10.00元。[建设银行]    2019/7/14
            '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)分银联入账收入人民币(?<money>\S+)元,活期余额(\S+)元。\[建设银行\]/',

            //招商
            '/^您账户(\S+)于(\S+)月(\S+)日收到人民币(?<money>\S+)\（\S+。[招商银行]\S+/',
            '/^您账户(\S+)于(\S+)月(\S+)收款人民币(?<money>\S+)，备注：(\S+)\[招商银行]/',
            '/^您账户(\S+)于(\S+)月(\S+)日他行实时转入人民币(?<money>\S+)，付方(\S+)。快抽话费 cmbt.cn\/yo 。\[招商银行]/',
            //您账户6581于07月05日收到本行转入人民币30.00，付方王倩雯，账号尾号5155，备注：转账[招商银行]
            '/^您账户(\S+)于(\S+)月(\S+)日收到本行转入人民币(?<money>\S+)，付方(?<username>\S+)，账号尾号(\S+)，备注：转账\[招商银行]/',
            //您账户8572于07月05日他行实时转入人民币10.00，付方霍明飞。领积分 cmbt.cn/jfqd 。[招商银行]
            '/^您账户(\S+)于(\S+)月(\S+)日他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分 cmbt.cn\/jfqd 。\[招商银行]/',
            //您账户4766于07月05日11:17银联入账人民币10.00元（财付通支付科技有限公司/转账到银行卡）[招商银行]
            '/^您账户(\S+)于(\S+)月(\S+)银联入账人民币(?<money>\S+)元\（财付通支付科技有限公司\/转账到银行卡\）\[招商银行]/',
            //您账户4168于07月10日他行实时转入人民币2000.00，付方曹凯。领积分cmbt.cn/jfqd。
            '/^您账户(\S+)于(\S+)月(\S+)日他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。领积分cmbt.cn\/jfqd。/',
            //您账户1553于07月10日他行实时转入人民币12.00，付方赵振东。福利 cmbt.cn/ali07 。[招商银行]
            '/^您账户(\S+)于(\S+)月(\S+)日他行实时转入人民币(?<money>\S+)，付方(?<username>\S+)。福利 cmbt.cn\/ali07 。\[招商银行]/',
            //您账户3755于07月11日01:17二维码收款（蒋欣），人民币400.00元[招商银行]
            '/^您账户(\S+)日(\S+)二维码收款\（(\S+)\），人民币(?<money>\S+)元\[招商银行]/',

            //中国农业银行*/
            '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(?<money>\S+)，/',
            '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(?<money>\S+)。/',
            '/^【中国农业银行】(\S+)于(\S+)日(\S+)完成网银转账交易人民币(?<money>\S+)。/',
            '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成工资交易人民币(?<money>\S+)，余额(\S+)。/',
            '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成支付宝发交易人民币(?<money>\S+)，/',
            '/^【中国农业银行】(\S+)账户(\S+)日(\S+)完成代付交易人民币(?<money>\S+)，/',
            '/^【中国农业银行】财付通支付科技有限公司于(\S+)日(\S+)向您尾号(\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(\S+)。/',
            '/^【中国农业银行】(\S+)于(\S+)向您尾号(\S+)账户完成转存交易人民币(?<money>\S+)，余额(\S+)。/',
            '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成银联入账交易人民币(?<money>\S+)，余额(\S+)。/',
            '/^【中国农业银行】(\S+)于(\S+)向您尾号(\S+)账户完成银联入账交易人民币(?<money>\S+)，余额(\S+)。/',
            //【中国农业银行】说揭锌ㄓ?07月11日21:48向您尾号5274账户完成代付交易人民币12.00，余额12.54。
            '/^\【中国农业银行\】(\S+)日(\S+)向您尾号(\S+)账户完成代付交易人民币(?<money>\S+)，余额(\S+)。/',
            //【中国农业银行】糁局Ц侗ψ?汪志支付宝转账于07月11日21:35向您尾号6572账户完成代付交易人民币99.00，余额14333.85。
            '/^\【中国农业银行\】(\S+)[\s\S]+;(\S+)日向您尾号(\S+)账户完成代付交易人民币(?<money>\S+)，余额(\S+)。/',

            //浦发
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[互联汇入](?<money>\S+)，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[银联入账:余额宝]，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[转账到银行卡]，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[(\S+)支付宝转账]，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[支付宝(\S+)支付宝转\]，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[银联入账:(\S+)]，可用余额(\S+)。【浦发银行】/',
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[支付宝-(\S+)支付宝]，可用余额(\S+)。【浦发银行】/',
            //您尾号1418卡人民币活期23:13存入20.00[转入黄孟4620]【浦发银行】
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[转入(?<username>\S+)[\d]{4}]【浦发银行】/',
            //您尾号6924卡人民币活期13:59存入10.00[财付通-转账到银行卡]，可用余额10.00。【浦发银行】
            '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(?<money>\S+)\[财付通-转账到银行卡\]，可用余额(\S+)。\【浦发银行\】/',
            //华夏银行：
            '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(?<money>\S+)元，余额(\S+)元。【华夏银行】/',
            '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(?<money>\S+)元，余额(\S+)元。银联入账。【华夏银行】/',
            '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(?<money>\S+)元，余额(\S+)元。收到网联付款。【华夏银行】/',

            //光大银行：
            '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:银联入账—商户名称:银联转账（云闪付）,付款方账号后四位:(\S+)。\[光大银行]/',
            '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:银联入账—付款方姓名:(?<username>\S+),付款方账号后四位:(\S+)。\[光大银行]/',
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行]/',
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。\[光大银行]/',
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。逛阳光花园，礼遇618！\[光大银行\]/',
            '/^尾号(\S+)账户(\S+)：(\S+)存入(?<money>\S+)元，余额(\S+)，摘要：(\S+) (\S+)。逛阳光花园，礼遇618！\【光大银行\】/',
            '/^(\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行\]/',
            '/^尾号(\S+)账户(\S+)：(\S+)存入(?<money>\S+)元，余额(\S+)，摘要：转账到银行卡。逛阳光花园，礼遇618！\【光大银行\】/',
            '/^(\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要：网银跨行汇款。逛阳光花园，礼遇618！\【光大银行\】/',
            '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:银联入账—商户名称:财付通支付科技有限公司,付款方账号后四位:(\S+)。\[光大银行]/',

            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款。\[光大银行]/',
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
            //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
            //周鹏向尾号2757账户23:22转入12元，余额为17元，摘要:网银跨行汇款手机转账。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款手机转账。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
            //杨雷向您尾号3844的账户10:18转入30元，余额为30.89元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
            '/^(?<username>\S+)向您尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
            //卞海涛向尾号5616账户20:44转入3285元，余额为28841元，摘要:网银跨行汇款跨行转出。[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款跨行转出。\[光大银行\]/',
            //杨云华向尾号8023账户21:23转入12元，余额为60.2元，摘要:网银跨行汇款。人气理财“随心定”发售中，登录手机银行购买[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款。人气理财“随心定”发售中，登录手机银行购买\[光大银行\]/',
            //尾号6675账户19:34存入50元，余额为31370.99元，摘要:转账到银行卡。   //@todo 确认句号
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)，摘要:转账到银行卡。/',

            //于淼向您尾号8023的账户22:46转入12元，余额为96.2元，摘要:网银跨行汇款。人气理财“随心定”发售中，登录手机银行购买[光大银行]
            '/^(?<username>\S+)向您尾号(\S+)的账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款。人气理财“随心定”发售中，登录手机银行购买\[光大银行\]/',
            //于淼向您尾号8023的账户22:46转入12元，余额为96.2元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买[光大银行]
            '/^(?<username>\S+)向您尾号(\S+)的账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买\[光大银行\]/',
            //王怀九向尾号6568账户15:45转入1000元，余额为26231.98元，摘要:网银跨行汇款手机银行转账。[光大银行]   2019/7/7 1602
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款手机银行转账。\[光大银行\]/',
            //娄艳军向尾号6822账户16:58转入12元，余额为13元，摘要:网银跨行汇款手机转账。[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款手机转账。\[光大银行\]/',
            //向尾号2554账户12:45转入130元，余额为20700元，摘要:转账到银行卡。
            '/^(\S+)尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:转账到银行卡。/',
            //郭凯歌向尾号5730账户12:17转入999元，余额为11247元，摘要:网银跨行汇款  【网银发起，如误请退】。[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款  \【网银发起，如误请退\】。\[光大银行\]/',
            //交易提醒：账号：*1911，金额：59.00，类型：网银转入，附言：跨行转出，余额：9093.50，时间：07月10日07：31。
            '/^(\S+)易(\S+)：账号：\*(\S+)，金额：(?<money>\S+)，类型：网银转入，附言：跨行转出，余额：(\S+)，时间(\S+)日(\S+)。/',
            //尾号4947账户13:21存入4999元，余额58520元，摘要:转到银行卡。[光大银行]
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)，摘要:转到银行卡。\[光大银行\]/',
            //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。\[光大银行\]/',
            //尾号5771账户09:10存入300元，余额8463元，摘要:刘凤娟支付宝转账 刘凤娟支付宝转账。瑞幸咖啡在手机银行等您！[光大银行]   2019/7/12  12：12
            '/^尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:(\S+)支付宝转账 (\S+)支付宝转账。瑞幸咖啡在手机银行等您！\[光大银行\]/',
            //王连岐向尾号5730账户10:40转入455元，余额为11572元，摘要:网银跨行汇款。瑞幸咖啡在手机银行等您！[光大银行]    2019/7/12  13：20
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款。瑞幸咖啡在手机银行等您！\[光大银行\]/',
            //宋赛波向尾号3581账户11:18转入199元，余额为7373元，摘要:网银跨行汇款跨行转出。瑞幸咖啡在手机银行等您！[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款跨行转出。瑞幸咖啡在手机银行等您！\[光大银行\]/',
            //袁钰瑜向尾号4482账户18:19转入4000元，余额为79788元，摘要:网银跨行汇款转账。瑞幸咖啡在手机银行等您！[光大银行]
            '/^(?<username>\S+)向尾号(\S+)账户(\S+)转入(?<money>\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。瑞幸咖啡在手机银行等您！\[光大银行\]/',
            //尊敬的客户：您尾号6720账户21:22存入2元，余额2427.88元，摘要:银联入账—商户名称:基金赎回款,付款方账号后四位:无。[光大银行]
            '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(?<money>\S+)元，余额(\S+)元，摘要:银联入账—商户名称:基金赎回款,付款方账号后四位:无。\[光大银行]/',
            //光大结束

            //广发
            //【广发银行】您尾号2959卡09日17:08收入人民币12.00元(网银入账)。账户余额:13.00元。
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(网银入账\）。账户余额:(\S+)元。/',
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(网银入账\)。/',
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(转账存入\)。/',   //张笛新增
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。/',   //张笛新增
            //【广发银行】您尾号2167卡05日21:24收入人民币20.00元。账户余额:40.00元。               摘要：支付宝银行卡转账
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元。账户余额:(\S+)元。/',
            //【广发银行】您尾号2290卡25日21:11收入人民币10.00元(银联入账-财付通支付科技有限公司）。账户余额:10.00元。     摘要：微信银行卡转账
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司\)。账户余额:(\S+)元。/',   //张笛新增
            //【广发银行】您尾号2167卡05日21:19收入人民币20.00元(网银入账)。账户余额:20.00元。     摘要：跨行手机银行转账
            '/^\【广发银行\】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(网银入账\)。账户余额:(\S+)元。/',   //张笛新增
            //【广发银行】您尾号2290卡25日21:11收入人民币10.00元(银联入账-财付通支付科技有限公司）。                        摘要：微信银行卡转账
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(银联入账-财付通支付科技有限公司）。/',
//
            //【广发银行】您尾号6070卡10日02:50收入人民币200.00元(银联入账-许慧龙)。
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\(银联入账-(\S+)\)。/',
            //【广发银行】您尾号6913卡10日22:55收入人民币50.00元（银联入账-张晓雨）。   和上个就是括号的问题
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\（银联入账-(\S+)\）。/',
            //【广发银行】您尾号2486卡10日00:50收入人民币420.00元。
            '/^\【广发银行\】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元。/',
            //【广发银行】您尾号5058卡10日23:49收入人民币12.00元（网银入账）。
            '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(?<money>\S+)元\（网银入账\）。/',

            //兴业银行
            '/^(\S+)日(\S+)账户(\S+)网联付款收入(?<money>\S+)元，余额(\S+)元\[兴业银行]/',
            '/^(\S+)日(\S+)账户(\S+)汇款汇入收入(?<money>\S+)元，余额(\S+)元。对方户名:(\S+)（转账汇款）\[兴业银行]/',

            //中原银行
            '/^【中原银行】您尾号(\S+)的卡(\S+)支付宝-支付宝（中国）网络技术有限公司转入；(?<username>\S+)支付宝转账；转账(?<money>\S+)元，可用余额(\S+)元。/',
            '/^【中原银行】您尾号(\S+)的卡(\S+)日(\S+)跨行网银转入(?<money>\S+)元，可用余额(\S+)元，对方户名：(?<username>\S+)。/',
            '/^【中原银行】您尾号(\S+)的卡(\S+)日(\S+)财付通-财付通支付科技有限公司转入；转账到银行卡；转账(?<money>\S+)元，可用余额(\S+)元。/',
            //【中原银行】您尾号0980的卡07月05日01:14移动银行转入10元，可用余额10元，对方户名：姬翼鹏。
            '/^【中原银行】您尾号(\S+)的卡(\S+)日(\S+)移动银行转入(?<money>\S+)元，可用余额(\S+)元，对方户名：(?<username>\S+)/',
            //民生银行
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。转账。【民生银行】/',
            //"账户*6995于07月15日18:00存入￥100.00元，可用余额401.00元。魏传文支付宝转账-魏传文支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。(?<username>\S+)支付宝转账-(?<username1>\S+)支付宝转账-支付宝（中国）网络技术有限公司。【民生银行】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。银联入账：转账到银行卡。【民生银行】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。手机转账。【民生银行】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。存款。【民生银行】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。转账到银行卡-财付通支付科技有限公司。【民生银行】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。跨行转出。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。手机银行转账。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。银联入账。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。NonResident。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(\S+)元。存款。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，付方(?<username>\S+)，可用余额(\S+)元。跨行转出。\【民生银行\】/',
            '/^账户\*(\S+)于(\S+)日(\S+)存入￥(?<money>\S+)元，可用余额(\S+)元。转账汇款。\【民生银行\】/',

            //交通银行
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)网银转入(?<money>\S+)元,交易后余额为(\S+)元。【交通银行】/',
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)财付通公司网银转入(?<money>\S+)元,交易后余额为(\S+)元。【交通银行】/',
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)网络支付转入(?<money>\S+)元,交易后余额为(\S+)元。【交通银行】/',
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)支付宝网银转入(?<money>\S+)元,交易后余额为(\S+)元。【交通银行】/',
            //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：手机转账，余额：24.00，时间：07月08日22：09。
            '/^(\S+)易(\S+)\：账号：\*(\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机转账，余额：(\S+)，时间：(\S+)日(\S+)。/',
            //您尾号卡的于07月09日07:25手机银行交行转入99.00元，交易后余额为162.00元。【交通银行】
            '/^您尾号卡的于(\S+)月(\S+)日(\S+)手机银行交行转入(?<money>\S+)元，交易后余额为(\S+)元。\【交通银行\】/',
            //交易提醒：账号：*3780，金额：12.00，类型：网银转入，附言：网银转账，余额：24.00，时间：07月08日22：09。
            '/^(\S+)易(\S+)\：账号\：\*(\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账汇款，余额(\S+)，时间\：(\S+)日(\S+)。/',
            //您尾号*1911的卡于07月10日02:27支付宝网银转入99.00元，交易后余额为9034.50元。
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)支付宝网银转入(?<money>\S+)元，交易后余额为(\S+)元。/',
            //您尾号*1176的卡于07月10日10:08财付通公司网银转入100.00元，交易后余额为7328.00元。
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)财付通公司网银转入(?<money>\S+)元，交易后余额为(\S+)元。/',
            //交易提醒：账号：*3917，金额：1000.00，类型：网银转入，附言：转账，余额：11096.00，时间：07月10日21：33。
            '/^(\S+)易(\S+)\：账号\：\*(\S+)，金额：(?<money>\S+)，类型：网银转入，附言：转账，余额(\S+)，时间\：(\S+)日(\S+)。/',
            //交易提醒：账号：*5956，金额：12.00，类型：网银转入，附言：手机银行转账，余额：48.00，时间：07月11日16：22。
            '/^(\S+)易(\S+)\：账号\：\*(\S+)，金额：(?<money>\S+)，类型：网银转入，附言：手机银行转账，余额(\S+)，时间\：(\S+)日(\S+)。/',
            //您尾号*8394的卡于07月12日17:37手机银行交行转入1999.00元,交易后余额为4469.00元。【交通银行】
            '/^您尾号\*(\S+)的卡于(\S+)日(\S+)手机银行交行转入(?<money>\S+)元,交易后余额为(\S+)元。【交通银行】/',
            //交通银行end

            // 邮政银行
            //【邮储银行】19年07月05日16:14您尾号874账户提现金额12.00元，余额12.00元。
            //【邮储银行】19年07月05日16:18您尾号874账户提现金额12.00元，余额36.00元。
            //【邮储银行】19年07月05日16:15您尾号874账户提现金额12.00元，余额24.00元。
            '/^【邮储银行】(\S+)日(\S+)您尾号(\S+)账户提现金额(?<money>\S+)元，余额(\S+)元/',
            //微信转帐
            //【邮储银行】19年07月05日16:20张亚伟账户8847向您尾号874账户他行来账金额12.00元，余额48.00元。
            '/^【邮储银行】(\S+)账户(\S+)向您尾号(\S+)账户他行来账金额(?<money>\S+)元，余额(\S+)元/',
        ];
        foreach ($pattern as $key => $value){
//        dump($value);
            preg_match($value,$str,$match);
            if(!empty($match)){
                $amount = $match[4];
                $match[4] = str_replace(',', '', $amount) ;//去除金额中的逗号
//            else if (substr($match[0],-9) == '设银行'){
//                $match[3] = 1;
//                $aa = str_replace('收入人民币', '', strtok($match[2], ',')) ;
//                $match[4] =str_replace('元', '', $aa) ;
//            }
                return $match;
            }
        }
    }
    public static function apiJsonReturn($code=null,$msg=null,$data=null){


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

   public static function exportExcel($expTitle,$expCellName,$expTableData){
        include_once EXTEND_PATH.'PHPExcel/PHPExcel.php';//方法二
//        require_once (dirname(__FILE__).EXTEND_PATH.'PHPExcel/PHPExcel.php');
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $yesterday = strtotime('yesterday');
        $fileName = $expTitle;//.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
//        $objPHPExcel = new PHPExcel();//方法一
        $objPHPExcel = new \PHPExcel();//方法二
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean();//这一步非常关键，用来清除缓冲区防止导出的excel乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//"xls"参考下一条备注
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');//"Excel2007"生成2007版本的xlsx，"Excel5"生成2003版本的xls
        $objWriter->save('php://output');

        exit;
    }


}