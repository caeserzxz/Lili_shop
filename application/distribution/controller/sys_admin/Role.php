<?php
namespace app\distribution\controller\sys_admin;
use app\AdminController;
use app\distribution\model\DividendRoleModel;
use app\distribution\model\RoleGoodsModel;
use think\facade\Env;

use app\member\model\UsersModel;
/**
 * 分销身份管理
 */
class Role extends AdminController
{	
	//*------------------------------------------------------ */
	//-- 初始化
	/*------------------------------------------------------ */
   public function initialize()
   {	
   		parent::initialize();
		$this->Model = new DividendRoleModel(); 
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
		
		$this->sqlOrder = 'level ASC';
        $data = $this->getPageList($this->Model);			
		$this->assign("data", $data);
		if ($runData == false){
			$data['content']= $this->fetch('list')->getContent();
			unset($data['list']);
			return $this->success('','',$data);
		}
        return true;
    }
	/*------------------------------------------------------ */
	//-- 获取所有接口程序
	/*------------------------------------------------------ */
    public function getFunction() {
		$rows = readModules(Env::get('extend_path').'/distribution/'.config('config.dividend_type'),'Level');

		$modules = array();
		foreach ($rows as $row){
			$modules[$row['function']] = $row;
		}
		return $modules;
	}
	/*------------------------------------------------------ */
	//-- 详细页调用
	/*------------------------------------------------------ */
    public function asInfo($data) {
		$this->assign('upLevel',  $this->getFunction());
		$roleList = $this->Model->getRows();
        $this->assign('allroleList', $roleList);
		if ($data['role_id'] > 0){
			foreach ($roleList as $key=>$role){
				if ($role['level'] > $data['level']){
					unset($roleList[$key]);	
				}
			}
			unset($roleList[$data['role_id']]);	
		}
		$this->assign('roleList', $roleList);

		$data['function'] = [];
		if ($data['role_id'] > 0){
			$upleve_value = json_decode($data['upleve_value'],true);
			$data['function'][$data['upleve_function']] = $upleve_value;
			if (empty($upleve_value['buy_goods']) == false){
                $buy_goods_ids = array_keys($upleve_value['buy_goods']);
				$where[] = ['goods_id','in',$buy_goods_ids];
				$glist = (new \app\shop\model\GoodsModel)->where($where)->field('goods_id,goods_sn,goods_name')->select();
				$buy_goods = [];
                if (empty($glist) == false){
                    foreach ($glist as $g){
                        $g['limit_num'] = $upleve_value['buy_goods'][$g['goods_id']];
                        $buy_goods[] = $g;
                    }
                }
                $data['function'][$data['upleve_function']]['buy_goods'] = $buy_goods;
			}
		}
		
		return $data;
	}
	/*------------------------------------------------------ */
	//-- 添加前处理
	/*------------------------------------------------------ */
    public function beforeAdd($data) {
		$count = $this->Model->where('role_name',$data['role_name'])->count('role_id');
		if ($count > 0) return $this->error('操作失败:已存在相同的身份名称，不允许重复添加！');
		$byLevel = input('byLevel',0,'intval');

        $data['level'] = $byLevel + 1;
		$data['add_time'] = $data['update_time'] = time();
		$upleve_value = input('function_val');
        $buy_goods_id = input('buy_goods_id');
        $buy_goods_limit_num = input('buy_goods_limit_num');
        $buy_goods = [];
        foreach ($buy_goods_id as $key=>$bgid){
            $buy_goods[$bgid] = $buy_goods_limit_num[$key];
        }
        $upleve_value['buy_goods'] = $buy_goods;
		$data['upleve_value'] = json_encode($upleve_value);
		return $data;
	}
	/*------------------------------------------------------ */
	//-- 添加后处理
	/*------------------------------------------------------ */
    public function afterAdd($data) {
        $where[] = ['level','>=',$data['level']];
        $where[] = ['role_id','<>',$data['role_id']];
        $this->Model->where($where)->setInc('level');
		$this->_Log($data['role_id'],'添加分销身份:'.$data['role_name']);
	}
	/*------------------------------------------------------ */
	//-- 修改前处理
	/*------------------------------------------------------ */
    public function beforeEdit($data){	
		$where[] = ['role_id','<>',$data['role_id']];
		$where[] = ['role_name','=',$data['role_name']];
		$count = $this->Model->where($where)->count('role_id');
		if ($count > 0) return $this->error('操作失败:已存在相同的身份名称，不允许重复添加！');

        $info = $this->Model->find($data['role_id']);
        $byLevel = input('byLevel',0,'intval');
        if ($info['level'] > $byLevel){
            $data['level'] = $byLevel + 1;
        }else{
            $data['level'] = $byLevel;
        }
        $this->eidtLevel = false;
        if ($info['level'] != $data['level']){
            $this->eidtLevel = true;
        }
		$data['update_time'] = time();
		$upleve_value = input('function_val');
		$buy_goods_id = input('buy_goods_id');
        $buy_goods_limit_num = input('buy_goods_limit_num');
        $buy_goods = [];
        foreach ($buy_goods_id as $key=>$bgid){
            $buy_goods[$bgid] = $buy_goods_limit_num[$key];
        }
        $upleve_value['buy_goods'] = $buy_goods;
		$data['upleve_value'] = json_encode($upleve_value);
		return $data;		
	}
	/*------------------------------------------------------ */
	//-- 修改后处理
	/*------------------------------------------------------ */
    public function afterEdit($data) {
        if ($this->eidtLevel == true){
            $byLevel = input('byLevel',0,'intval');
            $where[] = ['level','>=',$data['level']];
            $where[] = ['role_id','<>',$data['role_id']];
            if ($byLevel == $data['level']){
                $this->Model->where($where)->setDec('level');
            }else{
                $this->Model->where($where)->setInc('level');
            }
        }
		$this->_Log($data['role_id'],'修改分销身份:'.$data['role_name'].'，级别：'.$data['level']);
	}
	/*------------------------------------------------------ */
    //-- 删除
    /*------------------------------------------------------ */
    public function delete()
    {
        $role_id = input('role_id/d');
		if ($role_id < 1){
			return $this->error('传参错误.');	
		}
		$data = $this->Model->where('role_id',$role_id)->find();
		if (empty($data) == true){
			return $this->error('没有找到相关身份.');	
		}
		$count = (new UsersModel)->where('role_id',$role_id)->count();
		if ($count > 0){
			return $this->error('有会员是此分销身份，请修改相关会员身份后再操作.');
		}
        $res = $this->Model->where('role_id',$role_id)->delete();
        if ($res < 1) return $this->error('删除失败，请重试.');
        $this->Model->cleanMemcache();
		$this->_Log($data['role_id'],'删除分销身份:'.$data['role_name']);
        return $this->success('删除成功.');
    }
}
