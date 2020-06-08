<?php
namespace app\store\model;
use app\BaseModel;
use think\facade\Cache;
use think\facade\Session;
use app\shop\model\GoodsImgsModel;
//*------------------------------------------------------ */
//-- 商家分类
/*------------------------------------------------------ */
class CategoryModel extends BaseModel
{
	protected $table = 'store_category';
	public  $pk = 'id';
	protected $mkey = 'store_category_mkey';
     /*------------------------------------------------------ */
	//-- 清除缓存
	/*------------------------------------------------------ */
	public function cleanMemcache(){
		Cache::rm($this->mkey);
	}
	/*------------------------------------------------------ */
	//-- 获取列表
	/*------------------------------------------------------ */
    public function getRows(){  
        $rows = Cache::get($this->mkey);    
        if (empty($rows) == false) return $rows;
        $rows = $this->order('sort_order,pid ASC')->select()->toArray();
        $rows = returnRows($rows);
        Cache::set($this->mkey,$rows,3600);
        return $rows;
    }
    //*------------------------------------------------------ */
    //-- 更新数据
    /*------------------------------------------------------ */
    public function saveDate($data,$type = ''){
        if ($type != 'login'){
            $data['update_time'] = time();
        }
        $res = $this->save($data,$data['supplyer_id']);
        if ($res == true){
            if ($type == 'login'){//登陆处理
                (new LogLoginModel)->save(['log_ip'=>$data['login_ip'],'log_time'=>$data['login_time'],'supplyer_id'=>$data['supplyer_id']]);
            }
        }
        return $res;
    }


    /**
     * 获取首页展示的3个分类
     */
    public function getIndexCate(){
        $where = array(
            'status'=> 1,
            'pid'   => 0,
            'status'=>1
        );
//        $list = $this->where($where)->order('sort_order asc')->limit(0,3)->select();
        $list = $this->where($where)->order('sort_order asc')->select();
        return $list;
    }

    /**
     * 获取商家列表的二级行业
     */
    public function getStoreCate($pid = 0){
        $where = array(
            'status' => 1,
            'pid'   =>$pid
        );
        $list = $this->where($where)->order('sort_order asc')->limit(0,4)->select();
        return $list;
    }

}
