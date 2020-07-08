<?php
namespace app\unique\controller\api;
use app\unique\model\PayRecordModel;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
use app\member\model\UsersModel;
use app\store\model\BusinessGiftModel;
use app\unique\model\RedbagModel;
use app\member\model\AccountLogModel;
use app\mainadmin\model\PaymentModel;

/*------------------------------------------------------ */
//-- 首页相关API
/*------------------------------------------------------ */

class PayRecord extends ApiController
{

    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new PayRecordModel();
    }

    /*------------------------------------------------------ */
    //-- 下单
    /*------------------------------------------------------ */
    public function add_order(){
        $PayRecordModel = new PayRecordModel();
        $RedbagModel = new RedbagModel();
        $UserBusinessModel = new UserBusinessModel();
        $money = input('money');
        $red_id = input('red_id');
        $is_hearten = input('is_hearten');
        $business_id = input('business_id');
        $pay_id = input('pay_id');
        $PaymentModel = new PaymentModel();
        $paymentList = $PaymentModel->getRows();
        $payment = $paymentList[$pay_id];
        $business = $UserBusinessModel->where('business_id',$business_id)->find();
        #红包金额
        $red_price = 0;
        #鼓励金抵扣
        $balance_amount = 0;
        $time = time();
        if(empty($money)) return $this->ajaxReturn(['code' => 0,'msg' => '请输入消费金额']);
        if(empty($business))  return $this->ajaxReturn(['code' => 0,'msg' => '商家不存在']);
        #红包是否存在,是否过期
        if($red_id>0){
            $redbag = $RedbagModel->where('redbag_id',$red_id)->find();
            if(empty($redbag)) return $this->ajaxReturn(['code' => 0,'msg' => '红包不存在']);
            if($time<$redbag['start_time']) return $this->ajaxReturn(['code' => 0,'msg' => '红包没到使用时间']);
            if($time>$redbag['expire_time']) return $this->ajaxReturn(['code' => 0,'msg' => '红包已经失效']);
            if($redbag['business_id']!=$business_id) return $this->ajaxReturn(['code' => 0,'msg' => '红包与商家不一致']);
            $red_price = $redbag['price'];
            $inArr['redbag_id'] = $red_id;
        }

        #是否存在鼓励金抵扣
        if($is_hearten==1){
            if($money-$red_price>=$this->userInfo['account']['balance_money']){
                $balance_amount = $this->userInfo['account']['balance_money'];
            }else{
                $balance_amount = $money-$red_price;
            }
        }

        #鼓励金比例处理
//        $profits = unserialize(settings('profits'));
//        $profit = intval($profits[$business['profits']]['hearten']);

        $inArr['order_sn'] = $PayRecordModel->getOrderSn();
        $inArr['business_id'] = $business_id;
        $inArr['user_id'] = $this->userInfo['user_id'];
        $inArr['pay_type'] = $payment['pay_code'];
        $inArr['pay_code'] = $payment['pay_code'];
        $inArr['amount'] = $money;
        $inArr['amount_actual'] = $money-$red_price-$balance_amount;
        $inArr['redbag_amount'] = $red_price;
        $inArr['balance_amount'] = $balance_amount;
        $inArr['balance_proportion'] = $business_id;
        $inArr['status'] = 0;
        $inArr['pay_id'] = $payment['pay_id'];
        $inArr['add_time'] = $time;

        Db::startTrans();
        $res = $PayRecordModel->insertGetId($inArr);
        if($res){
            $orderInfo = $PayRecordModel->where('log_id',$res)->find();
            $PayRecordModel->_log($orderInfo,'生成订单.');

            if($balance_amount>0){
                #更新账户鼓励金
                $changedata['change_desc'] = '线下消费,鼓励金抵扣';
                $changedata['change_type'] = 16;
                $changedata['by_id'] = $res;
                $changedata['balance_money'] = -$balance_amount;
                $AccountLogModel = new AccountLogModel();
                $res1 = $AccountLogModel->change($changedata, $this->userInfo['user_id'], false);
                if(!$res1){
                    Db::rollback();// 回滚事务
                    return $this->ajaxReturn(['code' => 0,'msg' => '更新账户失败']);
                }
            }
            if($red_id>0){
                #更新红包状态
                $map2['status'] = 1;
                $map2['update_time'] = $time;
                $res2 = $RedbagModel->where('redbag_id',$red_id)->update($map2);
                if(!$res2){
                    Db::rollback();// 回滚事务
                    return $this->ajaxReturn(['code' => 0,'msg' => '更新红包状态失败']);
                }
            }

        }else{
            Db::rollback();// 回滚事务
            return $this->ajaxReturn(['code' => 0,'msg' => '生成订单失败']);
        }
        Db::commit();

        $return['log_id'] = $res;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取订单操作
    /*------------------------------------------------------ */
    public function action(){
        $log_id = input('log_id',0,'intval');
        $type = input('type','','trim');
        $config = config('config.');
        $upData['log_id'] = $log_id;
        switch ($type){
            case 'cancel'://取消
                $upData['status'] = $config['XX_CANCEL'];
                $upData['end_time'] = time();
                $_log = '用户取消订单';
                break;
            default:
                return $this->error('没有相关操作.');
                break;
        }
        $res = $this->Model->upInfo($upData);

        if ($res !== true) return $this->error($res);
        $orderInfo = $this->Model->where('log_id',$log_id)->find();
        $this->Model->_log($orderInfo,$_log);
        $return['status'] = $orderInfo['status'];
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
}