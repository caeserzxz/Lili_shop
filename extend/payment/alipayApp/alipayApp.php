<?php
namespace payment\alipayApp;
define('AOP_SDK_WORK_DIR', __DIR__);
define('AOP_SDK_DEV_MODE', true);

//require_once 'AopSdk.php';
require_once("aop/AopClient.php");
require_once ('aop/request/AlipayTradeAppPayRequest.php');

use think\Model;
use think\Request;
use think\Db;
use think\Exception;
use think\Hook;
use think\Log;
use think\facade\Env;
use app\member\model\RechargeLogModel;
use app\shop\model\OrderModel;
use app\mainadmin\model\PaymentModel;

class alipayApp 
{
    public $tableName = 'main_payment'; // 插件表
    public $alipay_config = array();// 支付宝支付配置参数
    protected $aopClient;

    function __construct() {

       
        unset($_GET['pay_code']);   // 删除掉 以免被进入签名
        unset($_REQUEST['pay_code']);// 删除掉 以免被进入签名

        /**APP支付**/
        $payment = (new PaymentModel)->where('pay_code', 'alipayApp')->find(); // 找到支付插件的配置(APP的)
        $config_value = json_decode(urldecode($payment['pay_config']),true);  // 配置反序列化
        // dump($config_value);die;
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";


        $aop->appId = $config_value['alipay_partner'];
        //设置私钥和公钥
        $aop->rsaPrivateKey = $config_value['alipay_private_key'];      //私钥
        $aop->alipayrsaPublicKey = $config_value['alipay_rsa_public_key'];  //公钥

        $aop->rsaPrivateKeyFilePath = $config_value['rsa_private_key_file'] ? ROOT_PATH . $config_value['rsa_private_key_file'] : null;
        $aop->alipayPublicKey = $config_value['alipay_rsa_public_key_file'] ? ROOT_PATH . $config_value['alipay_rsa_public_key_file'] : null;

        //trace(var_export($config_value,true),'debug');
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $this->aopClient = $aop;
        /**APP支付**/
    }
    /**
     * 发起支付(App支付宝)
     *
     * @param array $options
     * @return array
     * @throws PayFailedException
     */
    public function get_code($order,$options) {
        // try {
            // 实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
            
            $request = new \AlipayTradeAppPayRequest();

            $notify_url = SITE_URL.'/index.php/shop/Payment/notifyUrl/pay_code/alipayApp';

            //服务器异步通知页面路径 //必填，不能修改
            //$return_url = SITE_URL.U('Payment/returnUrl',array('pay_code'=>'alipayApp'));  //页面跳转同步通知页面路径
            $request->setNotifyUrl($notify_url);
            // // 单位转换为元
            // $amount = ceil($options['amount']) / 100;
            
           
            $return_url = SITE_URL.'/index.php/member/center/index';  //页面跳转同步通知页面路径
            
            // $shop_info = tpCache('shop_info.store_name');
            // $shop_info && $store_name = $shop_info['store_name'];
            // empty($store_name) ? $store_name = "shop" : $store_name = $store_name.'订单';
            // FIXME: 请求的时间无法设置
            // dump($order);die;
            $bizParameters = [
                'subject'         => '商品订单',
                'body'            => '商品订单',
                'out_trade_no'    => $order['order_sn'],
                'total_amount'    => $order['order_amount'],// 单位元
                'timeout_express' => '5m',// 单位转换成分钟
                'product_code'    => 'QUICK_MSECURITY_PAY',
            ];
             
            $request->setBizContent(json_encode($bizParameters));

            // 这里和普通的接口调用不同，使用的是sdkExecute
            $orderString = $this->aopClient->sdkExecute($request);
        // } catch (Exception $e) {
        //     throw $e;
        // }

        //ios记得引入ios.js
        $html = <<<EOF
    <script src="/static/js/ios.js"></script>
	<script type="text/javascript">
	//调用微信JS api 支付
	var u = navigator.userAgent;
	var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1;
	if(isAndroid){
	   window.auc.AliPay('$orderString');//安卓
	}else{
	   window.app.AliPay('$orderString'); //ios
	}
    function aliPayCallback() {
        window.location.href = '$return_url';
    }
	</script>
EOF;

        return $html;
    }

    /**
     * 生成支付(网页支付宝)
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function web_code($order, $config_value)
    {
        $shop_info = tpCache('shop_info');
        $shop_info && $store_name = $shop_info['store_name'];
        empty($store_name) ? $store_name = "TPshop订单" : $store_name =$store_name.'订单';

        // 接口类型
        $service = array(
            1 => 'create_partner_trade_by_buyer', //使用担保交易接口
            2 => 'create_direct_pay_by_user', //使用即时到帐交易接口
        );
        //构造要请求的参数数组，无需改动
        $parameter = array(

            "partner" => trim($this->alipay_config['partner']), //合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
            'seller_id'=> trim($this->alipay_config['partner']), //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
            "key" => trim($this->alipay_config['key']), // MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
            // "seller_email" => trim($this->alipay_config['seller_email']),
            "notify_url"    => SITE_URL.U('shop/Payment/notifyUrl',array('pay_code'=>'alipayApp')) , //服务器异步通知页面路径 //必填，不能修改
            "return_url"    => SITE_URL.U('shop/Payment/returnUrl',array('pay_code'=>'alipayApp')),  //页面跳转同步通知页面路径
            "sign_type"     => strtoupper('MD5'), //签名方式
            "input_charset" =>strtolower('utf-8'), //字符编码格式 目前支持utf-8
            "cacert"    =>  getcwd().'\\cacert.pem',
            "transport" => 'http', // //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            "service" => 'alipay.wap.create.direct.pay.by.user',   // // 产品类型，无需修改
            "payment_type"  => "1", // 支付类型 ，无需修改
            "_input_charset"=> trim(strtolower($this->alipay_config['input_charset'])), //字符编码格式 目前支持 gbk 或 utf-8
            "out_trade_no"  => $order['order_sn'], //商户订单号
            "subject"       => $store_name, //订单名称，必填
            "total_fee" => $order['account'], //付款金额
            "show_url"  => "http://zpwechatmall.fujinapp.cn", //收银台页面上，商品展示的超链接，必填

        );
        //  如果是支付宝网银支付
        if(!empty($config_value['bank_code']))
        {
            $parameter["paymethod"] = 'bankPay'; // 若要使用纯网关，取值必须是bankPay（网银支付）。如果不设置，默认为directPay（余额支付）。
            $parameter["defaultbank"] = $config_value['bank_code'];
            $parameter["service"] = 'create_direct_pay_by_user';
        }
        //建立请求
        require_once("lib/alipay_submit.class.php");
        $alipaySubmit = new \AlipaySubmit($this->alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        return $html_text;
    }
    /**
     * 获取支付返回的参数
     *
     * @param array $getData
     * @param array $postData
     * @return mixed
     */
    public function response(){

        //验签
        $res = $this->aopClient->rsaCheckV1($_POST,'','RSA2');
        if(!$res){
            echo 'fail';die;
        }

        if ($verify_result) //验证成功
        {
            if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
                $data['order_sn'] =  $_POST['out_trade_no']; //商户订单号
                $data['total_fee'] = $_POST['price'] * 100;//支付金额
                $data['transaction_id'] = $_POST['trade_no'];//支付宝交易号
                $res = (new UpdatePayModel)->update($data);
            }

            if ($res == true) {
                echo "success"; // 告诉支付宝处理成功
            } else {
                echo "fail"; //验证失败
            }
        } else {
            echo "fail"; //验证失败
        }
    }
}