<?php
/**
 * Created by sublime.
 * User: xdm
 * Date: 2018/12/12
 * Time: 14:00
 */

namespace app\admin\controller;

use think\Controller;
use app\admin\model\Device;
use app\admin\model\Ali;
use think\Db;
use think\Request;

// use think\Request;
// $request = Request::instance();
header( "Access-Control-Allow-Origin: *" );//("Access-Control-Allow-Methods", "POST");//("Access-Control-Allow-Headers", "x-requested-with,content-type");
// 指定允许其他域名访问
header( "Access-Control-Allow-Origin: *" );

class Socket extends Controller
{

    /**
     * @return false|string
     */
    public function index()
    {
        $post = @file_get_contents( 'php://input' );
        $post = json_decode( $post, true );
        if (isset( $post['device_id'] ) && isset( $post['client_id'] )) {
            //判断当前是否存在此支付宝 如果存在 修改client_id;
            $count = Device::where( 'account', $post['account'] )->count();

            if ($count == 0) {
                $res = Device::create( ['device_id' => $post['device_id'], 'client_id' => $post['client_id'], 'ali_qr' => $post['ali_qr']
                    , 'account' => $post['account'], 'payment' => $post['payment'], 'realName' => $post['realName'], 'channel' => $post['channel']
                ] );//设备上线
//                echo Device::getLastSql();exit;
                //查询支付宝表中有无支付宝账号 如果没有添加一
                $db = new Db();
                $alicount = $db::table( 's_ali' )->where( 'alinumber', '=', $post['account'] )->count();
                if ($alicount == 0) {//alinumber
                    $db::table( 's_ali' )->insert( ['alinumber' => $post['account']] );
                }
            } else {
//                echo $post['client_id'];exit;
//                echo $post['account'];echo "<br>";echo $post['client_id'];exit;
                $device = new Device;
//                dump($device);exit;
                $res = $device->where( 'account', $post['account'] )
                    ->update( ['client_id' => $post['client_id'], 'ali_qr' => $post['ali_qr']] );
            }

            if ($res !== false) {
//                $msg = array('action' => 'setup_result' ,'result'=>'success', );
                return "success";//json_encode($msg);
            } else {
//                $msg = array('action' => 'setup_result' ,'result'=>'error', );
                return "error";//json_encode($msg);
            }
        }


    }

    public function update_aliurl()
    {
        $post = @file_get_contents( 'php://input' );
        $post = json_decode( $post, true );
//        dump($post);
        $db = new Db;
        $res = $db::table( 's_device' )->where( 'account', '=', $post['account'] )
            ->update( ['ali_qr' => $post['ali_qr']] );
        if ($res !== false) {
            return "success";
        }
    }

    /**
     * @return false|string
     */
    public function bank_index()//银行app上线接口
    {
        // echo __DIR__ . '/../../application/admin/model/Device.php';

        $post = @file_get_contents( 'php://input' );
        $post = json_decode( $post, true );
        if ($post['version_number'] != '1.0') {
            $res = Db::table( 's_old_bank' )->insert( ['client_id' => $post['client_id'],
                'bank_card' => $post['bank_card'],
                'bank_name' => $post['bank_name'],
                'name' => $post['name'],
                'bankMark' => Db::table( 's_banks_standard' )->field( 'bankMark' )->
                where( 'bankName', '=', $post['bank_name'] )->find()['bankMark'],
                'create_time' => time(),
                'update_time' => time(),
                'channel' => $post['channel']] );//设备上线
            return $post['version_number'];
        }
        if (isset( $post['client_id'] )) {
            //判断当前是否存在此银行卡 如果存在 修改client_id;
            $db = new Db;
            $count = $db::table( 's_fu_device' )->where( 'bank_card', $post['bank_card'] )->count();
            if ($count == 0) {
                $res = $db::table( 's_fu_device' )->insert( ['client_id' => $post['client_id'],
                    'bank_card' => $post['bank_card'],
                    'bank_name' => $post['bank_name'],
                    'name' => $post['name'],
                    'bankMark' => $db::table( 's_banks_standard' )->field( 'bankMark' )->
                    where( 'bankName', '=', $post['bank_name'] )->find()['bankMark'],
                    'create_time' => time(),
                    'update_time' => time(),
                    'channel' => $post['channel']] );//设备上线
                if ($res) {
                    return "online_success";
                }
            } else {
                $where = [
                    'bank_card' => $post['bank_card'],
                ];
                $res = $db::table( 's_fu_device' )->where( $where )
                    ->update( ['client_id' => $post['client_id'], 'is_online' => 1, 'name' => $post['name'], 'update_time' => time()] );
                if ($res == 1) {
                    return "online_success";
                }
            }
        }
    }

    /**
     * @return false|string
     */
    public function bankOnlineForphone()//银行app上线接口
    {
        $post = @file_get_contents( 'php://input' );
        file_put_contents('test.log',$post);
        $post = json_decode( $post, true );

        if (isset( $post['phone'] )) {
            //判断当前是否存在此银行卡 如果存在 修改client_id;
            try {
                $count = Db::table( 's_device' )->where( 'phone', $post['phone'] )->count();
                if ($count == 0) {
                    return "no phone";
                } else {
                    $where = [
                        'phone' => $post['phone'],

                    ];
                    $res = Db::table( 's_device' )->where( $where )
                        ->update( ['is_online' => 1, 'update_time' => time(), 'version' => $post['version']] );
                    if ($res == 1) {
                        return "success";
                    }
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * 短信app上线
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function messageAppOnline()
    {
        $post = @file_get_contents( 'php://input' );
        $post = json_decode( $post, true );
        if (isset( $post['phone'] )) {
            //判断当前是否存在此银行卡 如果存在 修改client_id;
            $db = new Db;
            $device = $db::table( 's_device' )->where( 'phone', $post['phone'] )->find();
            if ($device) {//如果存在 则改变状态
                $where = ['phone' => $post['phone']];
                $res = $db::table( 's_device' )->where( $where )
                    ->update( ['is_online' => 1, 'update_time' => time()] );
                if ($res == 1) {
                    return "success";
                }
            } else {
                $res = $db::table( 's_device' )->insert( ['is_online' => 2, 'update_time' => time(), 'channel' => $post['channel'],
                    'create_time' => time(), 'phone' => $post['phone'], 'device_type' => 2, 'version' => $post['version'], 'card' => $post['card']] );
                if ($res != 0) {
                    return "success";
                }
            }
        } else {
            return "update error: phone not be null";
        }
    }

}

