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
namespace app\api\validate;

use think\Validate;

class DeviceValidate extends Validate
{
    protected $rule = [
        ['BankCardNo','require|number|length:15,25', 'missing_parameters_BankCardNo_require|missing_parameters_BankCardNo_not_number|BankCardNo_error_for_length'],
        ['AccountName','require', 'missing_parameters_AccountName_require'],
        ['BankShortName','require', 'missing_parameters_BankShortName_require'], //CCB
        ['OpenAccountBranch','require', 'missing_parameters_OpenAccountBranch_require'], //CCB
        ['OpenAccountBranch','require', 'missing_parameters_OpenAccountBranch_require'], //中国建设银行
        ['PhoneNum','require|number|length:11', 'missing_parameters_PhoneNum_require|PhoneNum_is_must_be_number|PhoneNum_error_for_length'], //电话号码
    ];
}