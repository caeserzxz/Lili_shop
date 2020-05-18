<?php

namespace app\mainadmin\model;
use app\BaseModel;
//*------------------------------------------------------ */
//-- 异步执行错误日志
/*------------------------------------------------------ */
class AsynRunErrorLog extends BaseModel
{
	protected $table = 'asyn_run_error_log';
    public  $pk = 'log_id';

}
