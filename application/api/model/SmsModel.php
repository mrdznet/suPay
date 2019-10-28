<?php

namespace app\api\model;

use think\Db;
use think\Model;
use app\admin\model\SystemConfigModel;
use app\admin\model\Merchant;
use app\api\model\DeviceModel;

class  SmsModel extends Model
{
    // 确定链接表名
    protected $name = 'sms';

    /**
     * 插件上传的短信入库
     * @return string
     */
    public function addSms($sms,$phone,$channel,$address)
    {
        $insertSmsData['sms'] = $sms;
        $insertSmsData['add_time'] = time();
        $insertSmsData['phone'] = $phone;
        $insertSmsData['channel'] = $channel;
        $insertSmsData['address'] = $address;
        $insertSmsData['sms_md5'] = md5($sms);
        $res = $this->insert($insertSmsData);
        if($res){
            return "success";
        }else{
            return "error";
        }
    }
    /**
     * 插件上传的短信入库
     * @return string
     */
    public function newaddSms($sms,$phone,$channel,$address,$sign,$version,$use_state,$level)
    {
        $insertSmsData['sms'] = $sms;
        $insertSmsData['add_time'] = time();
        $insertSmsData['phone'] = $phone;
        $insertSmsData['channel'] = $channel;
        $insertSmsData['address'] = $address;
        $insertSmsData['sms_md5'] = md5($sms);
        $insertSmsData['version'] = $version;
        $insertSmsData['use_state'] = $use_state;
        $insertSmsData['level'] = $level;
        $insertSmsData['sign'] = $sign;
        $res = $this->insert($insertSmsData);
        if($res){
            return "success";
        }else{
            return "error";
        }
    }
    /*
     * 插件上传的短信入库
     * @return string
     */
    public function findsms($sms)
    {
        $where['sms_md5'] = md5($sms);
        $smsData = $this->where($where)->find();
        return $smsData;
    }
    /**
     * 读取短信
     * @return string
     */
    public function getSms()
    {
        $selectWhere['use_state'] = 0;//未使用过的短信
        $smsData = $this->where($selectWhere)->find();//每次拿一条
        if($smsData){
            return apiJsonReturn('10000','找到未使用的短信',$smsData);
        }else{
            return apiJsonReturn('10001','当前没有未使用过的短信');

        }
    }
    /**
     * 修改短信状态
     * @return string
     */
    public function updateSms($smsid,$level,$orderno,$msg)
    {
        $smsWhere['id'] = $smsid;
        $smsUpdateData['use_state'] = 1;
        $smsUpdateData['level'] = $level;
        $smsUpdateData['order_no'] = $orderno;
        $smsUpdateData['return_msg'] = $msg;
        $smsUpdateData['use_time'] = time();
        $this->where($smsWhere)->update($smsUpdateData);
    }
    /**
     * 根据条件查询短信
     * @return string
     */

    public function getSmsListByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    public function getSmsListCount($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 读取短信多条
     * @return string
     */
    public function getSmsSelect()
    {
        $selectWhere['use_state'] = 0;//未使用过的短信
        $smsData = $this->where($selectWhere)->select();//拿全部的未使用短信
        if($smsData){
            return apiJsonReturn('10000','找到未使用的短信',$smsData);
        }else{
            return apiJsonReturn('10001','当前没有未使用过的短信');

        }
    }

}
