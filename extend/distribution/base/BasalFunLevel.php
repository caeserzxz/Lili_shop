<?php
namespace distribution\base;
use app\member\model\UsersModel;
/*------------------------------------------------------ */
//-- 基础升级方案
//-- @author iqgmy
/*------------------------------------------------------ */
$i = (isset($modules)) ? count($modules) : 0;
$modules[$i]["name"] = "升级方案";
$modules[$i]["explain"] = "基础升级方案.";
$modules[$i]["val"][] = ['name'=>'referral','text'=>'直推','input'=>'sel_role','tip'=>'个'];
$modules[$i]["val"][] = ['name'=>'team_role','text'=>'团队身份人数','input'=>'team_role','tip'=>'个'];
$modules[$i]["val"][] = ['name'=>'team_total','text'=>'团队总人数','rule'=>'integer','tip'=>'个'];
$modules[$i]["val"][] = ['name'=>'team_score','text'=>'团队总业绩','rule'=>'ismoney','tip'=>'元,包括自己业绩'];
$modules[$i]["val"][] = ['name'=>'total_score','text'=>'个人业绩','rule'=>'ismoney','tip'=>'元'];
$modules[$i]["val"][] = ['name'=>'total_consume','text'=>'个人累计消费','input'=>'number','rule'=>'ismoney','tip'=>'元'];
$modules[$i]["val"][] = ['name'=>'one_consume','text'=>'个人单次消费','input'=>'number','rule'=>'ismoney','tip'=>'元'];
$modules[$i]["val"][] = ['name'=>'buy_goods','text'=>'指定商品','input'=>'sel_goods'];


class BasalFunLevel{
	/*------------------------------------------------------ */
	//-- 执行级别更新
	// $user_id int 会员ID
	// $role array 当前准备提升的分销身份
	// $orderInfo array 订单信息
	// $goodsList array 订单商品
	/*------------------------------------------------------ */ 
	public function judgeIsUp($user_id,&$role,&$orderInfo,&$goodsList){
        static $UsersModel;
        if (!isset($UsersModel)){
            $UsersModel = new UsersModel();
        }
        if ($orderInfo['user_id'] != $user_id) return false;
        $upLeveValue = $role['upleve_value'];
		if ($upLeveValue['is_auto'] == 9) return false;

        //购买身份商品直接升级
        if ($orderInfo['d_type'] == 'role_order'){
            if ($role['role_id'] == $orderInfo['role_id']){
                return true;
            }
        }

		//直推条件判断
		foreach ($upLeveValue['referral'] as $rid=>$referral){
		    if ($referral > 0){
		        $where = [];
                $where['pid'] = $user_id;
                $where['role_id'] = $rid;
                $count = $UsersModel->where($where)->count();
                if ($referral > $count){
                    return false;
                }
            }
        }

        //累计消费
        if ($upLeveValue['total_consume'] > 0){
           $total_consume = $UsersModel->where('user_id',$user_id)->value('total_consume');
            $total_consume  += $orderInfo['order_amount'];
           if ($upLeveValue['total_consume'] > $total_consume){
               return false;
           }
        }
        //单次消费
        if ($upLeveValue['one_consume'] > 0){
            if ($upLeveValue['one_consume'] > $orderInfo['order_amount']){
                return false;
            }
        }
        //指定购买商品
        if (empty($upLeveValue['buy_goods']) == false){
            foreach ($goodsList as $goods){
                if (empty($upLeveValue['buy_goods'][$goods['goods_id']]) == false){
                    if ($goods['goods_number'] >= $upLeveValue['buy_goods'][$goods['goods_id']]){
                        return true;//返回可升级
                    }
                }
            }
            return false;//未满足条件
        }
        return true;
	}
}
?> 