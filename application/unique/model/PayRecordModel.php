<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 付款记录
/*------------------------------------------------------ */
class PayRecordModel extends BaseModel
{
	protected $table = 'users_pay_record';
	public  $pk = 'log_id';
	public static $type_str = ['wxpay'=>'微信支付','alipay'=>'支付宝支付','balance'=>'鼓励金支付'];

    /*------------------------------------------------------ */
    //-- 生成订单编号
    /*------------------------------------------------------ */
    public function getOrderSn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        $date = date('Ymd');
        $order_sn = $date . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $where[] = ['order_sn', '=', $order_sn];
        $where[] = ['add_time', '>', strtotime($date)];
        $count = $this->where($where)->count('	log_id');
        if ($count > 0) return $this->getOrderSn();
        return $order_sn;
    }

}
