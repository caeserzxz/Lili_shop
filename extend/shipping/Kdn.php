<?php
/*------------------------------------------------------ */
//-- 快递100接口
//-- @author iqgmy
/*------------------------------------------------------ */
namespace shipping;
use kuaidi\Kdapieorder;
$i = (isset($modules)) ? count($modules) : 0;
$modules[$i]["name"] = "快递鸟";
class Kdn{
     /*------------------------------------------------------ */
    //--  获取物流信息
    /*------------------------------------------------------ */
    public static function getLog($shipping_code,$invoice_no,$mobile=''){
        
        $kdn_appid = settings('kdn_appid');
        $kdn_apikey = settings('kdn_apikey');
        $kdn_apiurl = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $kd_config = [];
        $kd_config['kd_id'] = $kdn_appid; //电商ID
        $kd_config['ke_appkey'] = $kdn_apikey; //电商加密私钥
        $kd_config['ke_requrl'] = $kdn_apiurl;
        $kdapiorder = new Kdapieorder($kd_config);
        $res = $kdapiorder->getOrderTracesByJson($shipping_code,$invoice_no);
        $arr = json_decode($res,true);
        if($arr['Traces']){
            foreach ($arr['Traces'] as $k=>&$v){
                $v['time'] = $v['AcceptTime'];
                $v['context'] = $v['AcceptStation'];
            }
        }
        $return['code'] = 1;
        $return['data'] = $arr['Traces'];
        return $return;
    }

    
}


?> 