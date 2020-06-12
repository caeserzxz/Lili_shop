<?php
namespace app\store\controller\sys_admin;
use app\AdminController;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\member\model\UsersModel;

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
//        $ActivityWhere[] = ['is_putaway', '=', 1];
//        $Activity = 0;
//        $ActivityModel = new ActivityModel();
//        $Activity_list = $ActivityModel->where($ActivityWhere)->select();
//        foreach ($Activity_list as $key => $value){
//            $supplyer_ids = explode(',', $value['supplyer_ids']);
//            if (in_array($data['supplyer_id'], $supplyer_ids)){
//                $Activity = 1;
//                break;
//            }
//        }
//        $this->assign("activity", $Activity);

        $UsersModel = new UsersModel();
        $store_user = $UsersModel->field('mobile,nick_name')->where('user_id','=',$data['user_id'])->find();
        $data['nick_name'] = ' ' .$data['user_id'].'-'. $store_user['mobile'] . ' - ' . $store_user['nick_name'] . ' ';

        $CategoryModel = new CategoryModel();
        $catList = $CategoryModel->getRows();
        $this->assign("catList", $catList);
        $this->assign("catOpt", arrToSel($catList,$data['category_id']));

        $img   = explode(',',$data['live_views']);
        $album = explode(',',$data['license']);

        $live_views_bum = $license_bum = [];
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
        $this->assign('live_views_bum', $live_views_bum);
        $this->assign('license_bum', $license_bum);
        return $data;
    }

    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data) {

        if (empty($data['supplyer_name'])){
            return $this->error('请输入供应商名称.');
        }
        if (empty($data['type'])){
            return $this->error('请选择商家类型.');
        }
        $where[] = ['supplyer_name','=',$data['supplyer_name']];
        $where[] = ['user_id','<>',$data['user_id']];
        $count = $this->Model->where($where)->count('supplyer_id');
        if ($count > 0) return $this->error('操作失败:已存在供应商名称，不允许重复添加！');
        $data['merger_name'] = $this->getRegion($data['province'],$data['city'],$data['district']);
        $data['add_time'] = time();
        $data['update_time'] = time();
        if ($data['password']) {
            $data['password'] = _hash($data['password']);
        }
        //审核通过时判断等级并且修改等级
        $this->userLevel($data);

        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加后调用
    /*------------------------------------------------------ */
    public function afterAdd($data)
    {
        $logInfo = '添加供应商帐号，供应商帐号状态：';
        $logInfo .= $data['is_ban'] == 1 ? '封禁':'正常';
        $this->_Log($data['supplyer_id'],$logInfo);
        return $this->success('修改成功.',url('index'));
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){
        if (empty($data['supplyer_name'])){
            return $this->error('请输入供应商名称.');
        }
        $data['merger_name'] = $this->getRegion($data['province'],$data['city'],$data['district']);
        $supplyerImg   = input('post.supplyerImg');
        $supplyerAlbum = input('post.supplyerAlbum');
        $data['supplyer_img']   = serialize($supplyerImg);
        $data['supplyer_album'] = serialize($supplyerAlbum);
        unset($post['supplyerImg'],$post['supplyerAlbum']);

        $data['is_ban'] = $data['is_ban'] * 1;
        $where[] = ['supplyer_name','=',$data['supplyer_name']];
        $where[] = ['supplyer_id','<>',$data['supplyer_id']];
        $where[] = ['user_id','<>',$data['user_id']];
        $count = $this->Model->where($where)->count('supplyer_id');
        if ($count > 0) return $this->error('操作失败:已存在供应商名称，不允许重复添加！');
        $data['update_time'] = time();
        if (empty($data['password']) == false){
            $data['password'] = _hash($data['password']);
        }else{
            unset($data['password']);
        }
        //审核通过时判断等级并且修改等级
        $this->userLevel($data);
        $logInfo = '修改供应商信息，状态：'.($data['is_ban'] == 1 ? '封禁':'正常');
        $this->_log($data['supplyer_id'], $logInfo ,'supplyer');
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 修改后调用
    /*------------------------------------------------------ */
    public function afterEdit($data){
        $GoodsImgsModel = new GoodsImgsModel();
        $supplyerImg = input('post.supplyerImg');
        $supplyerAlbum = input('post.supplyerAlbum');
        foreach ($supplyerImg['id'] as $key => $img_id) {
            $imgwhere = [];
            $imgwhere[] = ['img_id', '=', $img_id];
            $imgwhere[] = ['admin_id', '=', AUID];

            $upData['sort_order'] = $key;
            $upData['supplyer_id'] = $data['supplyer_id'];
            $GoodsImgsModel->where($imgwhere)->update($upData);
        }
        foreach ($supplyerAlbum['id'] as $key => $img_id) {
            $imgwhere = [];
            $imgwhere[] = ['img_id', '=', $img_id];
            $imgwhere[] = ['admin_id', '=', AUID];

            $upData['sort_order'] = $key;
            $upData['supplyer_id'] = $data['supplyer_id'];
            $GoodsImgsModel->where($imgwhere)->update($upData);
        }

        $logInfo = '供应商帐号状态：';
        if ($data['is_ban'] == 1){
            $logInfo .= '封禁';
            //批量执行商品下架
            (new GoodsModel)->where('supplyer_id',$data['supplyer_id'])->update(['isputaway'=>0]);
        }else{
            $logInfo .= '正常';
        }
        if (empty($data['password']) == false){
            $logInfo .= '，修改供应商密码.';
            $this->_Log($data['supplyer_id'],'平台修改供应商密码','supplyer');
        }
        $this->_Log($data['supplyer_id'],$logInfo);
        return $this->success('修改成功.',url('index'));
    }

    /**
     * 删除商品图片
     */
    public function removeImg($file='') {
        $img_id = input('post.id',0,'intval');
        $business_id = input('business_id',0,'intval');
        $type = input('type');
        if ($business_id > 0){
            if($type=='license_bum'){
                $img = $this->Model->where('business_id',$business_id)->value('license');
            }else{
                $img = $this->Model->where('business_id',$business_id)->value('live_views');
            }

            if (empty($img)){
                return $this->error('没有找到相关图片.');
            }
            $imgs = explode(',',$img);
            $file = $imgs[$img_id];
            unset($imgs[$img_id]);
            $new_imgs =  implode(',',$imgs);
            dump($type);
            if($type=='license_bum'){
                dump(111);die;
                $res = $this->Model->where('business_id',$business_id)->update(['license'=>$new_imgs]);
            }else{
                dump(222);die;
                $res = $this->Model->where('business_id',$business_id)->update(['live_views'=>$new_imgs]);
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

        if ($this->supplyer_id > 0){
            $dir = 'supplyer/'.$this->supplyer_id.'/gimg';
        }else{
            $dir = 'gimg/';
        }
        $result = $this->_upload($_FILES['file'],$dir,$thumb);
        if ($result['error']) {
            $data['code'] = 1;
            $data['msg'] = $result['info'];
            return $this->ajaxReturn($data);
        }
        $addarr['goods_id'] = input('post.gid',0,'intval');
        $addarr['sku_val'] = input('post.sku','','trim');

        if ($this->store_id > 0){
            $where[] = ['store_id','=',$this->store_id ];
        }elseif ($this->supplyer_id > 0){//供应商相关
            $addarr['supplyer_id'] = $this->supplyer_id;
            $addarr['admin_id'] = 0;
            $where[] = ['supplyer_id','=',$this->supplyer_id];
        }else{
            $addarr['admin_id'] = AUID;
            $where[] = ['admin_id','=',AUID];
        }
        $savepath = trim($result['info'][0]['savepath'],'.');

        $addarr['store_id'] = $this->store_id;
        $addarr['goods_img'] = $file_url = $savepath.$result['info'][0]['savename'];
        $addarr['goods_thumb'] = str_replace('.','_thumb.',$addarr['goods_img']);
        $GoodsImgsModel =  new GoodsImgsModel();
        //如果sku不为空，查询之前是否已上传过,则删除
        if (empty($addarr['sku_val']) == false){
            $where[] = ['goods_id','=',$addarr['goods_id']];
            $where[] = ['sku_val','=',$addarr['sku_val']];
            $imgObj = $GoodsImgsModel->where($where)->find();
            if (empty($imgObj) == false){
                unlink('.'.$imgObj['goods_thumb'],'.'.$imgObj['goods_img']);
                $imgObj->delete();
            }
        }
        $GoodsImgsModel->save($addarr);
        $img_id = $GoodsImgsModel->img_id;
        if ($img_id < 1){
            $this->removeImg($file_url);//删除刚刚上传的
            $data['code'] = 0;
            $data['msg'] = '商品图片写入数据库失败！';
            return $this->ajaxReturn($data);
        }
        $data['code'] = 0;
        $data['msg'] = "上传成功";
        $data['image'] = array('id'=>$img_id,'thumbnail'=>$file_url,'path'=>$file_url);
        $data['savename'] = $result['info'][0]['savename'];
        $data['src'] = $file_url;
        return $this->ajaxReturn($data);

    }

}
