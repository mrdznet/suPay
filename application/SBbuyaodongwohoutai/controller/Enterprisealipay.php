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

class Enterprisealipay extends Base
{


    /**
     * 修改支付宝限额
     * @return mixed|\think\response\Json
     */
    public function createAliConfig()
    {
        $db = new Db();
//        $Ali = new Ali();
        if(request()->isPost()){
//            dump(1111);
            $param = input('post.');
//            dump($param);exit;
            if($param['app_id'] == ''||$param['merchant_private_key'] == ''||$param['alipay_public_key'] == ''||$param['notify_url'] == ''){

                return json(msg(-1, '', '请填写完整信息'));
            }
            if($db::table('s_f_alipayconfig')->where('app_id','=',$param['app_id'])->count()!=0){
                return json(msg(-1, '', '已存在相同的appid'));
            }
            if($db::table('s_f_alipayconfig')->where('merchant_private_key','=',$param['merchant_private_key'])->count()!=0){
                return json(msg(-1, '', '已存在相同的私钥'));
            }
//            $param['studio_id'] = session('username');

//            dump($param);
            $res = $db::table('s_f_alipayconfig')->insert($param);
            $ress = $db::table('s_f_ali_account')->insert([
                'app_id'=>$param['app_id'],
                'app_description'=>$param['describe']
            ]);

            if($res&&$ress){
                return json(msg(1, url('enterprisealipay/alilist'), '添加配置成功'));
            }
//            dump($param);
            return json(msg(-1, url('enterprisealipay/alilist'), '添加配置失败'));
        }

        return $this->fetch();
    }

    /**
     * 银行卡列表
     * @return mixed|\think\response\Json
     */
    public function aliList()
    {
//        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['ali_number'] = ['=',  $param['searchText'] ];
            }

//            $Ali = new Ali();
//            $selectResult = $Ali->getAliListByWhere($where, $offset, $limit);
            $db = new Db();
            $selectResult= $db::table('s_f_alipayconfig')->where($where)->limit($offset, $limit)->order('id desc')->select();

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
//
            }
            $return['total'] = $db::table('s_f_alipayconfig')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    public function accountList()
    {
//        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['account'])) {
                $where['account'] = ['=',  trim($param['account']) ];
            }

            if (!empty($param['account_app_id'])) {
                $where['account_app_id'] = ['=',  trim($param['account_app_id']) ];
            }
            $db = new Db();
            $selectResult= $db::table('s_f_ali_account')->where($where)->limit($offset, $limit)->order('id asc')->select();

            foreach($selectResult as $key=>$vo){
                if( $selectResult[$key]['status']=='1'){
                    $selectResult[$key]['status']= '<span  class="label label-success">正常</span>';
                }
                if( $selectResult[$key]['status']=='2'){
                    $selectResult[$key]['status']= '<span  class="label label-primary">未启用</span>';
                }
                if( $selectResult[$key]['status']=='3'){
                    $selectResult[$key]['status']= '<span  class="label label-danger">禁用</span>';
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
//
            }
            $return['total'] = $db::table('s_f_ali_account')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    public function log()
    {
//        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['order_me'])) {
                $where['order_me'] = ['like',  '%'.trim($param['order_me']).'%' ];
            }

            $db = new Db();
            $selectResult= $db::table('s_test')->where($where)->limit($offset, $limit)->order('id desc')->select();

            $return['total'] = $db::table('s_test')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    public function bankEdit(){
        $bank = new BankModel();
        if(request()->isPost()){

            $param = input('post.');
            $flag = $bank->editBank($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $bank = DB::table('s_bank')->where('id', $id)->find();
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
