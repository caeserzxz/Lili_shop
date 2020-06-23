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


    function getInfo($business_id = 0,$field = '*'){
        if(empty($business_id))return false;
        $res = $this->get($business_id);
        if($field == '*'){
            return $res;
        }else{
            return $res[$field];
        }
    }


}
