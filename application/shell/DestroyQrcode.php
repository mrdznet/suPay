<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\admin\model\BankDeviceModel;
use app\admin\model\SystemConfigModel;

class DestroyQrcode extends Command
{
	protected function configure()
	{
		$this->setName('DestroyQrcode')->setDescription('销毁二维码');
	}

	/**
	 * 定时销毁 设备收款码（未支付锁默认锁解锁时间-60s）
	 * @param Input $input
	 * @param Output $output
	 */
	protected function execute(Input $input, Output $output)
	{
		//公用部分 start
		$limitTime = SystemConfigModel::getPayLimitTime();
		//
		$limitTime = $limitTime - 60;
		$now = time();
		$lockLimit = $now-$limitTime;
		//公用部分 end
        //销毁二维码
		$BankDeviceModel = new BankDeviceModel();
		$updateDeviceData = collection($BankDeviceModel
			->field('phone,client_id')
			->where('lock_time','>','0')
			->where('lock_time','<',$lockLimit)
			->where('destroy','=',2)
			->select()
		)->toArray();
//		//循环查找应该销毁二维码设备
		$errorNum =0;
		$successNum =0;
		$totalNum = count($updateDeviceData);
		foreach ($updateDeviceData as $key => $val){
            //销毁验证码
            $updateStepTwoWhere["phone"] = $val['phone'];
            $distoryQrcode = socketServerToClient($val['client_id'],"QrCodeInvalid");
            $updateStepTwo = BankDeviceModel::where($updateStepTwoWhere)->update([
                'destroy' =>  1,
            ]);
			if($distoryQrcode&&$updateStepTwo){
				$successNum++;
			}else{
				$errorNum ++;
			}
		}
		$output->writeln("DestroyQrcode:应该销毁二维码数".$totalNum."成功销毁二维码数".$successNum."失败销毁二维码数".$errorNum);

	}
}