<?php
namespace app\unique\controller\api;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
/*------------------------------------------------------ */
//-- 首页相关API
/*------------------------------------------------------ */

class Business extends ApiController
{

    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new UserBusinessModel();
    }

    /*------------------------------------------------------ */
    //-- 申请商家
    /*------------------------------------------------------ */
    public function add_business(){
        $this->checkLogin();//验证登陆
        $CategoryModel = new CategoryModel();
        $RegionModel = new RegionModel();
        $data = input('post.');

        if(empty($data['token'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写邀请码']);
        if(empty($data['business_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写店铺名称']);
        if(empty($data['business_user_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写真实姓名']);
        if(empty($data['business_mobile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写手机号']);
        if(empty($data['profits'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写让利率']);
        if(empty($data['category'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择行业分类']);
        if(empty($data['address'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写详细地址']);
        if(empty($data['data_codes'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择店铺地区']);
        if(empty($data['imgfile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请上传店铺实景图']);
        if(empty($data['imgfile2'])) return $this->ajaxReturn(['code' => 0,'msg' => '请上传店铺营业照']);

        #检查店铺区域与代理的代理区域是否相同

        #行业分类
        $category_id = $CategoryModel->where('name',$data['category'])->value('id');
        if(empty($category_id)){
            return $this->ajaxReturn(['code' => 0,'msg' => '选择的行业分类有误']);
        }
        #地区处理
        if(empty($data['data_codes'])==false){
            $codes = explode(",",$data['data_codes']);
            #区
            $district = $RegionModel->where('id',$codes[2])->find();
        }
        #实景图处理
        if(empty($data['imgfile'])==false){
            $imgfile_str = '';
            foreach ($data['imgfile'] as $k=>$v){
                $res = uploadimage($v,'store_img');
                if(empty($res['path'])){
                    return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                }
                $imgfile_str = ','.$res['path'];
            }
            $imgfile_str =  ltrim($imgfile_str, ",");
        }
        #店铺营业照
        if(empty($data['imgfile2'])==false){
            $imgfile2_str = '';
            foreach ($data['imgfile2'] as $k=>$v){
                $res2 = uploadimage($v,'store_img');
                if(empty($res2['path'])){
                    return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                }
                $imgfile2_str = ','.$res2['path'];
            }
            $imgfile2_str =  ltrim($imgfile2_str, ",");
        }
        #组装数据
        $inArr['user_id'] = $this->userInfo['user_id'];
        $inArr['business_name'] = trim($data['business_name'],'');
        $inArr['business_user_name'] = trim($data['business_user_name'],'');
        $inArr['business_mobile'] = trim($data['business_mobile'],'');
        $inArr['profits'] = intval($data['profits']);
        $inArr['category_id'] = $category_id;
        $inArr['province'] = $codes[0];
        $inArr['city'] = $codes[1];
        $inArr['district'] = $codes[2];
        $inArr['merger_name'] = $district['merger_name'];
        $inArr['address'] = trim($data['address'],'');
        $inArr['live_views'] = $imgfile_str;
        $inArr['license'] = $imgfile2_str;

        $result = $this->Model->insert($inArr);
        if($result){
            return $this->ajaxReturn(['code' => 1,'msg' => '申请成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '申请失败']);
        }
    }



}