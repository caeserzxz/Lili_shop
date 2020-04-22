<?php
/**
 *  微信支付插件
 
 */
namespace payment\weixin;
use app\mainadmin\model\PaymentModel;
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */

class weixin
{
    /**
     * 析构流函数
     */
    public function  __construct($code=""){
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");
	   if(!$code){
            $code = 'weixin';
        }
        $payment =  (new PaymentModel)->where('pay_code', 'weixin')->find();
        $config = json_decode(urldecode($payment['pay_config']),true);
        \WxPayConfig::$appid = $config['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        \WxPayConfig::$mchid = $config['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        \WxPayConfig::$key = $config['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        \WxPayConfig::$appsecret = $config['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，
        \WxPayConfig::$app_type = $code;
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config    支付方式信息
     */
    function get_code($order, $config)
    {
        $notify_url = SITE_URL . '/index.php/publics/payment/notifyUrl/pay_code/weixin'; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($config['body']); // 商品描述
        $input->SetAttach("weixin"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $input->SetOut_trade_no($order['order_sn'] . time()); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        $input->SetTotal_fee($order['order_amount'] * 100); // 订单总金额，单位为分，详见支付金额
        $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $input->SetTrade_type("NATIVE"); // 交易类型   取值如下：JSAPI，NATIVE，APP，详细说明见参数规定    NATIVE--原生扫码支付
        $input->SetProduct_id("123456789"); // 商品ID trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        $notify = new \NativePay();
        $result = $notify->GetPayUrl($input); // 获取生成二维码的地址
        $url2 = $result["code_url"];
        if (empty($url2))
            return '没有获取到支付地址, 请检查支付配置' . print_r($result, true);
        return '<img alt="模式二扫码支付" src="/index.php?m=shop&c=index&a=qr_code&data='.urlencode($url2).'" style="width:110px;height:110px;"/>';
    }
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config    支付方式信息
     */
    function get_H5code($order, $config)
    {
        $notify_url = _url('publics/payment/notifyUrl',['pay_code'=>'weixinH5'],true,true);  // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $sceneInfo = json_encode([
            'h5_info' => [
                'type' => 'Wap',
                'wap_url' => SITE_URL,
                'wap_name' => '支付'
            ]
        ], JSON_UNESCAPED_UNICODE);

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($config['body']); // 商品描述
        $input->SetAttach("weixinH5"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $input->SetOut_trade_no($order['order_sn'] . time()); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        $input->SetTotal_fee($order['order_amount'] * 100); // 订单总金额，单位为分，详见支付金额
        $input->SetTrade_type("MWEB"); // 交易类型   取值如下：JSAPI，NATIVE，APP，MWEB 详细说明见参数规定    MWEB--H5wap支付
        $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $input->SetSceneInfo($sceneInfo);

        try {
            $return = \WxPayApi::unifiedOrder($input);

        } catch (\Exception $e) {

            return ['status' => -1, 'msg' => $e->getMessage()];
        }

        if ($return['return_code'] == 'SUCCESS' && $return['result_code'] == 'SUCCESS') {
            return ['status' => 1, 'msg' => $return['return_msg'], 'result' => $return['mweb_url']];
        } else {

            return ['status' => -1, 'msg' => $return['return_msg'], 'result' => $return];
        }
    }
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {
        file_get_contents('php://input');

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

    function getJSAPI($order)
    {
        //①、获取用户openid
        $tools = new \JsApiPay();
        //$openId = $tools->GetOpenid();
        $openId = session('wxInfo.wx_openid');
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'].time());
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("tp_wx_pay");
        $input->SetNotify_url(SITE_URL.'/index.php/publics/Payment/notifyUrl/pay_code/weixin');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = \WxPayApi::unifiedOrder($input);
        if (empty($order2['prepay_id'])){
            return $order2;
        }
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);exit;  
        return  $tools->GetJsApiParameters($order2);
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
            $input->SetTotal_fee($data["order_amount"]*100);
            $input->SetRefund_fee($data["refund_amount"]*100);
            $input->SetOut_refund_no(\WxPayConfig::$mchid.date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$mchid);
            $res = \WxPayApi::refund($input);

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