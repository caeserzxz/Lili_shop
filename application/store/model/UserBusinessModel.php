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


    function getInfo($id = 0,$id_type = 'user_id',$field = '*'){
        if(empty($id))return false;
        $res = $this->where($id_type,$id)->find();
        if($field == '*'){
            return $res;
        }else{
            return $res[$field];
        }
    }


}
