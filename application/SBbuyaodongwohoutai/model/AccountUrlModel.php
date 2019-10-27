<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/26
 * Time: 22:59
 */
namespace app\SBbuyaodongwohoutai\model;

use think\Model;
use think\db;
use app\SBbuyaodongwohoutai\model\AliModel;
use app\SBbuyaodongwohoutai\model\SystemConfigModel;

class AccountUrlModel extends Model
{
	// 确定链接表名
	protected $name = 'account_url';


	/**
	 * 获取可付款支付宝账户 url
	 * 不能被锁定、无超过限额
	 * @param string $money
	 * @return mixed
	 */
	public static function getUseUrl($money = '')
	{
		try{
			//查询在线使用url
			$onlineUrlData1 = self::field('account,account_url')
				->where('status','=','1')
				->where('lock_time','=','0')
				->where('money','=',$money)
				->orderRaw('rand()')
				->find();
			//存在就返回
			if(!empty($onlineUrlData1)){
				$returnData['account'] = $onlineUrlData1['account'];
				$returnData['ali_qr'] = $onlineUrlData1['account_url'];
				return $returnData;
			}else{
				//1、查找在线设备 如果有可用支付连接 但是在锁定中 返回无可使用收款账户
				$onlineUrlData2 = self::field('account,account_url')->where('status','=','1')->where('lock_time','>','0')->where('money','=',$money)->find();
				if(!empty($onlineUrlData2)){
					$returnData['account'] = '';
					$returnData['ali_qr'] = '';
					return $returnData;
				}else{
				//2、查询在线设备 A->有的话选择支付宝号插入请求url表  B->没有的话就返回无可用设备

					$create_times_limit = SystemConfigModel::accountCreateLimit();
					$Db = new db();
					$onlineAliData3 = $Db::table('s_ali')->field('alinumber')->where('create_times','<',$create_times_limit)->order('create_times asc')->find();
					//B->没有的话就返回无可用设备
					if(empty($onlineAliData3)){
						$returnData['account'] = '';
						$returnData['ali_qr'] = '';
						return $returnData;
					}else{
						//A->有的话选择支付宝号插入请求url表
						$createPreData['alinumber'] = $onlineAliData3['alinumber'];
						$createPreData['money'] = $money;
						//判断请求是否存在
						$isHadPre = $Db::table('s_pre')->where('alinumber','=',$createPreData['alinumber'])->where('money','=',$createPreData['money'])->find();
						if(empty($isHadPre)){
							$Db::table('s_pre')->insert($createPreData);
							sleep(5);
							$useData = self::getOneUseUrl($createPreData['money']);
							if($useData){
								$returnData['account'] = $useData['account'];
								$returnData['ali_qr'] = $useData['account_url'];
								return $returnData;
							}else{
								$returnData['account'] = '';
								$returnData['ali_qr'] = '';
								return $returnData;
							}
						}else{
							sleep(5);
							$useData = self::getOneUseUrl($createPreData['money']);
							if($useData){
								$returnData['account'] = $useData['account'];
								$returnData['ali_qr'] = $useData['account_url'];
								return $returnData;
							}else{
								$returnData['account'] = '';
								$returnData['ali_qr'] = '';
								return $returnData;
							}
						}

					}

				}

			}


		}catch(\Exception $e){
			$returnData['account'] = '';
			$returnData['ali_qr'] = '';
			$returnData['msg'] = '平台异常';
			return $returnData;
		}
	}


	/**
	 * 获取指定金额url
	 * @param $money
	 * @return array|false|\PDOStatement|string|Model
	 */
	static function getOneUseUrl($money)
	{
		$useData = self::field('account,account_url')
			->where('status','=','1')
			->where('lock_time','=','0')
			->where('money','=',$money)
			->orderRaw('rand()')
			->find();
		return $useData;
	}

}
