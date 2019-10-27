<?php
namespace app\shell;

use app\admin\model\Helper;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;

/**
 * 统计成功率
 * Class ProcessingOrderOne
 * @package app\shell
 */
class StatisticsSuccessRate extends Command
{
    protected function configure()
    {
        $this->setName('StatisticsSuccessRate')->setDescription('统计成功率');

    }

    /**
     * 统计成功率
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $db = new Db();
        $devices  = $db::table('s_device')->Field('card,name')
            ->where('is_online','=',1)
            ->where('is_prohibit','=',1)
            ->select();
        $startTime = time()-1800;//30分钟之前的时间戳
        foreach ($devices as $key=>$value){
            $card = $value['card'];
            //半个小时内的下单数
            $SemihSingular = $db::table('s_order')->where('card','=',$card)
                ->where('add_time','>',$startTime)
                ->count();
//            半个小时内的成功单数
            $SemihSuccessSingular = $db::table('s_order')->where('card','=',$card)
                ->where('order_status','=',1)
                ->where('add_time','>',$startTime)
                ->count();
            //总下单数
            $allSemihSingular = $db::table('s_order')->where('card','=',$card)
                ->count();
//            总的成功单数
            $allSuccessSingular = $db::table('s_order')->where('card','=',$card)
                ->where('order_status','=',1)
                ->count();
            $potatoSTR = "";
            if($SemihSingular != 0 &&$allSemihSingular >0 &&$allSuccessSingular == 0){
                if ($SemihSuccessSingular == 0) {
                    $success_rate = 0;
                    $Semihsuccess_ratestr = "0/" . $SemihSingular . "成功率0.00%";
                } else {
                    $success_rate = round($SemihSuccessSingular * 100/$SemihSingular, 2);
                    $Semihsuccess_ratestr = $SemihSuccessSingular . "/" . $SemihSingular . "成功率" . $success_rate . "%";
                }
                $potatoSTR = "成功率机器人报告：姓名：".$value['name'].",卡号：".$value['card'];
                $potatoSTR.= ",半小时成功率:".$Semihsuccess_ratestr;
            }
            if($allSemihSingular >0 &&$allSuccessSingular == 0){
                $potatoSTR.= ",警告：姓名：".$value['name']."卡号：".$value['card']."总下单数：".$allSemihSingular.",成功量：".$allSuccessSingular."请检查设备";
            }
            if($potatoSTR!=""){

                $sendData = [
                    'chat_type' => 2,
                    'chat_id' => 10890918,
                    'text' => $potatoSTR,
                ];
                $headers = ['Content-Type:application/json'];
                $result = Helper::cUrlGetData('http://18.138.140.45:8000/10101207:CWZIsDhF13uH3CBTusCGpTMd/sendTextMessage', json_encode($sendData), $headers);
                $output->writeln("StatisticsSuccessRate: 成功率：".$result);

            }

            sleep(1);
        }
	}
}