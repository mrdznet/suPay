<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2019/1/10
 * Time: 15:23
 */
namespace app\SBbuyaodongwohoutai\controller;


use think\Db;
use app\SBbuyaodongwohoutai\model\Ali;
use app\SBbuyaodongwohoutai\model\SystemConfigModel;

class Ratemanagement extends Base
{
	//系统配置文件 @todo
	public function accountFloatingEdit()
	{
		if(request()->isPost()){

			try{
				$param = input('param.');
				//验证格式
				if(!is_numeric($param['amountFloatingStart'])){
					return json(msg(-1, '', '范围起始值格式错误'));
				}
				if(!is_numeric($param['amountFloatingEnd'])){
					return json(msg(-1, '', '范围最大值格式错误'));
				}
				if($param['config_status']>2||$param['config_status']<0){
					return json(msg(-1, '', '参数错误修改失败'));
				}
				if($param['amountFloatingStart']>$param['amountFloatingEnd']){
					return json(msg(-1, '', '范围起始值应小于范围最大值'));
				}

				//修改费率参数
				$updateDateOne['config_status'] = $param['config_status'];
				$updateDateOne['config_data'] = $param['config_data'];
				$updateDateOneWhere['config_name'] = 'account_amount_floating';

				//修改使用费率起始值参数
				$updateDateTwo['config_data'] = $param['amountFloatingStart'];
				$updateDateTwoWhere['config_name'] = 'account_amount_floating_start';

				//修改使用费率结束值参数
				$updateDateThree['config_data'] = $param['amountFloatingEnd'];
				$updateDateThreeWhere['config_name'] = 'account_amount_floating_end';
				Db::startTrans();
				$Db = new Db();
				//修改费率//存在就return true;
				$updateOne = $Db::table('s_system_config')
					->where($updateDateOneWhere)
					->where($updateDateOne)
					->find();
				if(empty($updateOne)){
					$updateOne = $Db::table('s_system_config')->where($updateDateOneWhere)->update($updateDateOne);
				}

				//修改使用费率起始值 //存在就return true;
				$updateTwo = $Db::table('s_system_config')
					->where($updateDateTwoWhere)
					->where($updateDateTwo)
					->find();
				if(empty($updateTwo)){
					$updateTwo = $Db::table('s_system_config')->where($updateDateTwoWhere)->update($updateDateTwo);
				}

				//修改使用费率结束值 //存在就return true;
				$updateThree = $Db::table('s_system_config')
					->where($updateDateThreeWhere)
					->where($updateDateThree)
					->find();
				if(empty($updateThree)){
					$updateThree = $Db::table('s_system_config')->where($updateDateThreeWhere)->update($updateDateThree);
				}
				if($updateOne&&$updateTwo&&$updateThree){
					db::commit();
					return json(msg(1, url('ratemanagement/accountfloatingedit'), '修改成功'));
				}else{
					db::rollback();
					return json(msg(-1, url('ratemanagement/accountfloatingedit'), '修改失败!'));
				}

			}catch(\Exception $e){
				json(msg(-1, url('ratemanagement/accountfloatingedit'), $e->getMessage()));
			}

		}

		$System = new SystemConfigModel();
		$accountAmountFloatingData = $System::getAccountAmountFloating();
//		var_dump($accountAmountFloatingData);exit;
		$this->assign([
			'config_data' => $accountAmountFloatingData['config_data'],
			'config_status' => $accountAmountFloatingData['config_status'],
			'amountFloatingStart' => $accountAmountFloatingData['amountFloatingStart'],
			'amountFloatingEnd' => $accountAmountFloatingData['amountFloatingEnd'],
		]);
		return $this->fetch();
	}


	/**
	 * 修改支付宝限额
	 * @return mixed|\think\response\Json
	 */
	public function accountEdit()
	{
		$Ali = new Ali();
		if(request()->isPost()){

			$param = input('post.');
			$flag = $Ali->editAccount($param);

			return json(msg($flag['code'], $flag['data'], $flag['msg']));
		}

		$id = input('param.id');
		$this->assign([
			'account' => $Ali->getOneAccount($id)
		]);
		return $this->fetch();
	}

	/**
	 * 删除记录
	 * @return \think\response\Json
	 */
	public function accountDel()
	{
		$id = input('param.id');

		$ali = new Ali();
		$flag = $ali->delAli($id);
		return json(msg($flag['code'], $flag['data'], $flag['msg']));
	}

	/**
	 * 拼装操作按钮
	 * @param $id
	 * @return array
	 */
	private function makeButton($id)
	{
		return [
			'编辑' => [
				'auth' => 'account/accountedit',
				'href' => url('account/accountedit', ['id' => $id]),
				'btnStyle' => 'primary',
				'icon' => 'fa fa-paste'
			],
			'删除' => [
				'auth' => 'account/accountdel',
				'href' => "javascript:accountDel(" . $id . ")",
				'btnStyle' => 'danger',
				'icon' => 'fa fa-trash-o'
			]
		];
	}
}
