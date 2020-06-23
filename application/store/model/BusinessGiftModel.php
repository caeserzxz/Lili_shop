<?php
namespace app\store\model;
use app\BaseModel;
use think\facade\Cache;
use think\facade\Session;
//*------------------------------------------------------ */
//-- 商家分类
/*------------------------------------------------------ */
class BusinessGiftModel extends BaseModel
{
    protected $table = 'users_bussiness_gift';
    public  $pk = 'id';
    protected $mkey = 'users_bussiness_gift_id';
    /*------------------------------------------------------ */
    //-- 清除缓存
    /*------------------------------------------------------ */
    public function cleanMemcache(){
        Cache::rm($this->mkey);
    }

}
