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


}