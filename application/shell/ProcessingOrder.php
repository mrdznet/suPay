<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\console\input\Argument;
use think\DB;
use app\common\Redis;
use app\admin\model\Device;
use app\admin\model\OrderModel;
use app\admin\model\Helper;

/**
 * 派单接口对应数据库
 * Class ProcessingOrderOne
 * @package app\shell
 */
class ProcessingOrder extends Command
{
    protected function configure()
    {
        $this->setName('ProcessingOrder')
			->addArgument('redisDbId', Argument::REQUIRED, 'redis库编号')
			->setDescription('处理总下单数据');
    }

    /**
     * 定时下订单并且商户回调
     * @param Input  $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
		$redisDbId = $input->getArgument('redisDbId');
		$redisDbId = 0;
		if(empty($redisDbId)){
			$redis = new Redis();
		}else{
			$redis = new Redis(['index'=>$redisDbId]);
		}
		$orderData = $redis->rpop('order_data_start');
		$orderData = json_decode($orderData,true);

		if(empty($orderData)){
			$output->writeln("ProcessingOrder:暂无缓存数据");
            sleep(10);
			exit;
		}else{
			$orderData['create_order_notify_url'] = "wwww.baidu.com";
			$redis->lpush('order_data_second',json_encode($orderData),300);
			//去可用设备
			$accountData = Device::getUseAccout();
			if(empty($accountData['account'])||empty($accountData['ali_qr'])){
				$message['code'] = '10004';
				$message['msg'] = "无可用设备，下单失败";
				postCurl($orderData['create_order_notify_url'], $message);
			}else{
				$time = time();
				$insertOrderData['merchant_id'] = $orderData['merchant_id'];  //商户
				$insertOrderData['orderme'] = OrderModel::GUID(); //本平台订单号
				$insertOrderData['amount'] = $orderData['amount'];
				$insertOrderData['payable_amount'] = $orderData['payable_amount'];
				$insertOrderData['order_no'] = $orderData['order_no'];
				$insertOrderData['orderme'] = OrderModel::GUID();
				$insertOrderData['payment'] = $orderData['payment'];  //平台自定义id
				$insertOrderData['add_time'] = $time;  //商户请求时间
				$insertOrderData['notify_url'] = $orderData['notify_url'] ;
				$insertOrderData['qr_url'] = $accountData['ali_qr'] ;
				$insertOrderData['account'] = $accountData['account'];
				$insertOrderData['create_order_notify'] = '1';  //下单回调状态
				$Db = new Db();
				Db::startTrans();
				//锁设备
				$lockDevice = $Db::table('s_device')->where(['account'=>$accountData['account']])->update(['lock_time'=>$time]);
				//存库order
				$inserOrderResult = OrderModel::create($insertOrderData);
				//回调
				if($inserOrderResult&&$lockDevice){
					Db::commit();  // @ todo 下订单回调
//					Helper::apiLog('success',"商户~~~".$insertOrderData['merchant_id']."~~~订单号~~~".$insertOrderData['order_no']."~~~下单成功~~~","create_oreder");
//					$returnData['code'] = '10000';
//					$returnData['order_no'] = $insertOrderData['order_no'];
//					$returnData['msg'] = "下单成功";
//					$returnData['data'] = "http://129.204.132.45/api/qrcode/sendcode?qr_url=".$accountData['ali_qr']."&amount=".$inserOrderResult['amount'];
//					postCurl($orderData['create_order_notify_url'],$returnData);
				}else{
					Db::rollback(); // todo 下订单回调
//					$returnData['code'] = '19999';
//					$returnData['order_no'] = $insertOrderData['order_no'];
//					$returnData['msg'] = '系统异常，下单失败';
//					$returnData['data'] = '';
//					postCurl($orderData['create_order_notify_url'],$returnData);
				}

			}
		}
		$output->writeln("ProcessingOrder:下单请求成功，订单号".$orderData['order_no']);
    }
}