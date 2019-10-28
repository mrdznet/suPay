<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);
use \GatewayWorker\Lib\Gateway;
use \think\Db;
use app\admin\model\Device;
use app\admin\model\ChannelAccountModel;
use think\log;
use think\Request;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, json_encode(['action' => 'ping', 'client_id' => $client_id])
        );
    }

    /**
     * 当客户端发来消息时触发
     * @param int $clientId 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($clientId, $message)
    {
        $messageData = json_decode($message, true);

        if (!$messageData) {
            return;
        }
        if (!isset($messageData['cardno']) || !is_numeric($messageData['cardno'])) {
            return;
        }
//        if (!isset($messageData['phone']) || !is_numeric($messageData['phone'])) {
//            $messageData['phone'] = '';
//        }
        try {
            switch ($messageData['action']) {
                case "ping":   //心跳包
                    //判断当前设备是否存在
                    $where['card'] = $messageData['cardno'];
                    $res = Db::table('s_device')->field('id')->where($where)->find();
                    $updateData['client_id'] = $clientId;
                    $updateData['is_online'] = 1;
                    $updateData['channel'] = $messageData['channel'];
                    $updateData['update_time'] = time();
                    //版本号
                    if (isset($messageData['version'])) {
                        $updateData['version'] = $messageData['version'];
                    }
                    //设备标识
                    if (isset($messageData['device_id'])) {
                        $updateData['device_id'] = $messageData['device_id'];
                    }
                    if ($res) {
                        //如果存在改变状态
                        Db::table('s_device')->where($where)->update($updateData);
                    } else {
                        //如果不存在,新增一条记录
                        $updateData['card'] = $messageData['cardno'];
                        $updateData['bank_name'] = $messageData['bank_name'];
                        $updateData['name'] = $messageData['card_name'];
                        $updateData['bank_mark'] = $messageData['bank_mark'];
                        $updateData['create_time'] = time();
                        $res1 = Db::table('s_device')->insert($updateData);
                        $lastSql = Db::table('s_device')->getLastSql();
                        if (!$res1) {
                            logs(json_encode(['message' => $message, 'client_id' => $clientId, 'last_sql' => $lastSql], 512), 'ping_two');
                        }
                    }
                    //心跳返回
                    $ping = array("action" => 'ping', "client_id" => $clientId);
                    Gateway::sendToClient($clientId, json_encode($ping));
                    break;
            }
        } catch (\Exception $exception){
            logs(json_encode(['message'=>$messageData,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'events_exception');
        }catch (\Error $error){
            logs(json_encode(['message'=>$messageData,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'events_error');
        }
    }

    /**
     * 当用户断开连接时触发
     * @param $client_id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function onClose($client_id)
    {
        $db = new Db();
        $db::table('s_device')->where('client_id', '=', $client_id)->update(['is_online' => 2]);
    }
}