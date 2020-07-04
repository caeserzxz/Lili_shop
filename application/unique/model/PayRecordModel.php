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
        $UsersModel = new UsersModel();
        $AgentModel = new AgentModel();
        #订单信息
        $orderInfo =  $this->where('log_id',$log_id)->find();
        if(empty($orderInfo))   return false;
        #分佣的设置
        $profits = unserialize(settings('profits'));

        Db::startTrans();
        if($orderInfo['pay_code']=='alipayMobile'||$orderInfo['pay_code']=='alipayApp'){
            #支付宝支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照收款商家所在的关系链，代理奖给收款商家的上级代理,跨界奖给收款商家,会员1没有鼓励金，返佣基数是当笔消费所产生的让利款
            #商家信息
            $business = $UserBusinessModel->where('business_id',$orderInfo['business_id'])->find();
            #代理信息
            $agent = $AgentModel->where('agent_id',$business['agent_id'])->find();
            #付款用户信息
            $userInfo = $UsersModel->info($orderInfo['user_id']);
            #让利后的分佣金额
            $profits_money = $business['profits'] * $orderInfo['amount']/100;

            #返还货款


        }else if($orderInfo['pay_type']=='appWeixinPay'||$orderInfo['pay_type']=='weixin'||$orderInfo['pay_type']=='miniAppPay'){
            #微信支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照会员1所在的关系链，始终代理奖都是给代理A,跨界奖给商家C,会员1拿鼓励金，返佣基数是当笔消费所产生的让利款

            #跨界奖

            #鼓励金

            #代理奖
        }

        #跨界奖--商家
        if(empty($business)==false){
            $trans_money = $profits_money*$profits[$business['profits']]['trans']/100;
            #更新商家跨界奖金
            $res1 = $this->change_data($business['user_id'],12,'跨界奖',$trans_money,$orderInfo['log_id']);
            if(!$res1){
                Db::rollback();// 回滚事务
                return ['code' => 0,'msg' => '更新商家跨界奖金'];
            }
        }

        #鼓励金--用户
        if(empty($userInfo)==false){
            $hearten_money = $profits_money*$profits[$business['profits']]['hearten']/100;
            #更新用户鼓励金
            $res2 = $this->change_data($orderInfo['user_id'],11,'鼓励金',$hearten_money,$orderInfo['log_id']);
            if(!$res2){
                Db::rollback();// 回滚事务
                return ['code' => 0,'msg' => '更新用户鼓励金'];
            }
        }
        #代理奖--代理
        if(empty($agent)==false){
            $agent_money  =$profits_money*$profits[$business['profits']]['agent']/100;
            #更新代理代理奖
            $res2 = $this->change_data($agent['user_id'],13,'代理奖',$agent_money,$orderInfo['log_id']);
            if(!$res2){
                Db::rollback();// 回滚事务
                return ['code' => 0,'msg' => '更新代理代理奖'];
            }
        }

        Db::commit();
        return true;
    }

    public function change_data($user_id,$change_type,$change_desc,$money=0,$by_id=''){
        $changedata['change_desc'] = $change_desc;
        $changedata['change_type'] = $change_type;
        $changedata['by_id'] = $by_id;
        $changedata['balance_money'] = $money;
        $AccountLogModel = new AccountLogModel();
        $res1 = $AccountLogModel->change($changedata, $user_id, false);
        return $res1;
    }
}
