<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 商家红包
/*------------------------------------------------------ */
class PayLogModel extends BaseModel
{
    protected $table = 'users_pay_log';
    public  $pk = 'id';

    /*------------------------------------------------------ */
    //-- 写入订单日志
    /*------------------------------------------------------ */
    public static function _log(&$order, $logInfo = '')
    {
        $inArr['log_id'] = $order['log_id'];
        $inArr['admin_id'] = defined("AUID") ? AUID : 0;
        $inArr['status'] = $order['status'];
        $inArr['log_info'] = $logInfo;
        $inArr['log_time'] = time();
        return self::create($inArr);
    }
}
