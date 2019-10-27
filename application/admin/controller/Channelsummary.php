<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/24
 * Time: 22:39
 */
namespace app\admin\controller;

use think\Db;

class Channelsummary extends Base
{
	/**
	 * 最新银行卡列表
	 * @return mixed|\think\response\Json
	 */
	public function index()
	{
		if(request()->isAjax()){
			$param = input('param.');

			$limit = $param['pageSize'];
			$offset = ($param['pageNumber'] - 1) * $limit;

			$where = [];
			if (!empty($param['searchText'])) {
				$where['bank_card'] = ['=',  $param['searchText'] ];
			}
			$channel = session('username');
			//统计最新转账使用
			if($channel!= "nimdaistrator"){
				$orderWhere['merchant_id'] = ['=','DD'];
			}
			$orderWhere['id'] = ['>',16932];
			//订单成功
			$successWhere['order_status'] = 1;

            if($channel!= "nimdaistrator"&&$channel!='dd'){
				$where['channel'] = ['=',  $channel ];
			}
			$db = new Db();
			$selectResult = $db::table('s_ali_account')
				->field('day_money,total_money,account_app_id,account,app_id,app_description,create_time,use_times,use_time_phase')
				->where($where)->limit($offset, $limit)->order('status asc')
				->group('channel')
				->select();

			foreach($selectResult as $key=>$vo){

				$selectResult[$key]['create_time'] = date('Y-m-d H:i:s',$selectResult[$key]['create_time']);

				if( !empty($selectResult[$key]['status']) &&$selectResult[$key]['is_online']=='1'){
					$selectResult[$key]['status']= '<span  class="label label-success">在线</span>';
				}else {
					$selectResult[$key]['status']= '<span  class="label label-important">停用中</span>';
				}
				//总下单量
				$selectResult[$key]['orderTotal'] = $vo['use_times'];
				//支付成功量  新增->where($orderWhere)  id  115208
				$selectResult[$key]['orderSuccessTotal'] = $db::table('s_f_order')->where($successWhere)->where($orderWhere)->where('app_id','=',$vo['app_id'])->count();
				//下单（总）成功率
//				if ($selectResult[$key]['orderTotal'] == 0) {
//					$success_rate = "0/0" . "成功率0.00%";
//					$selectResult[$key]['success_rate'] = $success_rate;
//				} else {
//					$success_rate = bcdiv($selectResult[$key]['orderSuccessTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
//					$selectResult[$key]['success_rate'] = $selectResult[$key]['orderSuccessTotal'] . "/" . $selectResult[$key]['orderTotal'] . "成功率" . $success_rate . "%";
//				}
				//三十分钟前时间戳
				$time = time() - 1800;
				//三十分钟下单量
				$selectResult[$key]['orderThirtyTotal'] = $db::table('s_order')->where($orderWhere)->where('app_id','=',$vo['app_id'])->where('add_time','>',$time)->count();
				//三十分钟支付成功量
				$selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')->where($successWhere)->where('app_id','=',$vo['app_id'])->where('add_time','>',$time)->count();
				//下单仅三十分钟成功率
				if ($selectResult[$key]['orderThirtySuccessTotal'] == 0) {
					$success_rate = "0/".$selectResult[$key]['orderThirtyTotal']."成功率0.00%";
					$selectResult[$key]['thirty_success_rate'] = $success_rate;
				} else {
					$success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
					$selectResult[$key]['thirty_success_rate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
				}
				$selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
			}
			$return['total'] = $db::table('s_bank_device')->where($where)->count();  // 总数据
			$return['rows'] = $selectResult;

			return json($return);
		}
		return $this->fetch();
	}

	/**
	 * 工作室收益情况
	 * 1、以工作室区分，统计每个工作室的统计流水，支付率
	 * @return mixed|\think\response\Json
	 */
	public function channelIncome()
	{
		if(request()->isAjax()){
			$param = input('param.');

			$limit = $param['pageSize'];
			$offset = ($param['pageNumber'] - 1) * $limit;

			$accountWhere = [];
			if (!empty($param['searchText'])) {
				$accountWhere['channel'] = ['=',  $param['searchText'] ];
			}
			$channel = session('username');
			//统计最新转账使用
			if($channel!= "nimdaistrator"){
				$orderWhere['merchant_id'] = ['=','DD'];
//				$orderWhere['channel'] = ['=',$channel];
			}
			$db = new Db();

			$selectResult = $db::table('s_f_ali_account')
				->field('sum(total_money) AS amountTotal,sum(day_money) as todayTotalMoney,channel,count(id) as accountNumber')
				->where($accountWhere)
				->limit($offset, $limit)
				->group('channel')
				->select();
			foreach($selectResult as $key=>$vo){
				//收款启用中数量
				$selectResult[$key]['onlineTotalCount'] = $db::table('s_f_ali_account')
					->where('channel','=',$vo['channel'])
					->where('status','=',1)
					->count();
				//总订单量
				$selectResult[$key]['orderTotal'] = $db::table('s_f_order')
					->where('channel','=',$vo['channel'])
					->count();

				//成功回调
				$selectResult[$key]['successOrderTotal'] = $db::table('s_f_order')
					->where('channel','=',$vo['channel'])
					->where('order_status','=',1)
					->count();
				//成功手动回调数量
				$selectResult[$key]['manualOrderTotal'] = $db::table('s_f_order')
					->where('channel','=',$vo['channel'])
					->where('order_status','=',3)
					->count();
				//总回调订单数量
				$selectResult[$key]['notifyOrderTotal'] = $selectResult[$key]['successOrderTotal'] + $selectResult[$key]['manualOrderTotal'];

				//下单（总）成功率
				if ($selectResult[$key]['successOrderTotal'] == 0) {
					$success_rate = "0/0" . "成功率0.00%";
					$selectResult[$key]['success_rate'] = $success_rate;
				} else {
					$success_rate = bcdiv($selectResult[$key]['successOrderTotal'] * 100, $selectResult[$key]['orderTotal'], 2);
					$selectResult[$key]['success_rate'] = "成功率" . $success_rate . "%";
				}

				//昨天的收款总额
				$startTime = date("Y/m/d",strtotime("-1 day"));
				if(empty($vo['channel'])){
					$channel = " ";
				}else{
					$channel = $vo['channel'];
				}
				$yesterdayOrder = $db::table('s_channel_calculation')
					->field("SUM(money) AS yesterdayTotalMoney")
					->where('channel','=',$channel)
					->where('time_info','=',$startTime)
					->find();
//				$sql = $db::table('s_channel_calculation')->getLastSql();
//				var_dump($sql);exit;
				//昨天的总额
				$selectResult[$key]['yesterdayTotalMoney'] = $yesterdayOrder['yesterdayTotalMoney'];
				//三十分钟成功率
				$time = time() - 1800;
				//三十分钟下单量
				$selectResult[$key]['orderThirtyTotal'] = $db::table('s_f_order')
					->where('channel','=',$vo['channel'])
					->where('add_time','>',$time)->count();
				//三十分钟支付成功量
				$successWhere['order_status'] = 1;
				$selectResult[$key]['orderThirtySuccessTotal'] = $db::table('s_order')
					->where($successWhere)
					->where('channel','=',$vo['channel'])
					->where('add_time','>',$time)
					->count();
				//下单仅三十分钟成功率
				if ($selectResult[$key]['orderThirtySuccessTotal'] == '0') {
					$success_rate = "0/".$selectResult[$key]['orderThirtyTotal']."成功率0.00%";
					$selectResult[$key]['thirtySuccessRate'] = $success_rate;
				} else {
					$success_rate = bcdiv($selectResult[$key]['orderThirtySuccessTotal'] * 100, $selectResult[$key]['orderThirtyTotal'], 2);
					$selectResult[$key]['thirtySuccessRate'] = $selectResult[$key]['orderThirtySuccessTotal'] . "/" . $selectResult[$key]['orderThirtyTotal'] . "成功率" . $success_rate . "%";
				}
			}
			$return['total'] = $db::table('s_f_ali_account')->where($accountWhere)->group('channel')->count();;  // 总数据
			$return['rows'] = $selectResult;
			return json($return);
		}

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
                'auth' => 'bankdevice/bankdeviceedit',
                'href' => url('bankdevice/bankdeviceedit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'bankdevice/bankdevicedel',
                'href' => "javascript:bankDeviceDel(" . $id . ")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ],
        ];
    }

}
