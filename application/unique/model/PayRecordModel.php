<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
use app\member\model\UsersModel;
use app\store\model\BusinessGiftModel;
use app\member\model\AccountLogModel;
use app\store\model\UserBusinessModel;
use app\agent\model\AgentModel;
use app\unique\model\PayLogModel;
use app\unique\model\RedbagModel;

/*------------------------------------------------------ */
//-- 付款记录
/*------------------------------------------------------ */
class PayRecordModel extends BaseModel
{
	protected $table = 'users_pay_record';
	public  $pk = 'log_id';
	public static $type_str = ['alipayApp'=>'App支付宝支付','alipayMobile'=>'支付宝支付','balance'=>'鼓励金支付','appWeixinPay'=>'App微信支付','weixin'=>'微信支付','miniAppPay'=>'微信小程序支付'];
    public function initialize()
    {
        parent::initialize();
        $this->config = config('config.');
    }

    /*------------------------------------------------------ */
    //-- 生成订单编号
    /*------------------------------------------------------ */
    public function getOrderSn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        $date = date('Ymd');
        $order_sn = 'sn'.$date . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
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
        if(empty($orderInfo))   ['code' => 0,'msg' => '订单不存在'];
        #分佣的设置
        $profits = unserialize(settings('profits'));

        if(empty($orderInfo['user_id'])){
            #支付宝支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照收款商家所在的关系链，代理奖给收款商家的上级代理,跨界奖给收款商家,会员1没有鼓励金，返佣基数是当笔消费所产生的让利款
            #商家信息
            $business = $UserBusinessModel->where('business_id',$orderInfo['business_id'])->find();
            #代理信息
            $agent = $AgentModel->where('agent_id',$business['agent_id'])->find();
            #让利后的分佣金额
            $profits_money = $business['profits'] * $orderInfo['amount']/100;

        }else{
            #微信支付分佣逻辑--会员1不管在商家C店消费还是到平台其它商家店消费，返佣逻辑按照会员1所在的关系链，始终代理奖都是给代理A,跨界奖给商家C,会员1拿鼓励金，返佣基数是当笔消费所产生的让利款
            #付款用户信息
            $userInfo = $UsersModel->info($orderInfo['user_id']);
            #商家信息
            $business = $UserBusinessModel->where('business_id',$userInfo['first_business_id'])->find();
            #代理信息
            $agent = $AgentModel->where('agent_id',$business['agent_id'])->find();
            #让利后的分佣金额
            $profits_money = $business['profits'] * $orderInfo['amount']/100;
        }

        #跨界奖--商家
        if(empty($business)==false){
            $trans_money = $profits_money*$profits[$business['profits']]['trans']/100;
            #更新商家跨界奖金
            $res1 = $this->change_data($business['user_id'],12,'跨界奖',$trans_money,$orderInfo['log_id']);
            if(!$res1){
                return ['code' => 0,'msg' => '更新商家跨界奖金失败'];
            }
        }

        #鼓励金--用户
        if(empty($userInfo)==false){
            $hearten_money = $profits_money*$profits[$business['profits']]['hearten']/100;
            #更新用户鼓励金
            $res2 = $this->change_data($orderInfo['user_id'],11,'鼓励金',$hearten_money,$orderInfo['log_id']);
            if(!$res2){
                return ['code' => 0,'msg' => '更新用户鼓励金失败'];
            }
        }
        #代理奖--代理
        if(empty($agent)==false){
            $agent_money  =$profits_money*$profits[$business['profits']]['agent']/100;
            #更新代理代理奖
            $res2 = $this->change_data($agent['user_id'],13,'代理奖',$agent_money,$orderInfo['log_id']);
            if(!$res2){
                return ['code' => 0,'msg' => '更新代理代理奖失败'];
            }
        }

     return ['code' => 1,'msg' => '分佣成功'];
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

    /*------------------------------------------------------ */
    //-- 订单支付时, 获取商家名称
    //-- $order_id int 订单ID
    /*------------------------------------------------------ */
    public function getPayBody($log_id)
    {
        if (empty($log_id)) return "订单ID参数错误";
        $order_info = $this->where('log_id', $log_id)->find();
        $business_name = (new UserBusinessModel)->where('business_id', $order_info['business_id'])->column('business_name');
        $gns = implode($business_name, ',');
        $payBody = getSubstr($gns, 0, 18);
        return $payBody;
    }

    /*------------------------------------------------------ */
    //-- 订单支付成功处理
    /*------------------------------------------------------ */
    public function updatePay($upData = [], $_log = '支付成功')
    {
        unset($upData['order_amount']);
        $upData['status'] = $this->config['XX_PAYED'];
        $upData['pay_time'] = time();
        $upData['is_pay_eval'] = 1;//设为待执行支付成功后的相关处理
        $res = $this->upInfo($upData, 'sys');
        if ($res !== true) {
            return false;
        }
        $orderInfo = $this->find($upData['log_id'])->toArray();
        $this->_log($orderInfo,$_log);
        asynRun('unique/PayRecordModel/asynRunPaySuccessEval',['log_id'=>$upData['log_id']]);//异步执行
        return true;
    }

    /*------------------------------------------------------ */
    //-- 支付成功后执行(异步执行)
    //-- $param array 必须带有log_id
    /*------------------------------------------------------ */
    public function asynRunPaySuccessEval($param)
    {
        if ($param['log_id'] < 1){
            return '缺少订单ID';
        }

        $param['log_id'] = $param['log_id'] * 1;//异步执行传入必须强制类型
        $orderInfo = $this->find($param['log_id'])->toArray();
        Db::startTrans();//启动事务
        #绑定关系
        if(empty($orderInfo)==false){
            $UsersModel = new UsersModel;
            $userInfo = $UsersModel->where('user_id',$orderInfo['user_id'])->find();
            if(empty($userInfo['first_business_id'])){
                #绑定第一次消费的商家
                $updata_user['first_business_id'] = $orderInfo['business_id'];
                $updata_user['first_business_time'] = time();
                $user_res = $UsersModel->where('user_id',$orderInfo['user_id'])->update($updata_user);
                if($user_res==false){
                    Db::rollback();// 回滚事务
                    $this->_log($orderInfo,'绑定第一次消费的商家失败');
                    return '绑定第一次消费的商家失败';
                }
            }
        }

        #执行分佣
        $sub_res = $this->sub_commission($orderInfo['log_id']);
        if($sub_res['code']==0){
            Db::rollback();// 回滚事务
            $this->_log($orderInfo,$sub_res['msg']);
            return '分佣失败';
        }

        #返还货款
        $UserBusinessModel = new UserBusinessModel();
        $business = $UserBusinessModel->where('business_id',$orderInfo['business_id'])->find();
        //让利后的货款
        $return_money = $orderInfo['amount'] - ($orderInfo['amount']*$business['profits']/100);
        $return_changedata['change_desc'] = '货款';
        $return_changedata['change_type'] = 17;
        $return_changedata['by_id'] = $return_money;
        $return_changedata['balance_money'] = $orderInfo['log_id'];
        $AccountLogModel = new AccountLogModel();
        $res = $AccountLogModel->change($return_changedata, $business['user_id'], false);
        if($res==false){
            Db::rollback();// 回滚事务
            $this->_log($orderInfo,'返还商家货款失败');
            return '返还商家货款失败';
        }

        Db::commit();// 提交事务
        $upData['is_pay_eval'] = 2;
        $this->where('log_id',$orderInfo['log_id'])->update($upData);
        return true;
    }

    /**更新订单信息
     * @param $upData
     * @param string $extType 扩展执行内容
     * @param bool $isTrans 是否启动事务
     * @return bool|string
     */
    function upInfo($upData, $extType = '')
    {
        $log_id = $upData['log_id'];
        $orderInfo = $this->where('log_id', $log_id)->find();
        if (empty($orderInfo)) return '订单不存在.';
        $orderInfo = $orderInfo->toArray();
        Db::startTrans();//启动事务
        $RedbagModel = new RedbagModel();
        $AccountLogModel = new AccountLogModel();
        $BusinessGiftModel = new BusinessGiftModel();
        if($upData['status']==$this->config['XX_PAYED']){
            //支付成功,暂无逻辑

        }
        if($upData['status']==$this->config['XX_CANCEL']){
            //取消订单
            //退还抵扣的鼓励金
            if($orderInfo['balance_amount']>0){
                $changedata['change_desc'] = '退还鼓励金';
                $changedata['change_type'] = 16;
                $changedata['by_id'] = $orderInfo['log_id'];
                $changedata['balance_money'] = $orderInfo['balance_amount'];
                $res1 = $AccountLogModel->change($changedata, $orderInfo['user_id'], false);
                if(!$res1){
                    Db::rollback();// 回滚事务
                    return '退还鼓励金失败.';
                }
            }
            //如果选用了红包,退还红包
            if($orderInfo['redbag_id']>0){
               $red_res = $RedbagModel->where('redbag_id',$orderInfo['redbag_id'])->update(['status'=>0,'by_id'=>'']);
               if($red_res!=true){
                   Db::rollback();//回滚
                   return '退还红包失败.';
               }
            }
        }
        $res = $this->where('log_id', $log_id)->update($upData);
        if ($res < 1) {
            return '订单更新失败.';
        }
        Db::commit();// 提交事务
        return true;
    }

    /*------------------------------------------------------ */
    //-- 写入订单日志
    /*------------------------------------------------------ */
    public function _log(&$order, $logInfo = ''){
        $PayLogModel = new PayLogModel();
        return $PayLogModel->_log($order, $logInfo);
    }


}
