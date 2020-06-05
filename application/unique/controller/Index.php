<?php
/*------------------------------------------------------ */
//-- 首页
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\shop\model\SlideModel;

class Index extends ClientbaseController{

    /*------------------------------------------------------ */
    //-- 首页
    /*------------------------------------------------------ */
    public function index(){
        $this->assign('title', '首页');
        //首页头条
        $headline = (new \app\mainadmin\model\ArticleModel)->getHeadline();
        $this->assign('headline', $headline);

        $this->assign('slideList', SlideModel::getRows(2));//获取幻灯片
        return $this->fetch('index');
    }


}?>