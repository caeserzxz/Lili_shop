<?php

namespace app\member\controller\api;

use app\ApiController;

use app\member\model\UsersModel;
use app\member\model\UsersBindSuperiorModel;
use app\distribution\model\DividendRoleModel;

/*------------------------------------------------------ */
//-- 我的团队相关API
/*------------------------------------------------------ */

class MyTeam extends ApiController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
		$this->checkLogin();//验证登陆
        $this->Model = new UsersBindSuperiorModel();
    }
    /*------------------------------------------------------ */
	//-- 获取列表
	/*------------------------------------------------------ */
 	public function getList(){
		$level = input('level',0,'intval');
		if ($level == 1) {
            $where[] = ['pid', '=', $this->userInfo['user_id']];
        }elseif ($level == 2){
            $where[] = ['pid_b','=',$this->userInfo['user_id']];
        }elseif ($level == 3){
            $where[] = ['pid_c','=',$this->userInfo['user_id']];
		}else{
            $where[] = ['pid|pid_b|pid_c','=',$this->userInfo['user_id']];
        }
        $UsersModel = new UsersModel();
        $user_id = input('user_id','','trim');
		if (empty($user_id) == false){
		    if (is_numeric($user_id) == true){
                $where[] = ['user_id','=',$user_id];
            }else{
		        $uwhere[] = ['nick_name','like','%'.$user_id.'%'];
                $user_ids = $UsersModel->where($uwhere)->column('user_id');
                if (empty($user_ids)) $user_ids = 0;
                $where[] = ['user_id','in',$user_ids];
            }
		}
		
		$this->sqlOrder = 'user_id DESC';
        $data = $this->getPageList($this->Model, $where,'user_id',10);
		$DividendRoleModel = new DividendRoleModel();
        foreach ($data['list'] as $key=>$user){
            $user = $UsersModel->info($user['user_id']);
			$_user['user_id'] = $user['user_id'];
			$_user['nick_name'] = $user['nick_name'];
			$_user['headimgurl'] = $user['headimgurl'];
			$_user['account'] = $user['account'];
            $_user['role_id'] = $user['role_id'];
			$_user['role_name'] = '';
			if ($user['role_id'] > 0){
				$_user['role_name'] = $DividendRoleModel->info($user['role_id'],true);
			}
            $return['list'][] = $_user;
        }
		//执行统计
		$is_stat = input('is_stat',0,'intval');
		if ($is_stat == 1){
			$return['stat'] = $UsersModel->userShareStats($this->userInfo['user_id']);
		}
		//end
        $return['page_count'] = $data['page_count'];
		$return['code'] = 1;
		return $this->ajaxReturn($return);
	}
}
