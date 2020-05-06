<?php

namespace app\weixin\model;
use app\BaseModel;
use think\facade\Cache;
use think\Db;

use app\member\model\UsersModel;
//*------------------------------------------------------ */
//-- 微信会员
/*------------------------------------------------------ */
class WeiXinUsersModel extends BaseModel
{
	protected $table = 'weixin_users';
	public  $pk = 'wxuid';
	protected $mkey = 'weixin_users_mkey_';
	/*------------------------------------------------------ */
    //--  清除memcache
    /*------------------------------------------------------ */
    public function cleanMemcache($wxuid=0){
        Cache::rm($this->mkey.'_'.$wxuid);
    }
	/*------------------------------------------------------ */
	//-- 微信用户查询，不存在写入数据
	/*------------------------------------------------------ */
    public function login($access_token){
		if (empty($access_token)) return false;
        $where['wx_openid'] = $access_token['openid'];
        $where['is_xcx'] = 0;
        $wx_info = $this->where($where)->find();
		$UsersModel = new UsersModel();
		if (empty($wx_info) == false){
		    if ($wx_info['update_time'] < time() - 86400 ){
                $WeiXinModel = new WeiXinModel();
                $wx_arr = $WeiXinModel->getWxUserInfoSubscribe($wx_info['wx_openid']);
                if ($wx_arr['subscribe'] == 1){
                    $upArr['update_time'] = time();
                    $upArr['sex'] = $wx_arr['sex'];
                    $upArr['subscribe'] = $wx_arr['subscribe'] * 1;
                    $upArr['wx_nickname'] = $wx_arr['nickname'];
                    $upArr['wx_headimgurl'] = $wx_arr['headimgurl'];
                    $upArr['wx_city'] = $wx_arr['city'];
                    $upArr['wx_province'] = $wx_arr['province'];
                    $this->where('wxuid',$wx_info['wxuid'])->update($upArr);
                    $wx_info = $this->where('wxuid',$wx_info['wxuid'])->find();
                }
            }
            $UsersModel->doLogin($wx_info['user_id'],'wxH5');
			return $wx_info->toArray();
		}
		//没有数据，执行注册会员
		$WeiXinModel = new WeiXinModel();
		//获取相关微信帐号信息
        $wx_arr = $WeiXinModel->getWxUserInfoSubscribe($access_token['openid']);
        //防止微信并发-插入出现脏写锁-支持三秒等待执行
        $lock = Cache::get('wxinfo_lock_'.$access_token['openid']);
        if(empty($lock) == false){
			sleep(1);
			$this->login($access_token);
		}
		Cache::set('wxinfo_lock_'.$access_token['openid'],1,3);


        if ($wx_arr['subscribe'] == 0) {//没有关注，上面的无法获取微信信息调用
            $wx_arr = $WeiXinModel->getWxUserInfo($access_token);
        }
		$inarr['user_id'] = 0;
		$inarr['wx_openid'] = $access_token['openid'];		
		$inarr['add_time'] = $inarr['update_time'] = time();
		$inarr['sex'] = $wx_arr['sex'];
		$inarr['subscribe'] = $wx_arr['subscribe'] * 1;
		$inarr['wx_nickname'] = $wx_arr['nickname'];
		$inarr['wx_headimgurl'] = $wx_arr['headimgurl'];
		$inarr['wx_city'] = $wx_arr['city'];
		$inarr['wx_province'] = $wx_arr['province'];

		if (settings('register_status') == 2){//微信自动注册会员
			Db::startTrans();
			$res = $this->save($inarr);
			if ($res < 1) return false;
            $wxuid = $res->wxuid;
			$userInArr['sex'] = $wx_arr['sex'] == '男' ? 1 : 2;
			$userInArr['nick_name'] = $wx_arr['nickname'];
			$userInArr['headimgurl'] = $wx_arr['headimgurl'];
			$res = $UsersModel->register($userInArr,$wxuid);//注册会员
			if ($res != true) return $res;
		}else{
            $res = $this->save($inarr);
            if ($res < 1) return false;
            $wxuid = $res->wxuid;
        }
        $wx_info = $this->info($wxuid,'wx');
        $UsersModel->doLogin($wx_info['user_id'],'wxH5');
		return $wx_info;
	}
		
	
	/*------------------------------------------------------ */
	//-- 获取微信用户信息
	/*------------------------------------------------------ */
    public function info($id=0,$type='user'){
		if (empty($id)) return false;

		$info = Cache::get($this->mkey.$type.$id);
		if ($info) return $info;
		if ($type == 'user'){
            $where[] = ['user_id','=',$id];
        }else{
            $where[] = ['wxuid','=',$id];
        }
		$info = $this->where($where)->find();
		if (empty($info)) return [];
		$info = $info->toArray();
		Cache::set($this->mkey.$type.$id,$info,60);
		return $info;//如何存直接返回
	}
	/*------------------------------------------------------ */
	//-- 微信关联用户ID
	/*------------------------------------------------------ */
    public function bindUserId($wxuid,$user_id = 0){
		if ($user_id < 1 || $wxuid < 1) return false;
		$uparr['update_time'] = time();
		$uparr['user_id'] = $user_id;
		return $this->where('wxuid',$wxuid)->update($uparr);
	}
}
