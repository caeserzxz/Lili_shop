<?php

namespace app\shop\controller\api;

use app\ApiController;
use app\shop\model\BonusModel;
use app\shop\model\GoodsModel;
use app\shop\model\CategoryModel;

class Category extends ApiController
{

    /*------------------------------------------------------ */
    //-- 获取商品分类信息
    /*------------------------------------------------------ */
    //http://www.moduleshop.top/shop/api.category/getlist
    public function getlist()
    {
        $GoodsModel = new GoodsModel();
        $return['allSort'] = $GoodsModel->getClassToAllSort();//获取分层分类
        $classList = $GoodsModel->getClassList();
        foreach ($classList as $key => $val) {
            $_t = $val;
            $_t['pic'] = 'http://'.$_SERVER['HTTP_HOST'] . $val['pic'];
            $classList[$key] = $_t;
        }
        $return['classList'] = $classList;//获取分类
        return $this->ajaxReturn($return);
    }
    
    /*------------------------------------------------------ */
    //-- 根据父分类id获取商品子分类信息
    /*------------------------------------------------------ */
    //wemk  20190401 17:14
    public function pid_get_category()
    {
        $pid = input('pid',0);
        $camod = new CategoryModel();
        $return['list'] = $camod->getRows($pid);
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

}
