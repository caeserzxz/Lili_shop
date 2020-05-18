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
        Db::startTrans();
        $order_id = $upData['order_id'];
        unset($upData['order_id']);
        $upData['pay_status'] = 1;
        $upData['pay_time'] = time();
        $res = $this->where('order_id',$order_id)->update($upData);
        if ($res < 1) {
            Db::rollback();// 回滚事务
            return false;
        }
        $orderInfo = $this->where('order_id',$order_id)->find()->toArray();
        if ($orderInfo['pay_code'] == 'balance') {//使用余额支付扣减用户余额
            $AccountLogModel = new AccountLogModel();
            $upData['money_paid'] = $orderInfo['order_amount'];
            $upData['pay_time'] = time();
            $changedata['change_desc'] = '订单余额支付';
            $changedata['change_type'] = 3;
            $changedata['by_id'] = $order_id;
            $changedata['balance_money'] = $orderInfo['order_amount'] * -1;
            $res = $AccountLogModel->change($changedata, $this->userInfo['user_id'], false);
            if ($res !== true) {
                Db::rollback();// 回滚事务
                return false;
            }
        }
        Db::commit();// 提交事务
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

        $orderInfo['d_type'] = 'role_order';//身份订单
        $data = (new \app\distribution\model\DividendModel)->_eval($orderInfo,'pay');
        if (is_array($data) == false){
            return false;
        }
        $this->where('order_id',$orderInfo['order_id'])->update($data);
        return true;
    }


}
