<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/12
 * Time: 14:00
 */
namespace app\admin\controller;

use app\admin\model\ArticleModel;
use app\admin\model\ChannelAccountModel;
use app\admin\model\Device;
use think\Db;


class Alichannel extends Base
{
    // 渠道支付宝列表
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['ali_account'] = ['=',  $param['searchText'] ];
            }

            $merchantId = session('username');
            if($merchantId!= "nimdaistrator"){
                $where['channel_id'] = ['=',  $merchantId ];
            }
            $ChannelAccount = new ChannelAccountModel();
            $selectResult = $ChannelAccount->getChannelAccountByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['add_time'] = date('Y-m-d h:i:s',$selectResult[$key]['add_time']);
                $selectResult[$key]['success_time'] = date('Y-m-d h:i:s',$selectResult[$key]['success_time']);
                if( $selectResult[$key]['device_status']=='1'){
                    $selectResult[$key]['device_status']= '<span  class="label label-success">正常</span>';
                }else if($selectResult[$key]['device_status']=='2'){
                    $selectResult[$key]['device_status']= '<span  class="label label-important">离线</span>';
                }
                if( $selectResult[$key]['account_status']=='1'){
                    $selectResult[$key]['account_status']= '<span  class="label label-success">正常</span>';
                }else if($selectResult[$key]['account_status']=='2'){
                    $selectResult[$key]['account_status']= '<span  class="label label-important">离线</span>';
                }

            }

            $return['total'] = $ChannelAccount->getChannelAccountCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }


    /**
     * 渠道用户绑定支付宝账户第一步（查询是否有绑定，匹配合适的设备号）
     * @return mixed|\think\response\Json
     */
    public function channelAccountAdd()
    {

        if(request()->isAjax()){
            $param = input('param.');
            //支付宝账户
            $channelAccount = new ChannelAccountModel();
            $aliAccount = $param['ali_account'];
            $result =$channelAccount->getBindingDeviceId($aliAccount);
            return json($result);exit;
        }
        //查询
        return $this->fetch();
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
                'auth' => 'alichannel/channeledit',
                'href' => url('alichannel/channelaccountedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'alichanneledit/channelaccountdel',
                'href' => "javascript:channelaccountDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }
    /**
     *
     * @return mixed|\think\response\Json
     */
    public function channelAccountAjaxAdd()
    {

        if(request()->isAjax()){
            $param = input('param.');
            $account = $param['account'];
            $deviceId = $param['deviceId'];
            $ali_qr = $param['ali_qr'];
            //支付宝账户
//            $channelAccount = new ChannelAccountModel();
            $Device = new Device();
            $Db = new Db();
            //1、device 绑定支付宝号
            db::startTrans();
            //
            $deviceUpdateData['account'] = $account;
            $deviceUpdateData['ali_qr'] = $ali_qr;
            $result1 = $Db::table('s_device')->where('device_id','=',$deviceId)->update($deviceUpdateData);
            $deviceData = $Db::table('s_device')->where('device_id','=',$deviceId)->find();
            //2channel_account 添加绑定记录
            $data2 = $Db::table('s_ali')->where('alinumber','=',$account)->find();
            if(!$data2){
                $aliInsertData['alinumber'] = $account;
                $result2 = $Db::table('s_ali')->insert($aliInsertData);
            }else{
                $result2 = true;
            }
            //3ali表添加记录
            $data3 = $Db::table('s_channel_account')->where('ali_account','=',$account)->find();
            if($data3){
                $channelAccountUpdateData['account_status'] = 1;
                $channelAccountUpdateData['device_status'] = 1;
                $channelAccountUpdateData['binding_times'] = 1;
                $channelAccountUpdateData['channel_id'] = session('username');
                $channelAccountUpdateData['add_time'] = time();
                $channelAccountUpdateData['success_time'] = time();
                $channelAccountUpdateData['device_id'] = $deviceId;
//                $channelAccountUpdateData['client_id'] = $deviceData['client_id'];
                $channelAccountUpdateData['binding_times'] = $data3['binding_times']+1;
                $result3 = $Db::table('s_channel_account')->where('ali_account','=',$account)->update($channelAccountUpdateData);
            }else{
                $channelAccountInsertData['account_status'] = '1';
                $channelAccountInsertData['device_status'] = '1';
                $channelAccountInsertData['channel_id'] = session('username');
                $channelAccountInsertData['ali_account'] = $account;
                $channelAccountInsertData['device_id'] = $deviceId;
//                $channelAccountUpdateData['client_id'] = $deviceData['client_id'];
                $channelAccountInsertData['add_time'] = time();
                $channelAccountInsertData['success_time'] = time();
                $channelAccountInsertData['bindling_times'] = '1';
                $result3 = $Db::table('s_channel_account')->insert($channelAccountInsertData);
            }
            if($result1&&$result2&&$result3){
                $Db::commit();
                return json(msg('1','alichannel/index','支付宝登陆且绑定成功'));die();
            }
            if($result1&&$result2){
                $Db::commit();
                return json(msg('2','alichannel/index','支付宝登陆成功，未绑定商户'));die();
            }
            $Db::rollback();
            return json(msg('3','alichannel/index','支付宝登陆失败'));die();
        }
    }

    public function aliList(){
        //        dump(session('username'));exit;
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['account'] = ['=',  $param['searchText'] ];
            }

//            $Ali = new Ali();
//            $selectResult = $Ali->getAliListByWhere($where, $offset, $limit);
            $db = new Db();
            $selectResult= $db::table('s_f_ali_account')->where($where)->limit($offset, $limit)->order('id desc')->select();

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['operate'] = showOperate($this->makeButtons($vo['id']));
//
            }
            $return['total'] = $db::table('s_f_ali_account')->where($where)->count();//$Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();

    }
    public function alichanneledit(){
        $db = new Db();
        if(request()->isPost()){
            $param = input('post.');

            if(empty($param['account'])){
                return json(msg(-1, '', '支付宝账号不能为空！'));
            }
            //去除银行卡空格
            $param['account'] = deleteStringSpace($param['account']);
            $param['account_name'] = deleteStringSpace($param['account_name']);
            $param['account_app_id'] = deleteStringSpace($param['account_app_id']);
            if(empty($param['account_name'])){
                return json(msg(-1, '', '支付宝姓名不能为空！'));
            }
            $alione = $db::table('s_f_ali_account')->where('account','=',$param['account'])->find();
            if($alione){
                return json(msg(-1, '', '存在相同的支付宝账号！'));
            }
            $channel = session('username');
            $param['update_user'] = $channel;
            $id = $param['id'];
            unset($param['id']);
            $flag = $db::table('s_f_ali_account')->where('id','=',$id)->update($param);//$bankDeviceModel->editBankDevice($param);
            if($flag){
//                return json(msg($flag['code'], $flag['data'], $flag['msg']));
                return msg(1, url('Alichannel/aliList'), '绑定提现支付宝成功');
            }

        }

        $id = input('param.id');
        $accountdata = $db::table('s_f_ali_account')->where('id','=',$id)->find();
        $this->assign([
            'alidata' => $accountdata,
        ]);
        return $this->fetch();
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButtons($id)
    {
        return [
            '编辑' => [
                'auth' => 'alichannel/alichanneledit',
                'href' => url('alichannel/alichanneledit', ['id' => $id]),
                'btnStyle' => 'sprimary',
                'icon' => 'fa fa-paste'
            ],
        ];
    }
}
