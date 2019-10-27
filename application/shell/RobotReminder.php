<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;
use app\admin\model\Helper;


/**
 * 派单接口对应数据库
 * Class ProcessingOrderOne
 * @package app\shell
 */
class RobotReminder extends Command
{
	protected function configure()
	{
		$this->setName('RobotReminder')
			->setDescription('定时请求pototo请求，提醒支付率低的设备下线');
	}

	/**
	 * 定时potato 提醒工作室群支付率异常银行卡  <=10%
	 * @param Input  $input
	 * @param Output $output
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	protected function execute(Input $input, Output $output)
	{
		$db = new Db();
		//十分钟前时间戳
		$time = time() - 600;
		$selectResult = $db::table('s_bank_device')
			->field('bank_card,create_time,channel')
			->where('is_prohibit','=',1)
			->where('create_time','<',$time)
			->group('channel')
			->select();

		//订单条件
		$orderWhere['merchant_id'] = ['=','DD'];
		//支付成功条件
		$successWhere['order_status'] = 1;

		$headers = ['Content-Type:application/json'];

		$reminderNum = 0;
		foreach($selectResult as $key=>$vo){
			//十分钟下单量
			$selectResult[$key]['orderTotal'] = $db::table('s_order')->where($orderWhere)->where('card','=',$vo['bank_card'])->where('add_time','>',$time)->count();
			//十分钟支付成功量
			$selectResult[$key]['orderSuccessTotal'] = $db::table('s_order')->where($successWhere)->where('card','=',$vo['bank_card'])->where('add_time','>',$time)->count();
			//如果订单量不等于零  下单十分钟成功率
			if($selectResult[$key]['orderTotal']>=10){
				if ($selectResult[$key]['orderSuccessTotal']==0) {
					$successRate = "0/".$selectResult[$key]['orderTotal'] . "支付成功率0.00";
					$reminderNum ++;
					$returnText = "工作室账号~~".$vo['channel']."~~银行卡~~".$vo['bank_card'].'~~支付率~~'.$successRate."%建议下线";

					$chatId = 10437005;  //默认
					if($vo['channel'] == 'studio_1'){
						$chatId =  10239085;
					}
					//10331313
					if($vo['channel'] == 'studio_5'){
						$chatId =  10331313;
					}
					if($vo['channel'] == 'studio_ts'){
						$chatId =  10355598;
					}
					//10331396
					if($vo['channel'] == 'studio_7'){
						$chatId =  10331396;
					}
					if($vo['channel'] == 'studio_8'){
						$chatId =  10331408;
					}
//					if($vo['channel'] ='studio_ll'){
//						$chatId =  10331313;
//					}
					$sendData = [
						'chat_type' => 2,
						'chat_id' => $chatId,
						'text' => $returnText,
					];
					Helper::cUrlGetData('https://api.potato.im:8443/10101207:CWZIsDhF13uH3CBTusCGpTMd/sendTextMessage', json_encode($sendData), $headers);
				} else {
					//成功率
					$successRate = bcdiv($selectResult[$key]['orderSuccessTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
					if($successRate<=10){
						$reminderNum ++;
						$returnText = "工作室账号~~".$vo['channel']."~~银行卡~~".$vo['bank_card'].'~~支付成功率~~'.$successRate."%建议下线";
						$chatId = 10437005;  //默认

						if($vo['channel'] == 'studio_1'){
							$chatId =  10239085;
						}
						if($vo['channel'] == 'studio_5'){
							$chatId =  10331313;
						}
						if($vo['channel'] == 'studio_ts'){
							$chatId =  10355598;
						}
						if($vo['channel'] == 'studio_7'){
							$chatId =  10331396;
						}
						if($vo['channel'] == 'studio_8') {
							$chatId = 10331408;
						}
//						if($vo['channel'] ='studio_ll'){
//							$chatId =  10331313;
//						}
						$sendData = [
							'chat_type' => 2,
							'chat_id' => $chatId,
							'text' => $returnText,
						];
						Helper::cUrlGetData('https://api.potato.im:8443/10101207:CWZIsDhF13uH3CBTusCGpTMd/sendTextMessage', json_encode($sendData), $headers);
					}
				}
			}

		}
		$output->writeln("RobotReminder: 机器人请求成功！,提醒数量下线数量".$reminderNum);
    }

}