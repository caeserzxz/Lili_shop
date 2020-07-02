<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
use app\member\model\UsersModel;
use app\store\model\BusinessGiftModel;
use app\member\model\AccountLogModel;
use app\store\model\UserBusinessModel;
use app\agent\model\AgentModel;

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

    /*------------------------------------------------------ */
    //-- 订单分佣
    /*------------------------------------------------------ */
    public function sub_commission($log_id){
        $UserBusinessModel = new UserBusinessModel();
        #订单信息
        $orderInfo =  $this->where('log_id',$log_id)->find();
        if(empty($orderInfo))   return false;
        #商家信息
        $business = $UserBusinessModel->where('business_id',$orderInfo['business_id'])->find();
        if(empty($business))    return false;
        

        if($orderInfo['pay_type']=='alipayMobile'||$orderInfo['pay_type']=='alipayApp'){
            #支付宝支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照收款商家所在的关系链，代理奖给收款商家的上级代理,跨界奖给收款商家,会员1没有鼓励金，返佣基数是当笔消费所产生的让利款
            
        }else if($orderInfo['pay_type']=='appWeixinPay'||$orderInfo['pay_type']=='weixin'||$orderInfo['pay_type']=='miniAppPay'){
            #微信支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照会员1所在的关系链，始终代理奖都是给代理A,跨界奖给商家C,会员1拿鼓励金，返佣基数是当笔消费所产生的让利款
            
            #跨界奖
            
            #鼓励金

            #代理奖
        }



    }

}
