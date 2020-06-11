<?php
namespace app\unique\controller\api;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\CategoryModel;
/*------------------------------------------------------ */
//-- 首页相关API
/*------------------------------------------------------ */

class Index extends ApiController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();

    }

    /*------------------------------------------------------ */
    //-- 获取推荐分类
    /*------------------------------------------------------ */
    public function get_recommend_class(){
        $model = new CategoryModel();
        $where[] = ['status','=',1];
        $where[] = ['pid','>',1];
        $list['list'] = $model->where($where)->order('sort_order asc')->limit(0,4)->select();
        return $this->ajaxReturn($list);
    }

    /*------------------------------------------------------ */
    //-- 获取所有分类
    /*------------------------------------------------------ */
    public function get_class(){
        $model = new CategoryModel();
        $where[] = ['status','=',1];
        $where[] = ['pid','=',0];
        $list['list'] = $model->where($where)->select();

        foreach ($list['list'] as $k=>$v){
            $where1 = [];
            $where1[] = ['status','=',1];
            $where1[] = ['pid','=',$v['id']];
            $list['list'][$k]['list'] = $model->where($where1)->select();
        }

        return $this->ajaxReturn($list);
    }

}
