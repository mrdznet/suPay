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

class BankDeviceModel extends Model
{
	// 确定链接表名
	protected $name = 'bank_device';

	/**
	 * 添加银行卡号
	 * @param $param
	 * @return array
	 */
	public function addBankDevice($param)
	{
		try{
			$result = $this->validate('BankDeviceValidate')->save($param);
			if(false === $result){
				// 验证失败 输出错误信息
				return msg(-1, '', $this->getError());
			}else{
				return msg(1, url('bankdevice/index'), '添加银行卡成功');
			}
		}catch (\Exception $e){
			return msg(-2, '', $e->getMessage());
		}
	}


	/**
	 * 编辑银行卡号
	 * @param $param
	 * @return array
	 */
	public function editBankDevice($param)
	{
		try{

			$result = $this->validate('BankDeviceValidate')->save($param, ['id' => $param['id']]);

			if(false === $result){
				// 验证失败 输出错误信息
				return msg(-1, '', $this->getError());
			}else{

				return msg(1, url('bankdevice/index'), '编辑银行卡信息成功');
			}
		}catch(\Exception $e){
			return msg(-2, '', $e->getMessage());
		}
	}

	/**
	 * 根据id 获取银行信息
	 * @param $id
	 * @return array|false|\PDOStatement|string|Model
	 */
	public function getOneBankDevice($id)
	{
		return $this
			->table('s_bank_device')
			->where('id', $id)
			->find();
	}

	/**
	 * 删除银行卡
	 * @param $id
	 * @return array
	 */
	public function delBankDevice($id)
	{
		try{
		    $update['is_delete'] = 1;
			$this->where('id', $id)->update($update);
			return msg(1, '', '删除银行卡号成功');

		}catch(\Exception $e){
			return msg(-1, '', $e->getMessage());
		}
	}

    /**
     * 测试
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