<?php
namespace app\admin\controller;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;
use app\admin\model\BankDeviceModel;
use app\api\model\OrderModel;
use app\admin\model\SystemConfigModel;

class Autodevice extends Command
{
	protected function configure()
	{
		$this->setName('Autodevice')->setDescription('解锁设备任务 ');
	}

	/**
	 * 定时解锁未支付  设备（未支付锁默认锁480S）
	 * @param Input $input
	 * @param Output $output
	 */
	protected function execute(Input $input, Output $output)
	{
		//公用部分 start
		$limitTime = SystemConfigModel::getPayLimitTime();
		$now = time();
		$lockLimit = $now-$limitTime;
		//公用部分 end
		//锁设备 start
//		$updateData['lock_time'] = '0';
//		//旧锁账户解锁  start
//		$db = new Db();
//		$BankDeviceModel = new BankDeviceModel();
//		$updateDeviceData = collection($BankDeviceModel
//			->field('phone,client_id')
//			->where('lock_time','>','0')
//			->where('lock_time','<',$lockLimit)
//			->select()
//		)->toArray();
////		//循环查找扫码超时订单
//		$errorNum =0;
//		$successNum =0;
//		$totalNum = count($updateDeviceData);
//		foreach ($updateDeviceData as $key => $val){
//
//			//修改订单状态
//			$updateStepOneData['order_status'] = '4';
//			$updateStepOneWhere['order_status'] = '0';
//			$updateStepOneWhere['account'] = $val['phone'];
//
//            // 新增添加userId 未付款次数 start
//            $orderData =  OrderModel::where($updateStepOneWhere)->order('id asc')->find();
//            // table: s_prohibited_user
//            $prohibitedUserData = $db::table('s_prohibited_user')->where('user_id','=',$orderData['payersessionid'])->find();
//            if($prohibitedUserData){
//                $addNoPayTimes = $db::table('s_prohibited_user')
//                        ->where('user_id','=',$orderData['payersessionid'])
//                        ->update([
//                            'user_id' => $prohibitedUserData['user_id'],
//                            'no_pay_times' =>  Db::raw("no_pay_times+1"),
//                            'add_time' =>  time(),
//                        ]);
//            }else{
//                $createData['user_id'] = $orderData['payersessionid'];
//                $addNoPayTimes = $db::table('s_prohibited_user')->insert($createData);
//            }
//            //添加userId 未付款次数 end
//            //修改订单状态  end
//			$updateStepOne = OrderModel::where($updateStepOneWhere)->update($updateStepOneData);
//
//			//解锁设备状态
//			$updateStepTwoData['lock_time'] = '0';
//			$updateStepTwoData['need'] = 2;
//			$updateStepTwoData['is_pay'] = 2;
//			$updateStepTwoWhere['phone'] = $val['phone'];
//			$updateStepTwo = BankDeviceModel::where($updateStepTwoWhere)->update($updateStepTwoData);
//            //解锁设备  client_id
//            socketServerToClient($val['client_id'],"unlockDevice");
//			if($addNoPayTimes&&$updateStepOne&&$updateStepTwo){
//				$successNum++;
//			}else{
//				$errorNum ++;
//			}
//		}
////		//修改不在线设备的订单状态
//		$updateData['order_status'] = '4';
//		$updateOfflineDeviceOrderNum = OrderModel::where('add_time','<',$lockLimit)
//			->where('order_status','=','0')
//			->update($updateData);
//		if(!$updateOfflineDeviceOrderNum){
//			$updateOfflineDeviceOrderNum = 0;
//		}
//		$updateStepThreeData['lock_time'] = '0';
//		$updateStepThreeData['need'] = '0';
//		$updateStepThreeData['is_pay'] = '0';
//		$updateStepThree = BankDeviceModel::where('lock_time','<',$lockLimit)->where('lock_time','>','0')->update($updateStepThreeData);
//		if(!$updateStepThree){
//			$updateStepThree = 0;
//		}
//		$output->writeln("Autodevice:总应解锁设备数".$totalNum."成功解锁设备数".$successNum."失败解锁设备数".$errorNum."其他订单数量".$updateOfflineDeviceOrderNum."无订单设备数量".$updateStepThree);

//		//旧锁账户解锁  end

		//银行转账金额解锁start
		$updateStepOneData['order_status'] = '4';
		$totalNum = OrderModel::where('add_time','<',$lockLimit)->where('order_status','=',3)->count();
		$successNum = OrderModel::where('add_time','<',$lockLimit)->where('order_status','=',3)->update($updateStepOneData);
		$errorNum = 0;
		if(!$successNum){
			$errorNum = $totalNum;
		}
		//银行转账金额解锁  end
		$output->writeln("Autodevice:总应强制超时订单数".$totalNum."成功超时订单数".$successNum."成功超时设备数".$errorNum);
	}
}