<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 付款记录
/*------------------------------------------------------ */
class PayRecordModel extends BaseModel
{
	protected $table = 'users_pay_record';
	public  $pk = 'log_id';

}
