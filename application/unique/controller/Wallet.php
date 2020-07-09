<?php
/*------------------------------------------------------ */
//-- 钱包相关
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\AccountLogModel;


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
        $type = input('type', 0, 'trim');
        if($type >= 11 && $type <= 15)
            $title = AccountLogModel::$type_desc[$type];
        $title = $title ? $title : '钱包';
        $this->assign('title', $title);
        $this->assign('type', $type);
        $this->assign('date1', date('Y-m-d',strtotime("-1 month")));
        $this->assign('date2', date('Y-m-d'));
        $this->assign('userInfo',$this->userInfo);
        return $this->fetch('mylog');
    }
    /*------------------------------------------------------ */
    //-- 消费记录
    /*------------------------------------------------------ */
    public function payRecord(){
        $this->assign('title', '门店消费记录');
        $this->assign('date1', date('Y-m-d',strtotime("-1 month")));
        $this->assign('date2', date('Y-m-d'));
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 账单详情
    /*------------------------------------------------------ */
    public function payRecordInfo(){
        $id = input('id');
        if(empty($id))$this->error('参数错误');
        $this->assign('title', '账单详情');
        $this->assign('id', $id);
        return $this->fetch();
    }
}?>