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
use app\store\model\UserBusinessModel;
use app\unique\model\SearchRecordsModel;

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
        $UsersModel = new UsersModel();
        $this->assign('title', '搜索结果');
        $keyword = trim(input('keyword'),' ');
        $this->assign('keyword',$keyword);

        #更新历史搜索
        if(empty($keyword)==false){
            $SearchRecordsModel = new SearchRecordsModel();
            $where[] = ['user_id','=',$this->userInfo['user_id']];
            $where[] = ['keyword','=',$keyword];
            $search_info = $SearchRecordsModel->where($where)->find();

            $map['keyword'] = $keyword;
            $map['user_id'] = $this->userInfo['user_id'];
            $map['add_time'] = time();
            if(empty($search_info)){
                $res = $SearchRecordsModel->insert($map);
            }else{
                $res = $SearchRecordsModel->where('id',$search_info['id'])->update($map);
            }
        }

        $userInfo = $UsersModel->where('user_id',$this->userInfo['user_id'])->find();
        $this->assign('longitude',$userInfo['longitude']);
        $this->assign('latitude',$userInfo['latitude']);

        return $this->fetch('search_result');
    }


}?>