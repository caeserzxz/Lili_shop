<?php

namespace app\mainadmin\model;

use think\facade\Cache;
use app\BaseModel;

/**
 * 后台菜单模型
 * Class StoreUser
 * @package app\store\model
 */
class MenuListModel extends BaseModel
{
    protected $table = 'main_menu_list';
    public $pk = 'id';
    protected $mkey = 'main_menu_list';
    /*------------------------------------------------------ */
    //-- 清除缓存
    /*------------------------------------------------------ */
    public function cleanMemcache()
    {
        Cache::rm($this->mkey);
    }
    /*------------------------------------------------------ */
    //-- 获取菜单
    /*------------------------------------------------------ */
    public function getList()
    {
        $data = Cache::get($this->mkey);
        if (empty($data) == false) {
            return $data;
        }
        $data = [];
        $_data = [];
        $rows = self::where('status', 1)->order('level DESC, sort_order DESC')->select()->toArray();
        foreach ($rows as $row) {
            $key = $row['pid'] < 1 ? $row['group'] : $row['id'];
            if ($row['pid'] > 0) {
                if (empty($_data[$row['id']]) == false) {
                    $row['submenu'] = $_data[$row['id']];
                    unset($_data[$row['id']]);
                }

                $_data[$row['pid']][$key] = $row;
            } else {
                $row['list'] = $_data[$row['id']];
                $data[$key] = $row;
            }

        }
        Cache::set($this->mkey, $data, 60);
        return $data;
    }

}
