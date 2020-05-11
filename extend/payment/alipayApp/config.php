<?php
return array(
    'code'=> 'alipayApp',
    'name' => '手机App支付宝',
    'version' => '1.1',
    'author' => 'wemk',
    'desc' => '手机端App支付宝 ',
    'icon' => 'logo.jpg',
    'scene' =>0,  // 使用场景 0 PC+手机 1 手机 2 PC
    'config' => array(
        array('name' => 'alipay_account','label'=>'支付宝帐户',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_key','label'=>'交易安全校验码',               'type' => 'text',   'value' => ''),
        array('name' => 'alipay_partner','label'=>'合作者身份ID',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_private_key','label'=>'应用私钥',           'type' => 'textarea',   'value' => ''),
        array('name' => 'alipay_public_key','label'=>'应用公钥',           'type' => 'textarea',   'value' => ''),
        array('name' => 'alipay_rsa_public_key','label'=>'支付宝公钥',           'type' => 'textarea',   'value' => ''),
    ),
);