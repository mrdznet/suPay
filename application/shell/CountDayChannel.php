<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;

/**
 * 定时统计各个渠道收益
 * Class ProcessingOrderOne
 * @package app\shell
 */
class CountDayChannel extends Command
{
    protected function configure()
    {
        $this->setName('CountDayChannel')->setDescription('统计渠道收款');
    }

    /**
     * 定时统计各个渠道收益
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $db = new Db();
        $db::startTrans();
        try{
            $startTime = strtotime(date("Y-m-d",strtotime("-1 day")));
            $endTime = $startTime+24 * 60 * 60-1;
            $channelData = $db::table('s_order')
                ->field("SUM(actual_amount) AS yesterdayTotalMoney,channel")
                ->where('order_status','=',1)
                ->where('add_time','>',$startTime)
                ->where('add_time','<',$endTime)
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
            if(!empty($insertData)){
                $insertResultOne = $db::table('s_channel_calculation')
                    ->insertAll($insertData);
            }else{
                $insertResultOne = true;
            }
            //清除merchant表得日收益
            $updateMerchantData['today_sum'] = 0;
//            $clearMerchantTodaySum = $db::table('s_merchant')->update($updateMerchantData);
//            var_dump($clearMerchantTodaySum);exit;
            //清除s_device表得日收益
            $updateAccountData['today_money'] = 0;
            $clearAccountTodaySum = $db::table('s_device')->where('lock_time','>=',0)->update($updateAccountData);
            if($insertResultOne&&$clearAccountTodaySum){
                $db::commit();
                $output->writeln("CountDayChannel: 今日统计成功");
            }else{
                $db::rollback();
                $output->writeln("CountDayChannel: 今日统计失败");
            }
        } catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'CountDayChannel_exception');
            $output->writeln("CountDayChannel: 今日统计失败");
        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'CountDayChannel_error');
            $output->writeln("CountDayChannel: 今日统计失败");
        }
	}
}