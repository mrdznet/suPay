<?php

namespace app\SBbuyaodongwohoutai\model;

use think\Db;
use think\Model;

class LoseNotifyModel extends Model
{
    // 确定链接表名
    protected $name = 'loseorder_notify_callback_log';

    /**
     * 根据条件查询订单
     * @param $where
     * @param $offset
     * @param $limit
     * @return bool|false|\PDOStatement|string|\think\Collection
     */
    public function getLoseNotifyListByWhere($where, $offset, $limit)
    {
        try{
            return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'get_notify_list_exception');
            return apiJsonReturn('18888','get_bank_device_exception');
        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'get_notify_list_error');
            return apiJsonReturn('19999','get_bank_device_error');
        }
    }
    /**
     * 根据条件查询订单 数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getONotifyListCount($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 根据条件查询订单 数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function deletelog($id)
    {
        $where['id'] = $id;
        return $this->where($where)->delete();
    }
}
