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

class TransferValidate extends Validate
{
    protected $rule = [
//        ['OrderNum','require|', 'missing_parameters_BankCardNo_require'], //订单号
        ['ChangeAmount','require|float', 'ChangeAmount_require|ChangeAmount_is_must_be_float'],  //实际打款金额
        ['CurrentBalance ','require|float', 'CurrentBalance_require|CurrentBalance_is_must_be_float'], //银行卡余额
        ['PayType','require|number', 'PayType_require|PayType_is_must_be_int'], //CCB 1-支付宝 2-微信 4-银行卡 8-云闪付	//支付类型
        ['Statu','require|number', 'Statu_require|Statu_require_is_must_be_int'], //0失败 1成功 2未知 3 处理中	//转账结果
        ['PhoneNum','require|number|length:11', 'missing_parameters_PhoneNum_require|PhoneNum_is_must_be_number|PhoneNum_error_length'], //电话号码
    ];
}