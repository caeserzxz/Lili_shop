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
    /*------------------------------------------------------ */
    //-- 余额明细
    /*------------------------------------------------------ */
    public function mylog(){
        $type = input('type','balance','trim');
        $title = '';
        $title = $type == 'balance' ? '奖励金' : $title;
        $this->assign('title', $title);
        $this->assign('type', $type);
        $this->assign('date1', date('Y-m-d',strtotime("-1 month")));
        $this->assign('date2', date('Y-m-d'));
        return $this->fetch('mylog');
    }
}?>