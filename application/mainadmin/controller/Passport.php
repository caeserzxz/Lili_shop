<?php

namespace app\mainadmin\controller;
use app\AdminController;
use app\mainadmin\model\AdminUserModel;

/**
 * 登陆
 * Class Passport
 * @package app\store\controller
 */
class Passport extends AdminController
{
    //*------------------------------------------------------ */
    //-- 初始化
    /*------------------------------------------------------ */
    public function initialize()
    {
        $this->isCheckPriv = false;
        parent::initialize();
    }
    /**
     * 后台登录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {

        if ($this->request->isAjax()) {
            $model = new AdminUserModel;
            if ($model->login(input('post.User'))) {
                $url =  url(config('default_module').'/'.config('default_controller').'/'.config('default_action'));
                return $this->success('登录成功',$url);
            }
            return $this->error($model->getError() ?: '登录失败');
        }
        $this->view->engine->layout(false);
        return $this->fetch('login');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
       $model = new AdminUserModel;
	   $model->logout();
       $this->redirect('passport/login');
    }

}
