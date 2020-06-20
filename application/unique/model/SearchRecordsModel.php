<?php
namespace app\unique\model;
use app\BaseModel;

use think\Db;
/*------------------------------------------------------ */
//-- 历史搜索记录
/*------------------------------------------------------ */
class SearchRecordsModel extends BaseModel
{
    protected $table = 'users_search_records';
    public  $pk = 'id';

}
