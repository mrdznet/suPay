<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2019/1/18
 * Time: 18:06
 */
namespace app\admin\validate;

use think\Validate;

class BankDeviceValidate extends Validate
{
	protected $rule = [
		['phone', 'require', '手机号不能为空'],
		['bank_name', 'require', '请选择银行名称'],
		['bank_card', 'require', '银行卡号不能为空'],
		['name', 'require', '持卡人姓名不能空'],
	];

}