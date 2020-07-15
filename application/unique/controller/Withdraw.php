<?php
/*------------------------------------------------------ */
//-- 提现相关
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\WithdrawAccountModel;
use think\facade\Cache;


class Withdraw  extends ClientbaseController{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize(){
        parent::initialize();
        $this->assign('sms_fun', settings('sms_fun'));//获取短信配置
    }
	/*------------------------------------------------------ */
	//-- 提现管理
	/*------------------------------------------------------ */
	public function index(){
        session('last_withdraw_url',url('unique/withdraw/index'));
	    if (empty($this->userInfo['mobile']) == true){//没有注册手机，须绑定手机后才能操作提现
            return $this->redirect('bindMobile');
        }
        $type = input('type','','trim');
        $this->assign('type', input('type','','trim'));
        $this->assign('title', '提现');
        $this->assign('withdraw_account_type', config('config.withdraw_account_type'));
		return $this->fetch('index');
	}
    /*------------------------------------------------------ */
    //-- 提现记录
    /*------------------------------------------------------ */
    public function lists(){
        $this->assign('title', '提现记录');
        $this->assign('withdraw_account_type', config('config.withdraw_account_type'));
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 绑定手机
    /*------------------------------------------------------ */
    public function bindMobile(){
        if (empty($this->userInfo['mobile']) == false){
//            return $this->redirect('unique/member/index');
            return $this->error('您已绑定过手机','unique/member/index');
        }
        $this->assign('title', '绑定手机');
        $this->assign('sms_fun', settings('sms_fun'));//获取短信配置
        return $this->fetch('bindMobile');
    }
	/*------------------------------------------------------ */
    //--  提现方式列表
    /*------------------------------------------------------ */
    public function bankList(){
        session('last_withdraw_url',url('unique/withdraw/bankList'));
        $this->assign('title', '提现方式');
        return $this->fetch('bankList');
    }
	/*------------------------------------------------------ */
    //-- 添加/修改银行卡
    /*------------------------------------------------------ */
    public function bankAdd(){
        $this->assign('last_withdraw_url', session('last_withdraw_url') ? session('last_withdraw_url') : "/");
        $account_id = input('account_id',0,'intval');
        if($account_id > 0){
            $WithdrawAccountModel = new WithdrawAccountModel();
            $account = $WithdrawAccountModel->where('account_id',$account_id)->where('user_id',$this->userInfo['user_id'])->find();
            if($account){
                $this->assign('account', $account);
            }else{
                $this->error('找不到相应账户');
            }
        }
        $this->assign('account_id', $account_id);
        $this->assign('title', '银行卡');
        return $this->fetch('bankAdd');
    }
	/*------------------------------------------------------ */
    //-- 添加/修改支付宝
    /*------------------------------------------------------ */
    public function alipayAdd(){
        $this->assign('last_withdraw_url', session('last_withdraw_url') ? session('last_withdraw_url') : "/");
        $account_id = input('account_id',0,'intval');
        if($account_id > 0){
            $WithdrawAccountModel = new WithdrawAccountModel();
            $account = $WithdrawAccountModel->where('account_id',$account_id)->where('user_id',$this->userInfo['user_id'])->find();
            if($account){
                $this->assign('account', $account);
            }else{
                $this->error('找不到相应账户');
            }
        }
        $this->assign('account_id', $account_id);
        $this->assign('title', '支付宝');
        return $this->fetch('alipayAdd');
    }
    /*------------------------------------------------------ */
    //-- 添加/修改支付宝
    /*------------------------------------------------------ */
    public function weixinAdd(){
        $this->assign('last_withdraw_url', session('last_withdraw_url') ? session('last_withdraw_url') : "/");
        $account_id = input('account_id',0,'intval');
        if($account_id > 0){
            $WithdrawAccountModel = new WithdrawAccountModel();
            $account = $WithdrawAccountModel->where('account_id',$account_id)->where('user_id',$this->userInfo['user_id'])->find();
            if($account){
                $this->assign('account', $account);
            }else{
                $this->error('找不到相应账户');
            }
        }
        $this->assign('account_id', $account_id);
        $this->assign('title', '微信');
        return $this->fetch('weixinAdd');
    }
    /*------------------------------------------------------ */
    //-- 申请提交成功
    /*------------------------------------------------------ */
    public function withdrawSuccess(){
        $this->assign('title', '申请提交成功');
        return $this->fetch('withdraw_success');
    }


}?>