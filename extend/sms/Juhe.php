<?php
/*------------------------------------------------------ */
//-- 阿里大鱼短信接口
//-- @author iqgmy
/*------------------------------------------------------ */
namespace sms;
$i = (isset($modules)) ? count($modules) : 0;
$modules[$i]["name"] = "聚合";
$modules[$i]["val"] = array('Smsappkey'=>'AppKey');

class Juhe{
    protected $appKey;
    protected $appSecret;
    protected $AlidayuSmsSgin;

    /**
     * 构造方法
     * 
     * @param string $appKey    [description]
     * @param string $appSecret [description]
     */
    public function __construct($val){		
        $this->appKey    = $val['Smsappkey'];
//        $this->appSecret = $val['AlidayuAppSecret'];
//		$this->AlidayuSmsSgin = $val['AlidayuSmsSgin'];
		
    }
	
	/*------------------------------------------------------ */
	//-- 发送短信
	/*------------------------------------------------------ */
	public function send($mobile,$tplCode,$smsParams = array()){
        $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL

        $accessKeyId = $this->appKey;
        $smsConf = array(
            'key'   =>$accessKeyId, //您申请的APPKEY
            'mobile'    => $mobile, //接受短信的用户手机号码
            'tpl_id'    => $tplCode, //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => urlencode('#code#='.$smsParams['code'])//您设置的模板变量，根据实际情况修改
        );

        $content = $this->juhecurl($sendUrl,$smsConf,1); //请求发送短信
        if($content){
            $result = json_decode($content,true);
            $error_code = $result['error_code'];
            if($error_code == 0){
                return true;
            }else{
                //状态非0，说明失败
                return $result['reason'].'_';
            }
        }else{
            //返回内容异常，以下可根据业务逻辑自行修改
            return '系统繁忙，稍后再试';
        }
	}
    /**
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $params [请求的参数]
     * @param  int $ipost [是否采用POST形式]
     * @return  string
     */
   public function juhecurl($url,$params=false,$ispost=0){
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
        curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
        curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
        if( $ispost )
        {
            curl_setopt( $ch , CURLOPT_POST , true );
            curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
            curl_setopt( $ch , CURLOPT_URL , $url );
        }
        else
        {
            if($params){
                curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
            }else{
                curl_setopt( $ch , CURLOPT_URL , $url);
            }
        }
        $response = curl_exec( $ch );
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
        curl_close( $ch );
        return $response;
    }

}


?> 