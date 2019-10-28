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
namespace app\admin\model;

use think\Model;

class OrderModel extends Model
{
    // 确定链接表名
     protected $name = 'order';
    // public static function device_off($client_id){
    //     self::get(1)
    // }

    /**
     * 生成唯一id
     * @param bool $fix
     * @return string
     */
    static public function GUID($fix = false)
    {
        if (function_exists('com_create_guid')) {
            $uuid = com_create_guid();
            if ($fix)
                $uuid = trim($uuid, '{}');
            //return com_create_guid();
            return strtolower($uuid);
        } else {
            mt_srand((double)microtime() * 10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = '';
            if ($fix)
                $uuid .= chr(123);// "{"
            $uuid .= substr($charid, 0, 8)
                . substr($charid, 8, 4)
                . substr($charid, 12, 4)
                . substr($charid, 16, 4)
                . substr($charid, 20, 12);
            //删除uuid-符号
            /*$uuid .= substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);*/
            if ($fix)
                $uuid .= chr(125);// "}"
            return strtolower($uuid);
        }
    }

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
    public function getOrderListByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据条件查询订单 数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getOrderListCount($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 获取全部下单成功的数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getOrderCount($time)
    {
        $end_time = $time+86400;
        return $this
            ->where('add_time','>=',$time)
            ->where('add_time','<=',$end_time)
            ->where('order_status','<>',2)
            ->count();
    }
    /**
 * 获取全部下单成功的数量 并且用户去了第二页面的数量
 * @param $where
 * @return int|string
 * @throws \think\Exception
 */
    public function getOrdersecoundCount($time)
    {
        $end_time = $time+86400;
        return $this
            ->where('add_time','>=',$time)
            ->where('add_time','<=',$end_time)
            ->where('is_come','=','1')
            ->count();
    }
    /**
     * 获取成功回调的数量
     * @param $where
     * @return int|string
     * @throws \think\Exception
     */
    public function getOrdersuccessCount($time)
    {
        $end_time = $time+86400;
        return $this
            ->where('add_time','>=',$time)
            ->where('add_time','<=',$end_time)
            ->where('order_status = 1 or order_status = 5')
            ->count();
    }

    /**
     * 根据订单号获取查询记录
     * @param $orderNo
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOneOrder($orderNo)
    {
        return $this->where('order_no', $orderNo)->find();
    }

    /**
     * 根据条件查询订单数量
     */
    public function getCOUNT($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 根据条件查询收款总额
     */
    public function getSum($where)
    {
        return $this->where($where)
            ->sum('actual_amount');
    }

    /**
     * 根据条件查询订单数量
     */
    public function GettingStudioRevenue($where,$wheretime)
    {
        return $this
            ->where($where)
            ->where($wheretime)
            ->where("(order_status = 1 or order_status = 5 or order_status = 3) and merchant_id !='test01'")
            ->Field('sum(actual_amount) as Total,channel')
            ->group('channel')
            ->select();
    }
}
