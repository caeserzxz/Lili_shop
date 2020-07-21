<?php
/*------------------------------------------------------ */
//-- APP首页
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use think\facade\Cache;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\shop\model\SlideModel;
use app\mainadmin\model\RegionModel;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\unique\model\SearchRecordsModel;
use app\store\model\BusinessGiftModel;

class Index extends ClientbaseController{

    /*------------------------------------------------------ */
    //-- 首页
    /*------------------------------------------------------ */
    public function index(){
        $userInfo = $this->userInfo;
        $this->assign('title', '首页');
        //首页头条
        $headline = (new \app\mainadmin\model\ArticleModel)->getHeadline();
        $this->assign('headline', $headline);
        //城市
        $city = input('city','');
        if(empty($city)==false){
            Cache::set('city'.$userInfo['user_id'], $city);
        }else{
            $city = Cache::get('city'.$userInfo['user_id']);
            if(empty($city)){
                $city = '北京';
            }
        }
        $regionModel = new RegionModel();
        $region = $regionModel->rawCitiesData2();
        $city_info = $regionModel->where('short_name',$city)->find();

        //传递商家id
        $business = (new \app\store\model\UserBusinessModel)->where(['user_id'=>$this->userInfo['user_id'],'status'=>1])->find();
        if(empty($business)==false&&$business['is_play']==2){
            $business_id = $business['business_id'];
        }else{
            $business_id = 0;
        }
        $this->assign('business_id',$business_id);
        $this->assign('city', $city);
        $this->assign('city_info',$city_info);
        $this->assign('slideList', SlideModel::getRows(2));//获取幻灯片
        $this->assign('userInfo',$this->userInfo);
        $this->assign('region',$region);
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
        $this->assign('city',input('city'));
        return $this->fetch('region');
    }

    /*------------------------------------------------------ */
    //-- 搜索
    /*------------------------------------------------------ */
    public function search(){
        $this->assign('title', '搜索');
        #热门搜索处理
        $hot_keywords = explode(' ',settings('hot_keywords'));
        $this->assign('hot_keywords',$hot_keywords);

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
        $this->assign('cid',input('cid'));
        return $this->fetch('search_result');
    }

    /*------------------------------------------------------ */
    //-- 店铺详情
    /*------------------------------------------------------ */
    public function detail(){
        $UserBusinessModel = new UserBusinessModel();
        $BusinessGiftModel = new BusinessGiftModel();
        $business_id = input('business_id');
        #店铺信息
        $info = $UserBusinessModel->where('business_id',$business_id)->find();
        #店铺相册处理
        $imgs_list = explode(',',$info['imgs']);
        #标签处理
        $label = explode(',',$info['label']);
        #鼓励金处理
        $profits = unserialize(settings('profits'))[$info['profits']]['hearten'];
        #是否有红包活动
        $info['gift_id'] = $BusinessGiftModel->where(['business_id'=>$info['business_id'],'status'=>0])->value('id');

        $this->assign('profits',$profits);
        $this->assign('label',$label);
        $this->assign('imgs_list',$imgs_list);
        $this->assign('info',$info);
        $this->assign('title', '搜索结果');
        return $this->fetch('detail');
    }
}?>