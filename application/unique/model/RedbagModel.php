<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 商家红包
/*------------------------------------------------------ */
class RedbagModel extends BaseModel
{
	protected $table = 'users_redbag';
	public  $pk = 'redbag_id';

}
