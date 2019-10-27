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
header("Access-Control-Allow-Origin: *");//("Access-Control-Allow-Methods", "POST");//("Access-Control-Allow-Headers", "x-requested-with,content-type");
// 指定允许其他域名访问
header("Access-Control-Allow-Origin: *");

class Codequotient extends Controller
{

    /**
     * @return false|string
     */
    public function index()
    {
            echo "代理列表";

    }

}

