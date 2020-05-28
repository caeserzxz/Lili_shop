<?php
namespace app\distribution\model;
use app\BaseModel;
use app\member\model\AccountLogModel;
use think\Db;
//*------------------------------------------------------ */
//-- 身份订单表
/*------------------------------------------------------ */
class RoleOrderModel extends BaseModel
{
	protected $table = 'distribution_role_order';
	public  $pk = 'order_id';

    /*------------------------------------------------------ */
    //-- 生成订单编号
    /*------------------------------------------------------ */
    public function getOrderSn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        $date = date('Ymd');
        $order_sn = 'role'.$date . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $where[] = ['order_sn', '=', $order_sn];
        $where[] = ['add_time', '>', strtotime($date)];
        $count = $this->where($where)->count('order_id');
        if ($count > 0) return $this->getOrderSn();
        return $order_sn;
    }
    /*------------------------------------------------------ */
    //-- 订单支付成功处理
    /*------------------------------------------------------ */
    public function updatePay($upData = [])
    {
        $order_id = $upData['order_id'];
        unset($upData['order_id']);
        $upData['pay_status'] = 1;
        $upData['pay_time'] = time();
        $res = $this->where('order_id',$order_id)->update($upData);
        if ($res < 1) {
            return false;
        }
        asynRun('shop/RoleOrderModel/asynRunPaySuccessEval',['order_id'=>$order_id]);//异步执行
        //升级，分佣处理
        return true;
    }
    /*------------------------------------------------------ */
    //-- 支付成功后执行(异步执行)
    //-- $param array 必须带有order_id
    /*------------------------------------------------------ */
    function asynRunPaySuccessEval($param)
    {
        $order_id = $param['order_id'] * 1;
        $orderInfo =  $this->where('order_id',$order_id)->find()->toArray();
        if (empty($orderInfo)) return '没有找到相关订单';
        $user_id = $orderInfo['user_id'];
        //如果设置支付再绑定关系时执行,须优先于分佣计算前执行
        if (settings('bind_pid_time') == 1){
            $res = (new \app\member\model\UsersModel)->regUserBind($user_id,-1);
            if ($res == false) return '绑定关系链失败.';
        }//end
        $DividendModel = new \app\distribution\model\DividendModel();
        $orderInfo['d_type'] = 'role_order';//身份订单
        Db::startTrans();//启动事务，身份订单独立事务，其它订单在订单主模块里使用事务

        $UsersModel =  new \app\member\model\UsersModel();
        $usersInfo = $UsersModel->info($orderInfo['user_id']);//获取会员信息

        //更新会员最后购买时间&累计消费
        if ($usersInfo['last_buy_time'] < $orderInfo['add_time']){
            $UsersModel->upInfo($orderInfo['user_id'],['last_buy_time'=>$orderInfo['add_time'],'total_consume'=>['INC',$orderInfo['order_amount']]]);
        }
        //如果设置支付再绑定关系时执行
        if (settings('bind_pid_time') == 1 && $usersInfo['is_bind'] == 0){//支付成功时绑定关系
            $UsersModel->regUserBind($orderInfo['user_id'],-1);
        }//end

        if ($orderInfo['is_dividend'] == 0){
            $buy_brokerage_amount = $DividendModel->buyBrokerageByRoleOrder($orderInfo);//自购返还
            $upData = $DividendModel->_eval($orderInfo,'pay');
            if (is_array($upData) == false){
                Db::rollback();// 回滚事务
                return $res;
            }
            if ($buy_brokerage_amount > 0){
                $upData['buy_brokerage_amount'] = $buy_brokerage_amount;
            }
        }

        $this->where('order_id',$orderInfo['order_id'])->update($upData);
        Db::commit();// 提交事务
        return true;
    }


}
