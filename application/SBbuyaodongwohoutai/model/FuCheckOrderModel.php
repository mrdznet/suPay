<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\SBbuyaodongwohoutai\model;

use think\Model;
use think\Db;

class FuCheckOrderModel extends Model
{
    // 确定链接表名
    protected $name = 'ju_check_order';

    /**
     * 根据条件查询订单
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCheckOrderListByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据条件查询订单 数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getCheckOrderListCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据订单号获取查询记录
     * @param $orderNo
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneCheckOrder($orderNo)
    {
        return $this->where('order_no', $orderNo)->find();
    }

    /**
     * 查询订单
     * @param $orderNo
     * @param bool $isTrue
     * @return array
     */
    public function checkOrder($orderNo,$isTrue = false)
    {
        try {
            if (empty($orderNo)) {
                return msg(-1, '', "订单号么不能为空！");
            }

            //1、查询订单
            $order = new OrderModel();
            $orderData = $order->getOneOrder($orderNo);
            if (empty($orderData)) {
                return msg(-1, '', "本平台无此订单信息");
            }

            //2、查询订单对应设备 ，获取设备client_id
            $fuDeviceModel = new FuDeviceModel();
            $deviceData = $fuDeviceModel->getOneFuDevice($orderData['account']);
            if (empty($deviceData)) {
                return msg(-1, '', "无订单设备！");
            }
            if ($deviceData['is_online'] != 1) {
                return msg(-2, '', "订单设备离线：" . $orderData['account']);
            }
            //条件
            $where['order_no'] = $orderNo;
            //3-1、不存在已查询 就插入查询记录
            if (!($isTrue)) {
                $data['check_name'] = session('username');
                $data['order_no'] = $orderNo;
                $data['order_momo'] = $orderData['order_momo'];
                $data['add_time'] = time();
                $createCheckOrderResult = $this->insert($data);
                if (!$createCheckOrderResult) {
                    return msg(-3, '', $this->getError());
                }
            } else {
                //3-2、存在更新查询记录
                $data['check_name'] = session('username');
                $addCheckOrderTimes = $this->where($where)
                    ->update([
                        'check_name' => $data['check_name'],
                        'check_times' => $this->raw("check_times + 1"),
                    ]);
                if (!$addCheckOrderTimes) {
                    return msg(-4, '', $this->getError());
                }
            }
            //4、websocket 请求设备
            $webSocketData['action'] = "checkOrder";
            $webSocketData['start_time'] = $orderData['add_time'];
            $webSocketData['order_momo'] = $orderData['order_momo'];
            $checkOrder = serverToClientCheckOrder($deviceData['client_id'], $webSocketData);

            logs(json_encode($webSocketData),"check_order");
            if (!$checkOrder) {
                $this->where($where)
                    ->update([
                        'status' => 2,
                    ]);
                return msg(-5, '', "请求设备查询失败！");
            }
            $this->where($where)
                ->update([
                    'status' => 1,
                ]);
            return msg(1, "", "请求设备查询成功！");
//        return msg(1, $webSocketData, $deviceData['client_id']);
        } catch (\Exception $e) {
            return msg(-2, "", $e->getMessage());
        }

    }
}
