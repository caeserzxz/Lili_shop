<?php
/*------------------------------------------------------ */
//-- 商家
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;


class Agent extends ClientbaseController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new AgentModel();
    }

    /*------------------------------------------------------ */
    //-- 申请代理
    /*------------------------------------------------------ */
    public function add_agent(){
        if($this->userInfo['is_agent']==1){
            return $this->error('您已是代理.');
        }
        $this->assign('agent_token',session('agent_token'));
        $this->assign('title', '申请代理');
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        $this->assign('info',$info);
        return $this->fetch('add_agent');
    }

    /*------------------------------------------------------ */
    //-- 代理管理
    /*------------------------------------------------------ */
    public function agent(){
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        $this->assign('info',$info);
        $this->assign('title', '代理管理');
        return $this->fetch('agent');
    }

    /*------------------------------------------------------ */
    //-- 审核中
    /*------------------------------------------------------ */
    public function agent_review(){
        $this->assign('title', '审核中');
        return $this->fetch('agent_review');
    }


    /*------------------------------------------------------ */
    //-- 审核未通过
    /*------------------------------------------------------ */
    public function agent_fail(){
        $this->assign('title', '审核未通过');
        return $this->fetch('agent_fail');
    }

    /*------------------------------------------------------ */
    //-- 我的商家
    /*------------------------------------------------------ */
    public function my_team(){
        $this->assign('title', '我的商家');
        return $this->fetch('my_team');
    }

    /*------------------------------------------------------ */
    //-- 我的业绩
    /*------------------------------------------------------ */
    public function sales(){
        $this->assign('date', date('Y-m'));
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        $this->assign('info',$info);
        $this->assign('title', '我的业绩');
        return $this->fetch('sales');
    }

    /*------------------------------------------------------ */
    //-- 邀请代理
    /*------------------------------------------------------ */
    public function agent_token(){
        $type = input('type');
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        $token = $info['token'];
        if($type==1){
            $share_url = 'http://'.$_SERVER['HTTP_HOST'].'/unique/agent/add_agent/agent_token/';
            $this->assign('title', '邀请代理');
        }else{
            $share_url = 'http://'.$_SERVER['HTTP_HOST'].'/unique/store/add_business/agent_token/';
            $this->assign('title', '邀请商家');
        }



        $this->assign('share_url',$share_url);
        $this->assign('token',$token);
        return $this->fetch('agent_token');
    }

    /*------------------------------------------------------ */
    //-- 邀请商家
    /*------------------------------------------------------ */
    public function business_token(){
        $this->assign('title', '邀请商家');
        return $this->fetch('business_token');
    }
}