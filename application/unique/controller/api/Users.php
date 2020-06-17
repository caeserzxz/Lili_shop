<?php

namespace app\unique\controller\api;

use app\ApiController;
use app\member\model\UsersModel;
use app\store\model\UserBusinessModel;
use app\unique\model\RedbagModel;
use think\facade\Cache;

/*------------------------------------------------------ */
//-- 会员相关API
/*------------------------------------------------------ */

class Users extends ApiController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->checkLogin();//验证登陆
        $this->Model = new UsersModel();
    }
    //*------------------------------------------------------ */
    //-- 获取红包
    /*------------------------------------------------------ */
    public function getredbag()
    {
        $type = input('type', 'all', 'trim');
        $limit = 100;
        $RedbagModel = new RedbagModel();
        $UserBusinessModel = new UserBusinessModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        if(in_array($type,['unused','all'])){
            $where_unused[] = ['status', '=', '0'];
            $where_unused[] = ['expire_time', '>', time()];
            $rows_unused = $RedbagModel->where($where)->where($where_unused)->order('redbag_id DESC')->limit($limit)->select()->toArray();
            $return['unused_num'] = $RedbagModel->where($where)->where($where_unused)->count();
            foreach ($rows_unused as $key => $row) {
                $Business = $UserBusinessModel->where($UserBusinessModel->pk,$row['business_id'])->find()->toarray();
                $row['business_name'] = $Business['business_name'];
                $row['_time'] = date('Y-m-d',$row['expire_time']);
                $return['list_unused'][] = $row;
            }
        }
        if(in_array($type,['used','all'])){
            $where_used[] = ['status', '=', '1'];
            $rows_used = $RedbagModel->where($where)->where($where_used)->order('redbag_id DESC')->limit($limit)->select()->toArray();
            $return['used_num'] = $RedbagModel->where($where)->where($where_used)->count();
            foreach ($rows_used as $key => $row) {
                $Business = $UserBusinessModel->where($UserBusinessModel->pk,$row['business_id'])->find()->toarray();
                $row['business_name'] = $Business['business_name'];
                $row['_time'] = date('Y-m-d',$row['expire_time']);
                $return['list_used'][] = $row;
            }
        }
        if(in_array($type,['expired','all'])){
            $where_expired[] = ['status', '=', '0'];
            $where_expired[] = ['expire_time', '<=', time()];
            $rows_expired = $RedbagModel->where($where)->where($where_expired)->order('redbag_id DESC')->limit($limit)->select()->toArray();
            $return['expired_num'] = $RedbagModel->where($where)->where($where_expired)->count();
            foreach ($rows_expired as $key => $row) {
                $Business = $UserBusinessModel->where($UserBusinessModel->pk,$row['business_id'])->find()->toarray();
                $row['business_name'] = $Business['business_name'];
                $row['_time'] = date('Y-m-d',$row['expire_time']);
                $return['list_expired'][] = $row;
            }
        }
        return $this->ajaxReturn($return);
    }
}
