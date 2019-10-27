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

class AliModel extends Model
{
    // 确定链接表名
     protected $name = 'ali';

    /**
     * 查询 支付宝列表（收款）
     * @param $where
     * @param $offset
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAliListByWhere($where, $offset, $limit)
    {
        $Db = new Db();
        return $Db::table('s_ali')
            ->field('a.id as id,a.alinumber,a.quota,a.day_quota,a.total_sum,d.create_time,d.binding_state')
            ->alias('a')
            ->join('s_device d','a.alinumber = d.account','LEFT')
            ->where($where)
            ->order('d.id desc')
            ->select();
    }

    /**
     * 查询 渠道用户在线账户 搜索条件的文章数量
     * @param $where
     * @return int|string
     */
    public function getAliListCount($where)
    {
        return $this->table('s_ali')->where($where)->count();
    }

    /**
     * 删除支付宝记录
     * @param $id
     * @return array
     */
    public function delAli($id)
    {
        try{

            $this->where('id', $id)->delete();
            return msg(1, '', '删除支付宝成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }

    /**
     * 编辑支付宝信息
     * @param $param
     * @return array
     */
    public function editAccount($param)
    {
        try{
            $result = $this->validate('AccountValidate');
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }
            $result = $this->table('s_ali')->update($param, ['id' => $param['id']]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{

                return msg(1, url('account/index'), '修改成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }


	/**
	 * 根据id 获取单条信息
	 * @param $id
	 * @return array|false|\PDOStatement|string|Model
	 */
    public function getOneAccount($id)
    {
        return $this->table('s_ali')->where('id', $id)->find();
    }
}
