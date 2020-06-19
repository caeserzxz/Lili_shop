<?php
/*------------------------------------------------------ */
//-- 李莉会员主页
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\store\model\UserBusinessModel;
use app\agent\model\AgentModel;

class Member extends ClientbaseController{
  
	/*------------------------------------------------------ */
	//-- 首页
	/*------------------------------------------------------ */
	public function index(){
        $this->assign('title', '会员中心');
        $this->assign('isUserIndex', 1);
        $this->assign('not_top_nav', true);
        $this->assign('user_center_nav_tpl', settings('user_center_nav_tpl'));
        $this->assign('navMenuList', (new \app\shop\model\NavMenuModel)->getRows(3));//获取导航菜单

        #判断商家入口
        $UserBusinessModel = new UserBusinessModel();
        $business = $UserBusinessModel->where('user_id',$this->userInfo['user_id'])->find();
        $business_sta = 0;
        if(empty($business)){
            #可申请
            $business_sta = 1;
        }else if($business['status']==1){
            #审核通过
            $business_sta = 2;
        }else if($business['status']==2){
            #审核不通过
            $business_sta = 3;
        }else if($business['status']==0){
            #审核中
            $business_sta = 4;
        }
        $this->assign('business_sta',$business_sta);

        #判断代理入口
        $AgentModel = new AgentModel();
        $agent = $AgentModel->where('user_id',$this->userInfo['user_id'])->find();
        $agent_sta = 0;
        if(empty($agent)){
            #可申请
            $agent_sta = 1;
        }else if($agent['status']==1){
            #审核通过
            $agent_sta = 2;
        }else if($agent['status']==2){
            #审核不通过
            $agent_sta = 3;
        }else if($agent['status']==0){
            #审核中
            $agent_sta = 4;
        }
        $this->assign('agent_sta',$agent_sta);
		return $this->fetch('index');
	}
	/*------------------------------------------------------ */
    //-- 我的分享二维码
    /*------------------------------------------------------ */
    public function myCode(){
        $DividendShareByRole = settings('DividendShareByRole');
        if ($DividendShareByRole == 1 && $this->userInfo['role_id'] < 1){
            return $this->error('请升级身份后再操作.');
        }
        $default_img = settings('GoodsImages');
        $arr = explode(',', $default_img);
        $default_img = $arr[0]?$arr[0]:'';
        $this->assign('default_img',$default_img);
        $this->assign('title', '我的二维码');
        return $this->fetch('my_code');
    }
    /*------------------------------------------------------ */
    //-- 会员收货地址页
    /*------------------------------------------------------ */
    public function address(){
        $this->assign('title', '收货地址');
        return $this->fetch('address/index');
    }
    /*------------------------------------------------------ */
    //-- 会员优惠券页
    /*------------------------------------------------------ */
    public function bonus(){
        $this->assign('title', '优惠券');
        return $this->fetch('shop@bonus/index');
    }
    /*------------------------------------------------------ */
    //-- 会员设置页
    /*------------------------------------------------------ */
    public function setting(){
        $this->assign('title', '设置');
        return $this->fetch('setting');
    }
    /*------------------------------------------------------ */
    //-- 修改密码
    /*------------------------------------------------------ */
    public function editPwd(){
        $this->assign('title', '修改密码');
        return $this->fetch('edit_pwd');
    }
    /*------------------------------------------------------ */
    //-- 修改支付密码
    /*------------------------------------------------------ */
    public function editPayPwd(){
        $this->assign('sms_fun', settings('sms_fun'));//获取短信配置
        $this->assign('title', '修改支付密码');
        return $this->fetch('edit_pay_pwd');
    }
    /*------------------------------------------------------ */
    //-- 个人资料
    /*------------------------------------------------------ */
    public function userInfo(){
        $this->assign('title', '个人资料');	
		$superior = (new UsersModel)->getSuperior($this->userInfo['pid']);
		$this->assign('superior', $superior);
        return $this->fetch('user_info');
    }
    /*------------------------------------------------------ */
    //-- 我的钱包
    /*------------------------------------------------------ */
    public function wallet(){
        $this->assign('title', '我的钱包');
        return $this->fetch('wallet');
    }
    /*------------------------------------------------------ */
    //-- 提现
    /*------------------------------------------------------ */
    public function withdraw(){
        $this->assign('title', '提现');
        return $this->fetch('withdraw');
    }
    /*------------------------------------------------------ */
    //-- 添加银行卡
    /*------------------------------------------------------ */
    public function addBankCard(){
        $this->assign('title', '添加银行卡');
        return $this->fetch('add_bank_card');
    }
    /*------------------------------------------------------ */
    //-- 首页
    /*------------------------------------------------------ */
    public function kefu(){
        $this->assign('title', '客服中心');
        $settings = settings();
        $this->assign('kefu_tel', $settings['kefu_tel']);
        $this->assign('kefu_ecode', $settings['kefu_ecode']);
        $this->assign('kefu_time', $settings['kefu_time']);
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 我的团队
    /*------------------------------------------------------ */
    public function my_team(){
        $this->assign('title', '我的会员');
        return $this->fetch();
    }
}?>