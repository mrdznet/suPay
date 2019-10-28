<?php
/**
 * 生成操作按钮
 * @param array $operate 操作按钮数组
 */
use think\Cookie;//Session
use think\Session;//Session
use think\Db;
use \GatewayWorker\Lib\Gateway;

function showOperate($operate = [])
{
    if(empty($operate)){
        return '';
    }

    $option = '';
    foreach($operate as $key=>$vo){
        if(authCheck($vo['auth'])){
            $option .= ' <a href="' . $vo['href'] . '"><button type="button" class="btn btn-' . $vo['btnStyle'] . ' btn-sm">'.
                '<i class="' . $vo['icon'] . '"></i> ' . $key . '</button></a>';
        }
    }

    return $option;
}

/**
 * 将字符解析成数组
 * @param $str
 */
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}

/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){

        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }

    return $res;
}

/**
 * 整理菜单住方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $param = objToArray($param);
    $parent = []; //父类
    $child = [];  //子类

    foreach($param as $key=>$vo){

        if(0 == $vo['type_id']){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $vo['href'] = url($vo['control_name'] .'/'. $vo['action_name']); //跳转地址
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){

            if($v['type_id'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);

    return $parent;
}

/**
 * 解析备份sql文件
 * @param $file
 */
function analysisSql($file)
{
    // sql文件包含的sql语句数组
    $sqls = array ();
    $f = fopen ( $file, "rb" );
    // 创建表缓冲变量
    $create = '';
    while ( ! feof ( $f ) ) {
        // 读取每一行sql
        $line = fgets ( $f );
        // 如果包含空白行，则跳过
        if (trim ( $line ) == '') {
            continue;
        }
        // 如果结尾包含';'(即为一个完整的sql语句，这里是插入语句)，并且不包含'ENGINE='(即创建表的最后一句)，
        if (! preg_match ( '/;/', $line, $match ) || preg_match ( '/ENGINE=/', $line, $match )) {
            // 将本次sql语句与创建表sql连接存起来
            $create .= $line;
            // 如果包含了创建表的最后一句
            if (preg_match ( '/ENGINE=/', $create, $match )) {
                // 则将其合并到sql数组
                $sqls [] = $create;
                // 清空当前，准备下一个表的创建
                $create = '';
            }
            // 跳过本次
            continue;
        }

        $sqls [] = $line;
    }
    fclose ( $f );

    return $sqls;
}


/*
 * 对象转换成数组
 * @param $obj
 */
function objToArray($obj)
{
    return json_decode(json_encode($obj), true);
}

/**
 * 统一返回信息
 * @param $code
 * @param $data
 * @param $msge
 */
function msg($code, $data, $msg)
{
    return compact('code', 'data', 'msg');
}


/**
 * 统一返回信息
 * @param $code
 * @param $data
 * @param $msge
 */
function msgs($code, $data, $msg)
{
    return compact('code', 'data', 'msg');
}


/**
 * 权限检测
 * @param $rule
 */
function authCheck($rule)
{
    $control = explode('/', $rule)['0'];
    if(in_array($control, ['login', 'index'])){
        return true;
    }
    if(in_array($rule, cache(session('role_id')))){
        return true;
    }

    return false;
}



/**
 * 整理出tree数据 ---  layui tree
 * @param $pInfo
 * @param $spread
 */
function getTree($pInfo, $spread = true)
{

    $res = [];
    $tree = [];
    //整理数组
    foreach($pInfo as $key=>$vo){

        if($spread){
            $vo['spread'] = true;  //默认展开
        }
        $res[$vo['id']] = $vo;
        $res[$vo['id']]['children'] = [];
    }
    unset($pInfo);

    //查找子孙
    foreach($res as $key=>$vo){
        if(0 != $vo['pid']){
            $res[$vo['pid']]['children'][] = &$res[$key];
        }
    }

    //过滤杂质
    foreach( $res as $key=>$vo ){
        if(0 == $vo['pid']){
            $tree[] = $vo;
        }
    }
    unset( $res );

    return $tree;
}
function cUrlGetData($url, $post_fields = null, $headers = null) {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($post_fields && !empty($post_fields)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    }
    if ($headers && !empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $data = curl_exec($ch);
    /*if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }*/
    curl_close($ch);
    return $data;
}
//生成验证码
function create_code(){

    $arr=array_merge(range('a','b'),range('A','B'),range('0','9'));
    shuffle($arr);
    $arr=array_flip($arr);
    $arr=array_rand($arr,8);
    $res='';
    foreach ($arr as $v){
        $res.=$v;
    }
    return $res;

}
//保存并发送验证码
/**
 * @param $user
 */
function setcode($user,$chat_id){
    $code = create_code();
    $text = $user.'的验证码：'.$code;
//    Cookie::('code', $code);
    Session::set('code',$code);
    $sendData = [
        'chat_type' => 1,
        'chat_id' => $chat_id,//小M20054587，群：10319082 10437005
        'text' => $text,
    ];
    $headers = [
        'Content-Type:application/json'
    ];
    $result = cUrlGetData('http://18.138.140.45:8000/10109402:7NO03MAYBYj1TyJ8NpFKc951/sendTextMessage', json_encode($sendData), $headers);

}
//预创建
function preCreates($account,$remarks,$money){
    $db = new Db;
    $res = $db::table('s_pre')->insert(['alinumber'=>$account,'remarks'=>$remarks,'money'=>$money]);
    if($res == 1){
        return "success";
    }else{
        return "error";
    }
}
function preg_message($str){
    $pattern = [ //各个银行短信正则标准
        //平安银行
        '/^您尾号(\S+)于(\S+)日(\S+)付款业务转入人民币(\S+)元,存款账户余额人民币(\S+)元。详询95511-3【平安银行】/',
        //工商银行
        '/^您尾号(\S+)卡(\S+)日(\S+)工商银行收入\(他行汇入\)(\S+)元，余额(\S+)元。【工商银行】/',
        //建设银行
        '/^(\S+)月(\S+)分向您尾号(\S+)的储蓄卡账户电子汇入收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',
        '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)支付机构提现收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
        '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)支付机构提现收入人民币(\S+)元，活期余额(\S+)元\[建设银行]/',
        '/^您尾号(\S+)的储蓄卡账户(\S+)日(\S+)转账存入收入人民币(\S+)元,(\S+)\[建设银行]/',
        '/^(\S+)日(\S+)向您尾号(\S+)的储蓄卡账户转账收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
        '/^(\S+)日(\S+)向您尾号(\S+)的储蓄卡账户转账存入收入人民币(\S+),活期余额(\S+)元。\[建设银行]/',
        //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]   银行卡转帐
        '/^(\S+)月(\S+)分向您尾号(\S+)的储蓄卡账户手机银行转账收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',
        //招商
        '/^您账户(\S+)于(\S+)月(\S+)日收到人民币(\S+)\（\S+。[招商银行]\S+/',
        '/^您账户(\S+)于(\S+)月(\S+)收款人民币(\S+)，备注：(\S+)\[招商银行]/',
//        /*中国农业银行*/
        '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(\S+)，/',
        '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(\S+)。/',
        '/^【中国农业银行】(\S+)于(\S+)日(\S+)完成网银转账交易人民币(\S+)。/',
        '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成工资交易人民币(\S+)，余额(\S+)。/',
        '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成支付宝发交易人民币(\S+)，/',
        '/^【中国农业银行】(\S+)账户(\S+)日(\S+)完成代付交易人民币(\S+)，/',
        '/^【中国农业银行】财付通支付科技有限公司于(\S+)日(\S+)向您尾号(\S+)账户完成银联入账交易人民币(\S+)，余额(\S+)。/',
        '/^【中国农业银行】(\S+)于(\S+)向您尾号(\S+)账户完成转存交易人民币(\S+)，余额(\S+)。/',
        '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成银联入账交易人民币(\S+)，余额(\S+)。/',
        '/^【中国农业银行】(\S+)于(\S+)向您尾号(\S+)账户完成银联入账交易人民币(\S+)，余额(\S+)。/',

        //浦发
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[互联汇入](\S+)，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[银联入账:余额宝]，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[转账到银行卡]，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[(\S+)支付宝转账]，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[支付宝(\S+)支付宝转\]，可用余额(\S+)。【浦发银行】/',

        //华夏银行：
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。【华夏银行】/',
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。银联入账。【华夏银行】/',
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。收到网联付款。【华夏银行】/',
        //光大银行：
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—商户名称:银联转账（云闪付）,付款方账号后四位:(\S+)。\[光大银行]/',
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—付款方姓名:(\S+),付款方账号后四位:(\S+)。\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+)：(\S+)存入(\S+)元，余额(\S+)，摘要：(\S+) (\S+)。逛阳光花园，礼遇618！【光大银行】/',
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+)：(\S+)存入(\S+)元，余额(\S+)，摘要：转账到银行卡。逛阳光花园，礼遇618！【光大银行】/',
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要：网银跨行汇款。逛阳光花园，礼遇618！【光大银行】/',
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—商户名称:财付通支付科技有限公司,付款方账号后四位:(\S+)。\[光大银行]/',

        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款。\[光大银行]/',
        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //杨雷向您尾号3844的账户10:18转入30元，余额为30.89元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向您尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。\[光大银行\]/',
        //梁盼盼向您尾号1349的账户15:48转入20元，余额为20元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买[光大银行]
        '/^(\S+)向您尾号(\S+)的账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买\[光大银行\]/',

        //广发
        '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(\S+)元。/',
        '/^【广发银行】您尾号(\S+)卡(\S+)日(\S+)收入人民币(\S+)元\(网银入账\)。/',
        //兴业银行
        '/^(\S+)日(\S+)账户(\S+)网联付款收入(\S+)元，余额(\S+)元\[兴业银行]/',
        '/^(\S+)日(\S+)账户(\S+)汇款汇入收入(\S+)元，余额(\S+)元。对方户名:(\S+)（转账汇款）\[兴业银行]/',
        //中原银行
        '/^【中原银行】您尾号(\S+)的卡(\S+)支付宝-支付宝（中国）网络技术有限公司转入；(\S+)支付宝转账；转账(\S+)元，可用余额(\S+)元。/',
        '/^【中原银行】您尾号(\S+)的卡(\S+)日(\S+)跨行网银转入(\S+)元，可用余额(\S+)元，对方户名：(\S+)。/',
        '/^【中原银行】您尾号(\S+)的卡(\S+)日(\S+)财付通-财付通支付科技有限公司转入；转账到银行卡；转账(\S+)元，可用余额(\S+)元。/',

        // 邮政银行
        //【邮储银行】19年07月05日16:14您尾号874账户提现金额12.00元，余额12.00元。
        //支付宝余额
        //支付宝余额宝
        //【邮储银行】19年07月05日16:18您尾号874账户提现金额12.00元，余额36.00元。
        //【邮储银行】19年07月05日16:15您尾号874账户提现金额12.00元，余额24.00元。
        '/^【邮储银行】(\S+)日(\S+)您尾号(\S+)账户提现金额(\S+)元，余额(\S+)元/',
        //微信转帐
        //【邮储银行】19年07月05日16:20张亚伟账户8847向您尾号874账户他行来账金额12.00元，余额48.00元。
        '/^【邮储银行】(\S+)账户(\S+)向您尾号(\S+)账户他行来账金额(\S+)元，余额(\S+)元/',

    ];
    foreach ($pattern as $key => $value){
//        dump($value);
        preg_match($value,$str,$match);
        if(!empty($match)){
                $amount = $match[4];
                $match[4] = str_replace(',', '', $amount) ;//去除金额中的逗号
//            else if (substr($match[0],-9) == '设银行'){
//                $match[3] = 1;
//                $aa = str_replace('收入人民币', '', strtok($match[2], ',')) ;
//                $match[4] =str_replace('元', '', $aa) ;
//            }
            return $match;
        }
    }
}
function preg_message1($str){
    $pattern = [ //各个银行短信正则标准
        //平安银行
        '/^您尾号(\S+)于(\S+)日(\S+)付款业务转入人民币(\S+)元,存款账户余额人民币(\S+)元。详询95511-3【平安银行】/',
        //工商银行
        //您尾号9757卡6月28日10:07工商银行收入(他行汇入)11元，余额55元。【工商银行】
        '/^您尾号(\S+)卡(\S+)日(\S+)工商银行收入\(他行汇入\)(\S+)元，余额(\S+)元。【工商银行】/',
        //您尾号9757卡6月28日10:05网上银行收入(刘玉贵支付宝转)11元，余额11元。【工商银行】
        '/^您尾号(\S+)卡(\S+)网上银行收入\((\S+)支付宝转\)(\S+)元，余额(\S+)元。【工商银行】/',
        //您尾号9757卡6月28日10:06快捷支付收入(转账到银行卡财付通)11元，余额33元。【工商银行】
        '/^您尾号(\S+)卡(\S+)日(\S+)快捷支付收入\(转账到银行卡财付通\)(\S+)元，余额(\S+)元。【工商银行】/',
        //您尾号9757卡6月28日10:05网上银行收入(张威支付宝转帐)11元，余额11元。【工商银行】
        '/^您尾号(\S+)卡(\S+)网上银行收入\((\S+)支付宝转帐\)(\S+)元，余额(\S+)元。【工商银行】/',
        //您尾号7768卡7月5日09:02网上银行收入(转账到银行卡)10元，余额20元。【工商银行】
        '/^您尾号(\S+)卡(\S+)日(\S+)网上银行收入\(转账到银行卡\)(\S+)元，余额(\S+)元。【工商银行】/',
        //您尾号7768卡7月5日09:08手机银行收入(网转)10元，余额30元。【工商银行】
        '/^您尾号(\S+)卡(\S+)日(\S+)手机银行收入\(网转\)(\S+)元，余额(\S+)元。【工商银行】/',
        //建设银行
        '/^(\S+)月(\S+)分向您尾号(\S+)的储蓄卡账户电子汇入收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',
        //您尾号6678的储蓄卡账户6月24日20时7分支付机构提现收入人民币10.00元,活期余额11.70元。[建设银行]
        '/^您尾号(\S+)的储蓄卡账户(\S+)月(\S+)支付机构提现收入人民币(\S+)元,活期余额(\S+)元。\[建设银行]/',
        '/^您尾号(\S+)的储蓄卡账户(\S+)日(\S+)转账存入收入人民币(\S+)元,(\S+)\[建设银行]/',

        //张亚伟7月5日17时17分向您尾号8348的储蓄卡账户手机银行转账收入人民币12.00元,活期余额48.00元。[建设银行]   银行卡转帐
        '/^(\S+)月(\S+)分向您尾号(\S+)的储蓄卡账户手机银行转账收入人民币(\S+)元,活期余额(\S+)\[建设银行]/',

        //招商
                '/^您账户(\S+)于(\S+)月(\S+)日收到人民币(\S+)\（\S+。[招商银行]\S+/',
                '/^您账户(\S+)于(\S+)月(\S+)收款人民币(\S+)，备注：(\S+)\[招商银行]/',
//        /*中国农业银行*/
                '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(\S+)，/',
                '/^【中国农业银行】(\S+)于(\S+)向您尾号(\d+)账户完成代付交易人民币(\S+)。/',
                '/^【中国农业银行】(\S+)于(\S+)日(\S+)完成网银转账交易人民币(\S+)。/',
                '/^【中国农业银行】您尾号(\S+)账户(\S+)日(\S+)完成支付宝发交易人民币(\S+)，/',
                '/^【中国农业银行】(\S+)账户(\S+)日(\S+)完成代付交易人民币(\S+)，/',
        //【中国农业银行】财付通支付科技有限公司于06月24日17:15向您尾号2466账户完成银联入账交易人民币11.00，余额31.00。  微信转卡
        //【中国农业银行】王志远于06月24日17:16向您尾号2466账户完成转存交易人民币11.00，余额42.00。   网银转卡
        '/^【中国农业银行】财付通支付科技有限公司于(\S+)日(\S+)向您尾号(\S+)账户完成银联入账交易人民币(\S+)，余额(\S+)。/',
        '/^【中国农业银行】(\S+)于(\S+)向您尾号(\S+)账户完成转存交易人民币(\S+)，余额(\S+)。/',

        //浦发
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[互联汇入](\S+)，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[银联入账:余额宝]，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[转账到银行卡]，可用余额(\S+)。【浦发银行】/',
        '/^您尾号(\S+)卡人民币活期(\S+):(\S+)存入(\S+)\[(\S+)支付宝转账]，可用余额(\S+)。【浦发银行】/',
        //华夏银行：
        //您的账户1051于06月24日15:25收入人民币3.00元，余额7.63元。【华夏银行】
        //您的账户1051于06月24日15:28收入人民币1.00元，余额8.63元。银联入账。【华夏银行】
        //您的账户1051于06月24日15:28收入人民币2.00元，余额10.63元。收到网联付款。【华夏银行】
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。【华夏银行】/',
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。银联入账。【华夏银行】/',
        '/^您的账户(\S+)于(\S+)日(\S+)收入人民币(\S+)元，余额(\S+)元。收到网联付款。【华夏银行】/',
        //光大银行：
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—商户名称:银联转账（云闪付）,付款方账号后四位:(\S+)。\[光大银行]/',
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—付款方姓名:(\S+),付款方账号后四位:(\S+)。\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。\[光大银行]/',
        '/^尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:(\S+) (\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+)：(\S+)存入(\S+)元，余额(\S+)，摘要：(\S+) (\S+)。逛阳光花园，礼遇618！【光大银行】/',
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:(\S+)。逛阳光花园，礼遇618！\[光大银行]/',
        '/^尾号(\S+)账户(\S+)：(\S+)存入(\S+)元，余额(\S+)，摘要：转账到银行卡。逛阳光花园，礼遇618！【光大银行】/',
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要：网银跨行汇款。逛阳光花园，礼遇618！【光大银行】/',
        '/^尊敬的客户：您尾号(\S+)账户(\S+):(\S+)存入(\S+)元，余额(\S+)元，摘要:银联入账—商户名称:财付通支付科技有限公司,付款方账号后四位:(\S+)。\[光大银行]/',

        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //杨雷向您尾号3844的账户10:18转入30元，余额为30.89元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名[光大银行]
        '/^(\S+)向您尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:转账汇款。支付赢华为手机、黄金礼盒，登手机银行报名\[光大银行\]/',
        //张威向尾号1349账户19:28转入10元，余额为60元，摘要:网银跨行汇款转账。[光大银行]
        '/^(\S+)向尾号(\S+)账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:网银跨行汇款转账。\[光大银行\]/',
        //梁盼盼向您尾号1349的账户15:48转入20元，余额为20元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买[光大银行]
        '/^(\S+)向您尾号(\S+)的账户(\S+)转入(\S+)元，余额为(\S+)元，摘要:转账汇款。人气理财“随心定”发售中，登录手机银行购买\[光大银行\]/',

        // 邮政银行
        //【邮储银行】19年07月05日16:14您尾号874账户提现金额12.00元，余额12.00元。
        //支付宝余额
        //支付宝余额宝
        //【邮储银行】19年07月05日16:18您尾号874账户提现金额12.00元，余额36.00元。
        //【邮储银行】19年07月05日16:15您尾号874账户提现金额12.00元，余额24.00元。
        '/^【邮储银行】(\S+)日(\S+)您尾号(\S+)账户提现金额(\S+)元，余额(\S+)元/',
        //微信转帐
        //【邮储银行】19年07月05日16:20张亚伟账户8847向您尾号874账户他行来账金额12.00元，余额48.00元。
        '/^【邮储银行】(\S+)账户(\S+)向您尾号(\S+)账户他行来账金额(\S+)元，余额(\S+)元/',


    ];
    foreach ($pattern as $key => $value){
//        dump($value);
        preg_match($value,$str,$match);
        if(!empty($match)){
            $amount = $match[4];
            $match[4] = str_replace(',', '', $amount) ;//去除金额中的逗号
//            else if (substr($match[0],-9) == '设银行'){
//                $match[3] = 1;
//                $aa = str_replace('收入人民币', '', strtok($match[2], ',')) ;
//                $match[4] =str_replace('元', '', $aa) ;
//            }
            return $match;
        }
    }
}

function setlog($pid,$channel,$level,$data){
    $log_message = [//拼装日志数据
        'Project_id'=>$pid,
        'Device_id'=>empty($channel)?"没有获取到device":$channel,
        'Level'=>$level,
        'Create_time'=>time(),
        'Log_content'=>$data
    ];
    $log_message = json_encode($log_message);
    $headers = ['Content-Type:application/json'];
    $notify_url = "http://212.129.134.95/Home/Log/create_log";
    cUrlGetData($notify_url, $log_message, $headers);//写入日志
}
function serverToClient($uid,$message){
    return Gateway::sendToClient($uid,$message);
}
/**
 * socket 查单请求
 * @param $str
 * @return mixed
 */
function serverToClientCheckOrder($uid,$data){
    $webSocketData['action'] = $data['action'];
    $webSocketData['start_time'] = $data['start_time'];
    $webSocketData['order_momo'] = $data['order_momo'];
    return Gateway::sendToClient($uid,json_encode($webSocketData));
}
function exportExcel($expTitle,$expCellName,$expTableData){
    include_once EXTEND_PATH.'PHPExcel/PHPExcel.php';//方法二
//        require_once (dirname(__FILE__).EXTEND_PATH.'PHPExcel/PHPExcel.php');
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $yesterday = strtotime('yesterday');
    $fileName = $expTitle;//.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
//        $objPHPExcel = new PHPExcel();//方法一
    $objPHPExcel = new \PHPExcel();//方法二
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
    for($i=0;$i<$cellNum;$i++){
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for($i=0;$i<$dataNum;$i++){
        for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    ob_end_clean();//这一步非常关键，用来清除缓冲区防止导出的excel乱码
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx");//"xls"参考下一条备注
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');//"Excel2007"生成2007版本的xlsx，"Excel5"生成2003版本的xls
    $objWriter->save('php://output');

    exit;
}
