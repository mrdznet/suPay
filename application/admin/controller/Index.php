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
namespace app\admin\controller;

use app\admin\model\NodeModel;
use think\Db;
use think\Request;
class Index extends Base
{
    public function index()
    {
        // 获取权限菜单
        $node = new NodeModel();

        $this->assign([
            'menu' => $node->getMenu(session('rule'))
        ]);

        return $this->fetch('/index');
    }

    /**
     * 后台默认首页
     * @return mixed
     */
    public function indexPage(Request $request)
    {
        $db = new Db();
        $message = $request->param();
        // 获取一个月内的数据
        $userwhere = [];
        if(isset($message['username']) && $message['username']!=''){
            $userwhere['channel'] = ['=',  $message['username'] ];
        }
        //获取一个月内每天的时间戳
        $thismonth = $this->dateDemo();
        $wheretimes = "";
        $data = [];
        foreach ($thismonth as $key=>$value){
            $starttime = $value['key'];
            $endtime = $value['key']+86400;
            $wheretimes = "finish_time>=".$starttime." and finish_time<".$endtime;
            $data[$value['date']]['total'] = $db::table('s_mcorder')
                ->where('transaction_status','=',1)
                ->where($wheretimes)
                ->where($userwhere)
                ->sum('transaction_amount');
            $data[$value['date']]['profit'] = $db::table('s_mcorder')
                ->where('transaction_status','=',1)
                ->where($wheretimes)
                ->where($userwhere)
                ->sum('profit');
        }
        //数据中包括总跑额 和总理润 在order表中计算

        $showData = [];
        foreach ($data as $key => $vo) {
            $showData['total'][] = $vo['total'];
            $showData['profit'][] = $vo['profit'];
            $showData['day'][] = $key;
        }

        $this->assign([
            'show_data' => json_encode($showData)
        ]);
        //计算总跑额
        $channelwhere = [];
        $userwhere = [];
        $tixianuserwhere = [];
        $merchantId = session('username');
        $this->assign('channel',$merchantId);
        if(isset($message['username']) && $message['username']!=''){
            $userwhere['channel'] = ['=',  $message['username'] ];
        }
        if($merchantId!='nimdaistrator'&&$merchantId!='agent_total'){//1bA8
            $channelwhere['channel'] = ['=',  $merchantId ];
            $tixianuserwhere['user_name'] = ['=',  $merchantId ];

        }
        $wheretime = "";
        if (!empty($message['starttime']) && !empty($message['endtime'])) {
            $starttime = strtotime($message['starttime']);
            $endtimes = strtotime($message['endtime']);
            $endtime = $endtimes+86400;
            $wheretime = "finish_time>=".$starttime." and finish_time<".$endtime;
        }
        $username = '';
        if(isset($message['username'])){
            $username = $message['username'];
            $tixianuserwhere['user_name'] = ['=',  $message['username'] ];
        }
        $this->assign('username',$username);

        $Totalamount = $db::table('s_mcorder')
                        ->where('transaction_status','=',1)
                        ->where($wheretime)
                        ->where($userwhere)
                        ->where($channelwhere)
                        ->sum('transaction_amount');
//        echo "跑量". $db::table('s_mcorder')->getLastSql();
        //计算利润
        $Totalprofit = $db::table('s_mcorder')
            ->where('transaction_status','=',1)
            ->where($wheretime)
            ->where($userwhere)
            ->where($channelwhere)
            ->sum('profit');
//        echo "利润". $db::table('s_mcorder')->getLastSql();
        //2查询此人已提现金额 s_withdrawalorder
        $Repaid = $db::table('s_withdrawalorder')
            ->where($tixianuserwhere)
            ->sum('withdrawalamount');
        //未回款 总跑两+利润-已汇款
        $Totalamount1 = $db::table('s_mcorder')
            ->where('transaction_status','=',1)
            ->where($userwhere)
            ->where($channelwhere)
            ->sum('transaction_amount');
        $Unpaid = $Totalamount1-$Repaid;
        if(!empty($message['starttime']) && !empty($message['endtime'])){
            $endtime = $message['endtime'];
            $starttime = $message['starttime'];
        }else{
            $starttime = date("Y-m-d",time());
            $endtime = date("Y-m-d",time());
        }
        $this->assign('starttime',$starttime);
        $this->assign('endtime',$endtime);
        $this->assign('Totalamount',$Totalamount);//跑量
        $this->assign('Totalprofit',$Totalprofit);//利润
        $this->assign('Repaid',$Repaid);//已回款
        $this->assign('Unpaid',$Unpaid);//未回款
        return $this->fetch('index');
    }

    public function dateDemo(){
        //1获取当天日期的时间
        $today =  date('Y-m-d',time());
        $todaychuo = strtotime($today);
        //2获取一个月之前的时间戳
        $lastmonth =  date("Y-m-d",strtotime("-1 month"));
        $lastmonthchuo = strtotime($lastmonth);
        //循环次数
        $foreach = ($todaychuo-$lastmonthchuo)/60/60/24;
        $days = [];
        for ($i=0; $i<=$foreach; $i++){
            $days[$i]['key'] = $lastmonthchuo;
            $days[$i]['date'] = date("m-d",$lastmonthchuo);
            $lastmonthchuo+=86400;
        }
        return $days;
    }
}
