<?php
namespace app\publics\controller\sys_admin;
use app\AdminController;

use app\publics\model\LinksModel;
/**
 * 设置
 * Class Index
 * @package app\store\controller
 */
class Links extends AdminController
{
    /*------------------------------------------------------ */
    //-- link
    /*------------------------------------------------------ */
    public function index(){
        $this->assign("_menu_index", input('_menu_index','','trim'));
        $this->assign("searchType", input('searchType','','trim'));
        $this->assign("linksfun", input('linksfun','','trim'));
        $this->assign("linkstype", input('linkstype','all','trim'));


        $this->assign('links', (new LinksModel)->links());
        $CategoryModel = new \app\shop\model\CategoryModel();
        $this->assign('CategoryList', $CategoryModel->getRows());

        return $this->fetch();
    }

    /*------------------------------------------------------ */
    //-- 搜索
    /*------------------------------------------------------ */
    public function search()
    {
        $type = input('type','','trim');
        $kw = input('kw','','trim');
        if (in_array($type,['good','article','diypage']) == false){
            return $this->error('请求错误.');
        }
        if ($type == 'good'){
            $GoodsModel = new \app\shop\model\GoodsModel();
            $where[] = ['goods_name','like','%'.$kw.'%'];
            $ids = $GoodsModel->where($where)->limit(20)->column('goods_id');
            foreach ($ids as $id){
                $list[] = $GoodsModel->info($id);
            }
        }elseif($type == 'article'){
            $ArticleModel = new \app\mainadmin\model\ArticleModel();
            $where[] = ['title','like','%'.$kw.'%'];
            $list = $ArticleModel->where($where)->limit(20)->select()->toArray();
        }elseif($type == 'diypage'){
            $where[] = ['theme_type','=','index'];
            $where[] = ['theme_name','like','%'.$kw.'%'];
            $list = (new \app\shop\model\ShopPageTheme)->where($where)->limit(20)->select()->toArray();
        }
        $this->assign('list',$list);
        $this->assign('kw',$kw);
        return $this->fetch('sys_admin/links/search_'.$type);
    }
}
