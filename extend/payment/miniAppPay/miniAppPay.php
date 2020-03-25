<?php
/**
 *  微信支付插件

 */
namespace payment\miniAppPay;
use app\mainadmin\model\PaymentModel;
/**
 * 支付 逻辑定义
 * Class
 * @package Home\Payment
 */

class miniAppPay
{
    /**
     * 析构流函数
     */
    public function  __construct($code=""){
        $post = file_get_contents('php://input');
        trace('mini_pay:'.$post,'debug');
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");
	    if(!$code){
            $code = 'miniAppPay';
        }
        $this->setting = settings();
        $payment =  (new PaymentModel)->where('pay_code', 'miniAppPay')->find();
        $config = json_decode(urldecode($payment['pay_config']),true);

        \WxPayConfig::$appid = $config['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        \WxPayConfig::$mchid = $config['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        \WxPayConfig::$key = $config['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        \WxPayConfig::$appsecret = $config['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，
        \WxPayConfig::$app_type = $code;
    }

    /**
     * 服务器点对点响应操作给支付接口方调用
     *
     */
    function response()
    {
        $post = file_get_contents('php://input');
		trace('response_pay:'.$post,'debug');
        require_once("example/notify.php");
        $notify = new \PayNotifyCallBack();
        $notify->Handle(false);
    }

    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        // 微信扫码支付这里没有页面返回
    }

    function getJSAPI($order,$open_id)
    {
    	if(stripos($order['order_sn'],'recharge') !== false){
    		$go_url = url('member/wallet/index',array('type'=>'recharge'));
    		$back_url = url('member/wallet/recharge',array('order_id'=>$order['order_id']));
    	}else{
    		$go_url = url('shop/order/info',array('order_id'=>$order['order_id']));
    		$back_url = url('shop/flow/done',array('order_id'=>$order['order_id']));
    	}
       // $order['order_amount'] = 0.01;
        //①、获取用户openid
        $tools = new \JsApiPay();
        //$openId = $tools->GetOpenid();
        // $openId = 'oI6mL5azOzvK_O4--JFLfa_uYVgI';
        $openId = $open_id;
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'].time());
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("tp_wx_pay");
        $input->SetNotify_url(SITE_URL.'/index.php/shop/Payment/notifyUrl/pay_code/miniAppPay');
        $input->SetTrade_type("JSAPI");
		// trace(SITE_URL.'/index.php/shop/Payment/notifyUrl/pay_code/miniAppPay','debug');
        $input->SetOpenid($openId);
        $order2 = \WxPayApi::unifiedOrder($input);
        if(!empty($order2['err_code'])){
            return $order2;
        }
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);exit;
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        return json_decode($jsApiParameters,true);
    }


    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    function array2xml($arr, $level = 1) {
    	$s = $level == 1 ? "<xml>" : '';
    	foreach($arr as $tagname => $value) {
    		if (is_numeric($tagname)) {
    			$tagname = $value['TagName'];
    			unset($value['TagName']);
    		}
    		if(!is_array($value)) {
    			$s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
    		} else {
    			$s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
    		}
    	}
    	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
    	return $level == 1 ? $s."</xml>" : $s;
    }

    function http_post($url, $param, $wxchat) {
    	$oCurl = curl_init();
    	if (stripos($url, "https://") !== FALSE) {
    		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
    		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    	}
    	if (is_string($param)) {
    		$strPOST = $param;
    	} else {
    		$aPOST = array();
    		foreach ($param as $key => $val) {
    			$aPOST[] = $key . "=" . urlencode($val);
    		}
    		$strPOST = join("&", $aPOST);
    	}
    	curl_setopt($oCurl, CURLOPT_URL, $url);
    	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($oCurl, CURLOPT_POST, true);
    	curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    	if($wxchat){
    		curl_setopt($oCurl,CURLOPT_SSLCERT,dirname(THINK_PATH).$wxchat['api_cert']);
    		curl_setopt($oCurl,CURLOPT_SSLKEY,dirname(THINK_PATH).$wxchat['api_key']);
    		curl_setopt($oCurl,CURLOPT_CAINFO,dirname(THINK_PATH).$wxchat['api_ca']);
    	}
    	$sContent = curl_exec($oCurl);
    	$aStatus = curl_getinfo($oCurl);
    	curl_close($oCurl);
    	if (intval($aStatus["http_code"]) == 200) {
    		return $sContent;
    	} else {
    		return false;
    	}
    }

     // 微信订单退款原路退回
    public function refund($data){
        if(!empty($data["transaction_id"])){
            $input = new \WxPayRefund();
            $input->SetTransaction_id($data["transaction_id"]);
            $input->SetTotal_fee($data["order_amount"]*100);//设置订单总金额
            $input->SetRefund_fee($data["money_paid"]*100);//设置退款总金额
            $input->SetOut_refund_no(\WxPayConfig::$mchid.date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$mchid);
            $res = \WxPayApi::refund($input);
             trace('retund_1:'.json_encode($res),'debug');
            if ($res['return_code'] == 'FAIL'){
                return $res['return_msg'];
            }
            if($res['result_code'] == 'FAIL'){
                return $res['err_code_des'];
            }
            return true;
        }else{
            return false;
        }

    }
}