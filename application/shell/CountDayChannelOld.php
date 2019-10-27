<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;
use app\admin\model\Device;

/**
 * 派单接口对应数据库
 * Class ProcessingOrderOne
 * @package app\shell
 */
class CountDayChannelOld extends Command
{
    protected function configure()
    {
        $this->setName('CountDayChannel')
			->setDescription('统计渠道收款');
    }

    /**
     * 定时统计各个渠道收益（）
     * @param Input  $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
		$db = new Db();
		$startTime = strtotime(date("Y-m-d",strtotime("-1 day")));
		$endTime = $startTime+24 * 60 * 60-1;
		$yesterdayOrderWhere['id'] = ['>',115208];
		$channelData = $db::table('s_fu_order')
			->field("SUM(actual_amount) AS yesterdayTotalMoney,channel")
			->where('id','>',115208)
			->where('order_status','=',1)
			->where('add_time','>',$startTime)
			->where('add_time','<',$endTime)
			->where($yesterdayOrderWhere)
			->group('channel')
			->select();
		if(!empty($channelData)){
			foreach ($channelData as $key =>$val){
				$insertData[$key]['channel'] = $val['channel'];
				$insertData[$key]['money'] = $val['yesterdayTotalMoney'];
				$insertData[$key]['time_info'] =  date('Y/m/d',strtotime("-1 day"));
				$insertData[$key]['start_time'] = $startTime;
				$insertData[$key]['end_time'] = $endTime;
				$insertData[$key]['type'] = 1;
			}
		}
		Db::startTrans();
		if(!empty($insertData)){
			$insertResultOne = $db::table('s_channel_calculation')
				->insertAll($insertData);
		}else{
			$insertResultOne = true;
		}
		//查询调单  s_lose_orders
		$channelLostData = $db::table('s_lose_orders')
			->field('sum(actual_amount) as money,studio_id')
			->where('create_time','>',$startTime)
			->where('create_time','<',$endTime)
			->group('studio_id')
			->select();
		if(!empty($channelLostData)){
			foreach ($channelLostData as $key =>$val){
				$insertLoseData[$key]['channel'] = $val['studio_id'];
				$insertLoseData[$key]['money'] = $val['money'];
				$insertLoseData[$key]['time_info'] =  date('Y/m/d',strtotime("-1 day"));
				$insertLoseData[$key]['start_time'] = $startTime;
				$insertLoseData[$key]['end_time'] = $endTime;
				$insertLoseData[$key]['type'] = 2;
			}
		}
		if(!empty($insertLoseData)){
			$insertResultTwo = $db::table('s_channel_calculation')
				->insertAll($insertLoseData);
		}else{
			$insertResultTwo = true;
		}
		if($insertResultOne&&$insertResultTwo){
			Db::commit();
			$output->writeln("CountDayChannel: 今日统计成功");
		}else{
			Db::rollback();
			$output->writeln("CountDayChannel: 今日统计失败");
		}
	}
}