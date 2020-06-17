<?php
/*------------------------------------------------------ */
//-- 钱包相关
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;


class Wallet extends ClientbaseController{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
    }
	/*------------------------------------------------------ */
	//-- 我的商家红包
	/*------------------------------------------------------ */
	public function redbag(){
        $this->assign('title', '红包');
		return $this->fetch();
	}
}?>