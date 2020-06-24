<?php

namespace app\unique\controller\api;

use app\ApiController;
use app\member\model\AccountLogModel;
use app\member\model\UsersBindSuperiorModel;
use app\member\model\UsersModel;
use app\store\model\UserBusinessModel;
use app\unique\model\PayRecordModel;
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
        $type = input('type', '0', 'trim');
        if($type < 11 || $type > 15)$type = 0;
        $date1 = input('date1', date('Y-m-d',strtotime("-1 month")), 'trim');
        $date2 = input('date2', date('Y-m-d'), 'trim');
        $flag = input('flag','all','trim');
        $date1 = strtotime($date1." 00:00:00");
        $date2 = strtotime($date2." 23:59:59");
        $return['date1'] = $date1;
        $return['date2'] = $date2;
        $return['code'] = 1;
        $AccountLogModel = new AccountLogModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];

        if($type){
            $where[] = ['change_type' ,'=' ,$type];
        }
        $field = 'balance_money';
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
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取会员帐户总额
    /*------------------------------------------------------ */
    public function getAccountLogNum()
    {
        $AccountLogModel = new AccountLogModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['balance_money' ,'>' ,0];
        $return['num_all'] = $AccountLogModel->where($where)->sum('balance_money');
        $return['num_11']  = $AccountLogModel->where($where)->where([['change_type' ,'=' ,11]])->sum('balance_money');
        $return['num_15']  = $AccountLogModel->where($where)->where([['change_type' ,'=' ,15]])->sum('balance_money');
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取我的会员总数
    /*------------------------------------------------------ */
    public function getteamnum()
    {
        $UsersBindSuperiorModel = new UsersBindSuperiorModel();
        $where1[] = ['superior',['like',"%,".$this->userInfo['user_id']]];
        $where2[] = ['superior',['like',"%,".$this->userInfo['user_id'].",%"]];
        $allnum = $UsersBindSuperiorModel->Field('user_id')->where($where1)->whereOr($where2)->order('user_id DESC')->count();
        $return['allnum'] = $allnum;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    //*------------------------------------------------------ */
    //-- 获取我的会员列表
    /*------------------------------------------------------ */
    public function getmyteam()
    {
        $limit = 20;
        $limit_start = input('limit_start', 0, 'trim');
        $UsersBindSuperiorModel = new UsersBindSuperiorModel();
        $user_id = $this->userInfo['user_id'];
        $where1[] = ['superior',['like',"%,".$user_id]];
        $where2[] = ['superior',['like',"%,".$user_id.",%"]];
        $rows = $UsersBindSuperiorModel->Field('user_id')->where($where1)->whereOr($where2)->order('user_id DESC')->limit($limit_start,$limit)->select()->toArray();
        if(count($rows) < $limit){
            $return['is_over'] = 1;
        }
        foreach ($rows as $key => $row) {
            $user_info = $this->Model->where($this->Model->pk,$row['user_id'])->find()->toarray();
            $row['nick_name'] = $user_info['nick_name'];
            $row['headimgurl'] = $user_info['headimgurl'];
            $row['_time'] = date('Y-m-d H:i',$user_info['reg_time']);
            $return['list'][] = $row;
        }
        $return['limit_start'] = $limit_start + $limit;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取会员消费记录
    /*------------------------------------------------------ */
    public function getPayRecord()
    {
        $date1 = input('date1', date('Y-m-d',strtotime("-1 month")), 'trim');
        $date2 = input('date2', date('Y-m-d'), 'trim');
        $flag = input('flag','all','trim');
        $date1 = strtotime($date1." 00:00:00");
        $date2 = strtotime($date2." 23:59:59");
        $return['date1'] = $date1;
        $return['date2'] = $date2;
        $return['code'] = 1;
        $UserBusinessModel = new UserBusinessModel();
        $PayRecordModel = new PayRecordModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['add_time', 'between', array($date1, $date2)];
        $rows = $PayRecordModel->where($where)->order('add_time DESC,log_id DESC')->select()->toarray();
        $return['expend'] = 0;
        foreach ($rows as $key => $row) {
            $return['expend'] += $row['amount'];
            $row['business_name'] = $UserBusinessModel->getInfo($row['business_id'],'business_id','business_name');
            $row['_time'] = timeTran($row['add_time']);
            $return['list'][] = $row;
        }
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取账单详情
    /*------------------------------------------------------ */
    public function getPayRecordInfo()
    {
        $id = input('id','0','trim');
        if(empty($id)){
            $return['code'] = 0;
            $return['msg'] = '参数错误';
            return $this->ajaxReturn($return);
        }
        $return['code'] = 1;
        $UserBusinessModel = new UserBusinessModel();
        $PayRecordModel = new PayRecordModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['log_id', '=', $id];
        $info = $PayRecordModel->where($where)->find();
        if(empty($info)){
            $return['code'] = 0;
            $return['msg'] = '找不到记录';
            return $this->ajaxReturn($return);
        }
        $info['business_name'] = $UserBusinessModel->getInfo($info['business_id'],'business_id','business_name');
        $info['pay_type_str'] = PayRecordModel::$type_str[$info['pay_type']];
        $info['addtime'] = date('Y-m-d H:i',$info['add_time']);
        $return['info'] = $info;
        return $this->ajaxReturn($return);
    }
}
