<?php
use think\Cookie;
use think\Db;
use think\Log;
use \GatewayWorker\Lib\Gateway;

/**
 * 纯数字的四位随机数
 * rand(1000,9999)
 * 数字和字符混搭的四位随机字符串：
 * @param $len
 * @return string
 */
function GetRandStr($len)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);
    $output = "";
    for ($i=0; $i<$len; $i++)
    {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}


function apiJsonReturn1($code=null,$msg=null,$data=null,$bank_name=null,$name=null,$card=null){



    $dataNow['code']=$code;
    $dataNow['msg']=$msg;
    $dataNow['data']=$data;
    $dataNow['bank_name']=$bank_name;
    $dataNow['name']=$name;
    $dataNow['card']=$card;


    $dataNow=json_encode($dataNow);
    return $dataNow;
}

/**
 * 返回json  并记录
 * @param null $code
 * @param null $msg
 * @param null $data
 * @param null $apiMsg
 * @param null $status
 * @return bool
 */
function jsonInfo($code=null,$msg=null,$data=null,$apiMsg=null,$status=null){

    if($apiMsg==null){ // 日志详情信息为空 不允许调用
        return false;
    }

    if($data==null){
        $dataNow['code']=$code;
        $dataNow['msg']=$msg;
        $dataNow['status']=$status;

    }else{
        $dataNow['code']=$code;
        $dataNow['msg']=$msg;
        $dataNow['data']=$data;
        $dataNow['status']=$status;

    }
    $dataNow=json_encode($dataNow);
//	Log::record($dataNow.' -- '.$apiMsg.' -- ','return info');
    return $dataNow;
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
 * curl
 * @param $url
 * @param null $post_fields
 * @param null $headers
 * @return bool|string
 */
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
/*
 * 中银来聚财
 */
function serverToClient($uid,$message){
    Gateway::sendToClient($uid,$message);
}

/**
 * array return
 * @param string $code
 * @param string $msg
 * @param string $data
 * @return array
 */
function arrayReturn($code='',$data='',$msg='')
{
    return compact('code', 'data', 'msg');
//    return array("code"=>$code,"data"=>$data,"msg"=>$msg);
}
/**
 * 生成唯一订单号码
 * @return string  16
 */
function createOrderMe()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return $orderSn;
}

/**
 * 生成唯一订单号码
 * @return string 22
 */
function guidForSelf()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    return  $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))).date('YmdHi').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}
/**
 * 生成唯一测试订单号 TE开头
 * @return string 22
 */
function guidForSelfTest()
{
    return  'TE'.date('YmdHi').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * socket 获取收款码请求
 * @return mixed
 */
function serverToClientForAddFriend($uid,$data){
    $message['action'] = $data['action'];
    $message['time'] = mmicrotime();
    $message['friend_account'] = $data['friend_account'];
    Gateway::sendToClient($uid,json_encode($message));
}

/**
 * socket 获取收款码请求
 * @param $str
 * @return mixed
 */
function serverToClientForQrCode($uid,$data){
    $message['action'] = $data['action'];
    $message['time'] = mmicrotime();
    $message['order_me'] = $data['order_me'];
    $message['amount'] = $data['amount'];
    Gateway::sendToClient($uid,json_encode($message));
}

/**
 * socket 获取收款码请求
 * @param $str
 * @return mixed
 */
function serverToClientForMoMoQrCode($uid,$data){
    $message['action'] = $data['action'];
    $message['time'] = mmicrotime();
    $message['order_me'] = $data['order_me'];
    $message['amount'] = $data['amount'];
    $message['friend_account'] = $data['friend_account'];
    Gateway::sendToClient($uid,json_encode($message));
}

/**
 * socket 查单请求
 * @param $str
 * @return mixed
 */
function serverToClientCheckOrder($uid,$data){
    $webSocketData['action'] = $data['action'];
    $webSocketData['start_time'] = $data['start_time'];
    $webSocketData['end_time'] = $data['end_time'];
    $webSocketData['order_momo'] = $data['order_momo'];
    return Gateway::sendToClient($uid,json_encode($webSocketData));
}

/**
 * 验证中文名
 * @param $str
 * @return bool
 */
function isAllChinese($str)
{
    //新疆等少数民族可能有·
    if (strpos($str, '·')) {
        //将·去掉，看看剩下的是不是都是中文
        $str = str_replace("·", '', $str);
        if (preg_match('/^[\x7f-\xff]+$/', $str)) {
            return true;//全是中文
        } else {
            return false;//不全是中文
        }
    } else {
        if (preg_match('/^[\x7f-\xff]+$/', $str)) {
            return true;//全是中文
        } else {
            return false;//不全是中文
        }
    }
}

function smsTimeStroTime($smsTime){
    $pos = strpos($smsTime, '年'); //判断字符串中是否有年 有年肯定有月和日
    if ($pos !== false) {//如果有年
        $smsTime = str_replace("年","-",$smsTime);
        $smsTime = str_replace("月","-",$smsTime);
        $smsTime = str_replace("日"," ",$smsTime);
        $smsTime = str_replace("：",":",$smsTime);
        $smsTime =  strtotime($smsTime);//07月18日12:02
        return $smsTime;
    }else{
        $pos = strpos($smsTime, '月'); //判断字符串中是否有月 有月肯定有日
        if ($pos !== false) {//如果无年有月
            $smsTime = str_replace("月","-",$smsTime);
            $smsTime = str_replace("日"," ",$smsTime);
            $smsTime = str_replace("：",":",$smsTime);
            $year = date("Y",time());
            $smsTime = $year."-".$smsTime;
            $smsTime =  strtotime($smsTime);//07月18日12:02
            return $smsTime;
        }else{
            $pos = strpos($smsTime, '日'); //判断字符串中是否有月 有月肯定有日
            if ($pos !== false) {//如果无年无月有日
                $smsTime = str_replace("日"," ",$smsTime);
                $smsTime = str_replace("：",":",$smsTime);
                $year = date("Y-m",time());
                $smsTime = $year."-".$smsTime;
                $smsTime =  strtotime($smsTime);//07月18日12:02
                return $smsTime;
            }else{
                //如果没有日 直接使用时间
                $smsTime = str_replace("：",":",$smsTime);
                $smsTime =  strtotime($smsTime);//07月18日12:02
                return $smsTime;
            }
        }
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


}



