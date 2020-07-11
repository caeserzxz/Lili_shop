<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'RedBagOverdue' => 'app\\unique\\command\\RedBagOverdue',//商家红包和用户领的红包,自动过期定时任务  每天:00:00:01执行
    'PayRecordOverdue' => 'app\\unique\\command\\PayRecordOverdue',//线下订单,多长时间未支付就自动取消
    'ManagementAward' => 'app\\unique\\command\\ManagementAward',//代理管理奖
];
