<?php

namespace app\api\model;

use think\Db;
use think\Model;
use app\api\model\OrderModel;
use app\admin\model\SystemConfigModel;

class DeviceModel extends Model
{
    // 确定链接表名
    protected $name = 'device';

    /**
     * 银行支付获取设备（从该用户支付成功订单里查找可用设备）
     * @param string $userId
     * @param string $merchant_id
     * @param string $card
     * @param int $amount
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getBankDevice($userId = "", $merchant_id = "", $card = "", $amount = 0)
    {
        $Dailycollectionquota = SystemConfigModel::getDailycollectionquota();
        $Dailycollectionquotaint = intval( $Dailycollectionquota );
        $leftCanUseAmount = $Dailycollectionquotaint - $amount;
        $deviceModel = new self();
        if ($card != "") {
            //给指定设备下单
            $is_canuse = $deviceModel::checkCanUse( $card, $leftCanUseAmount, $merchant_id );
            if ($is_canuse['code'] == 10000) {
                return arrayReturn( '10000', $is_canuse['data'], '获取设备成功！' );
            } else {
                return arrayReturn( '10001', "不可用", '测试下单获取设备失败！' );
            }
        } else {
            $db = new Db;
            //$isCanUse = $deviceModel::checkCanUse($val['card']);
            //查找支付成功分配设备
//            $cardData = $db::table('s_receivables_bind')->field('receivables')->where('payuserid', '=', $userId)->select();
//            //如果有使用过的卡，优先分配到之前使用过的卡
//            if($cardData){
//                foreach ($cardData as $key=>$value){
//                    $is_canuse = $deviceModel::checkCanUse($value['receivables'],$leftCanuseAmount);
//                    if($is_canuse['code'] == 10000){
//                        return arrayReturn('10000', $is_canuse['data'], '获取设备成功！');
//                    }
//                }
//                $newDevice = $deviceModel->GetAvailable($merchant_id,$leftCanuseAmount);
//                if($newDevice['code'] == 10000){
//                    return arrayReturn('10000', $newDevice['data'], '获取设备成功！');
//                }else{
//                    return arrayReturn('10001',false, '获取设备失败！');
//                }
//
//            }else{

            //获取新的收款卡
            $newDevice = $deviceModel->GetAvailable( $merchant_id, $leftCanUseAmount, $amount );
            if ($newDevice['code'] == 10000) {
                return arrayReturn( '10000', $newDevice['data'], '获取设备成功！' );
            } else {
                return arrayReturn( '10001', false, $newDevice['msg'] );
            }
//            }

        }

    }


    /**
     * 检查可用
     * @param string $card
     * @param int $leftCanUseAmount
     * @param string $merchant_id
     * @return array
     */
    static function checkCanUse($card = "", $leftCanUseAmount = 0, $merchant_id = '')
    {
        if (empty( $card )) {
            arrayReturn( '10001', "", "card_require" );
        }
        try {
            $deviceModel = new self();
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['card'] = $card;
            $deviceWhere['lock_time'] = 0;
            if ($merchant_id) {
                $deviceWhere['channel'] = trim( $merchant_id );
            }
            $time = time() - 20;
            $deviceData = $deviceModel::where( $deviceWhere )
                ->where( 'today_money', '<=', $leftCanUseAmount )
                ->lock( true )
                ->find();
            //计算 该卡对应的用户是否超过100
            $db = new Db;
//            $usecount = $db::table('s_receivables_bind')->where('receivables', '=', $card)->count();
            if ($deviceData) {
                $data['card'] = $deviceData['card'];
                $data['name'] = $deviceData['name'];
                $data['bank_name'] = $deviceData['bank_name'];
                $data['bank_mark'] = $deviceData['bank_mark'];
                $data['phone'] = $deviceData['phone'];
                $data['channel'] = $deviceData['channel'];
                //修改上次使用时间
                $update = $deviceModel::where( 'card', $data['card'] )
                    ->update( [
                        'use_times' => Db::raw( "use_times+1" ),
                        'last_use_time' => time(),
                        'lock_time' => time(),
                    ] );
                if ($update == 1) {
                    return arrayReturn( '10000', $deviceData, "可用" );
                }
            } else {
                return arrayReturn( '16666', false, "不可用" );
            }

        } catch (\Exception $exception) {
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'checkCanUse_exception' );

            return arrayReturn( '18888', 'checkCanUse_exception' );
        } catch (\Error $error) {
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'checkCanUse_error' );
            return arrayReturn( '19999', 'checkCanUse_error' );
        }
    }

    /**
     * 获取可用
     * @param $card
     * @return array
     */
    public function GetAvailable($merchantId = '', $leftCanuseAmount = 0, $amount = 0)
    {
        $db = new Db;
        //事务开启
        $this->startTrans();
        try {
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['lock_time'] = 0;
            $orderby = '';
            if ($merchantId != "") {
                //YD
                if ($merchantId == 'yd') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_yd";
                }
                //李
                if ($merchantId == 'lk') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_lk";
                }
                if ($merchantId == 'sg') {
                    $orderby = "today_money asc,last_use_time asc ,use_times asc";
                    $deviceWhere['channel'] = "studio_sg";
                }
                if ($merchantId == 'am') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $leftCanuseAmountam = $leftCanuseAmount - 10000;
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmountam];
                    $deviceWhere['channel'] = "studio_am";
                }
                if ($merchantId == 'dl') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_dl";
                }
                if ($merchantId == 'yd1') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studioyd1";
                }
                if ($merchantId == 'sy') {
                    $orderby = "use_times asc,last_use_time asc";
                    $deviceWhere['channel'] = "studio_sy";
                }
                if ($merchantId == 'aj') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_aj";
                }
                if ($merchantId == 'yy') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_yy";
                }
                if ($merchantId == 'studio_5m') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_5m";
                }
                if ($merchantId == 'studio_xj') {
                    $orderby = "today_money desc,last_use_time asc ,use_times asc";
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_xj";
                }
            }//studi_a


            $deviceData = $this->where( $deviceWhere )
                ->order( $orderby )->find();
            //如果订单金额小于等于100 不适用 兴业银行
            if ($amount <= 100) {
                $deviceData = $this->where( $deviceWhere )->where( 'bank_mark', '<>', 'CIB' )->order( $orderby )->find();
            }
            if (empty( $deviceData )) {
                return arrayReturn( '10001', "", '空闲设备不足,或所有设备加上此次下单金额超过了限额！' );
            }
            //事务开启   s_device  id $deviceData['id'] 行锁
            $this->where( 'id', '=', $deviceData['id'] )
                ->where( 'lock_time', '=', 0 )
                ->where( 'is_online', '=', 1 )
                ->where( 'is_prohibit', '=', 1 )
                ->lock( true )->find();
//            logs(json_encode(['lockData'=>$deviceData,'lockSql'=>$lastSql]),'GetAvailableNewTableLock');
            if (!empty( $deviceData )) {
                $data['card'] = $deviceData['card'];
                $data['name'] = $deviceData['name'];
                $data['bank_name'] = $deviceData['bank_name'];
                $data['bank_mark'] = $deviceData['bank_mark'];
                $data['phone'] = $deviceData['phone'];
                $data['channel'] = $deviceData['channel'];
                //修改上次使用时间
                for ($x = 0; $x < 10; $x++) {
                    $res = $this->where( 'card', $data['card'] )
                        ->update( [
                            'use_times' => intval( $deviceData['use_times'] ) + 1,
                            'last_use_time' => time(),
                            'lock_time' => time(),
                        ] );
                    if ($res == 1) {
                        $this->commit();
                        return arrayReturn( '10000', $deviceData, '获取设备成功！' );
                    }
                }

                if ($res != 1) {
                    $this->rollback();
                    return arrayReturn( '12999', $deviceData, '支付繁忙，请重新下单！' );
                }

            } else {
                $lastSql = $this->getLastSql();
                logs( json_encode( ['lastSql' => $lastSql, 'deviceData' => $deviceData] ), 'get_bank_device_fail' );
            }
            $this->rollback();
            return arrayReturn( '10001', $deviceData, '无可用设备！' );
        } catch (\Exception $exception) {
            $this->rollback();
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'GetAvailableNew_exception' );
            return arrayReturn( '18888', 'checkCanUse_exception' );
        } catch (\Error $error) {
            $this->rollback();
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'GetAvailableNew_error' );
            return arrayReturn( '19999', 'checkCanUse_error' );
        }


    }

    /**
     * 获取可用
     * @param $card
     * @return array
     */
    public function GetAvailableTEst($merchantId = '', $leftCanuseAmount = 0)
    {
        $db = new Db;
        //事务开启
        $this->startTrans();
        try {
            $deviceWhere['is_online'] = 1;
            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['lock_time'] = 0;
            $orderby = '';
            if ($merchantId != "") {
                //YD
                if ($merchantId == 'yd') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_yd";
                }
                //李
                if ($merchantId == 'lk') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_lk";
                }
                if ($merchantId == 'sg') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_sg";
                }
                if ($merchantId == 'am') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_am";
                }
                if ($merchantId == 'dl') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_dl";
                }
                if ($merchantId == 'yd1') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studioyd1";
                }
                if ($merchantId == 'sy') {
                    $deviceWhere['channel'] = "studio_sy";
                }
                if ($merchantId == 'aj') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_aj";
                }
                if ($merchantId == 'studio_5m') {
                    $deviceWhere['today_money'] = ['<=', $leftCanuseAmount];
                    $deviceWhere['channel'] = "studio_5m";
                }
            }//studi_a
            $order = 'today_money desc,last_use_time asc ,use_times asc';
            $deviceData = $this->where( $deviceWhere )
                ->order( $order )->find();
            return $this->getLastSql();
            if (empty( $deviceData )) {
                return arrayReturn( '10001', "", '在线设备不足,或所有设备加上此次下单金额超过了50000！' );
            }
            //事务开启   s_device  id $deviceData['id'] 行锁
            $this->where( 'id', '=', $deviceData['id'] )
                ->where( 'lock_time', '=', 0 )
                ->where( 'is_online', '=', 1 )
                ->where( 'is_prohibit', '=', 1 )
                ->lock( true )->find();
//            logs(json_encode(['lockData'=>$deviceData,'lockSql'=>$lastSql]),'GetAvailableNewTableLock');
            if (!empty( $deviceData )) {
                $data['card'] = $deviceData['card'];
                $data['name'] = $deviceData['name'];
                $data['bank_name'] = $deviceData['bank_name'];
                $data['bank_mark'] = $deviceData['bank_mark'];
                $data['phone'] = $deviceData['phone'];
                $data['channel'] = $deviceData['channel'];
                //修改上次使用时间
                for ($x = 0; $x < 10; $x++) {
                    $res = $this->where( 'card', $data['card'] )
                        ->update( [
                            'use_times' => intval( $deviceData['use_times'] ) + 1,
                            'last_use_time' => time(),
                            'lock_time' => time(),
                        ] );
                    if ($res == 1) {
                        $this->commit();
                        return arrayReturn( '10000', $deviceData, '获取设备成功！' );
                    }
                }

                if ($res != 1) {
                    $this->rollback();
                    return arrayReturn( '12999', $deviceData, '支付繁忙，请重新下单！' );
                }

            } else {
                $lastSql = $this->getLastSql();
                logs( json_encode( ['lastSql' => $lastSql, 'deviceData' => $deviceData] ), 'get_bank_device_fail' );
            }
            $this->rollback();
            return arrayReturn( '10001', $deviceData, '无可用设备！' );
        } catch (\Exception $exception) {
            $this->rollback();
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'GetAvailableNew_exception' );
            return arrayReturn( '18888', 'checkCanUse_exception' );
        } catch (\Error $error) {
            $this->rollback();
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'GetAvailableNew_error' );
            return arrayReturn( '19999', 'checkCanUse_error' );
        }


    }


    /**
     * 检查可用ceshi
     * @param $card
     * @return array
     */
    static function checkCanUseTest($card = "")
    {
        if (empty( $card )) {
            arrayReturn( '10001', "", "card_require" );
        }
        try {
            $deviceModel = new self();
            $deviceWhere['is_online'] = 1;
//            $deviceWhere['is_prohibit'] = 1;
            $deviceWhere['card'] = $card;
            $deviceWhere['lock_time'] = 0;
            $time = time() - 20;
            $deviceData = $deviceModel::where( $deviceWhere )
                ->find();
            //计算 该卡对应的用户是否超过100

            if ($deviceData) {
                $data['card'] = $deviceData['card'];
                $data['name'] = $deviceData['name'];
                $data['bank_name'] = $deviceData['bank_name'];
                $data['bank_mark'] = $deviceData['bank_mark'];
                $data['phone'] = $deviceData['phone'];
                $data['channel'] = $deviceData['channel'];
                //修改上次使用时间
                $Update = $deviceModel::where( 'card', $data['card'] )
                    ->update( [
                        'use_times' => Db::raw( "use_times+1" ),
                        'last_use_time' => time(),
                        'lock_time' => time(),
                    ] );
                if ($Update == 1) {
                    return arrayReturn( '10000', $deviceData, "可用" );
                }
            } else {
                return arrayReturn( '16666', false, "不可用" );
            }

        } catch (\Exception $exception) {
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'checkCanUse_exception' );

            return arrayReturn( '18888', 'checkCanUse_exception' );
        } catch (\Error $error) {
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'checkCanUse_error' );
            return arrayReturn( '19999', 'checkCanUse_error' );
        }
    }

    /**
     * 自动停用设备 （根据金额自动停用设备）
     * @param $device
     * @param int $type 1手机号 |2银行卡
     * @param int $money
     * @return bool
     */
    public function disableDeviceByMoney($device, $type = 1, $money = 0)
    {
        try {
            if (empty( $device ) || !is_numeric( $device )) {
                return false;
            }
            if ($type == 1) {
                $deviceWhere['phone'] = $device;
                $deviceData = $this->field( 'id,today_money,is_prohibit' )->where( $deviceWhere )->find();
            }
            if ($type == 2) {
                $deviceWhere['card'] = $device;
                $deviceData = $this->field( 'id,today_money,is_prohibit' )->where( $deviceWhere )->find();
            }
            if (empty( $deviceData )) {
                logs( json_encode( ['device' => $device, 'money' => $money, 'errorMessage' => "device_is_empty"] ), 'disableDeviceByMoney_get_device_fail' );
                return true;
            }
            if ($deviceData['is_prohibit'] == 2) {
                return true;
            }
            if ($money != 0 && is_numeric( $money )) {
                $todayMoney = $money;
            } else {
                $todayMoney = $deviceData['today_money'];
            }
            $systemConfigModel = new SystemConfigModel();
            //获取自动关闭额度
            $disableDeviceLimitMoney = $systemConfigModel->getDisableDeviceLimitMoney();
            $updateData['is_prohibit'] = 2;
            $differenceMoney = $todayMoney - $disableDeviceLimitMoney;
            //超过或到达限制额度
            if ($differenceMoney >= 0) {
                $updateRes = $this->where( $deviceWhere )->update( $updateData );
                if ($updateRes) {
                    return true;
                } else {
                    logs( json_encode( ['device' => $device, 'money' => $money, 'errorMessage' => "device_is_empty"] ), 'disableDeviceByMoney_update_status_fail' );
                    return false;
                }
            } else {
                return true;
            }
        } catch (\Exception $exception) {
            logs( json_encode( ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()] ), 'dsableDeviceByMoney_exception' );
            return false;
        } catch (\Error $error) {
            logs( json_encode( ['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()] ), 'dsableDeviceByMoney_error' );
            return false;
        }

    }

    public function getIsOnlineDeviceCount($id)
    {
        $where['is_online'] = 1;
        $where['is_prohibit'] = 1;
        $where['channel'] = "studio_" . $id;
        if ($id == "yd1") {
            $where['channel'] = "studioyd1";
        }
        if ($id == "studio_5m") {
            $where['channel'] = "studio_5m";
        }
        if ($id == "studio_xj") {
            $where['channel'] = "studio_xj";
        }
        $count = $this->where( $where )->count();
        return apiJsonReturn( '1000', '在线数量', $count );
    }

//测试下单获取设备
    public static function getBankDeviceTEST($userId = "", $merchant_id = "", $card = "", $amount = 0)
    {
        $leftCanuseAmount = 50000 - $amount;
        $deviceModel = new self();
        if ($card != "") {
            //给指定设备下单
            $is_canuse = $deviceModel::checkCanUseTest( $card );
            if ($is_canuse['code'] == 10000) {
                return arrayReturn( '10000', $is_canuse['data'], '获取设备成功！' );
            } else {
                return arrayReturn( '10001', "不可用", '获取设备失败！' );
            }

        } else {
            $db = new Db;
            //$isCanUse = $deviceModel::checkCanUse($val['card']);
            //查找支付成功分配设备
            $cardData = $db::table( 's_receivables_bind' )->field( 'receivables' )->where( 'payuserid', '=', $userId )->select();
            //如果有使用过的卡，优先分配到之前使用过的卡
            if ($cardData) {
                foreach ($cardData as $key => $value) {
                    $is_canuse = $deviceModel::checkCanUse( $value['receivables'], $leftCanuseAmount );
                    if ($is_canuse['code'] == 10000) {
                        return arrayReturn( '10000', $is_canuse['data'], '获取设备成功！' );
                    }
                }
                $newDevice = $deviceModel::GetAvailable( $merchant_id, $leftCanuseAmount );
                if ($newDevice['code'] == 10000) {
                    return arrayReturn( '10000', $newDevice['data'], '获取设备成功！' );
                } else {
                    return arrayReturn( '10001', false, '获取设备失败！' );
                }

            } else {
                //获取新的收款卡
                $newDevice = $deviceModel::GetAvailable( $merchant_id, $leftCanuseAmount );
                if ($newDevice['code'] == 10000) {
                    return arrayReturn( '10000', $newDevice['data'], '获取设备成功！' );
                } else {
                    return arrayReturn( '10001', false, '获取设备失败！' );
                }
            }

        }

    }


}
