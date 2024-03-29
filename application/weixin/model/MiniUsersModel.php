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
        $UsersModel = new UsersModel();
        $where['wx_openid'] = $minidata['openid'];
        $where['is_xcx'] = 1;
        $wx_info = $this->where($where)->find();
		if(empty($wx_info) == false){
		    if ($wx_info['user_id'] > 0 ){
                $UsersModel->doLogin($wx_info['user_id'],$data['source']);
            }
            return $wx_info->toArray();
		}

		//过滤微信名里面的表情特殊符号
		$data['nickName'] = filterEmoji($data['nickName']);
		$sex_arr = ['未知','男','女'];
		//没有数据，执行注册会员
		$inarr['user_id'] = 0;
        $inarr['is_xcx'] = 1;
		$inarr['wx_openid'] = $minidata['openid'];		
		$inarr['add_time'] = $inarr['update_time'] = time();
		$inarr['sex'] = $sex_arr[$data['gender']];
		$inarr['subscribe'] = 0;
		$inarr['wx_nickname'] = $data['nickName'];
		$inarr['wx_headimgurl'] = $data['avatarUrl'];
		$inarr['wx_city'] = $data['city'];
		$inarr['wx_province'] = $data['province'];	
        if (settings('register_status') == 2) {//微信自动注册会员
            Db::startTrans();
            $res = $this->save($inarr);
            if ($res < 1) return '数据入库失败';
            $wxuid = $res->wxuid;
            $userInArr['sex'] = $data['gender'];
            $userInArr['nick_name'] = $data['nickName'];
            $res = $UsersModel->register($userInArr, $wxuid);//注册会员
            if ($res != true) return $res;
        }else{
            $res = $this->save($inarr);
            if ($res < 1) return '数据入库失败';
            $wxuid = $res->wxuid;
        }
		//缓存微信账户数据 
        $wx_info = $this->info($wxuid,'wx');
        $UsersModel->doLogin($wx_info['user_id'],$data['source']);
		return $wx_info;
	}



}
