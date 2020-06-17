<?php

namespace app\unique\controller\api;

use app\ApiController;
use app\member\model\AccountLogModel;
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
    /*------------------------------------------------------ */
    //-- 获取会员帐户变动日志
    /*------------------------------------------------------ */
    public function getAccountLog()
    {
        $type = input('type', 'balance', 'trim');
        $date1 = input('time', date('Y-m-d',strtotime("-1 month")), 'trim');
        $date2 = input('time', date('Y-m-d'), 'trim');
        $flag = input('flag','all','trim');
        $date1 = strtotime($date1." 00:00:00");
        $date2 = strtotime($date2." 23:59:59");
        $return['date1'] = $date1;
        $return['date2'] = $date2;
        $return['code'] = 1;
        $AccountLogModel = new AccountLogModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        switch ($type) {
            //余额
            case 'balance':
                $field = 'balance_money';
                break;
            //默认查余额
            default:
                $field = 'balance_money';
                break;
        }

        //收入 支出 全部 筛选
        $arr = '';
        $arr = $flag == 'all' ? [$field, '<>', 0] : $arr;
        $arr = $flag == 'income' ? [$field, '>', 0] : $arr;
        $arr = $flag == 'expend' ? [$field, '<', 0] : $arr;
        $where[] = $arr;
        $where[] = ['change_time', 'between', array($date1, $date2)];
        $rows = $AccountLogModel->where($where)->order('change_time DESC')->select();
        $return['income'] = 0;
        $return['expend'] = 0;
        foreach ($rows as $key => $row) {
            if ($row[$field] > 0) {
                $return['income'] += $row[$field];
                $row['value'] = '+' . $row[$field];
            } else {
                $return['expend'] += $row[$field] * -1;
                $row['value'] = $row[$field];
            }
            $row['_time'] = timeTran($row['change_time']);
            $row['balance_remaining'] = $row['old_balance_money'] + $row['balance_money'];
            $return['list'][] = $row;
        }
        $return['balance_money'] = $this->userInfo['account']['balance_money'];
        $return['headimgurl'] = $this->userInfo['headimgurl'];
        return $this->ajaxReturn($return);
    }
}
