<?php
/*------------------------------------------------------ */
//-- 提成处理
//-- Author: iqgmy
/*------------------------------------------------------ */

namespace distribution\base;

use app\BaseModel;
use think\Db;

use app\member\model\UsersModel;
use app\shop\model\OrderModel;
use app\shop\model\OrderGoodsModel;
use app\distribution\model\DividendAwardModel;
use app\distribution\model\DividendRoleModel;

class Dividend extends BaseModel
{
    public $UsersModel;

    public function __construct($Model)
    {
        parent::__construct();
        $this->UsersModel = new UsersModel();
        $this->Model = $Model;
    }

    /*------------------------------------------------------ */
    //-- 计算提成并记录或更新
    //-- $orderInfo array 订单数据
    //-- $type string 操作类型
    //-- $status int 分佣状态，操作类型为add时，根据传值设置默认状态
    //-- return bool 如果$type为add或订单为身份订单则返回数组
    /*------------------------------------------------------ */
    public function _eval(&$orderInfo, $type = '', $status = 0)
    {
        if ($orderInfo['is_split'] > 0) return true;//需要拆单的不执行
        $goodsList = [];
        //身份订单处理
        if ($orderInfo['d_type'] == 'role_order') {
            $status = 3;//待分成
            $upData = $this->saveLog($orderInfo, $goodsList, $status);//佣金计算
            if (is_array($upData) == false) {
                return '佣金计算失败.';
            }
            $upData['is_dividend'] = 1;
            $this->Model->evalArrival($orderInfo['order_id'], 'role_order');//身份订单直接执行分佣
            return $upData;//返回数组
        }//end

        $upData = [];//更新分佣记录状态
        $OrderModel = new OrderModel();
        $order_operating = '';
        $send_msg = false;
        //先计算佣金再执行升级处理
        if ($type == 'add') {//写入分佣，普通订单下单时执行
            $upData = $this->saveLog($orderInfo, $goodsList, $status);//佣金计算
            if (is_array($upData) == false) return false;
            if ($orderInfo['pid'] == 0 && $orderInfo['pay_status'] == $OrderModel->config['DD_PAYED']) {
                if ($orderInfo['is_dividend'] == 0) {//第一次生成时才发送模板消息
                    $this->Model->sendMsg('pay', $orderInfo['order_id']);//支付模板消息
                }
            }
            return $upData;//返回数组
        } elseif ($type == 'pay') {//订单支付成功
            $res = $this->evalLevelUp($orderInfo);//升级处理
            if ($res == false) return false;
            $upData['status'] = $OrderModel->config['DD_PAYED'];
            $send_msg = true;
        } elseif ($type == 'cancel') {//订单取消
            $upData['status'] = $OrderModel->config['DD_CANCELED'];
            $order_operating = '订单取消';
            $send_msg = true;
        } elseif ($type == 'unpayed') {//未付款
            if ($orderInfo['order_status'] == $OrderModel->config['OS_CANCELED']) {
                $upData['status'] = $OrderModel->config['DD_CANCELED'];
            } elseif ($orderInfo['order_status'] == $OrderModel->config['OS_RETURNED']) {
                $upData['status'] = $OrderModel->config['DD_RETURNED'];
            } else {
                $upData['status'] = $OrderModel->config['DD_UNCONFIRMED'];
            }
        } elseif ($type == 'shipping') {//发货
            $upData['status'] = $OrderModel->config['DD_SHIPPED'];
        } elseif ($type == 'unshipping') {//未发货
            $upData['status'] = $OrderModel->config['DD_PAYED'];
        } elseif ($type == 'sign') {//签收
            $upData['status'] = $OrderModel->config['DD_SIGN'];
        } elseif ($type == 'unsign') {//撤销签收
            return $this->Model->returnArrival($orderInfo['order_id'], 'unsign', $orderInfo['user_id']);
        } elseif ($type == 'returned') {//退货
            return $this->Model->returnArrival($orderInfo['order_id'], 'returned', $orderInfo['user_id']);
        }

        if (empty($upData) == false) {//更新分佣状态
            $upWhere[] = ['order_id', '=', $orderInfo['order_id']];
            $upWhere[] = ['order_type', '=', 'order'];
            $count = $this->Model->where($upWhere)->count();
            if ($count < 1) return true;//如果没有佣金记录不执行
            $upData['update_time'] = time();
            $res = $this->Model->where($upWhere)->update($upData);
            if ($res < 1) return false;
        }

        if ($send_msg == true) {
            $this->Model->sendMsg($type, $orderInfo['order_id'], $order_operating);//发送模板消息
        }

        if ($type == 'sign') {//签收,执行佣金到帐
            $shop_after_sale_limit = settings('shop_after_sale_limit');
            if ($shop_after_sale_limit == 0) {
                return $this->Model->evalArrival($orderInfo['order_id'], 'order');
            }
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 计算提成并记录或更新
    /*------------------------------------------------------ */
    public function saveLog(&$orderInfo, &$goodsList, $status = 0)
    {
        $awardList = (new DividendAwardModel)->order('award_type DESC')->select();//获取全部奖项目,按类型倒序，先处理管理奖
        if (empty($awardList)) return false;
        $dividend_amount = 0;//共分出去的佣金

        $nowLevel = 0;//当前处理级别
        $nowLevelOrdinary = [];//普通分销当前处理级别，普通分销有逐级计算和无限级计算，如果无限级，不满条件将一直最后的上级
        $assignAwardNum = [];//记录已分出去的管理奖金额
        $assignAwardUser = [];//记录已领管理奖的用户

        $delWhere[] = ['order_id', '=', $orderInfo['order_id']];
        $delWhere[] = ['order_type', '=', $orderInfo['d_type']];
        $this->Model->where($delWhere)->delete();//清理旧的提成记录，重新计算
        $buyUserInfo = $this->UsersModel->info($orderInfo['user_id']);//获取购买会员信息

        //身份佣金处理
        if ($orderInfo['d_type'] == 'role_order') {
            foreach ($awardList as $key => $award) {
                $parentId = $buyUserInfo['pid'];//获取购买会员直属上级ID
                if ($award['goods_limit'] != 3) {//非指定身份商品的奖项跳出
                    continue;
                }
                if (isset($nowLevelOrdinary[$key]) == false) {
                    $nowLevelOrdinary[$key] = 0;
                }
                $nowLevelOrdinary[$key] += 1;
                //判断身份是否满足条件
                $limit_role = explode(',', $award['limit_role']);
                if (in_array($buyUserInfo['role_id'], $limit_role) == false) {
                    continue;
                }
                //判断购买身份商品
                if ($award['role_goods'] != $orderInfo['rg_id']) {
                    continue;
                }

                if ($parentId > 0) {
                    $awardValue = json_decode($award['award_value'], true);//奖项内容
                    foreach ($awardValue as $value) {
                        $userInfo = $this->UsersModel->info($parentId);//获取会员信息
                        $parentId = $userInfo['pid'];//优先记录下次循环用户ID
                        $role_id = $userInfo['role_id'] * 1;
                        $num = $value['num'][$role_id];
                        if ($num <= 0) {
                            continue;
                        }
                        $num_type = $value['num_type'][$role_id];
                        if ($num_type == 'money') {//固定金额
                            $inArr['dividend_amount'] = $num;
                        } else {//订单百分比，扣除运费后计算
                            $amount = $orderInfo['order_amount'] - $orderInfo['shipping_fee'];
                            $inArr['dividend_amount'] = $amount / 100 * $num;
                            if ($inArr['dividend_amount'] <= 0) {//佣金小于等于0，跳过
                                continue;
                            }
                        }
                        $inArr['status'] = $status;
                        $inArr['order_type'] = $orderInfo['d_type'];
                        $inArr['order_id'] = $orderInfo['order_id'];
                        $inArr['order_sn'] = $orderInfo['order_sn'];
                        $inArr['buy_uid'] = $orderInfo['user_id'];
                        $inArr['order_amount'] = $orderInfo['order_amount'];
                        $inArr['dividend_uid'] = $userInfo['user_id'];
                        $inArr['role_id'] = $role_id;
                        $inArr['role_name'] = $userInfo['role']['role_name'];
                        $inArr['level'] = $value['level'];
                        $inArr['award_id'] = $award['award_id'];
                        $inArr['award_name'] = $award['award_name'];
                        $inArr['level_award_name'] = $value['name'];

                        $dividend_amount += $inArr['dividend_amount'];
                        $inArr['add_time'] = $inArr['update_time'] = time();
                        $res = $this->Model->create($inArr);
                        if ($res->log_id < 1) return false;
                        if ($parentId < 1) break;//没有找到上级终止
                    }
                }
            }
            return ['dividend_amount' => $dividend_amount];
        }//end

        $parentId = $buyUserInfo['pid'];//获取购买会员直属上级ID
        //普通订单奖项处理
        $roleList = (new DividendRoleModel)->getRows();
        $lastRole = $roleList[$orderInfo['dividend_role_id']]['level'];//下单会员下单时身份级别

        if ($parentId < 1) return ['dividend_amount' => $dividend_amount];//没有上级不执行

        $order_goods_ids = [];
        $order_goods_num = 0;
        $goods_buy_num = [];//购买商品的数量
        $buy_goods_name = [];
        foreach ($goodsList as $goods) {
            $order_goods_ids[] = $goods['goods_id'];
            $order_goods_num += $goods['goods_number'];
            $goods_buy_num[$goods['goods_id']] = $goods['goods_number'];
            $buy_goods_name[] = $goods['goods_name'];
        }
        $amount = $orderInfo['order_amount'] - $orderInfo['shipping_fee'] - $orderInfo['back_dividend_amount'];

        do {
            $nowLevel += 1;
            $userInfo = $this->UsersModel->info($parentId);//获取会员信息
            $parentId = $userInfo['pid'];//优先记录下次循环用户ID
            foreach ($awardList as $key => $award) {
                if ($award['goods_limit'] == 4) {//身份分销的跳过
                    continue;
                }
                if (isset($nowLevelOrdinary[$key]) == false) {
                    $nowLevelOrdinary[$key] = 0;
                }
                $nowLevelOrdinary[$key] += 1;
                $awardValue = json_decode($award['award_value'], true);    //奖项内容

                if ($award['goods_limit'] == 2) {//购买全部指定分销商品
                    $award_limit_buy_goods = explode(',', $award['buy_goods_id']);
                    $isOk = true;
                    foreach ($award_limit_buy_goods as $goods_id) {
                        if (in_array($goods_id, $order_goods_ids) == false) {//限制商品不存在购买中，失败跳过
                            $isOk = false;
                            continue;
                        }
                    }
                    if ($isOk == false) {//不满足购买限制，跳出
                        continue;
                    }
                    $goods_num = count($order_goods_ids) * $award['goods_limit_num'];
                    if ($order_goods_num < $goods_num) {
                        continue;
                    }
                } elseif ($award['goods_limit'] == 3) {//购买任意指定分销商品
                    $award_limit_buy_goods = explode(',', $award['buy_goods_id']);
                    $isOk = false;
                    foreach ($award_limit_buy_goods as $goods_id) {
                        if (in_array($goods_id, $order_goods_ids) == true) {//限制商品存在购买中，成功跳出
                            $isOk = true;
                        }
                    }
                    if ($isOk == false) {//不满足购买限制，跳出
                        continue;
                    }
                    if ($order_goods_num < $award['goods_limit_num']) {
                        continue;
                    }
                }

                //判断身份是否满足条件
                $limit_role = explode(',', $award['limit_role']);
                if (in_array($userInfo['role_id'], $limit_role) == false) {
                    if ($award['award_type'] == 1 && $award['ordinary_type'] == 1) {//普通分销奖，无限级计算时执行
                        $nowLevelOrdinary[$key] -= 1;
                    }
                    continue;
                }
                if ($award['award_type'] == 2) {//判断管理奖是否享受
                    if ($userInfo['role']['level'] <= $lastRole) {//上级身份低于下级身份或平级时跳出
                        continue;
                    }
                    if (empty($awardValue[$userInfo['role_id']])) {//没有找到相应奖项级别跳出
                        continue;
                    }
                    if (isset($assignAwardNum[$award['award_id']]) == false) {//未定义附值为0
                        $assignAwardNum[$award['award_id']] = 0;
                    }
                    $endAward = end($awardValue);//获取最后奖项
                    if ($assignAwardNum[$award['award_id']] >= $endAward['num']) {
                        unset($awardList[$key]);//管理奖已达最大分配值，终止，跳出
                        continue;
                    }
                    $awardVal = $awardValue[$userInfo['role_id']];//获取对应角色奖项
                    $lastRole = $userInfo['role']['level'];
                    $award_num = $awardVal['num'] - $assignAwardNum[$award['award_id']];//计算当前可分值
                    if ($award_num <= 0) {//已分完终止
                        unset($awardList[$key]);//移除已结束的奖项
                        continue;
                    }
                } else {
                    if ($award['award_type'] == 1 && $award['ordinary_type'] == 1) {//普通分销，无限级计算时，会员判断级别方式不一样
                        if (empty($awardValue[$nowLevelOrdinary[$key]])) {//没有找到相应奖项级别跳出，并移除奖项
                            unset($awardList[$key]);//移除奖项
                            continue;
                        }
                        $awardVal = $awardValue[$nowLevelOrdinary[$key]];
                    } else {
                        if (empty($awardValue[$nowLevel])) {//没有找到相应奖项级别跳出，并移除奖项
                            unset($awardList[$key]);//移除奖项
                            continue;
                        }
                        $awardVal = $awardValue[$nowLevel];
                    }
                }

                //执行奖项处理
                $inArr = [];
                if ($award['award_type'] == 1) {//普通分销奖
                    if ($awardVal['num_type'] == 'money') {//固定金额
                        $inArr['dividend_amount'] = $awardVal['num'];
                    } else {//订单百分比，扣除运费和退款后计算
                        $inArr['dividend_amount'] = $amount / 100 * $awardVal['num'];
                    }
                } elseif ($award['award_type'] == 2) {//管理奖
                    $assignAwardUser[] = $userInfo['user_id'];
                    $assignAwardNum[$award['award_id']] += $award_num;
                    if ($awardVal['num_type'] == 'money') {//固定金额
                        $inArr['dividend_amount'] = $award_num;
                    } else {//订单百分比，扣除运费和退款后计算
                        $inArr['dividend_amount'] = $amount / 100 * $awardVal['num'];
                    }
                }
                if ($inArr['dividend_amount'] > 0) {//佣金大于0执行
                    $dividend_amount += $inArr['dividend_amount'];//计算总佣金
                    $inArr['order_type'] = $orderInfo['d_type'];
                    $inArr['status'] = $status;
                    $inArr['order_id'] = $orderInfo['order_id'];
                    $inArr['order_sn'] = $orderInfo['order_sn'];
                    $inArr['buy_uid'] = $orderInfo['user_id'];
                    $inArr['order_amount'] = $amount;
                    $inArr['dividend_uid'] = $userInfo['user_id'];
                    $inArr['role_id'] = $userInfo['role_id'];
                    $inArr['role_name'] = $userInfo['role']['role_name'];
                    $inArr['level'] = $nowLevel;
                    $inArr['award_id'] = $award['award_id'];
                    $inArr['award_name'] = $award['award_name'];
                    $inArr['level_award_name'] = $awardVal['name'];
                    $inArr['add_time'] = $inArr['update_time'] = time();
                    $res = $this->Model->create($inArr);
                    if ($res->log_id < 1) return false;
                }
            }

            if (empty($awardList) == true) {//没有奖项可分了，终止
                $parentId = 0;
            }
        } while ($parentId > 0);
        return ['dividend_amount' => $dividend_amount];
    }
    /*------------------------------------------------------ */
    //-- 执行升级方案
    //-- $orderInfo array 订单信息
    //-- $isup bool 是否更新会员信息
    /*------------------------------------------------------ */
    public function evalLevelUp(&$orderInfo)
    {
        //执行分销身份升级处理
        $roleList = (new DividendRoleModel)->getRows();
        $LogSysModel = new \app\member\model\LogSysModel();
        $oldFun = '';
        $DividendInfo = settings('DividendInfo');
        $_roleList = array_merge(['role_name' => '粉丝', 'role_id' => 0, 'level' => 0], $roleList);
        $UsersBindSuperiorModel = new \app\member\model\UsersBindSuperiorModel();
        $user_id = $orderInfo['user_id'];
        $goodsList = (new OrderGoodsModel)->where('order_id', $orderInfo['order_id'])->select();
        do {
            unset($stats);
            $usersInfo = $this->UsersModel->info($user_id);//获取会员信息
            $userRoleLevel = 0;
            if ($usersInfo['role_id'] > 0) {
                $userRoleLevel = $roleList[$usersInfo['role_id']]['level'];//获取当前会员身份等级
            }
            $stats['subRoleCount'] = [];
            //汇总直推身份的会员数
            foreach ($_roleList as $role) {
                $where = [];
                $where['pid'] = $user_id;
                $where['role_id'] = $role['role_id'];
                $stats['subRoleCount'][$role['role_id']] = $this->UsersModel->where($where)->count();
            }
            //汇总直推身份的会员数end
            $stats['teamRoleCount'] = [];
            //汇总团队身份的会员数
            foreach ($_roleList as $role) {
                $where = [];
                $where[] = ['', 'exp', Db::raw("FIND_IN_SET('" . $user_id . "',ub.superior)")];
                $where[] = ['ub.user_id', '<>', $user_id];
                $where[] = ['u.role_id', '=', $role['role_id']];
                $stats['teamRoleCount'][$role['role_id']] = $UsersBindSuperiorModel->alias('ub')->join("users u", 'ub.user_id=u.user_id', 'left')->where($where)->count();
            }
            //汇总团队身份的会员数end
            //团队总人数（包含自己）
            $where = [];
            $where[] = ['', 'exp', Db::raw("FIND_IN_SET('" . $user_id . "',superior)")];
            $stats['teamCount'] = $UsersBindSuperiorModel->where($where)->count();
            //团队总人数（包含自己）end

            //团队总业绩（包含自己）
            $where = [];
            $where[] = ['', 'exp', Db::raw("FIND_IN_SET('" . $user_id . "',ub.superior)")];
            $stats['teamIncome'] = $UsersBindSuperiorModel->alias('ub')->join("users_account ua", 'ub.user_id=ua.user_id', 'left')->where($where)->Sum('total_dividend');
            //团队总业绩（包含自己）end

            $upRole = [];
            foreach ($roleList as $role) {
                if ($role['level'] <= $userRoleLevel) {//当前分销身份低于等于用户现身份，跳过
                    continue;
                }
                if ($role['is_auto'] == 9) {//手动调整,跳过
                    continue;
                }

                $upLeveValue = $role['upleve_value'];

                //购买会员额外处理
                if ($user_id == $orderInfo['user_id']) {
                    //购买身份商品直接升级
                    if ($orderInfo['d_type'] == 'role_order') {
                        if ($role['role_id'] == $orderInfo['role_id']) {
                            $upRole = $role;
                            break;
                        }
                    }
                    //指定购买商品
                    if (empty($upLeveValue['buy_goods']) == false) {
                        $is_up = false;
                        foreach ($goodsList as $goods) {
                            if (empty($upLeveValue['buy_goods'][$goods['goods_id']]) == false) {
                                if ($goods['goods_number'] >= $upLeveValue['buy_goods'][$goods['goods_id']]) {
                                    $is_up = true;//返回可升级
                                    break;
                                }
                            }
                        }
                        if ($is_up == true) {
                            $upRole = $role;
                            break;
                        }
                    }
                }
                //购买会员额外处理end


                $fun = $role['upleve_function'];
                if ($oldFun != $fun) {
                    $oldFun = $fun;
                    $Class = new $fun();
                }

                $res = $Class->judgeIsUp($usersInfo,$orderInfo, $stats, $role);//判断是否能升级
                if ($res == false && $DividendInfo['level_up_type'] == 1) {//当前会员不执行升级，终止
                    continue;//可跨级升级时调用
                }
                $upRole = $role;
                if ($DividendInfo['level_up_type'] == 0) {//逐级升时调用
                    break;//跳出循环进行升级操作
                }

            }

            if (empty($upRole) == false) {
                $upData['last_up_role_time'] = time();
                $upData['role_id'] = $upRole['role_id'];
                $res = $this->UsersModel->upInfo($user_id, $upData);
                if ($res < 1) {
                    return false;
                }
                $inData['edit_id'] = $user_id;
                $inData['log_info'] = '';
                if ($orderInfo['d_type'] == 'role_order' && $user_id == $orderInfo['user_id']) {
                    $inData['log_info'] = '购买身份商品，';
                }
                $inData['log_info'] .= '【' . ($usersInfo['role_id'] == 0 ? '粉丝' : $roleList[$usersInfo['role_id']]['role_name']) . '】升级为【' . $upRole['role_name'] . '】';
                $inData['module'] = request()->path();
                $inData['log_ip'] = request()->ip();
                $inData['log_time'] = time();
                $inData['user_id'] = 0;
                $LogSysModel->save($inData);
            }
            $user_id = $usersInfo['pid'];//继续处理上级id
        } while ($user_id > 0);
        return true;
    }

}
