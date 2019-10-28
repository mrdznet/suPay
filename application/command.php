<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\push\Websocket',
    'app\admin\controller\Autodevice',
    'app\shell\ProcessingOrder',
    'app\shell\DisLockPayDevice',
	'app\shell\CountDayChannel',
    'app\shell\ClearTodayData',
    'app\shell\HeartBeatline',
    'app\shell\AutoOrder',
    'app\shell\RobotReminder',
    'app\shell\DestroyQrcode',  //销毁二维码
    'app\shell\StatisticsSuccessRate',  //销毁二维码
];