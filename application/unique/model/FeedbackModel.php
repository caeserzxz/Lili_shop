<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 反馈意见记录表
/*------------------------------------------------------ */
class FeedbackModel extends BaseModel
{
	protected $table = 'users_feedback';
	public  $pk = 'id';

}
