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

class FaceOrderModel extends Model
{
    // 确定链接表名
     protected $name = 'f_order';
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
    protected $update = ['time_update'];
    // public static function device_off($client_id){
    //     self::get(1)
    // }
    public function setTimeUpdateAttr(){
        return time();
    }

    /**
     * 根据条件查询订单
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getFaceOrderListByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据条件查询订单 数量
     * @param $where
     * @return int|string
     */
    public function getFaceOrderListCount($where)
    {
        return $this->where($where)->count();
    }
}
