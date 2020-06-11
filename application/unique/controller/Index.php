<?php
/*------------------------------------------------------ */
//-- APP首页
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\shop\model\SlideModel;
use app\mainadmin\model\RegionModel;
use app\store\model\CategoryModel;

class Index extends ClientbaseController{

    /*------------------------------------------------------ */
    //-- 首页
    /*------------------------------------------------------ */
    public function index(){
        $this->assign('title', '首页');
        //首页头条
        $headline = (new \app\mainadmin\model\ArticleModel)->getHeadline();
        $this->assign('headline', $headline);

        //城市
        $city = input('city','');
        $this->assign('city', $city);
        empty($city) && $city = "北京";
        $regionModel = new RegionModel();
        $city_info = $regionModel->where('short_name',$city)->find();
        $this->assign('city_info', $city_info);

        $this->assign('slideList', SlideModel::getRows(2));//获取幻灯片
        return $this->fetch('index');
    }

    /*------------------------------------------------------ */
    //-- 选择地区
    /*------------------------------------------------------ */
    public function region(){
        $this->assign('title', '选择地区');
        $regionModel = new RegionModel();
        $list = $regionModel->where('level_type',2)->select();
        $this->assign('list', $list);
        return $this->fetch('region');
    }

    /*------------------------------------------------------ */
    //-- 搜索
    /*------------------------------------------------------ */
    public function search(){
        $this->assign('title', '搜索');

        return $this->fetch('search');
    }

    /*------------------------------------------------------ */
    //-- 搜索结果
    /*------------------------------------------------------ */
    public function search_result(){
        $this->assign('title', '搜索结果');

        return $this->fetch('search_result');
    }

    /*------------------------------------------------------ */
    //-- 搜索结果
    /*------------------------------------------------------ */
    public function add_business(){
        $CategoryModel = new CategoryModel();
        $where[] = ['pid','>',0];
        $where[] = ['status','=',1];
        //分类名称
        $cate_name_list = $CategoryModel->where($where)->column('name');
        $cate_name_json = json_encode($cate_name_list,JSON_UNESCAPED_UNICODE);


        $this->assign('cate_name_json',$cate_name_json);
        $this->assign('title', '申请商家');
        return $this->fetch('add_business');
    }

}?>