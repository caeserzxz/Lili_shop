<?php
namespace app\unique\controller\api;
use app\unique\model\PayRecordModel;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
use app\member\model\UsersModel;

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
    //-- 获取商家业绩
    /*------------------------------------------------------ */
    public function getSales()
    {
        $date = input('date', date('Y-m'), 'trim');
        $date1 = strtotime($date.'-1 '." 00:00:00");
        $date2 = strtotime(date('Y-m-t', $date1)) + 86399;
        $return['date'] = $date;
        $return['code'] = 1;
        $PayRecordModel = new PayRecordModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['add_time', 'between', array($date1, $date2)];
        //SELECT sum(amount) all_amount,FROM_UNIXTIME(add_time,"%Y.%m.%d") addtime FROM `users_pay_record` group by addtime order by addtime asc
        $rows = $PayRecordModel->field('sum(amount) all_amount,FROM_UNIXTIME(add_time,"%Y.%m.%d") addtime')->where($where)->group('addtime')->order('addtime ASC')->select()->toArray();
        $return['all_amount'] = 0;
        foreach ($rows as $key => $row) {
            $return['all_amount'] += $row['all_amount'];
            $return['list'][] = $row;
        }
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取商家信息
    /*------------------------------------------------------ */
    public function getBusinessInfo()
    {
        return $this->ajaxReturn($this->Model->getInfo($this->userInfo['user_id']));
    }
}