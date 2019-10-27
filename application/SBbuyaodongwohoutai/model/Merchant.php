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

class Merchant extends Model
{
    // 确定链接表名
     protected $name = 'merchant';
    // public static function device_off($client_id){
    //     self::get(1)
    // }

    /**
     * 查询商户
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getMerchantByWhere($where, $offset, $limit)
    {
        return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
    }

    /**
     * 根据搜索条件获取所有的商户数量
     * @param $where
     * @return int|string
     */
    public function getAllMerchant($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据搜索条件获取所有的商户数量
     * @param $where
     * @return int|string
     */
    public function getAllUsers($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据文章的id 获取商户的信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function getOneMerchant($id)
    {
        return $this->where('id', $id)->find();

    }

    /**
     * 编辑商户
     * @param $param
     * @return array|false|\PDOStatement|string|Model
     */
    public function editMerchant($param)
    {
        try{

            $result = $this->validate('MerchantValidate')->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{

                return msg(1, url('merchant/index'), '编辑商户成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }

    }

    /**
     * 根据文章的id 获取商户的信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function delMerchant($id)
    {
        try{

            $this->where('id', $id)->delete();
            return msg(1, '', '删除商户成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }

    }

    /**
     * 添加文章
     * @param $param
     */
    public function insertMerchant($param)
    {
        try{
            $result = $this->validate('MerchantValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{

                return msg(1, url('merchant/index'), '添加商户成功');
            }
        }catch (\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }
}
