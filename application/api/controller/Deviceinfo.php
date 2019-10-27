<?php
/**
 * Created by PhpStorm.
 * User: dd
 * Date: 2019/3/19
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use app\api\model\DeviceModel;

class Deviceinfo extends Controller
{

    /**
     * 猫池设备信息上传
     * @return bool
     */
    public function putDevice()
    {
//        {
//            "BankCardNo": "6217001250017642327",
//            "AccountName": "王洪梅",
//            "BankShortName": "CCB",
//            "OpenAccountBranch": "建设银行",
//            "PhoneNum": "13225269825",
//            "Statu": "0",
//            "Uuid": "01.05.14"
//        }
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
            if (!isset($message['Uuid']) || empty($message['Uuid'])) {
                return apiJsonReturn('10001', "missing_parameters_Uuid");
            }
            if (!isset($message['channel']) || empty($message['channel'])) {
                return apiJsonReturn('10001', "missing_parameters_channel");
            }
            if (!isset($message['BankCardNo']) || empty($message['BankCardNo'])) {
                return apiJsonReturn('10001', "missing_BankCardNo");
            }
            if (!isset($message['AccountName']) || empty($message['AccountName'])) {
                return apiJsonReturn('10001', "missing_AccountName");
            }
            if (!isset($message['BankShortName']) || empty($message['BankShortName'])) {
                return apiJsonReturn('10001', "missing_BankShortName");
            }
            if (!isset($message['OpenAccountBranch']) || empty($message['OpenAccountBranch'])) {
                return apiJsonReturn('10001', "missing_OpenAccountBranch");
            }
            if (!isset($message['PhoneNum']) || empty($message['PhoneNum'])) {
                return apiJsonReturn('10001', "missing_PhoneNum");
            }

            $where['card'] = $message['BankCardNo'];
            $deviceModel = new DeviceModel();
            $insertData['device_id'] = $message['Uuid'];
            $insertData['name'] = $message['AccountName'];     //张三
            $insertData['bank_mark'] = $message['BankShortName'];    //CCB
            $insertData['bank_name'] = $message['OpenAccountBranch'];  //中国建设银行
            $insertData['phone'] = $message['PhoneNum'];  //电话号码
            $insertData['channel'] = $message['channel'];

//            $insertData['status'] = $message['Statu'];  //0空闲 1工作中
            $insertData['update_time'] = time();  //更新时间
            $res = $deviceModel::field('id')->where($where)->find();
            if($res){
                $deviceModel::where($where)->update($insertData);
            }else{
                //不存在 插入
                $insertData['card'] = $message['BankCardNo'];
                $insertData['create_time'] = time();//device_type
                $insertData['device_type'] = 1;//device_type
                $deviceModel::insert($insertData);
            }
            return apiJsonReturn('10000', "接收成功");

        } catch ( \Exception $exception) {
            logs(json_encode(['message'=>$message,'file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'create_order_exception');
            return apiJsonReturn('20009', "上传服务器异常".$exception->getMessage());
        }catch ( \Error $error) {
            logs(json_encode(['message'=>$message,'file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'create_order_exception');
            return apiJsonReturn('20099', "上传服务器错误".$error->getMessage());
        }
    }
    //猫池server 通知此接口银行卡下线
    public function bankCardOffline(){
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        if (!isset($message['card']) || empty($message['card'])) {
            return apiJsonReturn('10001', "missing_card");
        }
        $offlineWhere['card'] = $message['card'];
        $offineUpdate['is_online'] = 2;
        $deviceModel = new DeviceModel();
        $is_online = $deviceModel::where($offlineWhere)->field('is_online')->find()['is_online'];
        if($is_online == 2){
            return "this card Already offine";
        }else{
            $updateRes = $deviceModel::where($offlineWhere)->update($offineUpdate);
            if($updateRes == 1){
                return "success";
            }else{
                return "error";
            }

        }

    }
    //猫池server与此服务器之间心跳
    public function ping(){
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        if (!isset($message['action']) || empty($message['action'])) {
            return apiJsonReturn('10001', "missing_action");
        }
        if (!isset($message['ip']) || empty($message['ip'])) {
            return apiJsonReturn('10001', "missing_ip");
        }
        if($message['action'] == "ping"){
            $ip = $message['ip'];
            $db = new Db;
            $card = $db::table('s_serverlist')->field('ip')->where('ip', '=',$ip)->find();
            if(!$card){
                return "false";
            }else{
                $updatetime['update_time'] = time();
                $updateRes = $db::table('s_serverlist')->where('ip', '=',$ip)->update($updatetime);
                if($updateRes == 1){
                    sleep(2);
                    return "ping";
                }

            }

        }


    }

    /**
     * 获取在线数量   is_oneline  =1  |is_prohibit  =1
     * @return bool
     * @throws \think\Exception
     */
    public function getIsOnlineDeviceCount(){
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        if(!isset($message['merchant_id'])||empty($message['merchant_id'])){
            return apiJsonReturn('1001','缺少参数_merchant_id');
        }
        $deviceModel = new DeviceModel();
        return $deviceModel->getIsOnlineDeviceCount($message['merchant_id']);

    }

    public function echotimedemo(){
        echo "当前服务器时间：".time();
    }

    /**
     * @return false|string
     */
    public function bankOnlineForphone()//银行app上线接口
    {
        // echo __DIR__ . '/../../application/admin/model/Device.php';

        $post = @file_get_contents('php://input');
        $post = json_decode($post,true);
        if(!isset($post['phone'])||empty($post['phone'])){//收款银行简称
            return apiJsonReturn('10001','missing_7');
        }
        if(!isset($post['version'])||empty($post['version'])){//收款银行简称
            return apiJsonReturn('10001','missing_6');
        }
        if(!isset($post['sign'])||empty($post['sign'])){//收款银行简称
            return apiJsonReturn('10001','missing_5');
        }
        if(!isset($post['channel'])||empty($post['channel'])){//收款银行简称
            return apiJsonReturn('10001','missing_4');
        }
        if(isset($post['phone'])){
            $db = new Db();
            $token = $db::table('s_device_token')->where('channel','=',$post['channel'])->find()['token'];
            $sign = $this->sing($post,$token);
            if($post['sign']!=$sign){
                return "error";
            }
            //判断当前是否存在此银行卡 如果存在 修改client_id;
            $count = $db::table('s_device')->where('phone', $post['phone'])->count();
            if($count == 0){
                return "no phone";
            }else{
                $where = [
                    'phone'=>$post['phone'],

                ];
                $res = $db::table('s_device')->where($where)
                    ->update(['is_online'=>1,'update_time'=>time(),'version'=>$post['version']]);
                if($res == 1){
                    return "success";
                }
            }
        }
    }

    public function sing($message,$token){
        // sign  = md5(phone+channel+address+time+token+body
        $sign = md5($message['phone'].$message['channel'].$token.$message['version']);
        return $sign;

    }



}
