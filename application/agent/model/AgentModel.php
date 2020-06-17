<?php
namespace app\agent\model;
use app\BaseModel;
use think\facade\Cache;
use think\facade\Session;

//*------------------------------------------------------ */
//-- 商家分类
/*------------------------------------------------------ */
class AgentModel extends BaseModel
{
    protected $table = 'users_agent';
    public  $pk = 'agent_id';
    protected $mkey = 'users_agent_mkey';
    /*------------------------------------------------------ */
    //-- 清除缓存
    /*------------------------------------------------------ */
    public function cleanMemcache(){
        Cache::rm($this->mkey);
    }




}
