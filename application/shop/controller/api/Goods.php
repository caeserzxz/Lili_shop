<?php
namespace app\shop\controller\api;
use app\ApiController;
use app\shop\model\CartModel;
use app\shop\model\GoodsModel;
use app\weixin\model\MiniModel;
use app\shop\model\BonusModel;

/*------------------------------------------------------ */
//-- 商品相关API
/*------------------------------------------------------ */
class Goods extends ApiController
{
	/*------------------------------------------------------ */
	//-- 优先执行
	/*------------------------------------------------------ */
	public function initialize(){
        parent::initialize();
        $this->Model = new GoodsModel();
    }
	/*------------------------------------------------------ */
    //-- 获取商品列表
    /*------------------------------------------------------ */
    public function getList(){
        $where[] = ['is_delete','=',0];
        $where[] = ['is_alone_sale','=',1];
        $where[] = ['isputaway','=',1];
        $where[] = ['is_promote','=',0];

        $search['keyword'] =  input('keyword','','trim');
        if (empty($search['keyword']) == false){
            $where['and'][] = "( goods_name like '%".$search['keyword']."%')  OR ( keywords like '%".$search['keyword']."%')";
        }
        
        $search['cid'] = input('cid',0,'intval');
        if ($search['cid'] > 0){
            $classList = $this->Model->getClassList();
            $where[] = ['cid','in',$classList[$search['cid']]['children']];
        }
        $search['brand_id'] = input('brand_id',0,'intval');
        if ($search['brand_id'] > 0 ){
            $where[] = ['brand_id','=',$search['brand_id']];
        }
        
        $search['min_price'] = input('min_price') * 1;
        $search['max_price'] = input('max_price') * 1;
        if ($search['min_price'] > 0){
            $where[] = ['shop_price','>',$search['min_price']];
        }
        if ($search['max_price'] > 0){
            $where[] = ['shop_price','<',$search['max_price']];
        }
        
        $sqlOrder = input('order','','trim');
         if (empty($sqlOrder)){
            $search['is_best'] = input('is_best',0,'intval');
            if ($search['is_best'] > 0){
                $this->sqlOrder = "is_best DESC, virtual_sale $sort_by";
            }
            $search['is_hot'] = input('is_hot',0,'intval');
            if ($search['is_hot'] > 0){
                $this->sqlOrder = "is_hot DESC, virtual_sale $sort_by";
            }   
            $search['is_new'] = input('is_new',0,'intval');
            if ($search['is_new'] > 0){
                $this->sqlOrder = "is_new DESC, virtual_sale $sort_by";
            }
        }
       
        if (empty($this->sqlOrder)) {               
            $sort_by = strtoupper(input('sort','DESC','trim'));
            if (in_array(strtoupper($sort_by), array('DESC', 'ASC')) == false) {
                $sort_by = 'DESC';
            }
            switch ($sqlOrder){
                case 'sales':
                    $this->sqlOrder = "virtual_sale $sort_by";
                    break;
                case 'price':
                    $this->sqlOrder = "shop_price $sort_by";
                    break;
                default:
                    $this->sqlOrder = "sort_order $sort_by,virtual_sale $sort_by,virtual_collect $sort_by,is_best $sort_by";
                    break;
            }           
        }
        
        $data = $this->getPageList($this->Model, $where,'goods_id',10);
        foreach ($data['list'] as $key=>$goods){
            $goods = $this->Model->info($goods['goods_id']);
            $_goods['goods_id'] = $goods['goods_id'];
            $_goods['goods_name'] = $goods['goods_name'];
            $_goods['short_name'] = $goods['short_name'];
            $_goods['is_spec'] = $goods['is_spec'];
            $_goods['exp_price'] = $goods['exp_price'];
            $_goods['now_price'] = $goods['sale_price'];
            $_goods['market_price'] = $goods['market_price'];
            $_goods['sale_count'] = $goods['sale_count'];
            $_goods['collect_count'] = $goods['collect_count'];
            $_goods['goods_thumb'] = $goods['goods_thumb'];
            $_goods['is_promote'] = $goods['is_promote'];
            $return['list'][] = $_goods;
        }
        $return['page_count'] = $data['page_count'];

        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取促销商品列表
    /*------------------------------------------------------ */
    public function promoteList(){
        $where[] = ['is_delete','=',0];
        $where[] = ['is_alone_sale','=',1];
        $where[] = ['isputaway','>',0];
        $where[] = ['is_promote','=',1];
        $where[] = ['promote_start_date','<=',time()];
        $where[] = ['promote_end_date','>=',time()];
        
        $search['keyword'] =  input('keyword','','trim');
        if (empty($search['keyword']) == false){
            $where['and'][] = "( goods_name like '%".$search['keyword']."%')  OR ( keywords like '%".$search['keyword']."%')";
        }
        
        $search['cid'] = input('cid',0,'intval');
        if ($search['cid'] > 0){
            $classList = $this->Model->getClassList();
            $where[] = ['cid','in',$classList[$search['cid']]['children']];
        }
        $search['brand_id'] = input('brand_id',0,'intval');
        if ($search['brand_id'] > 0 ){
            $where[] = ['brand_id','=',$search['brand_id']];
        }
        
        $search['min_price'] = input('min_price') * 1;
        $search['max_price'] = input('max_price') * 1;
        if ($search['min_price'] > 0){
            $where[] = ['shop_price','>',$search['min_price']];
        }
        if ($search['max_price'] > 0){
            $where[] = ['shop_price','<',$search['max_price']];
        }
        
        $sqlOrder = input('order','','trim');
         if (empty($sqlOrder)){
            $search['is_best'] = input('is_best',0,'intval');
            if ($search['is_best'] > 0){
                $this->sqlOrder = "is_best DESC, virtual_sale $sort_by";
            }
            $search['is_hot'] = input('is_hot',0,'intval');
            if ($search['is_hot'] > 0){
                $this->sqlOrder = "is_hot DESC, virtual_sale $sort_by";
            }   
            $search['is_new'] = input('is_new',0,'intval');
            if ($search['is_new'] > 0){
                $this->sqlOrder = "is_new DESC, virtual_sale $sort_by";
            }
        }
       
        if (empty($this->sqlOrder)) {               
            $sort_by = strtoupper(input('sort','DESC','trim'));
            if (in_array(strtoupper($sort_by), array('DESC', 'ASC')) == false) {
                $sort_by = 'DESC';
            }
            switch ($sqlOrder){
                case 'sales':
                    $this->sqlOrder = "virtual_sale $sort_by";
                    break;
                case 'price':
                    $this->sqlOrder = "shop_price $sort_by";
                    break;
                default:
                    $this->sqlOrder = "virtual_sale $sort_by,virtual_collect $sort_by,is_best $sort_by";
                    break;
            }           
        }
        
        
        
        $data = $this->getPageList($this->Model, $where,'goods_id',10);
        foreach ($data['list'] as $key=>$goods){
            $goods = $this->Model->info($goods['goods_id']);
            $_goods['goods_id'] = $goods['goods_id'];
            $_goods['goods_name'] = $goods['goods_name'];
            $_goods['short_name'] = $goods['short_name'];
            $_goods['is_spec'] = $goods['is_spec'];
            $_goods['exp_price'] = $goods['exp_price'];
            $_goods['now_price'] = $goods['sale_price'];
            $_goods['market_price'] = $goods['market_price'];
            $_goods['sale_count'] = $goods['sale_count'];
            $_goods['collect_count'] = $goods['collect_count'];
            $_goods['goods_thumb'] = $goods['goods_thumb'];
            $_goods['is_promote'] = $goods['is_promote'];
            $return['list'][] = $_goods;
        }
        $return['page_count'] = $data['page_count'];

        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取商品品牌列表
    /*------------------------------------------------------ */
    public function getBrandList()
    {
        $cid = input('cid',0,'intval');
        $return['list'] = $this->Model->getBrandList($cid);
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 添加/取消收藏商品
    /*------------------------------------------------------ */
    public function collect()
    {
        $this->checkLogin();//验证登陆
        $goods_id = input('goods_id',0,'intval');
        if ($goods_id < 1) return $this->error('传参失败.');
        $GoodsCollectModel = new \app\shop\model\GoodsCollectModel();
        $where['goods_id'] = $goods_id;
        $where['user_id'] = $this->userInfo['user_id'];
        $collect = $GoodsCollectModel->where($where)->find();
        if (empty($collect) == false){//存在,更新状态
            $upData['status'] = $collect['status'] == 1 ? 0 : 1;
			$upData['update_time'] = time();
            $res = $GoodsCollectModel->where($where)->update($upData);
        }else{
            $inData['status'] = 1;
            $inData['goods_id'] = $goods_id;
            $inData['user_id'] = $this->userInfo['user_id'];
            $inData['add_time'] = time();
			$inData['update_time'] = time();
            $res = $GoodsCollectModel->save($inData);
        }
        if ($res < 1) return $this->error('收藏商品失败.');
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
	/*------------------------------------------------------ */
    //-- 取消收藏商品
    /*------------------------------------------------------ */
    public function cancelCollect()
    {
        $this->checkLogin();//验证登陆
        $gids = input('gids','','trim');
        if (empty($gids)) return $this->error('传参失败.');
        $GoodsCollectModel = new \app\shop\model\GoodsCollectModel();
		$where[] = ['user_id','=',$this->userInfo['user_id']];
        $where[] = ['goods_id','in',explode(',',$gids)];        
        $res = $GoodsCollectModel->where($where)->update(['status'=>0,'update_time'=>time()]);
        if ($res < 1) return $this->error('操作失败.');
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
	/*------------------------------------------------------ */
    //-- 获取商品收藏列表
    /*------------------------------------------------------ */
    public function getCollectlist()
    {
		$this->checkLogin();//验证登陆
		$GoodsCollectModel = new \app\shop\model\GoodsCollectModel();
        $where['user_id'] = $this->userInfo['user_id'];
        $where['status'] = 1;
		$rows = $GoodsCollectModel->where($where)->order('update_time DESC')->select();
		foreach ($rows as $row){
			$goods = $this->Model->info($row['goods_id']);
			if ($goods['is_delete'] == 1){
				continue;
			}
            $_goods['goods_id'] = $goods['goods_id'];
            $_goods['goods_name'] = $goods['goods_name'];
            $_goods['short_name'] = $goods['short_name'];
            $_goods['is_spec'] = $goods['is_spec'];
            $_goods['exp_price'] = $goods['exp_price'];
            $_goods['now_price'] = $goods['_price'];
            $_goods['market_price'] = $goods['market_price'];
            $_goods['sale_count'] = $goods['sale_count'];
            $_goods['collect_count'] = $goods['collect_count'];
            $_goods['goods_thumb'] = $goods['goods_thumb'];
            $_goods['is_promote'] = $goods['is_promote'];
            $return['list'][] = $_goods;
			$return['count'] += 1;
		}
		$return['code'] = 1;
        return $this->ajaxReturn($return);
	}
    /*------------------------------------------------------ */
    //-- 获取商品详情
    /*------------------------------------------------------ */
    public function info(){
        $goods_id = input('id',0,'intval');
        if ($goods_id < 1) return $this->error('传参错误.');
        $goods = $this->Model->info($goods_id);
        $list['title'] = $goods['goods_name'];
          
        $web_path = config('config.host_path');
        $goods['m_goods_desc'] = preg_replace('/<img src=\"/', '<img style="width:100%;height:auto;" src="' .$web_path.$goods['m_goods_desc']);
        $list['goods'] = $goods;
        $list['imgsList'] = $this->Model->getImgsList($goods_id);
        $list['skuImgs'] = $this->Model->getImgsList($goods_id,true,true);
        $list['isCollect'] = $this->Model->isCollect($goods_id,$this->userInfo['user_id']);     
        //获取sku图片
        
        //获取购物车信息
        $CartModel = new CartModel();
        $list['cartInfo'] = $CartModel->getCartInfo();
        
        $return['code'] = 1;
        $return['list'] = $list;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取搜索词，热搜之类的
    /*------------------------------------------------------ */
    public function get_keyword(){
        $return['default_keyword'] = settings('shop_index_search_text');
        $return['searchKeys'] = $this->Model->searchKeys();
        $return['hot_search'] = explode(' ',settings('hot_search'));
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    //获取商品小程序二维码
    public function get_goods_mini_qrcode(){

        $goods_id = input('goods_id',0,'intval');
        if(!$goods_id){
            $this->error('参数错误！');
        }

        $user = $this->userInfo;
        $token = $user['token'];

        //$page = 'pages/productDetails/productDetails';
        $page = '';
        $scene = $goods_id.'$'.$token;
        $mini = new MiniModel();
        $qrcode = $mini->get_qrcode($page,$scene);
        $return['qrcode'] = $qrcode;
        //$return['list'] = $list;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
}
