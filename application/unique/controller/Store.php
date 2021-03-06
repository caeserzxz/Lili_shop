<?php
/*------------------------------------------------------ */
//-- 商家
//-- Author: iqgmy
/*------------------------------------------------------ */
namespace app\unique\controller;
use think\facade\Cache;
use app\ClientbaseController;
use app\store\model\BusinessQrcodeModel;
use app\store\model\CategoryModel;
use app\store\model\UserBusinessModel;
use app\store\model\BusinessGiftModel;
use app\unique\model\RedbagModel;
use app\unique\model\PayRecordModel;
use app\mainadmin\model\PaymentModel;

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
        if($this->userInfo['is_business']==1){
            return $this->error('您已是商家.');
        }
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
        $PayRecordModel = new PayRecordModel();
        $this->assign('userInfo',$this->userInfo);
        $business = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        if(empty($business)){
            $this->error('您还不是代理');
        }else{
            if($business['status']==2){
                $this->assign('title', '商家申请不通过');
                return $this->fetch('business_fail');
            }else if($business['status']==0){
                $this->assign('title', '商家申请审核中');
                return $this->fetch('business_review');
            }

        }
        #详细地址
        $address = str_replace(' ', '', $business['merger_name']).$business['address'];
        $this->assign('address',$address);
//        $sales_count = Cache::get('sales_mkey' . $business['business_id']);
        if(empty($sales_count)){
            $arr = [];
            #本月业绩
            $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
            $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $where1[] = ['business_id','=',$business['business_id']];
            $where1[] = ['status','=',1];
            $where1[] = ['add_time', 'between', array($beginThismonth, $endThismonth)];
            $this_month_count =  $PayRecordModel->where($where1)->sum('amount');
            #今日业绩
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where2[] = ['business_id','=',$business['business_id']];
            $where2[] = ['status','=',1];
            $where2[] = ['add_time', 'between', array($beginToday, $endToday)];
            $today_count =  $PayRecordModel->where($where2)->sum('amount');
            #更新时间
            $arr['count_time'] = time();
            $arr['this_month_count'] = sprintf("%.2f",$this_month_count);
            $arr['today_count'] = sprintf("%.2f",$today_count);
            $sales_count = $arr;
//            Cache::set('sales_mkey' . $business['business_id'], $arr, 600);
        }
        $this->assign('appType',session('appType'));
        $this->assign('business',$business);
        $this->assign('sales_count',$sales_count);
        $this->assign('title', '商家管理');
        //获取微信jsapi参数
        $wxShare = (new \app\weixin\model\WeiXinModel)->getSignPackage();
        $this->assign('wxShare',$wxShare);

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
    /*------------------------------------------------------ */
    //-- 商家业绩
    /*------------------------------------------------------ */
    public function sales(){
        $this->assign('title', '商家业绩');
        $this->assign('date', date('Y-m'));
        return $this->fetch();
    }

    /*------------------------------------------------------ */
    //-- 添加红包
    /*------------------------------------------------------ */
    public function add_gift(){
        $this->assign('title', '添加红包');
        return $this->fetch('add_gift');
    }

    /*------------------------------------------------------ */
    //-- 红包列表
    /*------------------------------------------------------ */
    public function gift_list(){
        $this->assign('title', '红包营销');
        return $this->fetch('gift_list');
    }

    /*------------------------------------------------------ */
    //-- 红包详情
    /*------------------------------------------------------ */
    public function gift_detail(){
        $BusinessGiftModel = new BusinessGiftModel();
        $RedbagModel = new RedbagModel();
        $id = input('id');
        #红包信息
        $info = $BusinessGiftModel->where('id',$id)->find();

        #已领金额
        $info['collected'] = $RedbagModel->where(['gift_id'=>$id,'status'=>1])->sum('price');
        #已用金额
        $info['used'] = $RedbagModel->where(['gift_id'=>$id,'status'=>1])->sum('price');
        #未领金额
        $info['unclaimed'] = $info['gift_money'] - $info['collected'] - $info['used'] ;

        $this->assign('info',$info);
        $this->assign('title', '红包详情');
        return $this->fetch('gift_detail');
    }

    /*------------------------------------------------------ */
    //-- 红包领取结果
    //-- $type 1领取成功  2已发完  3红包活动已结束
    /*------------------------------------------------------ */
    public function gift_receive(){
        $type = input('type');
        if($type==1){
            $RedbagModel = new RedbagModel();
            #领取成功
            $id = input('id');
            $money = $RedbagModel->where('redbag_id',$id)->value('price');
            $this->assign('money',$money);
        }
        $this->assign('type',$type);
        $this->assign('title', '领取红包');
        return $this->fetch('gift_receive');
    }

    /*------------------------------------------------------ */
    //-- 去买单
    /*------------------------------------------------------ */
    public function pay_bill(){
        $business_id = input('business_id');
        #商家信息
        $info = $this->Model->where('business_id',$business_id)->find();
        $this->assign('info',$info);
        #用户信息
        $this->assign('userInfo',$this->userInfo);
        $this->assign('title', '去买单');
        return $this->fetch('pay_bill');
    }

    /*------------------------------------------------------ */
    //-- 下单完成
    /*------------------------------------------------------ */
    public function done(){
        $log_id = input('log_id',0,'intval');
        $type = input('type','','trim');
        $this->assign('title', '订单支付');
        $PayRecordModel = new PayRecordModel();
        $orderInfo = $PayRecordModel->where('log_id',$log_id)->find();
        if (empty($orderInfo) || $orderInfo['user_id'] != $this->userInfo['user_id']){
            return $this->error('订单不存在.');
        }
        if ($orderInfo['status'] == 9){
            return $this->error('订单已作废.');
        }
        $goPay = 0;
        $orderInfo['is_pay'] = 1;
        $payment = (new PaymentModel)->where('pay_id', $orderInfo['pay_id'])->find();
        if ($type == 'add' && $orderInfo['status'] == 0){
            if ($orderInfo['is_pay'] == 1){
                $goPay = 1;
            }
        }
        $this->assign('settings',settings());
        $this->assign('payment', $payment);
        $this->assign('goPay', $goPay);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('balance_money',$this->userInfo['account']['balance_money']);
        return $this->fetch('done');
    }
    /*------------------------------------------------------ */
    //-- 扫收款码
    /*------------------------------------------------------ */
    public function qrcode()
    {
        $id = input('id', 0, 'intval');
        if (empty($id) || $id <= 0) {
            return $this->error('参数错误');
        }
        $BusinessQrcodeModel = new BusinessQrcodeModel();
        if ($this->userInfo['user_id']) {
            $BusinessQrcodeInfo = $BusinessQrcodeModel->where('id', $id)->find();
            if ($BusinessQrcodeInfo['is_del'] == '1') {
                $this->error('该收款码已作废');
            }
            if (empty($BusinessQrcodeInfo['bussiness_id'])) {
                $UserBusinessModel = new UserBusinessModel();
                $businessInfo = $UserBusinessModel->getInfo($this->userInfo['user_id']);
                if (!empty($businessInfo)) {
                    $qrnum = $BusinessQrcodeModel->where('bussiness_id', $businessInfo['business_id'])->where('is_del', 0)->count();
                    if ($qrnum <= 0) {
                        //去绑定
                        $this->assign('id', $id);
                        $this->assign('ecode_url', $BusinessQrcodeInfo['ecode_url']);
                        $this->assign('title', '绑定');
                        return $this->fetch('binding');
                    }
                }
                $this->error('该收款码暂未绑定商家');
            }
        }
        //跳转去付款页
        $this->redirect(url('unique/store/pay_bill', ['business_id' => $BusinessQrcodeInfo['bussiness_id']]));
    }
    /*------------------------------------------------------ */
    //-- 我的收款码
    /*------------------------------------------------------ */
    public function myqrcode()
    {
        $UserBusinessModel = new UserBusinessModel();
        $businessInfo = $UserBusinessModel->getInfo($this->userInfo['user_id']);
        $BusinessQrcodeModel = new BusinessQrcodeModel();
        $BusinessQrcodeInfo = $BusinessQrcodeModel->where('bussiness_id', $businessInfo['business_id'])->where('is_del', 0)->find();
        if (empty($BusinessQrcodeInfo)) {
            $this->error('暂未绑定收款码');
        }
        $this->assign('title', '收款码');
        $this->assign('ecode_url', $BusinessQrcodeInfo['ecode_url']);
        return $this->fetch();
    }

    /*------------------------------------------------------ */
    //-- 我的会员
    /*------------------------------------------------------ */
    public function my_team(){
        $this->assign('title', '我的会员');
        return $this->fetch();
    }
}?>