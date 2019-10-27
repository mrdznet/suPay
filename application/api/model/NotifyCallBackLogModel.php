<?php

namespace app\api\model;
use think\Model;

class NotifyCallBackLogModel extends Model
{
    // 确定链接表名
    protected $name = 'order_notify_callback_log';

    /**
     * 已存在的回调记录 回调请求次数加1
     * @param $PayHash
     * @param $times
     * @return NotifyCallBackLogModel|bool
     */
    public static function theSameNotifyLog($PayHash, $times)
    {
        try {
            $updateData['times'] = $times + 1;
            $notifyCallBackLogModel = new self();
            return $notifyCallBackLogModel::where('PayHash', '=', $PayHash)->update($updateData);

        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'theSameNotifyLog_exception');
            return true;

        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'ntheSameNotifyLog_error');
            return true;
        }
    }

    /**
     * 回调成功
     * @param $PayHash
     * @param $updateCallbackData
     * @return NotifyCallBackLogModel|bool
     */
    public static function updateNotifyLog($PayHash, $updateCallbackData)
    {
        try {
            $notifyCallBackLogModel = new self();
            return $notifyCallBackLogModel::where('PayHash', '=', $PayHash)->update($updateCallbackData);

        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'update_notify_log_exception');
            return true;

        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'update_notify_status_error');
            return true;
        }
    }

    /**
     * 添加记录
     * @param $message
     * @return NotifyCallBackLogModel|bool
     */
    public static function doNoMatchNotifyLog($message)
    {
        try {
            $notifyCallBackLogModel = new self();
            return $notifyCallBackLogModel::create($message);

        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'do_no_match_notify_log_exception');
            return false;

        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'do_no_match_notify_log_error');
            return false;
        }
    }

    public function getLogCount($PayHash){
        $notifyCallBackLogModel = new self();
        return $notifyCallBackLogModel::where('PayHash', '=', $PayHash)->count();
    }

}
