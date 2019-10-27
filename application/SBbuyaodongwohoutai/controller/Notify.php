<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 18:57
 */

namespace app\SBbuyaodongwohoutai\controller;

use app\SBbuyaodongwohoutai\model\NotifyModel;
use app\SBbuyaodongwohoutai\model\LoseNotifyModel;

class Notify extends Base
{
    // 渠道支付宝列表
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['orderNo'] = ['=', $param['searchText']];
            }
            if (!empty($param['searchStartTime'])) {
                $where['createTime'] = ['>', $param['searchStartTime']];
            }
            if (!empty($param['searchEndTime'])) {
                $where['createTime'] = ['=', $param['searchEndTime']];
            }
            if (!empty($param['amount'])) {
                $where['PayMoney'] = ['=', $param['amount']];
            }
            if (!empty($param['RecvCardNo'])) {
                $where['RecvCardNo'] = ['=', $param['card']];
            }
            if (!empty($param['payCard'])) {
                $where['PayCardNo'] = ['=', $param['payCard']];
            }
            if (!empty($param['PayCardUser'])) {
                $where['PayCardUser'] = ['=', $param['PayCardUser']];
            }
//            $merchantId = session('username');
//            if($merchantId!= "nimdaistrator"){
//                $where['merchant_id'] = ['=',  $merchantId ];
//            }
            $notifyModel = new NotifyModel();
            $selectResult = $notifyModel->getNotifyListByWhere($where, $offset, $limit);

            foreach ($selectResult as $key => $vo) {

                if ($selectResult[$key]['createTime'] != 0) {
                    $selectResult[$key]['createTime'] = date('Y-m-d H:i:s', $selectResult[$key]['createTime']);
                }else{
                    $selectResult[$key]['createTime'] = 0;
                }
                if (isset($selectResult[$key]['PayTime'])&&$selectResult[$key]['PayTime'] != 0) {
                    $selectResult[$key]['PayTime'] = date('Y-m-d H:i:s', $selectResult[$key]['PayTime']);
                }else{
                    $selectResult[$key]['PayTime'] = 0;
                }
                $selectResult[$key]['status'] = 0;
                if (isset($selectResult[$key]['status'])&&$selectResult[$key]['status'] == '0') {
                    $selectResult[$key]['status'] = '<span  class="label label-info">收到此消息</span>';
                }
                if (isset($selectResult[$key]['status'])&&$selectResult[$key]['status'] == '1') {
                    $selectResult[$key]['status'] = '<span  class="label label-primary">匹配到订单</span>';
                }
//                if ($selectResult[$key]['order_status'] == '2') {
//                    $selectResult[$key]['order_status'] = '<span  class="label label-important">收到未匹配到未回调</span>';
//                }

            }

            $return['total'] = $notifyModel->getONotifyListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
    public function loseOrder()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];

            if (!empty($param['searchStartTime'])) {
                $where['createTime'] = ['>', $param['searchStartTime']];
            }
            if (!empty($param['searchEndTime'])) {
                $where['createTime'] = ['=', $param['searchEndTime']];
            }

            if (!empty($param['amount'])) {
                $where['PayMoney'] = ['=', $param['amount']];
            }
            if (!empty($param['RecvCardNo'])) {
                $where['RecvCardNo'] = ['=', $param['card']];
            }
            if (!empty($param['payCard'])) {
                $where['PayCardNo'] = ['=', $param['payCard']];
            }
            if (!empty($param['PayCardUser'])) {
                $where['PayCardUser'] = ['=', $param['PayCardUser']];
            }
//            $merchantId = session('username');
//            if($merchantId!= "nimdaistrator"){
//                $where['merchant_id'] = ['=',  $merchantId ];
//            }
            $losNotifyModel = new LoseNotifyModel();
            $selectResult = $losNotifyModel->getLoseNotifyListByWhere($where, $offset, $limit);
//            var_dump($selectResult);die();
            foreach ($selectResult as $key => $vo) {

                if ($selectResult[$key]['createTime'] != 0) {
                    $selectResult[$key]['createTime'] = date('Y-m-d H:i:s', $selectResult[$key]['createTime']);
                }else{
                    $selectResult[$key]['createTime'] = 0;
                }
                if (isset($selectResult[$key]['PayTime'])&&$selectResult[$key]['PayTime'] != 0) {
                    $selectResult[$key]['PayTime'] = date('Y-m-d H:i:s', $selectResult[$key]['PayTime']);
                }else{
                    $selectResult[$key]['PayTime'] = 0;
                }
                if (isset($selectResult[$key]['status'])&&$selectResult[$key]['status'] == '0') {
                    $selectResult[$key]['status'] = '<span  class="label label-info">收到此消息</span>';
                }
                if (isset($selectResult[$key]['status'])&&$selectResult[$key]['status'] == 1) {
                    $selectResult[$key]['status'] = '<span  class="label label-primary">匹配到订单</span>';
                }
//                if ($selectResult[$key]['order_status'] == '2') {
//                    $selectResult[$key]['order_status'] = '<span  class="label label-important">收到未匹配到未回调</span>';
//                }
                $selectResult[$key]['prohibitButton'] = '<a href="javascript:deletelog(' . $vo['id'] . ')" class="label label-success" ><button type="button" class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i>删除</button></a>';

            }

            $return['total'] = $losNotifyModel->getONotifyListCount($where);  // 总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
    /**

     * 删除流水
     */
    public function deletelog()
    {

            $id = input('param.id');
            $losNotifyModel = new LoseNotifyModel();
            $selectResult = $losNotifyModel->deletelog($id);
            if($selectResult === false){
                return json(msg(-1, '', '删除失败'));

            }return json(msg(1, '', '删除成功！'));


    }




}
