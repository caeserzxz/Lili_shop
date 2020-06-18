<?php
/*------------------------------------------------------ */
//-- 商家
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use app\ClientbaseController;
use app\member\model\UsersModel;
use app\shop\model\SlideModel;
use app\mainadmin\model\RegionModel;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;

class Store extends ClientbaseController{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize(){
        parent::initialize();
        $this->Model = new UserBusinessModel();
    }

    /*------------------------------------------------------ */
    //-- 商家
    /*------------------------------------------------------ */
    public function add_business(){
        $UserBusinessModel = new UserBusinessModel();
        $CategoryModel = new CategoryModel();
        $where[] = ['pid','>',0];
        $where[] = ['status','=',1];
        //分类名称
        $cate_name_list = $CategoryModel->where($where)->column('name');
        $cate_name_json = json_encode($cate_name_list,JSON_UNESCAPED_UNICODE);
        //获取当前用户的商家信息
        $info = $UserBusinessModel->where('user_id',$this->userInfo['user_id'])->find();
        #实拍图处理
        $live_views_arr = [];
        if(empty($info['live_views'])==false){
            $live_views_arr = explode(',',$info['live_views']);
        }
        $this->assign('live_views_arr',$live_views_arr);
        #营业执照处理
        $license_arr = [];
        if(empty($info['license'])==false){
            $license_arr = explode(',',$info['license']);
        }
        $this->assign('license_arr',$license_arr);

        $this->assign('agent_token',session('agent_token'));
        $this->assign('info',$info);
        $this->assign('cate_name_json',$cate_name_json);
        $this->assign('title', '申请商家');
        return $this->fetch('add_business');
    }
    /*------------------------------------------------------ */
    //-- 商家申请审核中
    /*------------------------------------------------------ */
    public function business_review(){
        $this->assign('title', '商家申请审核中');
        return $this->fetch('business_review');
    }

    /*------------------------------------------------------ */
    //-- 商家申请不通过
    /*------------------------------------------------------ */
    public function business_fail(){
        $this->assign('title', '商家申请不通过');
        return $this->fetch('business_fail');
    }

    /*------------------------------------------------------ */
    //-- 商家管理
    /*------------------------------------------------------ */
    public function business(){
        $this->assign('title', '商家管理');
        return $this->fetch('business');
    }

    /*------------------------------------------------------ */
    //-- 商家设置
    /*------------------------------------------------------ */
    public function business_set(){
        $this->assign('title', '店铺设置');
        #商家信息
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        #店铺相册处理
        $imgs_arr = [];
        if(empty($info['imgs'])==false){
            $imgs_arr = explode(',',$info['imgs']);
        }
        $this->assign('imgs_arr',$imgs_arr);
        #店铺logo处理
        $logo_arr = [];
        if(empty($info['logo'])==false){
            $logo_arr = explode(',',$info['logo']);
        }
        $this->assign('logo_arr',$logo_arr);

        $this->assign('info',$info);
        return $this->fetch('business_set');
    }

}?>