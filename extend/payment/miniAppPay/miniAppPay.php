<?php
/**
 *  微信支付插件

 */
namespace payment\miniAppPay;
use payment\weixin\weixin;
/**
 * 支付 逻辑定义
 * Class
 * @package Home\Payment
 */

class miniAppPay extends weixin
{
    /**
     * 析构流函数
     */
    public function  initialize(){
        parent::initialize('miniAppPay');
    }


}