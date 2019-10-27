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
namespace app\admin\model;

use think\Model;
use think\Db;

class BankModel extends Model
{
    // 确定链接表名
     protected $name = 'bank';


    /**
     * 编辑文章信息
     * @param $param
     * @return array
     */
    public function editBank($param)
    {
        try{

            $result = $this->validate('BankValidate');
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }
            $result = $this->table('s_device')->update($param, ['id' => $param['id']]);
            if(false === $result){
                // 验证失败 输出错误信息
                return msg(-1, '', $this->getError());
            }else{

                return msg(1, url('bank/banklist'), '修改成功');
            }
        }catch(\Exception $e){
            return msg(-2, '', $e->getMessage());
        }
    }

    /**
     * 删除银行卡
     * @param $id
     * @return array
     */
    public function delBank($id)
    {
        try{
            $this->table('s_device')->where('id', $id)->delete();
            return msg(1, '', '删除设备成功');

        }catch(\Exception $e){
            return msg(-1, '', $e->getMessage());
        }
    }

}
