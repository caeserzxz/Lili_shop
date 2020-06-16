<?php
namespace app\shop\model;
use app\BaseModel;
use think\facade\Cache;

//*------------------------------------------------------ */
//-- 商品足迹
/*------------------------------------------------------ */
class GoodsFootprintModel extends BaseModel
{
	protected $table = 'shop_goods_footprint';
	public  $pk = 'footprint_id';
	
}
