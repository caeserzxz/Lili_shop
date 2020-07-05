<?php
namespace app\store\model;
use app\BaseModel;
use think\facade\Cache;
//*------------------------------------------------------ */
//-- 商家收款码
/*------------------------------------------------------ */
class BusinessQrcodeModel extends BaseModel
{
    protected $table = 'users_bussiness_qrcode';
    public  $pk = 'id';
    protected $mkey = 'users_bussiness_qrcode';
    /*------------------------------------------------------ */
    //-- 清除缓存
    /*------------------------------------------------------ */
    public function cleanMemcache(){
        Cache::rm($this->mkey);
    }
}
