<?php
namespace app\member\model;
use app\BaseModel;
use think\facade\Cache;
//*------------------------------------------------------ */
//-- 虚拟会员
/*------------------------------------------------------ */
class AvatarUserModel extends BaseModel
{
	protected $table = 'users_avatar_user';
	public $pk = 'id';
    protected static $mkey = 'users_avatar_user_mkey';
	 /*------------------------------------------------------ */
	//-- 清除缓存
	/*------------------------------------------------------ */ 
	public function cleanMemcache(){
		Cache::rm(self::$mkey);
	}
	/*------------------------------------------------------ */
	//-- 获取列表
	/*------------------------------------------------------ */
    public static function getRows(){	
		$list = Cache::get(self::$mkey);	
		if (empty($list) == false) return $list;
		$rows = self::select()->toArray();
		foreach ($rows as $key=>$row){					
			$list[$row['id']] = $row;
		}
		Cache::set(self::$mkey,$list,3600);
		return $list;
	}
	/*------------------------------------------------------ */
	//-- 获取会员
	/*------------------------------------------------------ */
    public static function info($id){
		$rows = self::getRows();
		return $rows[$id];
	}
}
