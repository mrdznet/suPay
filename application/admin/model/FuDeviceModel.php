<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2019/1/18
 * Time: 17:55
 */
namespace app\admin\model;

use think\Model;
use think\db;

class FuDeviceModel extends Model
{
	// 确定链接表名
	protected $name = 'ju_device';

    /**
     * 根据手机号 获取付呗设备信息
     * @param $phone
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
	public function getOneFuDevice($phone)
	{
		return $this->where('phone', $phone)->find();
	}

    /**
     * 根据手机号 获取在线设备
     * @param $phone
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function getOneOnlineFuDevice($phone)
    {
        return $this->where('phone', $phone)->where('is_online','=',1)->find();
    }

    /**
     * 根据id 获取银行信息
     * @return array
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     */
    public function getBankDevice()
    {
        return collection($this
            ->field('phone')
            ->table('s_bank_device')
            ->select()
		)->toArray();
    }

}