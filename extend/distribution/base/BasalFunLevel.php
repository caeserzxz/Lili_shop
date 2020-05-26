<?php
namespace distribution\base;

/*------------------------------------------------------ */
//-- 基础升级方案
//-- @author iqgmy
/*------------------------------------------------------ */
$i = (isset($modules)) ? count($modules) : 0;
$modules[$i]["name"] = "升级方案";
$modules[$i]["explain"] = "基础升级方案.";
$modules[$i]["val"][] = ['name' => 'referral', 'text' => '直推', 'input' => 'sel_role', 'tip' => '个'];
$modules[$i]["val"][] = ['name' => 'team_role', 'text' => '团队身份人数', 'input' => 'team_role', 'tip' => '个'];
$modules[$i]["val"][] = ['name' => 'team_total', 'text' => '团队总人数', 'rule' => 'integer', 'tip' => '个,包括自己'];
$modules[$i]["val"][] = ['name' => 'team_income', 'text' => '团队总业绩', 'rule' => 'ismoney', 'tip' => '元,包括自己业绩'];
$modules[$i]["val"][] = ['name' => 'total_income', 'text' => '个人业绩', 'rule' => 'ismoney', 'tip' => '元'];
$modules[$i]["val"][] = ['name' => 'total_consume', 'text' => '个人累计消费', 'input' => 'number', 'rule' => 'ismoney', 'tip' => '元'];
$modules[$i]["val"][] = ['name' => 'one_consume', 'text' => '个人单次消费', 'input' => 'number', 'rule' => 'ismoney', 'tip' => '元'];
$modules[$i]["val"][] = ['name' => 'buy_goods', 'text' => '指定商品', 'input' => 'sel_goods'];


class BasalFunLevel
{
    /*------------------------------------------------------ */
    //-- 执行级别更新
    // $usersInfo array 会员信息
    // $orderInfo array 订单信息
    // $stats array 汇总信息
    // $role array 当前准备提升的分销身份
    /*------------------------------------------------------ */
    public function judgeIsUp(&$usersInfo, &$orderInfo, &$stats, &$role)
    {
        $upLeveValue = $role['upleve_value'];
        //直推条件判断
        $status = false;
        foreach ($upLeveValue['referral'] as $rid => $val) {
            if ($val > 0) {
                $status = true;
                $count = $stats['subRoleCount'][$rid];
                if ($val > $count) {
                    $status = false;
                    break;
                }
            }
        }
        if ($role['up_condition'] == 2 && $status == false) {//不满足，失败
            return false;
        }
        if ($role['up_condition'] == 1 && $status == true) {//只需满足任一条件即可升级
            return true;
        }
        //直推条件判断end

        //团队身份人数条件判断
        $status = false;
        foreach ($upLeveValue['team_role'] as $rid => $val) {
            if ($val > 0) {
                $status = true;
                $count = $stats['teamRoleCount'][$rid];
                if ($val > $count) {
                    $status = false;
                    break;
                }
            }
        }
        if ($role['up_condition'] == 2 && $status == false) {//不满足，失败
            return false;
        }
        if ($role['up_condition'] == 1 && $status == true) {//只需满足任一条件即可升级
            return true;
        }
        //团队身份人数条件判断end

        //团队总人数
        if ($upLeveValue['team_total'] > 0){
            if ($upLeveValue['team_total'] > $stats['teamCount']){
                if ($role['up_condition'] == 2) {//不满足，失败
                    return false;
                }
            }elseif ($role['up_condition'] == 1) {//只需满足任一条件即可升级
                return true;
            }
        }
        //团队总人数end

        //团队总业绩
        if ($upLeveValue['team_income'] > 0){
            if ($upLeveValue['team_income'] > $stats['teamIncome']){
                if ($role['up_condition'] == 2) {//不满足，失败
                    return false;
                }
            }elseif ($role['up_condition'] == 1) {//只需满足任一条件即可升级
                return true;
            }
        }
        //团队总业绩end

        //个人总业绩
        if ($upLeveValue['total_income'] > 0){
            if ($upLeveValue['total_income'] > $usersInfo['account']['total_dividend']){
                if ($role['up_condition'] == 2) {//不满足，失败
                    return false;
                }
            }elseif ($role['up_condition'] == 1) {//只需满足任一条件即可升级
                return true;
            }
        }
        //个人总业绩end

        //累计消费
        if ($upLeveValue['total_consume'] > 0) {
            if ($upLeveValue['total_consume'] > $usersInfo['total_consume']) {
                if ($role['up_condition'] == 2) {//不满足，失败
                    return false;
                }
            } elseif ($role['up_condition'] == 1) {//只需满足任一条件即可升级
                return true;
            }
        }
        //累计消费end

        //单次消费(针对购买者)
        if ($usersInfo['user_id'] == $orderInfo['user_id'] && $upLeveValue['one_consume'] > 0) {
            if ($upLeveValue['one_consume'] > $orderInfo['order_amount']) {
                if ($role['up_condition'] == 2) {//不满足，失败
                    return false;
                }
            }elseif ($role['up_condition'] == 1) {//只需满足任一条件即可升级
                return true;
            }
        }
        //单次消费end

        return false;
    }
}

?>