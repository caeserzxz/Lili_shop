<?php

namespace app\shop\controller\api;

use app\ApiController;
use think\Db;

use app\shop\model\CartModel;
use app\mainadmin\model\PaymentModel;
use app\member\model\UserAddressModel;
use app\member\model\AccountLogModel;
use app\shop\model\BonusModel;
use app\shop\model\OrderModel;
use app\shop\model\OrderGoodsModel;
use app\shop\model\GoodsModel;
use app\shop\model\CategoryModel;
use app\shop\model\BonusListModel;
use app\member\model\UsersModel;
/*------------------------------------------------------ */
//-- 购物相关API
/*------------------------------------------------------ */

class Flow extends ApiController
{
    public $is_integral = 0;
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new CartModel();
         $this->Model->is_integral = $this->is_integral;
    }
    /*------------------------------------------------------ */
    //-- 添加购物车
    /*------------------------------------------------------ */
    public function addCart()
    {
        $goods_id = input('goods_id', 0, 'intval');
        $num = input('number', 1, 'intval');
        $type = input('type', '', 'trim');
        $prom_type = input('prom_type', 0, 'intval');
        $prom_id = input('prom_id', 0, 'intval');
        if ($type == 'onbuy') {
            $this->checkLogin();//验证登陆
        }
        if ($num < 1) $num = 1;
        $specifications = input('specifications', '', 'trim');
        $sku_id = input('sku_id', 0, 'intval');
        if ($specifications == 'undefined') $specifications = '';
        if ($goods_id < 1) return $this->error('传值失败，请重新尝试提交！');
        $rec_id = $this->Model->addToCart($goods_id, $num, $specifications, $sku_id, $type, $prom_type, $prom_id);
        if (is_numeric($rec_id) == false) {
            $return['code'] = 2;
            $return['msg'] = $rec_id;
            return $this->ajaxReturn($return);
//            return $this->error($rec_id);
        }
        if ($type != 'onbuy') {
            $return['cartInfo'] = $this->Model->getCartInfo();
            $return['msg'] = '添加购物车成功.';
        }
        $return['code'] = 1;
		$return['rec_id'] = $rec_id;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取购物车列表
    /*------------------------------------------------------ */
    public function getList()
    {
        $is_sel = input('is_sel', 0, 'intval');
		$recids = input('recids', '', 'trim');
        $return['cartInfo'] = $this->Model->getCartList($is_sel,false,$recids);
        $return['shipping_fee_type'] = settings('shipping_fee_type');
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取购物车信息
    /*------------------------------------------------------ */
    public function getCartInfo()
    {
        $return['cartInfo'] = $this->Model->getCartInfo();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 修改商品订购数量
    /*------------------------------------------------------ */
    public function editNum()
    {
        $rec_id = input('rec_id', 0, 'intval');
        $is_sel = input('is_sel', 0, 'intval');
		$recids = input('recids', '', 'trim');
        if ($rec_id < 1) return $this->error('传值失败，请重新尝试提交.');
        $num = input('num', 1, 'intval');
        if ($num < 1) return $this->error('订购数量不能小于1.');
        $where['rec_id'] = $rec_id;
        $res = $this->Model->updataGoods($rec_id, $num);
        if ($res != 1){
			if ($res === 0) return $this->error('');
			return $this->error($res);
		}
        $address_id = input('address_id', 0, 'intval');
        if ($address_id > 0) {
            $return['shippingFee'] = $this->evalShippingFee($address_id, true,$recids);
        }
        $return['cartInfo'] = $this->Model->getCartList($is_sel,true,$recids);
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 删除购物车的商品
    /*------------------------------------------------------ */
    public function delGoods()
    {
        $rec_id = input('rec_id', 0, 'intval');
        if ($rec_id < 1) return $this->error('传值失败，请重新尝试提交！');
        $this->Model->delGoods($rec_id);
        $return['cartInfo'] = $this->Model->getCartList();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 清除购物车无效的商品
    /*------------------------------------------------------ */
    public function clearInvalid()
    {
        $this->Model->clearInvalid();
        $return['cartInfo'] = $this->Model->getCartList();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 设置商品是否选中
    /*------------------------------------------------------ */
    public function setSel()
    {
        $rec_id = input('rec_id');
        if ($rec_id < 1 && $rec_id != 'all') return $this->error('传值失败，请重新尝试提交！');
        $is_select = input('is_select', 0, 'intval');
        $res = $this->Model->updateCart($rec_id, ['is_select' => $is_select]);
        $return['cartInfo'] = $this->Model->getCartList();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 删除选中的商品
    /*------------------------------------------------------ */
    public function delSelGoods()
    {
        $this->Model->delGoods();
        $return['cartInfo'] = $this->Model->getCartList();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /**计算运费
     * @param int $address_id 收货地址
     * @param bool $is_return 是否直接返回数据
     * @param string $recids 指定购物车中商品
     * @return mixed|void
     */
    public function evalShippingFee($address_id = 0, $is_return = false,$recids = '')
    {
        if ($address_id < 0) {
            $address_id = input('address_id', '0', 'intval');
			$recids = input('recids', '', 'trim');
        }
		$return['code'] = 1;
        if ($address_id < 1) {
            $return['shipping_fee'] = sprintf("%.2f", 0);
			if ($is_return == true) return $return;
            return $this->ajaxReturn($return);
        }
		$UserAddressModel = new UserAddressModel();
        $addressList = $UserAddressModel->getRows();
        $address = $addressList[$address_id];
        $cartList = $this->Model->getCartList(1, true,$recids);
        $shippingFee = $this->_evalShippingFee($address,$cartList);
        if ($is_return == true) return $shippingFee;
        $return['shippingFee'] = $shippingFee;
        return $this->ajaxReturn($return);
    }

    /**
     * @param $address 收货地址信息
     * @param $cartList 订购商品
     * @return mixed
     */
    private function _evalShippingFee($address,$cartList){
        $shipping_fee_type = settings('shipping_fee_type');
        if ($shipping_fee_type == 1){//供应商商品独立计算
            foreach ($cartList['supplyerGoods'] as $supplyer_id=>$sg){
                $_cartList = [];
                $_cartList['buyGoodsNum'] = 0;
                $_cartList['totalGoodsPrice'] = 0;
                $_cartList['buyGoodsWeight'] = 0;
                $_cartList['goodsList'] = [];
                foreach ($cartList['goodsList'] as $cg){
                    if ($cg['supplyer_id'] == $supplyer_id){
                        $_cartList['buyGoodsNum'] += $cg['goods_number'];
                        $_cartList['totalGoodsPrice'] += $cg['sale_price'] * $cg['goods_number'];
                        $_cartList['buyGoodsWeight']  += $cg['goods_weight'] * $cg['goods_number'];
                        $_cartList['goodsList'][] = $cg;
                    }
                }
                $_shippingFee = $this->Model->evalShippingFee($address,$_cartList);
                $_shippingFee = reset($_shippingFee);//现在只返回默认快递
                if (empty($shippingFee)){
                    $shippingFee = $_shippingFee;
                }else{
                    $shippingFee['shipping_fee'] += $_shippingFee['shipping_fee'];
                }
                $shippingFee['supplyerShippingFee'][$supplyer_id] = sprintf("%.2f", $_shippingFee['shipping_fee'] * 1);
            }
            $shippingFee['shipping_fee'] = sprintf("%.2f", $shippingFee['shipping_fee'] * 1);
        }else{
            $shippingFee = $this->Model->evalShippingFee($address,$cartList);
            $shippingFee = reset($shippingFee);//现在只返回默认快递
            $shippingFee['shipping_fee'] = sprintf("%.2f", $shippingFee['shipping_fee'] * 1);
        }
        return $shippingFee;
    }
    /*------------------------------------------------------ */
    //-- 执行下单
    /*------------------------------------------------------ */
    public function addOrder()
    {
        $this->checkLogin();//验证登录

        $address_id = input('address_id', 0, 'intval');
        if ($address_id < 0) return $this->error('请设置收货地址后，再操作.');
        $UserAddressModel = new UserAddressModel();
        $addressList = $UserAddressModel->getRows();
        $address = $addressList[$address_id];
        if (empty($address)) return $this->error('相关收货地址不存在.');
        $used_bonus_id = input('used_bonus_id', 0, 'intval');

        $balanceDeduction = input('balanceDeduction'); // 鼓励金抵扣金额

		$recids = input('recids', '', 'trim');
        $cartList = $this->Model->getCartList(1, true,$recids,false,false);

        if ($cartList['buyGoodsNum'] < 1) return $this->error('请选择订购商品.');

        if ($used_bonus_id > 0) {//优惠券验证
            $BonusModel = new BonusModel();
            $bonus = $BonusModel->binfo($used_bonus_id);
        }

        $GoodsModel = new GoodsModel();
        $supplyer_ids = [];//供应商ID

        $inArr['give_integral'] = 0;
        $inArr['settle_price'] =  0;
        $inArr['settle_goods_price'] =  0;
        $use_integral = 0;
        $rec_ids = [];
        $allGoodsSn = [];
        $allGoodsId = [];
        $allFavourId = [];
        $cartList['use_bonus_goods_amount'] = 0;//使用了优惠券的商品总额
        $FavourGoodsModel = new \app\favour\model\FavourGoodsModel();
        // 验证购物车中的商品能否下单
        foreach ($cartList['goodsList'] as $key => $grow) {
            $goods = $GoodsModel->info($grow['goods_id']);
            //活动信息相关：1-限时优惠
            $promInfo = [];
            if ($grow['prom_type'] == 1) {
                $promInfo = $FavourGoodsModel->getFavourInfo($grow['prom_id'], $grow['sku_id']);
            }

            // 判断是商品能否购买或修改
            $res = $this->Model->checkGoodsOrder($goods, $grow['goods_number'], $grow['sku_val'], $grow['prom_type'], $promInfo);
            if ($res !== true) return $this->error($res['msg']);

            $price = $GoodsModel->evalPrice($goods, $grow['goods_number'], $grow['sku_val'], $grow['prom_type'], $promInfo);//计算需显示的商品价格
            if ($this->is_integral == 0 && $grow['sale_price'] != $price['min_price']) {
                return $this->error('商品' . $grow['goods_name'] . $grow['sku_name'] . '价格发生变化，购物车价格：￥' . $grow['sale_price'] . '，当前价格：￥' . $price['min_price']);
            }
            $supplyer_ids[$grow['supplyer_id']] = 1;
            if ($grow['supplyer_id'] > 0) {//供应商商品计算供货总价
                $inArr['settle_goods_price'] += $grow['settle_price'] * $grow['goods_number'];
            }
            if ($grow['give_integral'] > 0) {//赠送积分总计
                $inArr['give_integral'] += $grow['give_integral'] * $grow['goods_number'];
            }
            if ($grow['use_integral'] > 0) {//扣减积分总计,组合购买时调用
                $use_integral += $grow['use_integral'] * $grow['goods_number'];
            }

            //使用了优惠券
            if ($used_bonus_id > 0) {
                $goods_amount = $grow['sale_price'] * $grow['goods_number'];//单个商品总金额
                $cartList['goodsList'][$key]['is_use_bonus'] = 0;
                if ($bonus['info']['use_type'] == 0) {//全场通用
                    $cartList['use_bonus_goods_amount'] += $goods_amount;
                    $cartList['goodsList'][$key]['is_use_bonus'] = 1;
                } elseif ($bonus['info']['use_type'] == 1) {//指定分类可用
                    $ClassList = (new CategoryModel)->getRows(); //商品分类列表
                    $use_by = explode(',', $bonus['info']['use_by']);
                    $cidInfoArr = [];//分类信息
                    foreach ($use_by as $cid) {
                        $cidInfoArr[] = $ClassList[$cid]['children'];
                    }
                    $cidInfo = join(',', $cidInfoArr);
                    $cidInfoArr = explode(',', $cidInfo);//转成数组
                    if (in_array($grow['cid'], $cidInfoArr)) {
                        $cartList['use_bonus_goods_amount'] += $goods_amount;
                        $cartList['goodsList'][$key]['is_use_bonus'] = 1;
                    }
                } elseif ($bonus['info']['use_type'] == 2) {//指定商品可用
                    $use_by = explode(',', $bonus['info']['use_by']);//可用的商品ID
                    if (in_array($grow['goods_id'], $use_by)) {//符合使用商品
                        $cartList['use_bonus_goods_amount'] += $goods_amount;
                        $cartList['goodsList'][$key]['is_use_bonus'] = 1;
                    }
                }
            }
            $allGoodsSn[$grow['goods_sn']] = 1;
            $allGoodsId[$grow['goods_id']] = 1;
            $rec_ids[] = $grow['rec_id'];
            if (empty($promInfo) == false) {
                $allFavourId[$promInfo['data']['goods']['fa_id']] = $promInfo['data']['goods']['fa_id'];
            }
            //购买返还调用
            $cartList['goodsList'][$key]['buy_brokerage_type'] = $goods['buy_brokerage_type'];
            $cartList['goodsList'][$key]['buy_brokerage_amount'] = $goods['buy_brokerage_amount'];
        }



        $inArr['use_bonus'] = 0;
        if ($used_bonus_id > 0) {//优惠券验证
            if ($bonus['user_id'] != $this->userInfo['user_id']) {
                return $this->error('优惠券出错，请核实.');
            }
            if ($bonus['status'] == 2) {
                return $this->error('优惠券无法使用：已失效');
            }
            if ($bonus['info']['stauts'] != 1) {
                return $this->error('优惠券无法使用：' . $bonus['info']['stauts_info']);
            }
            if ($cartList['totalGoodsPrice'] < $bonus['info']['min_amount']) {
                return $this->error('选择的优惠券满￥' . $bonus['info']['min_amount'] . '才可以使用，请核实.');
            }
            $inArr['use_bonus'] = $bonus['info']['type_money'];
        }
        $time = time();
        $inArr['order_status'] = 0;
        $inArr['pay_status'] = 0;
        $inArr['shipping_status'] = 0;
        $_log = '生成订单';
        //运费处理
        $shippingFee = $this->_evalShippingFee($address, $cartList);
        $inArr['shipping_fee'] = $shippingFee['shipping_fee'] * 1;
        $inArr['shipping_fee_detail'] = json_encode($shippingFee['supplyerShippingFee']);

        if (empty($supplyer_ids) == false){
            $supplyer_ids = array_keys($supplyer_ids);
            if (count($supplyer_ids) > 1){
                $inArr['is_split'] = 1;//供应商两个以上，需要进行拆单
            }else{
                $inArr['supplyer_id'] = reset($supplyer_ids);//获取唯一的供应商id
            }
        }
        $inArr['settle_price'] = $inArr['settle_goods_price'] + $inArr['shipping_fee'];

        //优惠金额处理
        if ($inArr['use_bonus'] > $cartList['use_bonus_goods_amount']) {
            $inArr['use_bonus'] = $cartList['use_bonus_goods_amount'];
        }
        $inArr['order_amount'] = $cartList['orderTotal'] + $inArr['shipping_fee'] - $inArr['use_bonus'];
        $inArr['order_amount'] -= $balanceDeduction;

        /*if ($inArr['order_amount'] > 0){
            $pay_id = input('pay_id', 0, 'intval');
            if ($pay_id < 0) return $this->error('请选择支付方式.');
            $PaymentModel = new PaymentModel();
            $paymentList = $PaymentModel->getRows(true);
            $payment = $paymentList[$pay_id];
        }elseif($this->is_integral == 1){//积分支付
            $payment['pay_code'] = 'integral';
            $payment['is_pay'] = 1;
            $payment['pay_name'] = '积分兑换';
        }*/
        if ($this->is_integral == 1){
            $payment['pay_code'] = 'integral';
            $payment['is_pay'] = 1;
            $payment['pay_name'] = '积分兑换';
        }else{//积分支付
            $pay_id = input('pay_id', 0, 'intval');
            if ($pay_id < 0) return $this->error('请选择支付方式.');
            $PaymentModel = new PaymentModel();
            $paymentList = $PaymentModel->getRows();
            $payment = $paymentList[$pay_id];
        }

        if (empty($payment)) return $this->error('相关支付方式不存在或已停用.');

        // if ($payment['pay_code'] == 'balance') {//如果使用余额，判断用户余额是否足够
            // if ($inArr['order_amount'] > $this->userInfo['account']['balance_money']) {
            if ($balanceDeduction > $this->userInfo['account']['balance_money']) {
                return $this->error('余额不足，请使用其它支付方式.');
            }
        // }
        $inArr['balance_deduction'] = $balanceDeduction;
        $inArr['order_type'] = $this->is_integral;//订单类型，0普通订单,1积分订单

        if($use_integral > 0){
            if ($use_integral > $this->userInfo['account']['use_integral']) {
                return $this->error('积分不足无法兑换，你的积分余额为：'.$this->userInfo['account']['use_integral']);
            }
            $inArr['use_integral'] = $use_integral;
        }

        $inArr['buyer_message'] = input('buy_msg', '', 'trim');
        $inArr['consignee'] = $address['consignee'];
        $inArr['address'] = $address['address'];
        $inArr['merger_name'] = $address['merger_name'];
        $inArr['province'] = $address['province'];
        $inArr['city'] = $address['city'];
        $inArr['district'] = $address['district'];
        $inArr['mobile'] = $address['mobile'];

        $inArr['add_time'] = $time;
        $inArr['user_id'] = $this->userInfo['user_id'];
        $inArr['dividend_role_id'] = $this->userInfo['role_id'];
        $inArr['pay_id'] = $payment['pay_id'];
		$inArr['pay_code'] = $payment['pay_code'];
        $inArr['pay_name'] = $payment['pay_name'];
        $inArr['discount'] = $cartList['totalDiscount'];
        $inArr['goods_amount'] = $cartList['totalGoodsPrice'];
        $inArr['buy_goods_sn'] = join(',', array_keys($allGoodsSn));
        $inArr['buy_goods_id'] = join(',', array_keys($allGoodsId));
        $inArr['favour_id'] = join(',', array_keys($allFavourId));
        $inArr['ipadderss'] = request()->ip();
        $inArr['is_pay'] = $payment['is_pay'];//是否需要支付,1线上支付，0，不需要支付，
        $inArr['is_success'] = 1;//普通订单默认有效，如果拼团默认为0，须拼团成功才会为1
        //执行商品库存和销量处理，后台设置下单减库存或余额支付时执行
        $shop_reduce_stock = settings('shop_reduce_stock');
        $inArr['is_stock'] = $shop_reduce_stock == 0 ? 1 : 0;
        Db::startTrans();//启动事务
        $OrderModel = new OrderModel();
        $inArr['order_sn'] = $OrderModel->getOrderSn();
        $res = $OrderModel->save($inArr);
        if ($res < 1) {
            Db::rollback();// 回滚事务
            return $this->error('未知原因，订单写入失败.');
        }
        $order_id = $OrderModel->order_id;

        $inArr['order_id'] = $order_id;
        $res = $OrderModel->_log($inArr,$_log);
        if ($res < 1) {
            Db::rollback();// 回滚事务
            return $this->error('未知原因，订单日志写入失败.');
        }
        //使用积分，下单即扣除
        if ($use_integral > 0){
            $changedata['use_integral'] = $use_integral * -1;
            $changedata['change_desc'] = '购物抵扣积分';
            $changedata['change_type'] = 3;
            $changedata['by_id'] = $order_id;
            $res = (new AccountLogModel)->change($changedata, $this->userInfo['user_id'], false);
            if ($res !== true) {
                Db::rollback();// 回滚事务
                return $this->error('扣减积分失败失败.');
            }
        }

        //执行扣库存
        if ($inArr['is_stock'] == 1) {
            if ($inArr['order_type'] == 1) {//积分订单
                $res = (new \app\integral\model\IntegralGoodsListModel)->evalGoodsStore($cartList['goodsList']);
            } else {
                $res = $GoodsModel->evalGoodsStore($cartList['goodsList']);
            }
            if ($res != true) {
                Db::rollback();//回滚
                return $this->error('扣库存失败.');
            }
        }
        //end
        //处理优惠券
        if ($used_bonus_id > 0) {
            $BonusModel = new BonusModel();
            $BonusListModel = new BonusListModel();
            $upArr = array();
            $upArr['used_time'] = $time;
            $upArr['order_id'] = $order_id;
            $upArr['order_sn'] = $inArr['order_sn'];
            $upArr['status'] = 1;
            $res = $BonusListModel->where('bonus_id', $used_bonus_id)->update($upArr);
            //增加优惠券使用数量
            $res1 = $BonusModel->where('type_id', $bonus['type_id'])->setInc('use_num', 1);
            if ($res < 1 || $res1 < 1) {
                Db::rollback();// 回滚事务
                return $this->error('未知错误，修改优惠券失败.');
            }
        }
        //end
        $this->addOrderGoods($order_id, $cartList, $bonus);//写入商品
        Db::commit();// 提交事务
        //订单下单消息提示
        \lib\OrderMessage::set($this->userInfo['headimgurl'],$this->userInfo['nick_name']);
        $return['order_id'] = $order_id;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 写入订单商品
    //--- 这里可能有bug,如果用户同时执行多次，商品有可能发生错误
    /*------------------------------------------------------ */
    public function addOrderGoods($order_id, $cartList, $bonus)
    {
        $OrderModel = new OrderModel();
        $orderInfo = $OrderModel->info($order_id);

        $orderGoods = [];
        $cart_ids = [];
        $add_time = time();
        $bonus_money = $bonus['info']['type_money'];
        $goodsBonus = [];
        $goodsBonus['totalMoney'] = 0;
        foreach ($cartList['goodsList'] as $key => $og) {
            if (empty($bonus) == false) {
                if ($og['is_use_bonus'] == 1) {
                    $goodsBonus['goods_end'] = $og['goods_id'];
                    $goodsBonus['goods'][$og['goods_id']] = $og['sale_price'] * $og['goods_number'];
                    $goodsBonus['totalMoney'] += $og['sale_price'] * $og['goods_number'];
                }
            }
        }
        foreach ($cartList['goodsList'] as $key => $og) {
            $cart_ids[] = $og['rec_id'];
            $bonus_ids = 0;
            $bonus_after_price = 0;
            $usd_bonus_price = 0;
            if (empty($bonus) == false) {
                if ($og['is_use_bonus'] == 1) {
                    $bonus_ids = $bonus['bonus_id'];
                    $scale = $og['sale_price'] / $cartList['use_bonus_goods_amount'];//对比总订单商品价格占比
                    $use_bonus = bcmul($bonus['info']['type_money'], $scale, 2);//精确两位小数，不四舍五入
                    $bonus_after_price = $og['sale_price'] - $use_bonus;
                    $per = $goodsBonus['goods'][$og['goods_id']] / $goodsBonus['totalMoney'];
                    if ($goodsBonus['goods_end'] == $og['goods_id']){
                        $usd_bonus_price = $bonus_money;
                    }else{
                        $usd_bonus_price = bcmul($bonus['info']['type_money'], $per, 2);
                        $bonus_money -= $usd_bonus_price;
                    }
                }
            }
            if ($orderInfo['balance_deduction']) {
                $balance_deduction = $og['sale_price'] / $orderInfo['goods_amount'] * $orderInfo['balance_deduction'];
            }

            $goods = array(
                'order_id' => $order_id,
                'brand_id' => $og['brand_id'],
                'cid' => $og['cid'],
                'supplyer_id' => $og['supplyer_id'],
                'prom_type' => $og['prom_type'],
                'prom_id' => $og['prom_id'],
                'goods_id' => $og['goods_id'],
                'goods_name'=>$og['goods_name'],
                'sku_id' => $og['sku_id'],
                'sku_val' => $og['sku_val'],
                'sku_name' => $og['sku_name'],
                'pic' => $og['pic'],
                'goods_sn' => $og['goods_sn'],
                'goods_number' => $og['goods_number'],
                'market_price' => $og['market_price'],
                'shop_price' => $og['shop_price'],
                'sale_price' => $og['sale_price'],
                'settle_price' => $og['settle_price'],
                'goods_weight' => $og['goods_weight'],
                'discount' => $og['discount'],
                'add_time'=>$add_time,
                'user_id' => $og['user_id'],
                'give_integral' => $og['give_integral'],
                'use_integral' => $og['use_integral'],
                'is_dividend' => $og['is_dividend'],
                'bonus_ids' => $bonus_ids,
                'bonus_after_price' => $bonus_after_price,
                'usd_bonus_price' => $usd_bonus_price,
                'buy_brokerage_type' => $og['buy_brokerage_type'],
                'buy_brokerage_amount' => $og['buy_brokerage_amount'],
                'balance_deduction' => $balance_deduction
            );
            $orderGoods[] = $goods;
        }
        $res = (new OrderGoodsModel)->insertAll($orderGoods);
        if ($res < 1) {
            Db::rollback();// 回滚事务
            return $this->error('未知原因，订单商品写入失败.');
        }

        $where[] = ['rec_id', 'in', $cart_ids];
        $this->Model->where($where)->delete();// 清理购物车的商品
        $this->Model->cleanMemcache();
        return $res;
    }


    /*------------------------------------------------------ */
    //-- 续付款-改成线下支付通道
    //--- wemk   20200103
    /*------------------------------------------------------ */
    public function toOffline(){

        $wh['order_id'] = input('order_id',0,'intval');
        if($wh['order_id'] <= 0){
            return $this->error('非法操作.');
        }

        $cd['offlineimg'] = input('payimg','','trim');
        if($cd['offlineimg'] == ''){
            return $this->error('请先上传付款凭证.');
        }

        $OrderModel = new OrderModel();
        $order = $OrderModel->find($wh['order_id']);
        if($this->userInfo['user_id'] != $order['user_id']){
            return $this->error('无订单操作权限.');
        }

        if($order['order_status'] != 0 || $order['pay_status'] != 0){
            return $this->error('订单状态异常.');
        }

        if($order['money_paid'] > 0){
            return $this->error('改订单已使用其他付款方式付款,无法改为线下支付.');
        }

        $PaymentModel = new PaymentModel();
        //此处写死线下支付
        $newpayment = $PaymentModel->where(['pay_code'=>'offline','status'=>1])->find();

        if(!$newpayment){
            return $this->error('支付方式已停用.');
        }

        //订单更改数据
        $cd['pay_id'] = $newpayment['pay_id'];
        $cd['pay_code'] = $newpayment['pay_code'];
        $cd['pay_name'] = $newpayment['pay_name'];
        $cd['is_pay'] = 2;
        $cd['update_time'] = time();

        //日志数据
        $log['order_id'] = $order['order_id'];
        $log['order_status'] = $order['order_status'];
        $log['shipping_status'] = $order['shipping_status'];
        $log['pay_status'] = $order['pay_status'];
        $_log = '支付方式由['.$order['pay_name'].']变更为['.$newpayment['pay_name'].']';

        Db::startTrans();
        //更新订单
        $res = $OrderModel->where($wh)->update($cd);
        if(!$res){
            Db::rollback();
            return $this->error('支付方式异常，请联系管理员.');
        }

        //添加日志
        $res = $OrderModel->_log($log,$_log);
        if ($res < 1) {
            Db::rollback();// 回滚事务
            return $this->error('未知原因，订单日志写入失败.');
        }
        
        //更新缓存
        $OrderModel->cleanMemcache($order['order_id']);

        Db::commit();
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
}
