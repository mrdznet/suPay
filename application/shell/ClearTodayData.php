<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;

/**
 * Class ProcessingOrderOne
 * @package app\shell
 */
class ClearTodayData extends Command
{
    protected function configure()
    {
        $this->setName('ClearTodayData')->setDescription('清除今日收款金额');
    }

    /**
     * 清除表中统计每日的数据（）
     * @param Input  $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
		$now = time();
		$startTime = strtotime(date('Y-m-d'));

		$db = new Db();
		//清除1
		$updateOneData['today_money'] = 0;
		$updateOne = $db::table('s_device')->where('1=1')->update($updateOneData);
		$sql = $db::getLastSql();
		echo $sql ;
		if($updateOne){
			$output->writeln("ClearTodayData: 清除每日数据成功");
		}else{
			$output->writeln("ClearTodayData: 清除每日数据失败");
		}
	}
}