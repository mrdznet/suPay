<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use think\Db;
use think\Exception;


class HeartBeatline extends Command
{
    protected function configure()
    {
        $this->setName('HeartBeatline')->setDescription('定时下线');
    }


    /**
     * 定时下线 心跳
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        try{
            $limitTime = 30;
            $now = time();
            $lockLimit = $now-$limitTime;
            $updateStepOneData['is_online'] = '2';//is_prohibit
//        $updateStepOneData['is_prohibit'] = '2';//is_prohibit
            $totalNum = Db::table('s_device')->where('update_time','<',$lockLimit)->where('is_online','=',1)->where('device_type','=',2)->count();
            $successNum = Db::table('s_device')->where('update_time','<',$lockLimit)->where('device_type','=',2)->update($updateStepOneData);
//            logs(json_encode(['sql'=>Db::getLastSql()]),'HeartBeatline_exception');
            $errorNum = 0;
            if(!$successNum){
                $errorNum = $totalNum;
            }
            $output->writeln("HeartBeatline:应下线数量：".$totalNum.",成功下线数量".$successNum."失败下线数量".$errorNum);
        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'HeartBeatline_exception');
            $output->writeln("HeartBeatline:应下线数量：".$totalNum.",成功下线数量".$successNum."失败下线数量".$errorNum);
        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'HeartBeatline_error');
            $output->writeln("HeartBeatline:应下线数量：".$totalNum.",成功下线数量".$successNum."失败下线数量".$errorNum);
        }

    }
}