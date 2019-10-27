<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 22:39
 */
namespace app\admin\controller;

use app\admin\model\BankModel;
use think\Db;
use think\Request;

class Bank extends Base
{


    /**
     * 修改支付宝限额
     * @return mixed|\think\response\Json
     */
    public function createBank()
    {
        $db = new Db();
        $data    = @file_get_contents('php://input');
        $param = input('post.');
//        $Ali = new Ali();
        if(request()->isPost()){
//            dump(1111);
            $param = input('post.');
//            dump($param);exit;
            if($param['account_no'] == ''||$param['name'] == ''){

                return json(msg(-1, '', '请填写完整信息'));
            }
            if($db::table('s_device')->where('account_no','=',$param['account_no'])->count()!=0){
                return json(msg(-1, '', '以存在相同的支付宝账户'));
            }
            $param['create_time'] = time();
            $newparm = $param;
            $newparm['channel'] = session('username');
            $newparm['device_type'] = 2;
            $newparm['update_time'] = time();
            $newparm['proxy_id'] = $param['proxy_id'];
            $res = $db::table('s_device')->insert($newparm);

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
        $id = input('param.id');
        $user = $db::table('s_user')->where('id','=',$id)->find();
        $nickname = $user['real_name'];
        $this->assign('nickname',$nickname);
        $this->assign('id',$user['user_name']);
        return $this->fetch();
    }

    /**
     * 银行卡列表
     * @return mixed|\think\response\Json
     */
    public function bankList(Request $request)
    {
//        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['channel'])) {
                $where['proxy_id'] = ['=',  $param['channel'] ];
            }
            if (!empty($param['name'])) {
                $where['name'] = ['=',  $param['name'] ];
            }
            if (!empty($param['account_no'])) {
                $where['account_no'] = ['=',  $param['account_no'] ];
            }
            //按照日期搜索 serchtime
            $wheretime = '';
            if (!empty($param['starttime']) && !empty($param['starttime'])) {
                $starttime = strtotime($param['starttime']);
                $endtimes = strtotime($param['endtime']);
                $endtime = $endtimes+86400;
                $wheretime = "finish_time>=".$starttime." and finish_time<".$endtime;
            }
            if(session('username')!= "nimdaistrator" && session('username')!= "agent_total"){
                $where['proxy_id'] = session('username');
            }

//            $Ali = new Ali();
//            $selectResult = $Ali->getAliListByWhere($where, $offset, $limit);
            $db = new Db();
            $selectResult= $db::table('s_device')->where($where)->limit($offset, $limit)->order('post_balance asc')->select();

            foreach($selectResult as $key=>$vo){
                if ($selectResult[$key]['create_time'] != 0) {
                    $selectResult[$key]['create_time'] = date('Y-m-d H:i:s', intval($selectResult[$key]['create_time']));
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
                //查询设备总跑单数量
                $selectResult[$key]['Oddnumber'] = $db::table('s_mcorder')
                    ->where($wheretime)
                    ->where('account_no','=',$vo['account_no'])->count('id');
                //查询设备成功跑单数量
                $selectResult[$key]['successOddnumber'] = $db::table('s_mcorder')
                                                    ->where($wheretime)
                                                    ->where('account_no','=',$vo['account_no'])
                                                    ->where('transaction_status','=','1')->count('id');
                //查询设备失败跑单数量
                $selectResult[$key]['errprOddnumber'] = $db::table('s_mcorder')
                    ->where('account_no','=',$vo['account_no'])
                    ->where($wheretime)
                    ->where('transaction_status','=','0')->count('id');//Totalwithdrawalamount
                //查询设备跑量总额
                $selectResult[$key]['Totalwithdrawalamount'] = $db::table('s_mcorder')
                    ->where('account_no','=',$vo['account_no'])
                    ->where($wheretime)
                    ->where('transaction_status','=','1')->sum('transaction_amount');//Totalwithdrawalamount
                //查询代理的昵称
                $selectResult[$key]['dlnickname'] = $db::table('s_user')
                    ->where('user_name','=',$vo['proxy_id'])->field('real_name')->find()['real_name'];
//
            }
            $return['total'] = $db::table('s_device')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }
//        if(isset($message['time'])){
//            $seachday = $message['time'];
//            $day = $message['time'];
//        }else{
//            $seachday = date("Y-m-d",time());
//            $day = "今天";
//        }
        $message = $request->param();
        $user_name = "";
        if(isset($message['username'])){
            $user_name = $message['username'];
        }
        $this->assign('user_name',$user_name);
        $this->assign('serchtime',date("Y-m-d",time()));
        return $this->fetch();
    }

    public function bankEdit(){
        $db = new Db();
        $bank = new BankModel();
        if(request()->isPost()){
            $param = input('post.');
            if($param['account_no'] == ''){
                return json(msg(-1, '', '请填写完整信息'));
            }
            $card = $db::table('s_device')->where('account_no','=',$param['account_no'])->find();
            if($card && $card['id'] != $param['id']){
                return json(msg(-1, '', '以存在相同支付宝卡号'));
            }
            $flag = $bank->editBank($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $bank = DB::table('s_device')->where('id', $id)->find();
        //获取代理信息
        $role = DB::table('s_user')->where('role_id', 13)->select();
//        dump($bank);exit;
        $this->assign([
                          'bank' => $bank,
                          'role' => $role
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
