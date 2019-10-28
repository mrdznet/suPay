<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/12
 * Time: 14:28
 */

namespace app\SBbuyaodongwohoutai\model;

use think\Model;
use app\SBbuyaodongwohoutai\model\Device;
use think\db;

class ChannelAccountModel extends Model
{
    // 确定链接表名
    protected $name = 'channel_account';

    /**
     * 查询 渠道用户在线账户
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getChannelAccountByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 查询 渠道用户在线账户 搜索条件的文章数量
     * @param $where
     * @return int|string
     */
    public function getChannelAccountCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 渠道用户绑定账户
     * @param $param
     * @return array
     */
    public function addChannelAccount($param)
    {
        try{
            $result = $this->validate('ArticleValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{

                return msg(1, url('alichannel/index'), '绑定账户成功');
            }
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 绑定入库
     * @param $insertData
     * @return array
     */
    public function insertAccountBindData($insertData)
    {
        //查找是否有绑定记录
        $isBinded = collection($this->field('id,binding_times')
            ->where('ali_account','=',$insertData['ali_account'])
            ->where('device_id','=',$insertData['device_id'])
            ->find() )->toArray();

        // 启动事务
        Db::startTrans();
        if($isBinded){
            $insertData['binding_times'] += $isBinded['binding_times'];
            try{
                $result1 = $this->save($insertData, ['id' => $insertData['id']]);
                $updateData['bindling_stats'] = '2';
                $result2 = $this->save($updateData, ['device_id' => $insertData['device_id']]);
                if(false === $result1 || false === $result2){
                    // 验证失败 输出错误信息
                    // 提交事务
                    Db::rollback();
                    return msg(103, '', $this->getError());
                }else{
                    // 提交事务
                    Db::commit();
                    return msg(100, url('alichannel/index'), '支付宝设备绑定成功');
                }
            }catch(\Exception $e){
                Db::rollback();
                return msg(-2, '', $e->getMessage());
            }
        }else{
            $insertData['binding_times'] = 1;
            $result1 = self::create($insertData);
            $updateData['bindling_stats'] = '2';
            $result2 = $this->save($updateData, ['device_id' => $insertData['device_id']]);
            if(false === $result1 || false === $result2){
                // 验证失败 输出错误信息
                // 提交事务
                Db::rollback();
                return msg(103, '', $this->getError());
            }else{
                // 提交事务
                Db::commit();
                return msg(100, url('alichannel/index'), '支付宝设备绑定成功');
            }
        }
    }

    /**
     * 获取合适的设备号与clientId
     * @param $aliAccount
     * @return array
     */
    public function getBindingDeviceId($aliAccount)
    {
        //验证是否绑定
        $isBinding = $this->field('id')
            ->where('ali_account','=',$aliAccount)
            ->where('account_status','=','1')
            ->find();
        //绑定中不予绑定
        if($isBinding){
            return msg('101',url('alichannel/index'),'账号已绑定成功，无需重新绑定');
        }

        //查询设备匹配记录 （channel_account）
        $bindDeviceData =  collection($this->where('ali_account','=',$aliAccount)
                ->where('account_status','>','1')
                ->order('binding_times','desc')
                ->column('device_id') )->toArray();
        $deviceModel = new Device();
        //如果有匹配记录 从匹配记录查询最佳匹配机器

        // 找出在线无绑定设备一个随机匹配
        if(!empty($bindDeviceData)){
            $deviceModel = new Device();
            $okDevice = array();
            foreach($bindDeviceData as $deviceId){
                //查询在线设备client_id & device_id
                $okDevice = collection($deviceModel
                    ->field('device_id,client_id')
                    ->where('device_id','=',$deviceId)
                    ->where('account','=',' ')
                    ->select()
                )->toArray();
                if(!empty($okDevice)){
                    return $okDevice;
                    break;
                }
            } //foreach exit

        }//如果有匹配记录 从匹配记录查询最佳匹配机器  exit
        //如果匹配记录中无最佳匹配机器  找出在线无绑定设备一个随机匹配
        $okDevice = collection($deviceModel
            ->field('device_id,client_id')
            ->where('binding_state','=','1')
            ->orderRaw('rand()')
            ->where('account','=',' ')
            ->limit(1)
            ->select()
        )->toArray();
        if(empty($okDevice)){
            return msg('102',url('alichannel/index'),'当前无可匹配设备，请稍后重试!');
        }
        return msg('100',$okDevice[0],'获取适配设备成功');
    }

}