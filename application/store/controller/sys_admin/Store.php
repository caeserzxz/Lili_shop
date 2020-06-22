<?php
namespace app\store\controller\sys_admin;
use app\AdminController;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\member\model\UsersModel;
use app\mainadmin\model\RegionModel;

/**
 * 行业分类
 * Class Index
 * @package app\store\controller
 */
class Store extends AdminController
{
    //*------------------------------------------------------ */
    //-- 初始化
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new UserBusinessModel();
    }
    /*------------------------------------------------------ */
    //--首页
    /*------------------------------------------------------ */
    public function index()
    {
        $this->getList(true);

        return $this->fetch('index');
    }

    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false) {
        $where = [];

        $keyword = input("keyword");
        if($keyword) $where[] = ['name','=',$keyword];

//        $type = input("type");
//        if($type) $where[] = ['type','=',$type];
        $data = $this->getPageList($this->Model, $where);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('list');
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }

    /*------------------------------------------------------ */
    //-- 信息页调用
    //-- $data array 自动读取对应的数据
    /*------------------------------------------------------ */
    public function asInfo($data)
    {
        $UsersModel = new UsersModel();
        $store_user = $UsersModel->field('mobile,nick_name')->where('user_id','=',$data['user_id'])->find();
        $data['nick_name'] = ' ' .$data['user_id'].'-'. $store_user['mobile'] . ' - ' . $store_user['nick_name'] . ' ';

        $CategoryModel = new CategoryModel();
        $catList = $CategoryModel->getRows();
        $this->assign("catList", $catList);
        $this->assign("catOpt", arrToSel($catList,$data['category_id']));

        $img   = explode(',',$data['live_views']);
        $album = explode(',',$data['license']);
        $lbum = explode(',',$data['logo']);
        $ibum = explode(',',$data['imgs']);
        $live_views_bum = $license_bum = $logo_bum = $imgs_bum = [];
        foreach ($img as $key => $value) {
            $live_views_bum[] = [
                'img_id'    => $key,
                'goods_img' => $value,
            ];
        }
        foreach ($album as $key => $value) {
            $license_bum[] = [
                'img_id'    => $key,
                'goods_img' => $value,
            ];
        }
        foreach ($lbum as $key => $value) {
            $logo_bum[] = [
                'img_id'    => $key,
                'goods_img' => $value,
            ];
        }
        foreach ($ibum as $key => $value) {
            $imgs_bum[] = [
                'img_id'    => $key,
                'goods_img' => $value,
            ];
        }

        if(empty($live_views_bum[0]['goods_img'])==false){
            $this->assign('live_views_bum', $live_views_bum);
        }
        if(empty($license_bum[0]['goods_img'])==false){
            $this->assign('license_bum', $license_bum);
        }
        if(empty($logo_bum[0]['goods_img'])==false){
            $this->assign('logo_bum', $logo_bum);
        }
        if(empty($imgs_bum[0]['goods_img'])==false){
            $this->assign('imgs_bum', $imgs_bum);
        }

        return $data;
    }

    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data) {
        if (empty($data['business_name'])){
            return $this->error('请输入商家名称.');
        }
        if (empty($data['category_id'])){
            return $this->error('请选择行业.');
        }
        $where[] = ['business_name','=',$data['business_name']];
        $where[] = ['user_id','<>',$data['user_id']];
        $count = $this->Model->where($where)->count('business_id');
        if ($count > 0) return $this->error('操作失败:已存在商家名称，不允许重复添加！');

        $data['merger_name'] = $this->getRegion($data['province'],$data['city'],$data['district']);
        $data['add_time'] = time();
        $data['update_time'] = time();
        #商家实拍图处理
        if(empty($data['live_views_bum']['path'])==false){
            $data['live_views'] =  implode(',',$data['live_views_bum']['path']);
        }
        #商家营业照处理
        if(empty($data['license_bum']['path'])==false){
            $data['license'] =  implode(',',$data['license_bum']['path']);
        }
        #店铺logo处理
        if(empty($data['logo_bum']['path'])==false){
            $data['logo'] =  implode(',',$data['logo_bum']['path']);
        }
        #店铺相册
        if(empty($data['imgs_bum']['path'])==false){
            $data['imgs'] =  implode(',',$data['imgs_bum']['path']);
        }
        $data['map_imgs']=$this->get_static_map($data['longitude'],$data['latitude']);
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加后调用
    /*------------------------------------------------------ */
    public function afterAdd($data)
    {
        #更新users表的商家状态
        $UsersModel = new UsersModel();
        if($data['status']==1){
            $map['is_business'] = 1;
            $UsersModel->where('user_id',$data['user_id'])->update($map);
        }

        $logInfo = '添加商家，商家帐号状态：';
        $logInfo .= $data['is_ban'] == 1 ? '封禁':'正常';
        $this->_Log($data['business_id'],$logInfo);
        return $this->success('添加成功.',url('index'));
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){
        if (empty($data['business_name'])){
            return $this->error('请输入商家名称.');
        }
        if (empty($data['category_id'])){
            return $this->error('请选择行业.');
        }

        $data['merger_name'] = $this->getRegion($data['province'],$data['city'],$data['district']);
        $data['live_views']   = implode(',',$data['live_views_bum']['path']);
        $data['license'] = implode(',',$data['license_bum']['path']);
        $data['logo'] = implode(',',$data['logo_bum']['path']);
        $data['imgs'] = implode(',',$data['imgs_bum']['path']);

        $data['is_ban'] = $data['is_ban'] * 1;
        $where[] = ['business_name','=',$data['business_name']];
        $where[] = ['business_id','<>',$data['business_id']];
        $where[] = ['user_id','<>',$data['user_id']];
        $count = $this->Model->where($where)->count('business_id');
        if ($count > 0) return $this->error('操作失败:已存在供应商名称，不允许重复添加！');
        $data['update_time'] = time();
        #生成静态地图
        $data['map_imgs']=$this->get_static_map($data['longitude'],$data['latitude']);

        $logInfo = '修改商家信息，状态：'.($data['is_ban'] == 1 ? '封禁':'正常');
        $this->_log($data['business_id'], $logInfo );
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 修改后调用
    /*------------------------------------------------------ */
    public function afterEdit($data){
        $logInfo = '供应商帐号状态：';
        if ($data['is_ban'] == 1){
            $logInfo .= '封禁';
        }else{
            $logInfo .= '正常';
        }
        #更新users表的商家状态
        $UsersModel = new UsersModel();
        if($data['status']==1){
            $map['is_business'] = 1;
            $UsersModel->where('user_id',$data['user_id'])->update($map);
        }
        $this->_Log($data['business_id'],$logInfo);
        return $this->success('修改成功.',url('index'));
    }

    /**
     * 删除商品图片
     */
    public function removeImg($file='') {
        $img_id = input('post.id');
        $business_id = input('business_id',0,'intval');
        $type = input('type');
        if ($business_id > 0){
            if($type=='license_bum'){
                $img = $this->Model->where('business_id',$business_id)->value('license');
            }else if($type=='live_views'){
                $img = $this->Model->where('business_id',$business_id)->value('live_views');
            }else if($type=='logo_bum'){
                $img = $this->Model->where('business_id',$business_id)->value('logo_bum');
            }else if($type=='imgs_bum'){
                $img = $this->Model->where('business_id',$business_id)->value('imgs_bum');
            }

            if (empty($img)){
                return $this->error('没有找到相关图片.');
            }
            $imgs = explode(',',$img);
            foreach ($imgs as $k=>$v){
                if($img_id==$v){
                    unset($imgs[$k]);
                }
            }
            $new_imgs =  implode(',',$imgs);
            if($type=='license_bum'){
                $res = $this->Model->where('business_id',$business_id)->update(['license'=>$new_imgs]);
            }else if($type=='live_views'){
                $res = $this->Model->where('business_id',$business_id)->update(['live_views'=>$new_imgs]);
            }else if($type=='logo_bum'){
                $res = $this->Model->where('business_id',$business_id)->update(['logo'=>$new_imgs]);
            }else if($type=='imgs_bum'){
                $res = $this->Model->where('business_id',$business_id)->update(['imgs'=>$new_imgs]);
            }
            if ($res < 1){
                return $this->error('删除图片失败.');
            }
        }
        if (empty($file))  return $this->error('传值错误.');
        unlink('.'.$file);
        unlink('.'.str_replace('.','_thumb.',$file));
        return $this->success('删除图片成功.');
    }

    /**
     * 图片上传
     */
    public function goodsUpload() {
        $thumb['width'] = 350;
        $thumb['height'] = 300;

        $dir = 'storeimg/';
        $result = $this->_upload($_FILES['file'],$dir,$thumb);
        if ($result['error']) {
            $data['code'] = 1;
            $data['msg'] = $result['info'];
            return $this->ajaxReturn($data);
        }

        $savepath = trim($result['info'][0]['savepath'],'.');
        $file_url = $savepath.$result['info'][0]['savename'];
        $data['code'] = 0;
        $data['msg'] = "上传成功";
        $data['image'] = array('path'=>$file_url,'thumbnail'=>$file_url);
        $data['savename'] = $result['info'][0]['savename'];
        $data['src'] = $file_url;
        return $this->ajaxReturn($data);

    }

    /*------------------------------------------------------ */
    //-- 地址区域获取
    /*------------------------------------------------------ */
    public function getRegion($province,$city,$district){
        $regionModel = new RegionModel();
        $province_name = $regionModel->where('id',$province)->value('name');
        $city_name = $regionModel->where('id',$city)->value('name');
        $district_name = $regionModel->where('id',$district)->value('name');
        $merger_name = "$province_name $city_name $district_name";
        return $merger_name;
    }
    /**
     * 百度后台获取经纬度
     */
    public function getLatLon(){
        $input = input();
        $regionModel = new RegionModel();
        $province = $regionModel->where('id',$input['province'])->value('name');
        $city = $regionModel->where('id',$input['city'])->value('name');
        $district = $regionModel->where('id',$input['district'])->value('name');
        $key = settings('tx_key');
        $secret_key = settings('secret_key');

        $sig = md5("/ws/geocoder/v1/?address=".$province.$city.$district.$input['address']."&key=".$key."&output=json&region=".$city.$secret_key);
        $longitude = 116.405289;$latitude = 39.904987;
        if(!empty($key)){
            $url = "https://apis.map.qq.com/ws/geocoder/v1/?address=".$province.$city.$district.$input['address']."&key=".$key."&output=json&region=".$city."&sig=".$sig;
            if ($result=file_get_contents($url)) {
                $result = json_decode($result,ture);
                $longitude = $result["result"]["location"]['lng'];
                $latitude = $result["result"]["location"]['lat'];
            }
        }
        $data = array(
            'code' => 1,
            'longitude' =>$longitude,
            'latitude' =>$latitude,
        );
        $this->ajaxReturn($data);
    }

    /*------------------------------------------------------ */
    //-- 静态图获取
    /*------------------------------------------------------ */
    public function get_static_map($longitude,$latitude){
        $arr = [
            'key'=>settings('tx_key'),
            'center'=>$latitude.','.$longitude,
            'zoom'=>16,
            'markers'=>'color:red|label:A|'.$latitude.','.$longitude,
            'size'=>'500*400',
        ];

        #排序
        ksort($arr);
        $param =http_build_query($arr);
        $sig = md5("/ws/staticmap/v2/?".$param.settings('secret_key'));
        #组装url
        $url = "https://apis.map.qq.com/ws/staticmap/v2/?".$param."&sig=".$sig;
        $result=file_get_contents($url);
        return $url;

    }

}
