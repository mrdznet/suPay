<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 22:39
 */

namespace app\admin\controller;

use think\Db;
use app\admin\model\BankDeviceModel;

class Bankdevice extends Base
{
    /**
     * 最新银行卡列表
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['card'] = ['=', $param['searchText']];
            }
            if (!empty($param['phone'])) {
                $where['phone'] = ['=', $param['phone']];
            }

            $channel = session('username');
            //
			if($channel!= "nimdaistrator" && !empty($param['channel']) && $param['channel']!=$channel&&$channel!='studio_kf'){
                return json(msgs(-1, '', '无法查看本工作室以外的设备'));
            }
            if (!empty($param['channel'])) {
                $where['channel'] = ['=', $param['channel']];
            }
            if (!empty($param['name'])) {
                $where['name'] = ['=', $param['name']];
            }
            if (!empty($param['is_online'])) {
                $where['is_online'] = ['=', $param['is_online']];
            }
            if (!empty($param['is_prohibit'])) {
                $where['is_prohibit'] = ['=', $param['is_prohibit']];
            }
            //订单成功
            $successWhere['order_status'] = 1;

            if ($channel != 'nimdaistrator' && $channel != 'studio_kf') {
                $where['channel'] = ['=', $channel];
            }
            $where['is_delete'] = 0;//查询未删除的设备
            $db = new Db();
            $selectResult = $db::table('s_device')->where($where)->limit($offset, $limit)->order('is_online asc,is_prohibit asc')->select();

            foreach ($selectResult as $key => $vo) {

                //警告次数 （获取收款码）
                if(empty($selectResult[$key]['version'])||$selectResult[$key]['version']==""){
                    $selectResult[$key]['version_no'] = '暂无';
                }else{
                    $selectResult[$key]['version_no'] = $selectResult[$key]['version'];
                }
                //添加时间
                $selectResult[$key]['create_time'] = date('Y-m-d H:i:s', $selectResult[$key]['create_time']);
                $selectResult[$key]['heart_time'] = date('Y-m-d H:i:s', $selectResult[$key]['update_time']);
                if($selectResult[$key]['lock_time']>0){
                    $selectResult[$key]['lock_time'] = "订单锁定中,锁定时间：".date("Y-m-d H:i:s",$selectResult[$key]['lock_time']);
                }else{
                    $selectResult[$key]['lock_time'] = "空闲中";
                }

                $startTime = strtotime(date('Y-m-d'));
                //今日总收钱
                $moneyData = $db::table('s_order')->field('SUM(actual_amount) as todayTotalActualAmount')
                    ->where('card', '=', $vo['card'])
                    ->where('add_time', '>', $startTime)
                    ->where('order_status', '=', 1)
                    ->find();
                $selectResult[$key]['today_money'] = $moneyData['todayTotalActualAmount'];
                //总收钱
                $moneyData = $db::table('s_order')->field('SUM(actual_amount) as totalActualAmount')
                    ->where('card', '=', $vo['card'])
                    ->where('order_status', '=', 1)
                    ->find();
                $selectResult[$key]['totalMoney'] = $moneyData['totalActualAmount'];
                if (!empty($selectResult[$key]['is_online']) && $selectResult[$key]['is_online'] == '1') {
                    $selectResult[$key]['is_online'] = '<span  class="label label-success">在线</span>';
                } else {
                    $selectResult[$key]['is_online'] = '<span  class="label label-important">离线</span>';
                }
                if (!empty($selectResult[$key]['is_prohibit']) && $selectResult[$key]['is_prohibit'] == '1') {
                    $selectResult[$key]['is_prohibit'] = '<a  class="label label-success">启用中</a>';
                    $selectResult[$key]['prohibitButton'] = '<a href="javascript:changeStatus(' . $vo['id'] . ')" class="label label-success" ><button type="button" class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i>点击停用</button></a>';
                    $selectResult[$key]['remainss'] = $selectResult[$key]['remains'];
                } else {

                    $selectResult[$key]['is_prohibit'] = '<a  class="label label-important">停用中</a>';
                    $selectResult[$key]['prohibitButton'] = '<a href="javascript:changeStatus(' . $vo['id'] . ')" class="label label-success" ><button type="button" class="btn btn-primary btn-sm"><i class="fa fa-trash-o"></i>点击启用</button></a>';
                    $selectResult[$key]['remainss'] = '<input type="text" class="form-control" id="remains'.$vo['id'].'" name="remains">';
                }
                //总下单量
                $selectResult[$key]['orderTotal'] = $db::table('s_order')->where('card', '=', $vo['card'])->count();
                //支付成功量  新增->where($orderWhere)
                $selectResult[$key]['orderSuccessTotal'] = $db::table('s_order')->where($successWhere)->where('card', '=', $vo['card'])->count();
                //下单（总）成功率
                if ($selectResult[$key]['orderTotal'] == 0) {
                    $success_rate = "0/0" . "成功率0.00%";
                    $selectResult[$key]['success_rate'] = $success_rate;
                    $selectResult[$key]['success_rateNUM'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['orderSuccessTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
                    $success_rate = round($selectResult[$key]['orderSuccessTotal'] * 100/$selectResult[$key]['orderTotal'], 2);
                    $selectResult[$key]['success_rate'] = $selectResult[$key]['orderSuccessTotal'] . "/" . $selectResult[$key]['orderTotal'] . "成功率" . $success_rate . "%";
                    $selectResult[$key]['success_rateNUM'] = $success_rate;
                }
                //三十分钟前时间戳
                $time = time() - 1800;
                //三十分钟下单量
                $selectResult[$key]['orderThirtyTotal'] = $db::table('s_order')->where('card', '=', $vo['card'])->where('add_time', '>', $time)->count();
                //三十分钟支付成功量
                $selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')->where($successWhere)->where('account', '=', $vo['phone'])->where('add_time', '>', $time)->count();

                //下单仅三十分钟成功率
                if ($selectResult[$key]['orderThirtySuccessTotal'] == 0) {
                    $success_rate = "0/" . $selectResult[$key]['orderThirtyTotal'] . "成功率0.00%";
                    $selectResult[$key]['thirty_success_rate'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
                    $success_rate = round($selectResult[$key]['orderThirtySuccessTotal'] * 100/$selectResult[$key]['orderThirtyTotal'], 2);
                    $selectResult[$key]['thirty_success_rate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }
            $return['total'] = $db::table('s_device')->where($where)->count();  // 总数据
            //使用总称功率 进行 升序排列
//            $last_names = array_column($selectResult,'success_rateNUM');
//            array_multisort($last_names,SORT_ASC,$selectResult);
            $return['rows'] = $selectResult;

            return json($return);
        }
        $db = new Db();
        $bankstandard = $db::table('s_banks_standard')->select();
        $this->assign('bankstandard',$bankstandard);
        return $this->fetch();
    }

    /**
     * a
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deviceorderbysuccessrate()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['card'] = ['=', $param['searchText']];
            }
            if (!empty($param['phone'])) {
                $where['phone'] = ['=', $param['phone']];
            }

            $channel = session('username');
            //
            if($channel!= "nimdaistrator" && !empty($param['channel']) && $param['channel']!=$channel){
                return json(msgs(-1, '', '无法查看本工作室以外的设备'));
            }
            if (!empty($param['channel'])) {
                $where['channel'] = ['=', $param['channel']];
            }
            if (!empty($param['name'])) {
                $where['name'] = ['=', $param['name']];
            }
            //订单成功
            $successWhere['order_status'] = 1;

            if ($channel != 'nimdaistrator' && $channel != 'dd') {
                $where['channel'] = ['=', $channel];
            }
            $where['is_online'] = 1;
            $where['is_prohibit'] = 1;
            $db = new Db();
            $selectResult = $db::table('s_device')->where($where)->order('is_online asc,is_prohibit asc')->select();

            foreach ($selectResult as $key => $vo) {

                //警告次数 （获取收款码）
                if(empty($selectResult[$key]['version'])||$selectResult[$key]['version']==""){
                    $selectResult[$key]['version_no'] = '暂无';
                }else{
                    $selectResult[$key]['version_no'] = $selectResult[$key]['version'];
                }
                //添加时间
                $selectResult[$key]['create_time'] = date('Y-m-d H:i:s', $selectResult[$key]['create_time']);
                $selectResult[$key]['heart_time'] = date('Y-m-d H:i:s', $selectResult[$key]['update_time']);
                if($selectResult[$key]['lock_time']>0){
                    $selectResult[$key]['lock_time'] = "订单锁定中,锁定时间：".date("Y-m-d H:i:s",$selectResult[$key]['lock_time']);
                }else{
                    $selectResult[$key]['lock_time'] = "空闲中";
                }

                $startTime = strtotime(date('Y-m-d'));
                //今日总收钱
                $moneyData = $db::table('s_order')->field('SUM(actual_amount) as todayTotalActualAmount')
                    ->where('card', '=', $vo['card'])
                    ->where('add_time', '>', $startTime)
                    ->where('order_status', '=', 1)
                    ->find();
                $selectResult[$key]['today_money'] = $moneyData['todayTotalActualAmount'];
                //总收钱
                $moneyData = $db::table('s_order')->field('SUM(actual_amount) as totalActualAmount')
                    ->where('card', '=', $vo['card'])
                    ->where('order_status', '=', 1)
                    ->find();
                $selectResult[$key]['totalMoney'] = $moneyData['totalActualAmount'];
                if (!empty($selectResult[$key]['is_online']) && $selectResult[$key]['is_online'] == '1') {
                    $selectResult[$key]['is_online'] = '<span  class="label label-success">在线</span>';
                } else {
                    $selectResult[$key]['is_online'] = '<span  class="label label-important">离线</span>';
                }
                if (!empty($selectResult[$key]['is_prohibit']) && $selectResult[$key]['is_prohibit'] == '1') {
                    $selectResult[$key]['is_prohibit'] = '<a  class="label label-success">启用中</a>';
                    $selectResult[$key]['prohibitButton'] = '<a href="javascript:changeStatus(' . $vo['id'] . ')" class="label label-success" ><button type="button" class="btn btn-danger btn-sm"><i class="fa fa-trash-o"></i>点击停用</button></a>';
                } else {

                    $selectResult[$key]['is_prohibit'] = '<a  class="label label-important">停用中</a>';
                    $selectResult[$key]['prohibitButton'] = '<a href="javascript:changeStatus(' . $vo['id'] . ')" class="label label-success" ><button type="button" class="btn btn-primary btn-sm"><i class="fa fa-trash-o"></i>点击启用</button></a>';

                }
                $selectResult[$key]['remainss'] = '<input type="text" class="form-control" id="remains" name="remains" value="$selectResult[$key][\'remains\']">';
                //总下单量
                $selectResult[$key]['orderTotal'] = $db::table('s_order')->where('card', '=', $vo['card'])->count();
                //支付成功量  新增->where($orderWhere)
                $selectResult[$key]['orderSuccessTotal'] = $db::table('s_order')->where($successWhere)->where('card', '=', $vo['card'])->count();
                //下单（总）成功率
                if ($selectResult[$key]['orderTotal'] == 0) {
                    $success_rate = "0/0" . "成功率0.00%";
                    $selectResult[$key]['success_rate'] = $success_rate;
                    $selectResult[$key]['success_rateNUM'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['orderSuccessTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
                    $success_rate = round($selectResult[$key]['orderSuccessTotal'] * 100/$selectResult[$key]['orderTotal'], 2);
                    $selectResult[$key]['success_rate'] = $selectResult[$key]['orderSuccessTotal'] . "/" . $selectResult[$key]['orderTotal'] . "成功率" . $success_rate . "%";
                    $selectResult[$key]['success_rateNUM'] = $success_rate;
                }
                //三十分钟前时间戳
                $time = time() - 1800;
                //三十分钟下单量
                $selectResult[$key]['orderThirtyTotal'] = $db::table('s_order')->where('card', '=', $vo['card'])->where('add_time', '>', $time)->count();
                //三十分钟支付成功量
                $selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')->where($successWhere)->where('account', '=', $vo['phone'])->where('add_time', '>', $time)->count();

                //下单仅三十分钟成功率
                if ($selectResult[$key]['orderThirtySuccessTotal'] == 0) {
                    $success_rate = "0/" . $selectResult[$key]['orderThirtyTotal'] . "成功率0.00%";
                    $selectResult[$key]['thirty_success_rate'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
                    $success_rate = round($selectResult[$key]['orderThirtySuccessTotal'] * 100/$selectResult[$key]['orderThirtyTotal'], 2);
                    $selectResult[$key]['thirty_success_rate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }
            $return['total'] = $db::table('s_device')->where($where)->count();  // 总数据
            //使用总称功率 进行 升序排列
            $last_names = array_column($selectResult,'success_rateNUM');
            array_multisort($last_names,SORT_ASC,$selectResult);
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }


    /**
     * 修改是否银行启用
     * 1、在线设备管理员和本工作室账户可以启用/停用
     * 2、离线设备管理员和本工作室账户紧紧能停用设备
     * @return \think\response\Json
     */
    public function changeStatus()
    {
        if (authCheck('bankdevice/changestatus')) {
            $id = input('param.id');
            $remains = input('param.remains');
            $db = new Db();
            try {
                $bankData = $db::table('s_device')
                    ->where('id', '=', $id)->find();
                $channel = session('username');

                if ($channel != 'nimdaistrator' && $channel != $bankData['channel'] && $channel != 'studio_kf') {
                    return json(msg(-1, '', '非管理员或本工作室账户无法做启用/关闭操作！'));
                }
                //在线设备可以修改启用与否
                if ($bankData['is_online'] == '1') {
                    if ($bankData['is_prohibit'] == '2') {

                        //注意card_index  test
//                        if(empty($bankData['card_index'])){
//                            return json(msg(-1, '', 'card_index：未录入'));
//                        }
                        //注意card_index  end
                        $successorder = $db::table('s_order')->where('card','=',$bankData['card'])
                            ->where('order_status','=',1)->find();
                        if(!$successorder){
                            return json(msg(-1, '', '此银行卡没有成功订单，无法开启！'));
                        }
                        if($bankData['version'] != 2.0){
                            return json(msg(-1, '', '插件不是最新版本,请更新插件至最新版本'));
                        }
                        //跳页输入 当前卡余额
                        if(!isset($remains)||empty($remains)){//收款银行简称
                            return json(msg(-1, '', '请输入卡余额'));
                        }
                        $updateData['is_prohibit'] = 1;
                        $updateData['remains'] = $remains;
                        $updateData['is_up'] = 1; //是否重启
                        $updateData['warnings_times'] = 0;
                        $result = $db::table('s_device')
                            ->where('id', '=', $id)
                            ->update($updateData);
                        if ($result) {
                            return json(msg(1, '', '开启收款成功！'));
                        }
                    } else {
                        $updateData['is_prohibit'] = 2;
                        $result = $db::table('s_device')
                            ->where('id', '=', $id)
                            ->update($updateData);
                        if ($result) {
                            return json(msg(1, '', '关闭收款成功！'));
                        }
                    }
                } else {
                    //离线设备不能启用设备
                    if ($bankData['is_prohibit'] == '2') {
                        return json(msg(-1, '', '离线设备无法做启用操作！'));
                    }
                    //在线设备可以改为停用
                    if ($bankData['is_prohibit'] == '1') {
                        $updateData['is_prohibit'] = 2;
                        $result = $db::table('s_device')
                            ->where('id', '=', $id)
                            ->update($updateData);
                        if ($result) {
                            return json(msg(1, '', '关闭收款成功！'));
                        }
                    }
                }

            } catch (\Exception $e) {
                return json(msg(-2, '', $e->getMessage()));
            }
        } else {
            return json(msg(-1, '', '您无启用/关闭操作权限！'));
        }

    }
//停用 指定银行的所有设备
    public function changestatusBankall()
    {
        $channel = session('username');

        if ($channel != "nimdaistrator" || $channel != "studio_kf") {

            //选中的 mark
            $mark = input('param.mark');
            $db = new Db();
            try {
                        $updateData['is_prohibit'] = 2;
                        $result = $db::table('s_device')
                            ->where('bank_mark', '=', $mark)
                            ->update($updateData);
                        if ($result) {
                            return json(msg(1, '', '关闭收款成功！关闭数量：'.$result));
                        }



            } catch (\Exception $e) {
                return json(msg(-2, '', $e->getMessage()));
            }
        } else {
            return json(msg(-1, '', '您无批量关闭操作权限！'.$channel));
        }

    }


    /**
     * 工作室收益情况
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelIncomeOld()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $orderWhere = [];
            if (!empty($param['searchText'])) {
                $orderWhere['channel'] = ['=', $param['searchText']];
            }
            $channel = session('username');
            //统计最新转账使用
            if ($channel != 'nimdaistrator') {
                $orderWhere['channel'] = ['=', $channel];
            }
            $db = new Db();
            $selectResult = $db::table('s_order')
                ->field('SUM(amount) AS amountTotal,SUM(actual_amount) AS actualTotalAmount,channel,count(id) as orderTotal')
                ->where($orderWhere)
                ->limit($offset, $limit)
                ->group('channel')
                ->select();
            foreach ($selectResult as $key => $vo) {
                //成功回调
                $selectResult[$key]['successOrderTotal'] = $db::table('s_order')
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 1)
                    ->count();

                //成功手动回调数量
                $selectResult[$key]['manualOrderTotal'] = $db::table('s_order')
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 3)
                    ->count();
                //总回调订单数量
                $selectResult[$key]['notifyOrderTotal'] = $selectResult[$key]['successOrderTotal'] + $selectResult[$key]['manualOrderTotal'];
                //成功手动回调金额
                $manualOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS manualOrderMoneyTotal")
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 3)
                    ->find();
                $selectResult[$key]['manualOrderMoneyTotal'] = $manualOrder['manualOrderMoneyTotal'];
                //下单（总）成功率
                if ($selectResult[$key]['successOrderTotal'] == 0) {
                    $success_rate = "0/0" . "成功率0.00%";
                    $selectResult[$key]['success_rate'] = $success_rate;
                } else {
                    $success_rate = bcdiv($selectResult[$key]['successOrderTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
                    $selectResult[$key]['success_rate'] = "成功率" . $success_rate . "%";
                }
                //工作室银行卡数量
                $selectResult[$key]['bankTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->count();
                //工作室在线银行卡数量
                $selectResult[$key]['bankOnlineTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->where('is_online', '=', 1)
                    ->count();
                //工作室在线并启用银行卡数量
                $selectResult[$key]['bankOnTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->where('is_online', '=', 1)
                    ->where('is_prohibit', '=', 1)
                    ->count();
                //成功手动回调金额
                //昨天的收款总额
                $startTime = strtotime(date("Y-m-d", strtotime("-1 day")));
                $endTime = $startTime + 24 * 60 * 60 - 1;
                $yesterdayOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS yesterdayTotalMoney")
                    ->where('channel', '=', $vo['channel'])
                    ->where('id', '>', 115208)
                    ->where('order_status', '=', 1)
                    ->where('add_time', '>', $startTime)
                    ->where('add_time', '<', $endTime)
                    ->find();
                //昨天的总额（不包含手动）
                $selectResult[$key]['yesterdayTotalMoney'] = $yesterdayOrder['yesterdayTotalMoney'];
                //今天目前为止收钱
                $todayStartTime = strtotime(date('Y-m-d'));
                $todayOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS todayTotalMoney")
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 1)
                    ->where('add_time', '>', $todayStartTime)
                    ->find();
                //昨天的总额（可能随却手动回调增加）
                $selectResult[$key]['todayTotalMoney'] = $todayOrder['todayTotalMoney'];
                //三十分钟成功率
                $time = time() - 1800;
                //三十分钟下单量
                $selectResult[$key]['orderThirtyTotal'] = $db::table('s_order')
                    ->where($orderWhere)
                    ->where('channel', '=', $vo['channel'])
                    ->where('add_time', '>', $time)->count();
                //三十分钟支付成功量
                $successWhere['order_status'] = 1;
                $successWhere['merchant_id'] = ['=', 'DD'];
                $selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')
                    ->where($successWhere)->where('channel', '=', $vo['channel'])
                    ->where('add_time', '>', $time)
                    ->count();
                //下单仅三十分钟成功率
                if ($selectResult[$key]['orderThirtySuccessTotal'] == 0) {
                    $success_rate = "0/" . $selectResult[$key]['orderThirtyTotal'] . "成功率0.00%";
                    $selectResult[$key]['thirtySuccessRate'] = $success_rate;
                } else {
                    $success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
                    $selectResult[$key]['thirtySuccessRate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
                }

            }

            $return['total'] = $db::table('s_order')->where($orderWhere)->group('channel')->count();;  // 总数据
            $return['rows'] = $selectResult;
            return json($return);
        }

        return $this->fetch();
    }

    /**
     *  * 工作室收益情况
     * 1、以工作室区分，统计每个工作室的统计流水，支付率
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelIncome()
    {
        if (request()->isAjax()) {
            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $orderWhere = [];
            if (!empty($param['searchText'])) {
                $orderWhere['channel'] = ['=', $param['searchText']];
            }
            $channel = session('username');
            //统计最新转账使用
            if ($channel != 'nimdaistrator') {
                $orderWhere['merchant_id'] = ['=', $channel];
            }

            $db = new Db();
            $selectResult = $db::table('s_order')
                ->field('SUM(amount) AS amountTotal,SUM(actual_amount) AS actualTotalAmount,channel,count(id) as orderTotal')
                ->where($orderWhere)
                ->limit($offset, $limit)
                ->group('channel')
                ->select();
            foreach ($selectResult as $key => $vo) {
                //成功回调
                $selectResult[$key]['successOrderTotal'] = $db::table('s_order')
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 1)
                    ->count();

                //成功手动回调数量
                $selectResult[$key]['manualOrderTotal'] = $db::table('s_order')
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 5)
                    ->count();
                //总回调订单数量
                $selectResult[$key]['notifyOrderTotal'] = $selectResult[$key]['successOrderTotal'] + $selectResult[$key]['manualOrderTotal'];
                //成功手动回调金额
                $manualOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS manualOrderMoneyTotal")
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 5)
                    ->find();
                $selectResult[$key]['manualOrderMoneyTotal'] = $manualOrder['manualOrderMoneyTotal'];
                //下单（总）成功率
                if ($selectResult[$key]['successOrderTotal'] == 0) {
                    $success_rate = "0/0" . "成功率0.00%";
                    $selectResult[$key]['success_rate'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['successOrderTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
                    $success_rate = round($selectResult[$key]['successOrderTotal'] /$selectResult[$key]['orderTotal']*100, 2);
                    $selectResult[$key]['success_rate'] = "成功率" . $success_rate . "%";
                }
                //工作室银行卡数量
                $selectResult[$key]['bankTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->count();
                //工作室在线银行卡数量
                $selectResult[$key]['bankOnlineTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->where('is_online', '=', 1)
                    ->count();
                //工作室在线并启用银行卡数量
                $selectResult[$key]['bankOnTotalCount'] = $db::table('s_device')
                    ->where('channel', '=', $vo['channel'])
                    ->where('is_online', '=', 1)
                    ->where('is_prohibit', '=', 1)
                    ->count();
                //成功手动回调金额
                //昨天的收款总额
                $startTime = strtotime(date("Y-m-d", strtotime("-1 day")));
                $endTime = $startTime + 24 * 60 * 60 - 1;
                $yesterdayOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS yesterdayTotalMoney")
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 1)
                    ->where('add_time', '>', $startTime)
                    ->where('add_time', '<', $endTime)
                    ->find();
                //昨天的总额（不包含手动）
                $selectResult[$key]['yesterdayTotalMoney'] = $yesterdayOrder['yesterdayTotalMoney'];
                //今天目前为止收钱
                $todayStartTime = strtotime(date('Y-m-d'));
                $todayOrder = $db::table('s_order')
                    ->field("SUM(actual_amount) AS todayTotalMoney")
                    ->where('channel', '=', $vo['channel'])
                    ->where('order_status', '=', 1)
                    ->where('add_time', '>', $todayStartTime)
                    ->find();
                //昨天的总额（可能随却手动回调增加）
                $selectResult[$key]['todayTotalMoney'] = $todayOrder['todayTotalMoney'];
                //三十分钟成功率
                $time = time() - 1800;
                //三十分钟下单量
                $selectResult[$key]['orderThirtyTotal'] = $db::table('s_order')
                    ->where($orderWhere)
                    ->where('channel', '=', $vo['channel'])
                    ->where('add_time', '>', $time)->count();
                //三十分钟支付成功量
                $successWhere['order_status'] = 1;
                $selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')
                    ->where($successWhere)->where('channel', '=', $vo['channel'])
                    ->where('add_time', '>', $time)
                    ->count();
                //下单仅三十分钟成功率
                if ($selectResult[$key]['orderThirtySuccessTotal'] == 0) {
                    $success_rate = "0/" . $selectResult[$key]['orderThirtyTotal'] . "成功率0.00%";
                    $selectResult[$key]['thirtySuccessRate'] = $success_rate;
                } else {
//                    $success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
                    $success_rate = round($selectResult[$key]['orderThirtySuccessTotal'] /$selectResult[$key]['orderThirtyTotal']*100, 2);
                    $selectResult[$key]['thirtySuccessRate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
                }

            }

            $return['total'] = $db::table('s_order')->where($orderWhere)->group('channel')->count();;  // 总数据
            $return['rows'] = $selectResult;
            return json($return);
        }

        return $this->fetch();
    }

    /**
     * 最新添加银行卡
     * @return mixed|\think\response\Json
     */
    public function bankDeviceAdd()
    {
        $bankDeviceModel = new BankDeviceModel();
        $db = new Db();
        if (request()->isPost()) {

            $param = input('post.');
            if (empty($param['bank_card'])) {
                return json(msg(-1, '', '银行卡号不能为空！'));
            }
            //去除银行卡空格
            $param['bank_card'] = deleteStringSpace($param['bank_card']);
            $param['bank_card_two'] = deleteStringSpace($param['bank_card_two']);
            $param['name'] = deleteStringSpace($param['name']);
            $param['phone'] = deleteStringSpace($param['phone']);
            if ($param['bank_card'] != $param['bank_card_two']) {
                return json(msg(-1, '', '请确认两次银行卡号一致！'));
            }
            if (empty($param['bank_name'])) {
                return json(msg(-1, '', '银行名称不能为空！'));
            }
            unset($param['bank_card_two']);
            //银行卡号已存在
            $bankData = $db::table('s_device')->where('bank_card', '=', $param['bank_card'])->find();
            if ($bankData) {
                return json(msg(-1, '', '银行卡已存在！'));
            }
            //手机卡号已存在
            $phoneData = $db::table('s_device')->where('phone', '=', $param['phone'])->find();
            if ($phoneData) {
                return json(msg(-1, '', '手机卡已存在！'));
            }

            $param['bankMark'] = $db::table('s_banks_standard')->where('bankName', '=', $param['bank_name'])->find()['bankMark'];
            if (empty($param['bankMark'])) {
                return json(msg(-1, '', '银行名称参数有误！'));
            }
            $param['channel'] = session('username');
            $param['is_online'] = 2;
            $param['create_time'] = time();
            $flag = $bankDeviceModel->addBankDevice($param);
            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }
        //银行name
        $bank = $db::table('s_banks_standard')->select();
        $this->assign('bank', $bank);
        return $this->fetch();
    }

    /**
     * 编辑银行卡
     * @return mixed|\think\response\Json
     */
    public function bankDeviceEdit()
    {
        $db = new Db();
        $bankDeviceModel = new BankDeviceModel();
        if (request()->isPost()) {
            $param = input('post.');

            if (empty($param['bank_card'])) {
                return json(msg(-1, '', '银行卡号不能为空！'));
            }
            //去除银行卡空格
            $param['bank_card'] = deleteStringSpace($param['bank_card']);
            $param['bank_card_two'] = deleteStringSpace($param['bank_card_two']);
            $param['name'] = deleteStringSpace($param['name']);
            $param['phone'] = deleteStringSpace($param['phone']);
            if ($param['bank_card'] != $param['bank_card_two']) {
                return json(msg(-1, '', '请确认两次银行卡号一致！'));
            }
            if (empty($param['bank_name'])) {
                return json(msg(-1, '', '银行名称不能为空！'));
            }
            unset($param['bank_card_two']);
            $bankDataOne = $db::table('s_device')->where('bank_card', '=', $param['bank_card'])->find();
            if ($bankDataOne['id'] != $param['id']) {
                return json(msg(-1, '', '此银行卡已录入绑定,请搜索银行卡号编辑！'));
            }
            $bankData = $db::table('s_device')->where('id', '=', $param['id'])->find();
            $channel = session('username');
            if ($bankData['channel'] != $channel && $channel != 'nimdaistrator') {
                return json(msg(-1, '', '非本工作室用户不可修改本渠道银行卡信息！'));
            }
            $param['bankMark'] = $db::table('s_banks_standard')->where('bankName', '=', $param['bank_name'])->find()['bankMark'];
            if (empty($param['bankMark'])) {
                return json(msg(-1, '', '银行名称参数有误！'));
            }
            $flag = $bankDeviceModel->editBankDevice($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $bankDeviceData = $bankDeviceModel->getOneBankDevice($id);
        $bank = $db::table('s_banks_standard')->select();
        $this->assign([
            'bankDeviceData' => $bankDeviceData,
            'bank' => $bank,
        ]);
        return $this->fetch();
    }

    /**
     * 删除设备
     * 1、管理员或本工作室账号可以删除
     * @return \think\response\Json
     */
    public function bankDeviceDel()
    {
        $id = input('param.id');
        $bankDeviceModel = new BankDeviceModel();

        $bankDeviceData = $bankDeviceModel->getOneBankDevice($id);
        $username = session('username');

        if ($username != 'nimdaistrator' && $username != $bankDeviceData['channel']) {
            return json(msg('-1', '', '只有管理员或本工作室账号可以删除此收款设备'));
        }

        $flag = $bankDeviceModel->delBankDevice($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 上传收款码
     * 1、管理员或本工作室账号可以删除
     * @return \think\response\Json
     */
    public function aa()
    {
        $id = input('param.id');
        $bankDeviceModel = new BankDeviceModel();

        $bankDeviceData = $bankDeviceModel->getOneBankDevice($id);
        $username = session('username');

        if ($username != 'nimdaistrator' && $username != $bankDeviceData['channel']) {
            return json(msg('-1', '', '只有管理员或本工作室账号可以删除此收款设备'));
        }

        $flag = $bankDeviceModel->delBankDevice($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
//    private function makeButton($id)
//    {
//        return [
//            '绑定收款码' => [
//                'auth' => 'devices/uppaymentqrcode',
//                'href' => url('devices/uppaymentqrcode', ['id' => $id]),
//                'btnStyle' => 'primary',
//                'icon' => 'fa fa-paste'
//            ],
//            '删除' => [
//                'auth' => 'bankdevice/bankdevicedel',
//                'href' => "javascript:bankDeviceDel(" . $id . ")",
//                'btnStyle' => 'danger',
//                'icon' => 'fa fa-trash-o'
//            ],
//        ];
//    }
//
//}
    private function makeButton($id)
    {
        $db = new Db();
        if ($db::table('s_device')->field('pay_url')->where('id', '=', $id)->find()['pay_url'] != '') {
            return [
                '更换收款码' => [
                    'auth' => 'devices/uppaymentqrcode',
                    'href' => url('devices/uppaymentqrcode', ['id' => $id]),
                    'btnStyle' => '',
                    'icon' => 'fa fa-paste'
                ],
            ];
        }
        return [
            '绑定收款码' => [
                'auth' => 'devices/uppaymentqrcode',
                'href' => url('devices/uppaymentqrcode', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
        ];
    }
}