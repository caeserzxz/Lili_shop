<?php

namespace app\unique\controller\api;

use app\ApiController;
use app\member\model\UsersModel;
use app\store\model\BusinessQrcodeModel;
use app\store\model\UserBusinessModel;

/*------------------------------------------------------ */
//-- 商家相关API
/*------------------------------------------------------ */

class Store extends ApiController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->checkLogin();//验证登陆
        $this->Model = new UsersModel();
    }

    /*------------------------------------------------------ */
    //-- 绑定收款码
    /*------------------------------------------------------ */
    public function binding(){
        $id = input('id', 0, 'intval');
        if (empty($id) || $id <= 0) {
            return $this->error('参数错误');
        }
        $BusinessQrcodeModel = new BusinessQrcodeModel();
        $BusinessQrcodeInfo = $BusinessQrcodeModel->where('id', $id)->find();
        if ($BusinessQrcodeInfo['is_del'] == '1') {
            $this->error('绑定失败,该付款码已作废');
        }else if (!empty($BusinessQrcodeInfo['bussiness_id'])) {
            $this->error('绑定失败,该付款码已被其他商家绑定');
        }else{
            $UserBusinessModel = new UserBusinessModel();
            $businessInfo = $UserBusinessModel->getInfo($this->userInfo['user_id']);
            if (empty($businessInfo)) {
                $this->error('绑定失败,商家身份才能绑定');
            }
            $qrnum = $BusinessQrcodeModel->where('bussiness_id', $businessInfo['business_id'])->where('is_del', 0)->count();
            if ($qrnum > 0) {
                $this->error('绑定失败,您已绑定过收款码');
            }
        }
        $res = $BusinessQrcodeModel->where('id', $id)->update(['bussiness_id'=>$businessInfo['business_id'],'update_time'=>time()]);
        if($res){
            $return['msg'] = '绑定成功';
            $return['code'] = 1;
            $return['url'] = url('unique/store/business');
        }else{
            $return['msg'] = '绑定失败';
            $return['code'] = 0;
        }
        return $this->ajaxReturn($return);
    }
}
