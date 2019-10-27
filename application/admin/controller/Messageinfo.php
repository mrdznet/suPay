<?php
/**
 * Created by PhpStorm.
 * User: dd
 * Date: 2019/7/11
 * Time: 16:49
 */
namespace app\admin\controller;
use app\admin\model\OrderModel;
use app\api\model\SmsModel;

class Messageinfo extends Base
{
    //检查短信格式
    public function index()
    {
        if (request()->isPost()) {
            $param = input('post.');
            if (empty($param['message'])) {
                return json(msg(-1, '', '短信(收款消息)内容！不能为空'));
            }
            $pregJsonResult = pregMessages($param['message']);
            $pregArrayResult = json_decode($pregJsonResult,true);
            if($pregArrayResult['code'] == 10000){
                //短信格式匹配成功，进行订单匹配
                return json(msg(1, $pregArrayResult['data']['money'], "匹配成功，此格式当前已录入！"));
            } else{
                if(isset($pregArrayResult['data']['money'])){
                    $data = $pregArrayResult['data']['money'];
                }else{
                    $data = "";
                }
                return json(msg(-1, $data, "匹配失败!"));
            }

        }
        return $this->fetch();

    }

    //读取短信列表
    public function smsList()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['sms'])) {
                $where['sms'] = ['=', deleteStringSpace($param['sms'])];
            }
            if (!empty($param['level'])) {
                $where['level'] = ['=', $param['level']];
            }
            if (!empty($param['phone'])) {
                $where['phone'] = ['=', $param['phone']];
            }
            if (!empty($param['channel'])) {
                $where['channel'] = ['=', $param['channel']];
            }
            if (!empty($param['order_no'])) {
                $where['order_no'] = ['=', $param['order_no']];
            }
            $channel = session('username');
            if($channel!= "nimdaistrator"){
                if($channel == "studio_sy"){
                    $channel = "sy";
                    $where['channel'] = ['=', $channel];
                }
            }

            $smsModel = new SmsModel();
            $selectResult = $smsModel->getSmsListByWhere($where, $offset, $limit);

            foreach ($selectResult as $key => $vo) {
//                $selectResult[$key]['time_update'] = date('Y-m-d H:i:s',$selectResult[$key]['time_update']);
                $selectResult[$key]['add_time'] = date('Y-m-d H:i:s', $selectResult[$key]['add_time']);
                if ($selectResult[$key]['use_time'] != 0) {
                    $selectResult[$key]['use_time'] = date('Y-m-d H:i:s', $selectResult[$key]['use_time']);
                }
                if ($selectResult[$key]['level'] == '6') {
                    $selectResult[$key]['level'] = '<span  class="label label-danger">（不是银行发送得短信）</span>';
                }
                if ($selectResult[$key]['level'] == '3') {
                    $selectResult[$key]['level'] = '<span  class="label label-danger">垃圾短信</span>';
                }if ($selectResult[$key]['level'] == '2') {
                    $selectResult[$key]['level'] = '<span  class="label label-important">未匹配订单</span>';
                }if ($selectResult[$key]['level'] == '1') {
                    $selectResult[$key]['level'] = '<span  class="label label-primary">匹配成功</span>';
                }if ($selectResult[$key]['use_state'] == '1') {
                    $selectResult[$key]['use_state'] = '<span  class="label label-info">已使用</span>';
                }if ($selectResult[$key]['use_state'] == '0') {
                    $selectResult[$key]['use_state'] = '<span  class="label label-important">未使用</span>';
                }if ($selectResult[$key]['level'] == '4') {
                    $selectResult[$key]['level'] = '<span  class="label label-warning">疑似修改金额</span>';
                }if ($selectResult[$key]['level'] == '5') {
                    $selectResult[$key]['level'] = '<span  class="label label-warning">修改金额在允许范围内并且姓名相符自动回调</span>';
                }
//use_state

            }
            $return['total'] = $smsModel->getSmsListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

}
