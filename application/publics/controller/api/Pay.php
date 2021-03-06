<?php
namespace app\publics\controller\api;
use app\ApiController;

use app\mainadmin\model\PaymentModel;

/*------------------------------------------------------ */
//-- 支付相关API
/*------------------------------------------------------ */
class Pay extends ApiController{

	/*------------------------------------------------------ */
	//-- 优先执行
	/*------------------------------------------------------ */
	public function initialize(){
        parent::initialize();
        $this->Model = new PaymentModel();
    }
	/*------------------------------------------------------ */
	//-- 获取支付列表
	/*------------------------------------------------------ */
 	public function getList(){
        $payList = $this->Model->getRows(true,'pay_code');
        $type = input('type','','trim');
        if ($type == 'is_recharge'){
            foreach ($payList as $key=>$pay){
                if ($pay['is_recharge'] == 0){
                    unset($payList[$key]);
                }
            }
        }
        $mode = input('mode','','trim');
        if($mode=='offline'){
            #屏蔽掉余额支付 线下打款  货到付款
            foreach ($payList as $key=>$pay){
                if($key=='cod'||$key=='balance'||$key=='offline'){
                    unset($payList[$key]);
                }
            }
        }

        $pay_type = input('pay_type','','trim');
        if(empty($pay_type)==false){
            if($pay_type=='Weixin'){
                foreach ($payList as $key=>$pay){
                   if($key!='weixin'&&$key!='miniAppPay'&&$key!='appWeixinPay'){
                       unset($payList[$key]);
                   }
                }
            }else if($pay_type=='Alipay'){
                foreach ($payList as $key=>$pay){
                    if($key!='alipayApp'&&$key!='alipayMobile'){
                        unset($payList[$key]);
                    }
                }
            }

        }

        $return['data'] = $payList;
        $return['balance_money'] = $this->userInfo['account']['balance_money'];
		$return['code'] = 1;
		return $this->ajaxReturn($return);
	}

}
