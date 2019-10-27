<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/26
 * Time: 22:59
 */
namespace app\SBbuyaodongwohoutai\model;

use think\Model;
use think\Db;

class SystemConfigModel extends Model
{
	// 确定链接表名
	protected $name = 'system_config';

	/**
	 * 查询 列表（收款）
	 * @param $where
	 * @param $offset
	 * @param $limit
	 * @return false|\PDOStatement|string|\think\Collection
	 */
	public function getSystemListByWhere($where, $offset, $limit)
	{
		return $this->where($where)->limit($offset, $limit)->order('id desc')->select();
	}

	/**
	 * 查询 数量
	 * @param $where
	 * @return int|string
	 */
	public function getSystemListCount($where)
	{
		return $this->where($where)->count();
	}

	//获取配置信息
	static function getConfigDataByName($name)
	{
		return self::field('config_data')->where('config_name','=',$name)->find();
	}

	/**
	 * 获取限制金额
	 * @return array|false|\PDOStatement|string|Model
	 */
	static function getMoneyFormat()
	{
		$money_format = self::getConfigDataByName('money_format');
		$money_format = explode(',',$money_format);
		return $money_format;
	}

	/**
	 * 检查订单金额格式
	 * @param $money
	 * @return bool
	 */
	static function isMoneyFormat($money)
	{
		$money_format = self::getConfigDataByName('money_format');
		if(!empty($money_format)){
			$money_format = explode(',',$money_format);
			if(in_array($money,$money_format)){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}


	/**
	 * 获取支付宝生成url限制次数
	 * @return array|false|\PDOStatement|string|Model
	 */
	static function accountCreateLimit()
	{
		$account_create_times = self::getConfigDataByName('account_create_times');
		return $account_create_times;

	}


	/**
	 * 获取锁支付宝付款手续费
	 * @return int|mixed
	 */
	static function accountAmountFloating($money)
	{
		$db = new Db();
		$accountAmountFloatingStart = $db::table('s_system_config')->field('config_status,config_data')
			->where('config_name','=','account_amount_floating_start')
			->find()['config_data'];
		$accountAmountFloatingEnd = $db::table('s_system_config')->field('config_status,config_data')
			->where('config_name','=','account_amount_floating_end')
			->find()['config_data'];

		if($money>=$accountAmountFloatingStart&&$money<=$accountAmountFloatingEnd){
			$accountAmountFloating = $db::table('s_system_config')->field('config_status,config_data')
				->where('config_name','=','account_amount_floating')
				->find();
			if(empty($accountAmountFloating)){
				return 0;
			}
			if(isset($accountAmountFloating['config_data'])&&$accountAmountFloating['config_status'] == '1'){
				return $accountAmountFloating['config_data'];
			}else{
				return 0;
			}
		}else{
			return 0;
		}

	}


	/**
	 * 获取锁支付宝手续费 配置
	 * @return mixed
	 */
	static function getAccountAmountFloating()
	{
		$db = new Db();
		$accountAmountFloatingData = $db::table('s_system_config')->field('config_status,config_data')
			->where('config_name','=','account_amount_floating')
			->find();
		$returnData['config_data'] = '';
		$returnData['configStatus'] = 2;
		$returnData['amountFloatingStart'] = 1000;
		$returnData['amountFloatingEnd'] = 20000;
		if(!empty($accountAmountFloatingData)){
			if(isset($accountAmountFloatingData['config_status'])&&!empty($accountAmountFloatingData['config_status'])){
				$returnData['config_status'] = $accountAmountFloatingData['config_status'];
				$returnData['config_data'] = $accountAmountFloatingData['config_data'];
			}
		}
//		var_dump($returnData);exit;
		$accountAmountFloatingStart = $db::table('s_system_config')->field('config_status,config_data')
			->where('config_name','=','account_amount_floating_start')
			->find()['config_data'];
		$accountAmountFloatingEnd = $db::table('s_system_config')->field('config_status,config_data')
			->where('config_name','=','account_amount_floating_end')
			->find()['config_data'];
		$returnData['amountFloatingStart'] = $accountAmountFloatingStart?$accountAmountFloatingStart:$returnData['amountFloatingStart'];
		$returnData['amountFloatingEnd'] = $accountAmountFloatingEnd?$accountAmountFloatingEnd:$returnData['amountFloatingEnd'];
		return $returnData;
	}

    /**
     * 获取倒计时锁定时间
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public static function getPayLimitTime()
	{
//		$db = new Db();
//		$orderPayLimitTime = $db::table('s_system_config')->where('config_name','=','order_pay_limit_time')->find()['config_data'];
//		if($orderPayLimitTime){
//			return $orderPayLimitTime;
//		}
		return 600;
	}
	/**
     * 获取修改金额范围 Modified amount callable scope
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public static function ModifiedAmountCallableScope()
	{
		$db = new Db();
		$orderPayLimitTime = $db::table('s_system_config')->where('config_name','=','ModifiedAmountCallableScope')->find()['config_data'];
		if($orderPayLimitTime){
			return $orderPayLimitTime;
		}
		return 0;
	}

    /**
     * 获取查询订单时间start
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getPayLimitTimeStart()
    {
        $db = new Db();
        $orderPayLimitTimeStart = $db::table('s_system_config')->where('config_name','=','order_pay_limit_time_start')->find()['config_data'];
        if($orderPayLimitTimeStart){
            return $orderPayLimitTimeStart;
        }
        return 3600;
    }

    /**
     * 获取自动停用金额 getDisableDeviceLimitMoney  默认50000
     * @return int|mixed
     */
    public static function getDisableDeviceLimitMoney()
    {
        try{
            $db = new Db();
            $orderPayLimitTimeStart = $db::table('s_system_config')->where('config_name','=','disable_device_limit_money')->find()['config_data'];
            if($orderPayLimitTimeStart){
                return $orderPayLimitTimeStart;
            }
            return 50000;
        }catch (\Exception $exception){
            logs(json_encode(['file'=>$exception->getFile(),'line'=>$exception->getLine(),'errorMessage'=>$exception->getMessage()]),'getDisableDeviceLimitMoney_exception');
            return 50000;
        }catch (\Error $error){
            logs(json_encode(['file'=>$error->getFile(),'line'=>$error->getLine(),'errorMessage'=>$error->getMessage()]),'getDisableDeviceLimitMoney_error');
            return 50000;
        }
    }
}
