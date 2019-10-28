<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 22:39
 */
namespace app\admin\controller;

use app\admin\model\Ali;

class Account extends Base
{
    //支付宝列表
    public function index()
    {
        if(request()->isAjax()){
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['alinumber'] = ['=',  $param['searchText'] ];
            }

            $channelId = session('id');
//            if($channelId>1){
//                $where['channel_id'] = ['=',  $channelId ];
//            }
            $Ali = new Ali();
            $selectResult = $Ali->getAliListByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){
                if( !empty($selectResult[$key]['binding_state']) &&$selectResult[$key]['binding_state']=='1'){
                    $selectResult[$key]['binding_state']= '<span  class="label label-success">在线</span>';
                }else {
                    $selectResult[$key]['binding_state']= '<span  class="label label-important">离线</span>';
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
//
            }
            $return['total'] = $Ali->getAliListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }


    /**
     * 修改支付宝限额
     * @return mixed|\think\response\Json
     */
    public function accountEdit()
    {
        $Ali = new Ali();
        if(request()->isPost()){

            $param = input('post.');
            $flag = $Ali->editAccount($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $this->assign([
            'account' => $Ali->getOneAccount($id)
        ]);
        return $this->fetch();
    }

    /**
     * 删除记录
     * @return \think\response\Json
     */
    public function accountDel()
    {
        $id = input('param.id');

        $ali = new Ali();
        $flag = $ali->delAli($id);
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
                'auth' => 'account/accountedit',
                'href' => url('account/accountedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'account/accountdel',
                'href' => "javascript:accountDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }
}
