<?php
namespace app\unique\controller\api;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
use app\member\model\UsersModel;
use app\store\model\BusinessGiftModel;
use app\unique\model\RedbagModel;
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
    //-- 获取商家数据
    /*------------------------------------------------------ */
    public function getList(){
        $where[] = ['status', '=', 1];
        $where[] = ['is_ban', '=', 0];

        $city = input('city',0);
        #名称搜索条件
        $keyword = trim(input('keyword'),' ');
        if(empty($keyword)==false){
            $where[] = ['business_name',['like',"%".$keyword."%"]];
        }
        #分类搜索条件
        $cid =input('cid');
        if(empty($cid)==false){
            $where[] = ['category_id','=',$cid];
        }

        $search['page'] = input('page',0,'int');

        $limit = $search['page']*20 . ',' . 20;
        $longitude = input('longitude');
        $latitude = input('latitude');
        //获取商家信息
        $storeInfo  = $this->Model->where($where)->field("*,round(6378.138*2*asin(sqrt(pow(sin( (".$latitude."*pi()/180-latitude*pi()/180)/2),2)+cos(".$latitude."*pi()/180)*cos(latitude*pi()/180)* pow(sin( (".$longitude."*pi()/180-longitude*pi()/180)/2),2)))*1000) as distance")->order('distance')->limit($limit)->select();
        foreach ($storeInfo as $key => $val) {
            #距离处理
            if($val['distance']){
                if( $val['distance'] > 1000 ){
                    $storeInfo[$key]['distance'] = round($val['distance']/1000). 'km';
                } else {
                    $storeInfo[$key]['distance'] = round($val['distance']) . 'm';
                }
            } else {
                $storeInfo[$key]['distance'] = '0m';
            }
            #标签处理
            $storeInfo[$key]['label_arr'] = explode(' ',trim($val['label']));
            #鼓励金处理
            $profits = unserialize(settings('profits'));
            $storeInfo[$key]['hearten'] = intval($profits[$val['profits']]['hearten']);

        }
        $return['list'] = $storeInfo;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取首页所需要定位
    /*------------------------------------------------------ */
    public function index(){
        $lat = input('lat');
        $lng = input('lng');
        if($this->userInfo['longitude'] != $lng || $this->userInfo['latitude'] != $lat){
            $this->userInfo['longitude'] = $lng;
            $this->userInfo['latitude'] = $lat;
        }
        $userModel = new UsersModel();
        $regionModel = new RegionModel();
        $userModel->upInfo($this->userInfo['user_id'],array('longitude'=>$lng,'latitude'=>$lat));
        $key = settings('tx_key');
        $secret_key = settings('secret_key');

        $sig= md5("/ws/geocoder/v1/?get_poi=1&key=".$key."&location=".$lat.",".$lng.$secret_key);
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?get_poi=1&key=".settings('tx_key')."&location=".$lat.",".$lng."&sig=".$sig;
        if ($result=file_get_contents($url)) {
            $result = json_decode($result,true);
        }

        $data = array(
            'code'=>1,
            'city'=>'广东',
            'address'=>'棠东御富科贸园C1座',
            'city_id'=>440100
        );
        if($result['status']==0){
            $data['city'] = $result['result']['address_component']['city'];
            $data['address'] = $result['result']['address'];
            $data['city_id'] = $regionModel->where('name',$result['result']['address_component']['city'])->value('id');
        }
        $this->userInfo['now_address'] = $data['address'];
        return $this->ajaxReturn($data);
    }
    /*------------------------------------------------------ */
    //-- 申请商家
    /*------------------------------------------------------ */
    public function add_business(){
        $this->checkLogin();//验证登陆
        $CategoryModel = new CategoryModel();
        $RegionModel = new RegionModel();
        $AgentModel = new AgentModel();
        $data = input('post.');

        if(empty($data['business_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写店铺名称']);
        if(empty($data['business_user_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写真实姓名']);
        if(empty($data['business_mobile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写手机号']);
        if(empty($data['profits'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写让利率']);
        if(empty($data['category'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择行业分类']);
        if(empty($data['address'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写详细地址']);
        if(empty($data['data_codes'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择店铺地区']);
        if(empty($data['imgfile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请上传店铺实景图']);
        if(empty($data['imgfile2'])) return $this->ajaxReturn(['code' => 0,'msg' => '请上传店铺营业照']);
        //获取当前用户的商家信息
        $UserBusinessModel = new UserBusinessModel();
        $info = $UserBusinessModel->where('user_id',$this->userInfo['user_id'])->find();
        #检查代理信息
        if(empty($data['token'])==false){
            $agent_info  = $AgentModel->where('token',$data['token'])->find();
            if(empty($agent_info)){
                return $this->ajaxReturn(['code' => 0,'msg' => '代理不存在']);
            }
        }
        #检查店铺区域与代理的代理区域是否相同
        if(empty($agent_info)==false){
            $codes = explode(",",$data['data_codes']);
            if($codes[0]!=$agent_info['province']||$codes[1]!=$agent_info['city']){
                return $this->ajaxReturn(['code' => 0,'msg' => '代理区域不同']);
            }
        }
        if(empty($agent_info)==false){
            $inArr['agent_id'] = $agent_info['agent_id'];
        }
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
                if(empty($info)==false){
                    if(strstr($info['live_views'],$v)){
                        $imgfile_str .= ','.$v;
                    }else{
                        $res = uploadimage($v,'store_img');
                        if(empty($res['path'])){
                            return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                        }
                        $imgfile_str .= ','.$res['path'];
                    }

                }else{
                    $res = uploadimage($v,'store_img');
                    if(empty($res['path'])){
                        return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                    }
                    $imgfile_str .= ','.$res['path'];
                }

            }
            $imgfile_str =  ltrim($imgfile_str, ",");
        }
        #店铺营业照
        if(empty($data['imgfile2'])==false){
            $imgfile2_str = '';
            foreach ($data['imgfile2'] as $k=>$v){
                if(empty($info)==false){
                    if(strstr($info['license'],$v)){
                        $imgfile2_str .= ','.$v;
                    }else{
                        $res2 = uploadimage($v,'store_img');
                        if(empty($res2['path'])){
                            return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                        }
                        $imgfile2_str .= ','.$res2['path'];
                    }
                }else{
                    $res2 = uploadimage($v,'store_img');
                    if(empty($res2['path'])){
                        return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                    }
                    $imgfile2_str .= ','.$res2['path'];
                }

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
        $inArr['status'] = 0;

        if(empty($info)==false){
            $result = $this->Model->where('business_id',$info['business_id'])->update($inArr);
        }else{
            $result = $this->Model->insert($inArr);
        }

        if($result){
            return $this->ajaxReturn(['code' => 1,'msg' => '申请成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '申请失败']);
        }
    }

    /*------------------------------------------------------ */
    //-- 店铺管理
    /*------------------------------------------------------ */
    public function business_set(){
        $this->checkLogin();//验证登陆
        $data = input('post.');

        if(empty($data['business_id'])) return $this->ajaxReturn(['code' => 0,'msg' => '数据错误']);
        if(empty($data['info'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写店铺介绍']);
        if(empty($data['contact_mobile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写联系电话']);
        #原店铺信息
        $info = $this->Model->where('business_id',$data['business_id'])->find();
        #店铺相册
        if(empty($data['imgfile'])==false){
            $imgfile_str = '';
            foreach ($data['imgfile'] as $k=>$v){
                if(strstr($info['imgs'],$v)){
                    $imgfile_str .= ','.$v;
                }else{
                    $res = uploadimage($v,'store_img');
                    if(empty($res['path'])){
                        return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                    }
                    $imgfile_str .= ','.$res['path'];
                }
            }
            $imgfile_str =  ltrim($imgfile_str, ",");
        }
        #店铺logo
        if(empty($data['imgfile3'])==false){
            $imgfile3_str = '';
            foreach ($data['imgfile3'] as $k=>$v){
                if(strstr($info['logo'],$v)){
                    $imgfile3_str .= ','.$v;
                }else{
                    $res2 = uploadimage($v,'store_img');
                    if(empty($res2['path'])){
                        return $this->ajaxReturn(['code' => 0,'msg' => '图片上传失败']);
                    }
                    $imgfile3_str .= ','.$res2['path'];
                }

            }
            $imgfile3_str =  ltrim($imgfile3_str, ",");
        }

        #组装数据
        $inArr['info'] = trim($data['info'],'');
        $inArr['contact_mobile'] =$data['contact_mobile'];
        $inArr['logo'] = $imgfile3_str;
        $inArr['imgs'] = $imgfile_str;

        $result = $this->Model->where('business_id',$data['business_id'])->update($inArr);
        if($result){
            return $this->ajaxReturn(['code' => 1,'msg' => '设置成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '设置失败']);
        }

    }

    /*------------------------------------------------------ */
    //-- 添加红包
    /*------------------------------------------------------ */
    public function add_gift(){
        $this->checkLogin();//验证登陆
        $data = input('post.');
        $BusinessGiftModel = new BusinessGiftModel();
        $UserBusinessModel = new UserBusinessModel();

        if(empty($data['gift_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写红包名称']);
        if(empty($data['gift_type'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择红包类型']);
        if(empty($data['gift_money'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写红包金额']);
        if(empty($data['gift_num'])) return $this->ajaxReturn(['code' => 0,'msg' => '清填写红包数量']);
        if(empty($data['start_time'])) return $this->ajaxReturn(['code' => 0,'msg' => '清选择使用开始时间']);
        if(empty($data['end_time'])) return $this->ajaxReturn(['code' => 0,'msg' => '清选择使用结束时间']);

        $start_time = strtotime($data['start_time']);
        $end_time  = strtotime($data['end_time'].' 23:59:59');
        if($start_time>=$end_time)return $this->ajaxReturn(['code' => 0,'msg' => '开始时间不能早于结束时间']);

        #商家信息
        $business = $UserBusinessModel->where(['user_id'=>$this->userInfo['user_id'],'status'=>1])->find();
        if(empty($business))  return $this->ajaxReturn(['code' => 0,'msg' => '商家信息出错']);

        #红包活动只能同时进行一个
        $where['business_id'] = $business['business_id'];
        $where['status'] = 0;
        $info = $BusinessGiftModel->where($where)->count('id');
        if(empty($info)==false) return $this->ajaxReturn(['code' => 0,'msg' => '红包活动只能同时进行一个']);

        #组装数据
        $inArr['business_id'] = $business['business_id'];
        $inArr['gift_name'] = trim($data['gift_name']);
        $inArr['gift_money'] = $data['gift_money'];
        $inArr['gift_num'] = $data['gift_num'];
        $inArr['gift_type'] = $data['gift_type'];
        $inArr['start_time'] = $start_time;
        $inArr['end_time'] = $end_time;
        $inArr['add_time'] = time();
        $inArr['threshold'] = $data['threshold'];
        $inArr['status'] = 0;
        $inArr['total_num'] = $data['total_num'];

        $res = $BusinessGiftModel->insert($inArr);
        if($res){
            return $this->ajaxReturn(['code' => 1,'msg' => '添加成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '添加失败']);
        }

    }

    /*------------------------------------------------------ */
    //-- 获取红包列表
    /*------------------------------------------------------ */
    public function get_gift_list(){
        $this->checkLogin();//验证登陆
        $BusinessGiftModel = new BusinessGiftModel();
        $UserBusinessModel = new UserBusinessModel();
        $page = input('page');
        $page_size = 7;
        #商家信息
        $business = $UserBusinessModel->where(['user_id'=>$this->userInfo['user_id'],'status'=>1])->find();
        if(empty($business))  return $this->ajaxReturn(['code' => 0,'msg' => '商家信息出错']);

        $where['business_id'] = $business['business_id'];
        $list = $BusinessGiftModel->where($where)->limit($page_size*$page,$page_size)->order('id desc')->select();
        foreach ($list as $k=>$v){
            #有效时间
            $start_time = date('Y.m.d',$v['start_time']);
            $end_time = date('Y.m.d',$v['end_time']);
            $list[$k]['valid'] = $start_time.'-'.$end_time;
            #发放时间
            $list[$k]['add_time'] = date('Y.m.d H:i',$v['add_time']);
            #红包类型
            if($v['gift_type']==1){
                $list[$k]['type_str'] = '随机红包';
            }else{
                $list[$k]['type_str'] = '平均红包';
            }
            #红包状态
            if($v['status']==0){
                $list[$k]['status_str'] = '发放中';
            }else if($v['status']==1){
                $list[$k]['status_str'] = '已发完';
            }else if($v['status']==2){
                $list[$k]['status_str'] = '已过期';
            }else if($v['status']==3){
                $list[$k]['status_str'] = '已停用';
            }
        }
        $arr['list'] = $list;
        return $arr;
    }

    /*------------------------------------------------------ */
    //-- 获取红包列表
    /*------------------------------------------------------ */
    public function get_gift_detail(){
        $RedbagModel = new RedbagModel();
        $page = input('page');
        $type = input('type');
        $id = input('id');
        $page_size = 12;

        if($type==1){
            #已领取
            $where['gift_id'] = $id;
            $where['status'] = 0;
        }else{
            #已使用
            $where['gift_id'] = $id;
            $where['status'] = 1;
        }
        $list = $RedbagModel->where($where)->alias('r')->join('users u','r.user_id=u.user_id')->limit($page_size*$page,$page_size)->order('redbag_id desc')->field('r.*,u.nick_name,u.headimgurl')->select();

        foreach ($list as $k=>$v){
            $list[$k]['add_time'] = date('m-d H:i',$v['add_time']);
            $list[$k]['update_time'] = date('m-d H:i',$v['update_time']);
        }
        $arr['list'] = $list;

        $this->ajaxreturn($arr);
    }

    /*------------------------------------------------------ */
    //-- 停用红包
    /*------------------------------------------------------ */
    public function stop_using(){
        $BusinessGiftModel = new BusinessGiftModel();
        $id = input('id');

        $info = $BusinessGiftModel->where('id',$id)->find();
        if($info['status']==2){
            return $this->ajaxReturn(['code' => 0,'msg' => '红包已过期']);
        }else if($info['status']==3){
            return $this->ajaxReturn(['code'=> 0,'msg' => '红包已停用']);
        }

        $map['status'] = 3;
        $map['stop_time'] = time();
        $res = $BusinessGiftModel->where('id',$id)->update($map);
        if($res){
            return $this->ajaxReturn(['code' => 1,'msg' => '停用成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '停用失败']);
        }


    }

}