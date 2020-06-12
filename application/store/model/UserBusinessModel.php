<?php
namespace app\store\model;
use app\BaseModel;
use think\facade\Cache;
use think\facade\Session;
use app\shop\model\GoodsImgsModel;
//*------------------------------------------------------ */
//-- 商家分类
/*------------------------------------------------------ */
class UserBusinessModel extends BaseModel
{
    protected $table = 'users_business';
    public  $pk = 'business_id';
    protected $mkey = 'users_business_mkey';
    /*------------------------------------------------------ */
    //-- 清除缓存
    /*------------------------------------------------------ */
    public function cleanMemcache(){
        Cache::rm($this->mkey);
    }




}
