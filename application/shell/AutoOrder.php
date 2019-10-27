<?php
namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Db;
use app\api\model\OrderModel;
use app\api\model\DeviceModel;
use app\admin\model\SystemConfigModel;
use think\Db;

class AutoOrder extends Command
{
    protected function configure()
    {
        $this->setName('AutoOrder')->setDescription('定时处理超时设备/订单');
    }

    /**
     * 定时处理超时订单 修改订单状态
     * @param Input  $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        try{
            //时间差
            $limitTime = SystemConfigModel::getPayLimitTime();
            $now = time();
            $lockLimit = $now-$limitTime;
            $updateStepOneData['order_status'] = '4';
            $unlockdevice['lock_time'] = 0;
            $updateDatawhere['order_status'] = 0;

            $totalNum = OrderModel::where('add_time','<',$lockLimit)->where($updateDatawhere)->count();
            $updateData = OrderModel::where('add_time','<',$lockLimit)->where($updateDatawhere)->select();
            $db = new Db();
            if($totalNum>0){
                foreach ($updateData as $key =>$val){
                    $prohibitedUserData = $db::table('s_prohibited_user')->where('user_id', '=', $val['payersessionid'])->find();
                    if($prohibitedUserData){
                        $db::table('s_prohibited_user')
                            ->where('user_id','=',$val['payersessionid'])
                            ->update([
                                'no_pay_times' =>  Db::raw("no_pay_times+1"),
                                'add_time' =>  time(),
                            ]);
                    }else{
                        $createData['user_id'] = $val['payersessionid'];
                        $createData['add_time'] = time();
                        $db::table('s_prohibited_user')->insert($createData);
                    }
                    //循环处理超时订单以及解锁相应得设备
                    $res = OrderModel::where('order_no','=',$val['order_no'])->update($updateStepOneData);
//                    if($res == 1){
//                        DeviceModel::where('card','=',$val['card'])->update($unlockdevice);
//                    }



                }
            }
//            //解锁脏数据导致的锁定设备  start
            DeviceModel::where('lock_time','<',$lockLimit)->update($unlockdevice);
//            //解锁脏数据导致的锁定设备   end

            $errorNum = 0;
            if(!$successNum){
                $errorNum = $totalNum;
            }
            $output->writeln("AutoOrder:总应强制超时订单数".$totalNum."成功超时订单数".$successNum."成功超时设备数".$errorNum);
        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'AutoOrder_exception');
            $output->writeln("AutoOrder:总应强制超时订单数".$totalNum."成功超时订单数".$successNum."成功超时设备数".$errorNum);
        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'AutoOrder_error');
            $output->writeln("AutoOrder:总应强制超时订单数".$totalNum."成功超时订单数".$successNum."成功超时设备数".$errorNum);
        }

    }
}