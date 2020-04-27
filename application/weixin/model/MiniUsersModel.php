<?php

namespace app\weixin\model;

use think\Db;
use app\member\model\UsersModel;
//*------------------------------------------------------ */
//-- 微信会员
/*------------------------------------------------------ */
class MiniUsersModel extends WeiXinUsersModel
{


	/*------------------------------------------------------ */
	//-- 微信小程序用户查询，不存在则写入数据
	/*------------------------------------------------------ */
    public function login($data){
		if (empty($data)) return '参数不能为空';
		$mini = new MiniModel();
		$minidata = $mini->GetOpenidFromMp($data['code']);

		if(!$minidata['openid']){
			return 'openid获取失败，请联系管理员';
		}

		$wx_info = $this->where('wx_openid',$minidata['openid'])->find();
		if(empty($wx_info)){
			return $this->doLogin($wx_info['user_id'],$data['source']);
		}

		//过滤微信名里面的表情特殊符号
		$data['nickName'] = filterEmoji($data['nickName']);
		$sex_arr = ['未知','男','女'];
		//没有数据，执行注册会员
		$inarr['user_id'] = 0;
		$inarr['wx_openid'] = $minidata['openid'];		
		$inarr['add_time'] = $inarr['update_time'] = time();
		$inarr['sex'] = $sex_arr[$data['gender']];
		$inarr['subscribe'] = 0;
		$inarr['wx_nickname'] = $data['nickName'];
		$inarr['wx_headimgurl'] = $data['avatarUrl'];
		$inarr['wx_city'] = $data['city'];
		$inarr['wx_province'] = $data['province'];	
		Db::startTrans();		
		$res = $this->save($inarr);
		if ($res < 1) return '数据入库失败';
        $wxuid = $res->wxuid;
		$userInArr['sex'] = $data['gender'];
		$userInArr['nick_name'] = $data['nickName'];
        $UsersModel = new UsersModel();
        $res = $UsersModel->register($userInArr,$wxuid);//注册会员
		if ($res != true) return $res;
		//缓存微信账户数据 
        $wx_info = $this->info($wxuid,'wx');
		return $UsersModel->doLogin($wx_info['user_id'],$data['source']);
	}



}
