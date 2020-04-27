<?php
/*------------------------------------------------------ */
//-- 微信小程序
//-- Author: wemk
/*------------------------------------------------------ */
namespace app\weixin\controller\api;
use app\ApiController;

use app\weixin\model\MiniUsersModel;

class Miniprogram  extends ApiController{

	public function initialize(){
        parent::initialize();
    }
    /*------------------------------------------------------ */
    //--  获取微信小程序openId
    /*------------------------------------------------------ */
    public function getOpenId(){
        $code = input('code','','trim');
        $res = file_get_contents('https://api.weixin.qq.com/sns/jscode2session?appid='.settings('xcx_appid').'&secret='.settings('xcx_appsecret').'&js_code='.$code.'&grant_type=authorization_co');
        $data = json_decode($res,true);
        $where[] = ['wx_openid','=',$data['openid']];
        $where[] = ['is_xcx','=',1];
        $data['defWebUrl'] = 'activity/index/index';
        $data['siteName'] = settings('site_name');

        return $this->ajaxReturn($data);
    }
    /*------------------------------------------------------ */
	//-- 小程序登录
	/*------------------------------------------------------ */
	public function do_login(){
        $post = input('post.');
        if(!$post['code']){
            $this->error('参数错误!');
        }
        if($post['share_token']){
            session('share_token',$post['share_token']);
        }
        $mini = new MiniUsersModel();
        $post['source'] = 'developers';
        $res = $mini->login($post);
        if (is_array($res) == false) return $this->error($res);
        $data['code'] = 1;
        $data['msg'] = '登录成功.';
        if ($res[0] == 'developers'){
            $data['developers'] = $res[1];
        }
        return $this->ajaxReturn($data);
    }
}?>