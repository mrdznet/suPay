<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\admin\model\BankDeviceModel;

/**
 * 派单接口对应数据库
 * Class ProcessingOrderOne
 * @package app\shell
 */
class DisLockPayDevice extends Command
{
    protected function configure()
    {
        $this->setName('DisLockPayDevice')
			->setDescription('处理总下单数据');
    }

    /**
     * 定时解锁已支付设备  （已经支付设备 支付完锁定60s）
     * @param Input  $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
		$output->writeln("DisLockPayDevice:解锁支付设备脚本停止");exit;

//		$updateData['lock_time'] = '0';
		$now = time();
		$limitTime = 480;
		//旧锁账户解锁  start
		$lockLimit = $now-$limitTime;
		$BankDeviceModel = new BankDeviceModel();
		$updateDeviceData = collection($BankDeviceModel
			->field('phone')
			->where('lock_time','>','0')
			->where('lock_time','<',$lockLimit)
			->select()
		)->toArray();
		//循环查找扫码超时订单
		$errorNum =0;
		$successNum =0;
		$totalNum = count($updateDeviceData);
		foreach ($updateDeviceData as $key => $val){
			//解锁设备状态
			$updateStepTwoData['lock_time'] = '0';
			$updateStepTwoWhere['phone'] = $val['phone'];
			$updateStepTwo = BankDeviceModel::where($updateStepTwoWhere)->update($updateStepTwoData);
			if($updateStepTwo){
				$successNum++;
			}else{
				$errorNum ++;
			}
		}
		//旧锁账户解锁  end
		$output->writeln("DisLockPayDevice:总应解锁支付设备数".$totalNum."成功解锁支付设备数".$successNum."失败解锁支付设备数".$errorNum);
	}
}