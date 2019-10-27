<?php

namespace app\api\model;

use think\Db;
use think\Model;
use app\api\model\OrderModel;

class AccountBindingModel extends Model
{
    // 确定链接表名
    protected $name = 'account_binding';

    /**
     * 更新绑定关系
     * @param $updateWhere
     * @param $data
     * @return array
     */
    public static function updateBindingStatus($updateWhere, $updateData)
    {
        try {
            $AccountBindingModel = new self();
            $changOrderQrUrl = $AccountBindingModel::where($updateWhere)->update($updateData);
            if ($changOrderQrUrl) {
                return arrayReturn(1,'','添加成功');
            }else{
                return arrayReturn(2,'',$changOrderQrUrl->getError());
            }
        } catch (\Exception $e) {
            return arrayReturn(-1,'',$e->getMessage());
        }
    }

    /**
     * 查询好友绑定状态
     * @param $account
     * @param $friendAccount
     * @param string $both
     * @return array|bool
     */
    public static function getFriendStatus($account, $friendAccount, $both = '2')
    {
        try{
            $AccountBindingModel = new self();
            $where['account'] = $account;
            $where['friend_account'] = $friendAccount;
            $friendBindingStatus = $AccountBindingModel::field('status')->where($where)->find();
            if($friendBindingStatus=='1'||$friendBindingStatus=='2'){
                if($both==1){
                    if($friendBindingStatus==1){
                        return true;
                    }else{
                        return false;
                    }
                }
                return true;
            }else{
                return false;
            }
        }catch(\Exception $e){
            return arrayReturn(-1,'',$e->getMessage());
        }

    }
}
