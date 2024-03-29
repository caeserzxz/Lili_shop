<?php

namespace app\publics\model;

use app\member\model\RechargeLogModel;
use app\shop\model\OrderModel;
use app\distribution\model\RoleOrderModel;
use app\activity\model\OrderModel as AcOrderModel;
use app\unique\model\PayRecordModel;

//*------------------------------------------------------ */
//-- 支付处理
/*------------------------------------------------------ */

class UpdatePayModel
{
    /**
     * 更新订单支付状态
     */
    public function update($data)
    {
        $order_sn = $data['order_sn'];
        if(strstr($order_sn,'sn')){
            #线下商家消费
            $PayRecordModel = new PayRecordModel();
            $orderInfo = $PayRecordModel->where('order_sn', $order_sn)->find();
            if ($orderInfo['status'] == 1) return true;
            if ((string)($orderInfo['amount_actual'] * 100) != (string)$data['total_fee']) {
                return false; //验证失败
            }
            return $PayRecordModel->updatePay(array('log_id' => $orderInfo['log_id'], 'money_paid' => $orderInfo['amount'], 'transaction_id' => $data["transaction_id"]), '线下消费支付成功，流水号：' . $data["transaction_id"]);// 修改订单支付状态
        }else{
            #线上商城消费
            //购买身份商品
            if (stripos($order_sn, 'role') !== false) {
                if (strlen($order_sn) > 17) {
                    $order_sn = substr($order_sn, 0, 17);
                }
                $RoleOrderModel = new RoleOrderModel();
                $orderInfo = $RoleOrderModel->where('order_sn', "$order_sn")->field('order_id,order_amount,user_id,pay_status')->find();
                if (empty($orderInfo)) return false;
                $orderInfo = $orderInfo->toArray();
                if ($orderInfo['pay_status'] == 1) return true;
                if ((string)($orderInfo['order_amount'] * 100) != (string)$data['total_fee']) {
                    return false; //验证失败
                }
                $orderInfo['transaction_id'] = $data["transaction_id"];
                return $RoleOrderModel->updatePay($orderInfo);// 修改订单支付状态
            } elseif (stripos($order_sn, 'ac') !== false) {//活动订单
                if (strlen($order_sn) > 15) {
                    $order_sn = substr($order_sn, 0, 15);
                }
                $OrderModel = new AcOrderModel();
                $orderInfo = $OrderModel->where('order_sn', "$order_sn")->field('order_id,order_amount,user_id,pay_status,activity_id')->find();
                if (empty($orderInfo)) return false;
                $orderInfo = $orderInfo->toArray();
                if ($orderInfo['pay_status'] == 1) return true;
                if ((string)($orderInfo['order_amount'] * 100) != (string)$data['total_fee']) {
                    return false; //验证失败
                }
                $orderInfo['transaction_id'] = $data["transaction_id"];
                return $OrderModel->updatePay($orderInfo);// 修改订单支付状态
            } elseif (stripos($order_sn, 'recharge') !== false) {//用户在线充值
                if (strlen($order_sn) > 20) {
                    $order_sn = substr($order_sn, 0, 20);
                }
                $RechargeLogModel = new RechargeLogModel();
                $orderInfo = $RechargeLogModel->where('order_sn', "$order_sn")->field('order_id,order_amount,user_id,status')->find();
                if (empty($orderInfo)) return false;
                $orderInfo = $orderInfo->toArray();
                if ($orderInfo['status'] == 9) return true;
                if ((string)($orderInfo['order_amount'] * 100) != (string)$data['total_fee']) {
                    return false; //验证失败
                }
                $orderInfo['transaction_id'] = $data["transaction_id"];
                return $RechargeLogModel->updatePay($orderInfo);// 修改订单支付状态
            } else {
                if (strlen($order_sn) > 13) {
                    $order_sn = substr($order_sn, 0, 13);
                }
                $OrderModel = new OrderModel();
                $orderInfo = $OrderModel->where('order_sn', "$order_sn")->field('order_id,order_amount,pay_status')->find();
                if (empty($orderInfo)) {
                    return false;
                }
                if ($orderInfo['pay_status'] == 1) return true;
                if ((string)($orderInfo['order_amount'] * 100) != (string)$data['total_fee']) {
                    return false; //验证失败
                }
                return $OrderModel->updatePay(array('order_id' => $orderInfo['order_id'], 'money_paid' => $orderInfo['order_amount'], 'transaction_id' => $data["transaction_id"]), '支付成功，流水号：' . $data["transaction_id"]);// 修改订单支付状态
            }
        }
        return false;
    }
}
