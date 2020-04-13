<?php
/*------------------------------------------------------ */
//-- 售后
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\shop\controller;
use app\ClientbaseController;
use app\shop\model\OrderModel;
use app\shop\model\OrderGoodsModel;
use app\shop\model\AfterSaleModel;
use app\shop\model\ShippingModel;
use app\supplyer\model\SupplyerModel;

class AfterSale extends ClientbaseController{
    /*------------------------------------------------------ */
    //-- 我的售后
    /*------------------------------------------------------ */
    public function index(){
        $this->assign('title','我的售后');
        return $this->fetch('index');
    }
    /*------------------------------------------------------ */
    //-- 售后详情
    /*------------------------------------------------------ */
    public function info(){
        $id = input('id',0,'intval');
        if ($id < 1) return $this->error('传参错误.');
        $AfterSaleModel = new AfterSaleModel();
        $info = $AfterSaleModel->find($id);
        if (empty($info)) return $this->error('没有找到相关售后信息.');
        if ($info['user_id'] != $this->userInfo['user_id']){
            return $this->error('你没有权限查看此售后.');
        }
        $goods = (new OrderGoodsModel)->find($info['rec_id']);
        $goods['exp_prcie'] = explode('.',$goods['sale_price']);
        $this->assign('goods',$goods);
        $info['imgs'] = explode(',',$info['imgs']);
        $info['_status'] = $AfterSaleModel->status[$info['status']];
        $this->assign('info',$info);
        $this->assign('title','售后详情');
        if ($info['status'] >= 2){
            if ($info['supplyer_id']>0){
                $SupplyerModel = new SupplyerModel();
                $returnInfo = $SupplyerModel->find($info['supplyer_id'])->toArray();
            }else{
                $settings = settings();
                $returnInfo['return_consignee'] = $settings['return_consignee'];
                $returnInfo['return_mobile'] = $settings['return_mobile'];
                $returnInfo['return_address'] = $settings['return_address'];
                $returnInfo['return_desc'] = $settings['return_desc'];
            }
            $where[] = ['is_zt','=',0];
            $where[] = ['status','=',1];
            $where[] = ['is_front','=',1];
            $shippingList = (new ShippingModel)->where($where)->select()->toArray();
            $this->assign('shippingList',$shippingList);
            $this->assign('returnInfo',$returnInfo);
        }
        return $this->fetch('info');
    }
	/*------------------------------------------------------ */
	//-- 申请售后
	/*------------------------------------------------------ */
	public function add(){
	    $rec_id = input('rec_id',0,'intval');
	    if ($rec_id < 1){
	        return $this->error('传参失败.');
        }
        $OrderGoodsModel = new OrderGoodsModel();
        $goods = $OrderGoodsModel->find($rec_id);
        if (empty($goods)){
            return $this->error('没有找到相关商品.');
        }

        if ($goods['goods_number'] -  $goods['after_sale_num'] < 1){
            return $this->error('此订单商品售后已全部申请，不能继续操作.');
        }
        $orderInfo = (new OrderModel)->info($goods['order_id']);
        if ($orderInfo['isAfterSale'] == 0){
            return $this->error('此订单不能申请售后，请联系客服.');
        }


        $usd_bonus_price = $goods['usd_bonus_price'];
        $one_bonus_price = priceFormat($usd_bonus_price / $goods['goods_number'],false,6);
        $return_bouns_money = $goods['after_sale_num'] * $one_bonus_price;
        $return_price = $goods['sale_price'] - $one_bonus_price;//单价退款金额
        $end_return_all = $goods['sale_price'] * $goods['return_num'] - ($usd_bonus_price - $return_bouns_money);//全退金额

        $this->assign('end_return_all', $end_return_all);
        $this->assign('return_price', $return_price);

        $this->assign('return_price', $return_price);
        //end
        $goods['exp_prcie'] = explode('.',$goods['sale_price']);
        $this->assign('goods',$goods);
        $this->assign('title','申请售后');
		return $this->fetch('add');
	}

}?>