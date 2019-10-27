<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 22:39
 */
namespace app\SBbuyaodongwohoutai\controller;

use app\SBbuyaodongwohoutai\model\BankModel;
use think\Db;

class Bank extends Base
{


    /**
     * 修改支付宝限额
     * @return mixed|\think\response\Json
     */
    public function createBank()
    {
        $db = new Db();
//        $Ali = new Ali();
        if(request()->isPost()){
//            dump(1111);
            $param = input('post.');
//            dump($param);exit;
            if($param['phone'] == ''||$param['bank_name'] == ''||$param['card'] == ''||$param['name'] == ''){

                return json(msg(-1, '', '请填写完整信息'));
            }
            if($db::table('s_device')->where('card','=',$param['card'])->count()!=0){
                return json(msg(-1, '', '以存在相同银行卡号码'));
            }
            if($db::table('s_device')->where('phone','=',$param['phone'])->count()!=0){
                return json(msg(-1, '', '存在相同的手机号'));
            }
//            $param['bank_jc'] = $db::table('s_banks_standard')->field('bankMark')->where('bankName','=',$param['bank_name'])
//                ->find()['bankMark'];
            $param['create_time'] = time();
            $newparm = $param;
            $newparm['channel'] = session('username');
            $newparm['device_type'] = 2;
            $newparm['update_time'] = time();
            $newparm['bank_mark'] = $db::table('s_banks_standard')->field('bankMark')->where('bankName','=',$param['bank_name'])
                ->find()['bankMark'];
//            dump($param);
            $res = $db::table('s_device')->insert($newparm);
//            echo $db::table('s_bank')->getLastSql();

            if($res){
                return json(msg(1, url('bank/banklist'), '添加银行卡成功'));
            }
//            dump($param);
            return json(msg(-1, url('bank/createbank'), '失败'));
        }

//        $id = input('param.id');
        $bank = $db::table('s_device')->select();
//        dump($bank);
        $this->assign('bank',$bank);
        $bankstandard = $db::table('s_banks_standard')->select();
        $this->assign('bankstandard',$bankstandard);
        return $this->fetch();
    }

    /**
     * 银行卡列表
     * @return mixed|\think\response\Json
     */
    public function bankList()
    {
//        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['card'] = ['=',  $param['searchText'] ];
            }
            if(session('username')!= "nimdaistrator"){
                $where['channel'] = session('username');
            }

//            $Ali = new Ali();
//            $selectResult = $Ali->getAliListByWhere($where, $offset, $limit);
            $db = new Db();
            $selectResult= $db::table('s_device')->where($where)->limit($offset, $limit)->order('id desc')->select();

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
//
            }
            $return['total'] = $db::table('s_device')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    public function bankEdit(){
        $db = new Db();
        $bank = new BankModel();
        if(request()->isPost()){
            $param = input('post.');
            if($param['phone'] == ''||$param['bank_name'] == ''||$param['card'] == ''||$param['name'] == ''){
                return json(msg(-1, '', '请填写完整信息'));
            }
            $card = $db::table('s_device')->where('card','=',$param['card'])->find();
            if($card && $card['id'] != $param['id']){
                return json(msg(-1, '', '以存在相同银行卡号码'));
            }
            $phone = $db::table('s_device')->where('phone','=',$param['phone'])->find();
            if($phone && $phone['id'] != $param['id']){
                return json(msg(-1, '', '存在相同的手机号'));
            }
            $flag = $bank->editBank($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $bank = DB::table('s_device')->where('id', $id)->find();
//        dump($bank);exit;
        $this->assign([
                          'bank' => $bank
                      ]);
        return $this->fetch();

    }
    public function bankDel(){

        $id = input('param.id');

        $bank = new BankModel();
        $flag = $bank->delBank($id);
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
                'auth' => 'bank/bankedit',
                'href' => url('bank/bankEdit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'bank/bankdel',
                'href' => "javascript:bankDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }
}
