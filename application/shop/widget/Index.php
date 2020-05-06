<?php
/*------------------------------------------------------ */
//-- 商城主页
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\shop\widget;
use app\ClientbaseController;
use think\Controller;
use app\shop\model\AvatarUserModel;
class Index  extends ClientbaseController{

	  /*------------------------------------------------------ */
    //-- 订单首页信息轮播
    /*------------------------------------------------------ */
    public function ordermessage(){
        $switch= settings('show_ordermessage_switch');
        if($switch != 1){
             echo "";exit;
        }

        //获取虚拟用户
        $us = \think\Db::name('shop_avatar_user')->select();
        shuffle($us);
        //if(!empty($us))  $us = shuffle(\think\Collection($us)->toArray());
        //dump($us);exit;
        //假订单命中率
        $data['hit'] = settings('show_ordermessage_hit'); 
        //显示频率
        $data['frequency'] = (settings('show_ordermessage_frequency') < 3 ? 3: settings('show_ordermessage_frequency'))* 1000;
        //获取真订单频率
        $data['rfrequency'] = settings('show_ordermessage_rfrequency') * 1000;
        //订单提示语
        $data['content'] = settings('show_ordermessage_content');

        $this->assign('data',$data);
        $this->assign('users', $us);

        return $this->fetch('/index/ordermessage');

    }

    
}