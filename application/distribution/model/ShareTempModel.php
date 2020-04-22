<?php
namespace app\distribution\model;
use app\BaseModel;
use think\facade\Cache;
//*------------------------------------------------------ */
//-- 分享模板表
/*------------------------------------------------------ */
class ShareTempModel extends BaseModel
{
    protected $table = 'distribution_share_temp';
    public  $pk = 'id';

}
