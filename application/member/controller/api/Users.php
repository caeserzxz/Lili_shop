<?php

namespace app\member\controller\api;

use app\ApiController;
use think\facade\Lang;
use think\facade\Env;
use app\member\model\UsersModel;
use app\member\model\WithdrawModel;
use app\member\model\AccountLogModel;
use app\distribution\model\DividendModel;
use app\shop\model\OrderModel;
use app\shop\model\BonusModel;


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
    /*------------------------------------------------------ */
    //-- 获取登陆会员信息
    /*------------------------------------------------------ */
    public function getInfo()
    {
        $return['info'] = $this->userInfo;
        $return['code'] = 1;
        $return['sign_in'] = settings('sign_in');
        $superior = $this->Model->getSuperior($this->userInfo['pid']);
        if ($superior) $superior['reg_time'] = date('Y-m-d', $superior['reg_time']);
        $return['superior'] = $superior;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 修改用户密码
    /*------------------------------------------------------ */
    public function editPwd()
    {
        $res = $this->Model->editPwd(input(), $this);
        if ($res !== true) return $this->error($res);
        return $this->success('密码已重置，请用新密码登陆.');
    }
    /*------------------------------------------------------ */
    //-- 修改用户密码
    /*------------------------------------------------------ */
    public function editPayPwd()
    {
        $pay_password = input('password','','trim');
        if (empty($pay_password))  return $this->error('请输入新的支付密码.');
        $this->checkCode('edit_pay_pwd',$this->userInfo['mobile'],input('code'));//验证短信验证
        $data['pay_password'] = f_hash($pay_password.$this->userInfo['user_id']);
        if ($data['pay_password'] == $this->userInfo['pay_password']){
            return $this->error('新密码与旧密码一致，无需修改.');
        }
        $res = $this->Model->where('user_id', $this->userInfo['user_id'])->update($data);
        if ($res < 1) {
            return $this->error('未知错误，处理失败.');
        }
        $this->_log($this->userInfo['user_id'], '用户修改支付密码.', 'member');
        return $this->success('支付密码修改成功.');
    }
    //*------------------------------------------------------ */
    //-- 绑定会员手机
    /*------------------------------------------------------ */
    public function bindMobile()
    {
        $this->checkCode('login', input('mobile'), input('code'));//验证短信验证
        $res = $this->Model->bindMobile(input(), $this);
        if ($res !== true) return $this->error($res);
        return $this->success('绑定手机成功.');
    }
    /*------------------------------------------------------ */
    //-- 获取会员中心首页所需数据
    /*------------------------------------------------------ */
    public function getCenterInfo()
    {
        $OrderModel = new OrderModel();
        $return['orderStats'] = $OrderModel->userOrderStats($this->userInfo['user_id']);
        $return['userInfo'] = $this->userInfo;
        $BonusModel = new BonusModel();
        $bonus = $BonusModel->getListByUser();
        $return['unusedNum'] = $bonus['unusedNum'];
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取分享二维码
    /*------------------------------------------------------ */
    public function myCode()
    {
        $file_path = config('config._upload_') . 'qrcode/' . substr($this->userInfo['user_id'], -1) . '/';
        $file = $file_path . $this->userInfo['token'] . '.png';
        if (file_exists($file) == false) {
            include EXTEND_PATH . 'phpqrcode/phpqrcode.php';//引入PHP QR库文件
            $QRcode = new \phpqrcode\QRcode();
            $value = config('config.host_path') . '/?share_token=' . $this->userInfo['token'];
            makeDir($file_path);
            $png = $QRcode::png($value, $file, "L", 10, 1, 2, true);
        }
        $return['file'] = config('config.host_path') . '/' . trim($file, '.');
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取分享商品二维码
    /*------------------------------------------------------ */
    public function goodsCode()
    {
        $goods_id = input('goods_id', 0, 'intval');
        $file_path = config('config._upload_') . 'goods_qrcode/' . $goods_id . '/';
        $file = $file_path . $this->userInfo['token'] . '.png';
        if (file_exists($file) == false) {
            include EXTEND_PATH . 'phpqrcode/phpqrcode.php';//引入PHP QR库文件
            $QRcode = new \phpqrcode\QRcode();
            $value = config('config.host_path') . url('shop/goods/info', ['id' => $goods_id, 'share_token' => $this->userInfo['token']]);
            makeDir($file_path);
            $png = $QRcode::png($value, $file, "L", 10, 1, 2, true);
        }
        $return['file'] = config('config.host_path') . '/' . trim($file, '.');
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取会员帐号数据
    /*------------------------------------------------------ */
    public function getAccount()
    {
        $return['account'] = $this->userInfo['account'];
        //计算提现中金额，即为冻结金额
        $WithdrawModel = new WithdrawModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['status', '=', 0];
        $return['frozen_amount'] = $WithdrawModel->where($where)->sum('amount');
        //end
        $DividendModel = new DividendModel();
        //今天收益
        unset($where);
        $where[] = ['dividend_uid', '=', $this->userInfo['user_id']];
        $where[] = ['status', 'in', [1, 2, 3, 9]];
        $where[] = ['add_time', '>=', strtotime("today")];
        $return['today_income'] = $DividendModel->where($where)->sum('dividend_amount');
        $return['today_income'] += $DividendModel->where($where)->sum('dividend_bean');
        //end
        //本月收益
        unset($where);
        $where[] = ['dividend_uid', '=', $this->userInfo['user_id']];
        $where[] = ['status', 'in', [1, 2, 3, 9]];
        $where[] = ['add_time', '>', strtotime(date('Y-m-01', strtotime('-1 month')))];
        $return['month_income'] = $DividendModel->where($where)->sum('dividend_amount');
        $return['month_income'] += $DividendModel->where($where)->sum('dividend_bean');
        //累计收益
        unset($where);
        $where[] = ['dividend_uid','=',$this->userInfo['user_id']];
        $where[] = ['status','in',[1,2,3,9]];
        $return['end_income'] = $DividendModel->where($where)->sum('dividend_amount');
        $return['end_income'] += $DividendModel->where($where)->sum('dividend_bean');
        $return['end_income'] = number_format($return['end_income'],2);
        //end
        $return['withdraw_status'] = settings('withdraw_status');//获取是否开启提现
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取会员帐户变动日志
    /*------------------------------------------------------ */
    public function getAccountLog()
    {
        $type = input('type', 'order', 'trim');
        $time = input('time', '', 'trim');
        if (empty($time)) {
            $time = date('Y年m月');
        }
        $return['time'] = $time;
        $_time = strtotime(str_replace(array('年', '月'), array('-', ''), $time));
        $return['code'] = 1;
        $AccountLogModel = new AccountLogModel();
        $where[] = ['user_id', '=', $this->userInfo['user_id']];
        $where[] = ['change_time', 'between', array($_time, strtotime(date('Y-m-t', $_time)) + 86399)];


        $rows = $AccountLogModel->where($where)->order('change_time DESC')->select();
        foreach ($rows as $key => $row) {

            if ($row['balance_money'] > 0) {
                $return['b_income'] += $row['balance_money'];
            } else {
                $return['b_expend'] += $row['balance_money'] * -1;
            }

            if ($row['use_integral'] > 0) {
                $return['i_income'] += $row['use_integral'];
            } else {
                $return['i_expend'] += $row['use_integral'] * -1;
            }

            if ($type == 'order'){
                if ($row['change_type'] != 3){
                    continue;
                }
            }elseif($type == 'brokerage'){
                if ($row['change_type'] != 4){
                    continue;
                }
            }elseif($type == 'withdraw'){
                if ($row['change_type'] != 5){
                    continue;
                }
            }elseif($type == 'use_integral'){
                if ($row['use_integral'] == 0){
                    continue;
                }
            }

            if ($row['balance_money'] > 0) {
                $row['value'] = '+' . $row['balance_money'];
            } elseif ($row['balance_money'] < 0) {
                $row['value'] = $row['balance_money'];
            }

            if ($row['use_integral'] > 0) {
                $row['value'] = '+' . $row['use_integral'];
            } elseif ($row['use_integral'] < 0){
                $row['value'] = $row['use_integral'];
            }

            $row['_time'] = timeTran($row['change_time']);
            $return['list'][] = $row;
        }
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 获取会员佣金日志
    /*------------------------------------------------------ */
    public function getDividendLog()
    {
        $type = input('type', 'all_balance_money', 'trim');
        $time = input('time', '', 'trim');
        if (empty($time)) {
            $time = date('Y年m月');
        }
        $return['time'] = $time;
        $_time = strtotime(str_replace(array('年', '月'), array('-', ''), $time));
        $return['code'] = 1;
        $DividendModel = new DividendModel();
        $where[] = ['dividend_uid', '=', $this->userInfo['user_id']];
        switch ($type) {
            case 'all_balance_money'://佣金
                $where[] = ['add_time', 'between', array($_time, strtotime(date('Y-m-t', $_time)) + 86399)];
                $where[] = ['dividend_amount', '<>', 0];
                break;
            case 'wait_balance_money'://等待分佣金
                $where[] = ['add_time', 'between', array($_time, strtotime(date('Y-m-t', $_time)) + 86399)];
                $where[] = ['dividend_amount', '<>', 0];
                $where[] = ['status', '=', 3];
                break;
            case 'arrival_balance_money'://金币已到帐明细
                $where[] = ['add_time', 'between', array($_time, strtotime(date('Y-m-t', $_time)) + 86399)];
                $where[] = ['dividend_amount', '<>', 0];
                $where[] = ['status', '=', 9];
                break;
            case 'cancel_balance_money'://金币失效明细
                $where[] = ['add_time', 'between', array($_time, strtotime(date('Y-m-t', $_time)) + 86399)];
                $where[] = ['dividend_amount', '<>', 0];
                $where[] = ['status', 'in', [1, 4]];
                break;
            case 'dividend_bean'://旅游豆
                $where[] = ['dividend_bean', '<>', 0];
                $where[] = ['status', '=', 9];
                $where[] = ['is_hide', '=', 0];
                $where[] = ['update_time', '>', time() - 172800];//只显示最近两到帐的佣金
                break;
            default:
                return $this->error('类型错误.');
                break;
        }
        $return['income'] = 0;
        $rows = $DividendModel->where($where)->order('add_time DESC')->select();

        $lang = lang('order');
        foreach ($rows as $key => $row) {
            $income = $row['dividend_amount'] > 0 ? $row['dividend_amount'] : $row['dividend_bean'];
            $return['income'] += $income;
            $row['_time'] = timeTran($row['add_time']);
            $row['value'] = $income;
            $row['nick_name'] = userInfo($row['buy_uid']);
            $row['status'] = $lang['ds'][$row['status']];
            $return['list'][] = $row;
        }
        return $this->ajaxReturn($return);
    }
    /*------------------------------------------------------ */
    //-- 修改会员信息
    /*------------------------------------------------------ */
    public function editInfo()
    {
        $imgfile = input('imgfile');
        if (empty($imgfile) == false) {
            $file_path = config('config._upload_') . 'headimg/' . substr($this->userInfo['user_id'], -1) . '/';
            makeDir($file_path);
            $file_name = $file_path . random_str(12) . '.jpg';
            file_put_contents($file_name, base64_decode(str_replace('data:image/jpeg;base64,', '', $imgfile)));
            $upArr['headimgurl'] = trim($file_name, '.');
        }
        $upArr['nick_name'] = input('nick_name', '', 'trim');
        if (empty($upArr['nick_name']) == true) {
            return $this->error('请填写用户昵称.');
        }
        $upArr['mobile'] = input('mobile', '', 'trim');
        $where[] = ['nick_name', '=', $upArr['nick_name']];
        $where[] = ['user_id', '<>', $this->userInfo['user_id']];
        $count = $this->Model->where($where)->count('user_id');
        if ($count > 0) return '昵称：' . $upArr['nick_name'] . '，已存在.';
        $upArr['signature'] = input('signature', '', 'trim');
        $upArr['sex'] = input('sex', '男', 'trim');
        $upArr['sex'] = $upArr['sex'] == '男' ? 1 : 0;
        $upArr['birthday'] = input('birthday', '', 'trim');
        $upArr['show_mobile'] = input('show_mobile', 0, 'intval');
        $res = $this->Model->upInfo($this->userInfo['user_id'], $upArr);
        if ($res < 1) {
            @unlink($file_name);
            return $this->error('修改用户信息失败，请重试.');
        }
        return $this->success('修改成功.');
    }
    /*------------------------------------------------------ */
    //-- 获取远程会员头像到本地
    /*------------------------------------------------------ */
    public function getHeadImg()
    {
        $headimgurl = $this->userInfo['headimgurl'];
        if (empty($headimgurl) == false) {
            if (strstr($headimgurl, 'http')) {
                $file_path = config('config._upload_') . 'headimg/' . substr($this->userInfo['user_id'], -1) . '/';
                makeDir($file_path);
                $file_name = $file_path . random_str(12) . '.jpg';
                file_put_contents($file_name, file_get_contents($this->userInfo['headimgurl']));
                $upArr['headimgurl'] = $headimgurl = trim($file_name, '.');
                (new UsersModel)->upInfo($this->userInfo['user_id'], $upArr);
            }
        }
        $return['headimgurl'] = $headimgurl;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取会员帐户变动日志-----改
    /*------------------------------------------------------ */
    public function AccountLog()
    {
        $type = input('type','order','trim');
        $time = input('time','','trim');
        $p = input('p',1);
        $limit = 10;
        $offset = ($p-1)*$limit;
        if (empty($time)){
            $time = date('Y年m月');
        }
        $return['time'] = $time;
        $_time = strtotime(str_replace(array('年','月'),array('-',''),$time));
        $return['code'] = 1;
        $AccountLogModel = new AccountLogModel();
        $where[] = ['user_id','=',$this->userInfo['user_id']];
        switch($type){
            case 'order'://订单相关
                $where[] = ['change_type','=',3];
                break;
            case 'brokerage'://佣金相关
                $where[] = ['change_type','=',4];
                break;
            case 'withdraw'://提现相关
                $where[] = ['change_type','=',5];
                break;
            case 'integral'://积分相关
                $where[] = ['use_integral','<>',0];
                break;
            default:
                return $this->error('类型错误.');
                break;
        }
        $where[] = ['change_time','between',array($_time,strtotime(date('Y-m-t',$_time))+86399)];
        $rows = $AccountLogModel->where($where)->limit($offset,$limit)->order('change_time DESC')->select();
        foreach ($rows as $key=>$row){
            //if ($row['bean_value'] > 0) {
            //    $return['income'] += $row['balance_money'];
            //}
            if ($row['balance_money'] != 0){
                if ( $row['balance_money'] > 0){
                    if ($row['change_type'] == 4){
                        $return['income'] += $row['balance_money'];
                    }
                    $return['expend'] += $row['balance_money'];
                    $row['value'] = '+'.$row['balance_money'];
                }else{

                    $row['value'] = $row['balance_money'];
                }
            }elseif ($row['use_integral'] != 0){
                if ( $row['use_integral'] > 0){
                    $return['expend'] += $row['use_integral'];
                    $row['value'] = '+'.$row['use_integral'];
                }else{
                    $return['income'] += $row['use_integral'];
                    $row['value'] = $row['use_integral'];
                }
            }else{
                continue;
            }
            $row['_time'] = timeTran($row['change_time']);
            $return['list'][] = $row;
        }
        $return['list'] = [];
        $return['income'] = $AccountLogModel->where($where)->where('balance_money','gt',0)->sum('balance_money');
        $return['expend'] = $AccountLogModel->where($where)->where('balance_money','lt',0)->sum('balance_money');
        $return['income'] = $return['income']?$return['income']:0;
        $return['expend'] = !empty($return['expend'])?$return['expend']:0;
        $return['list'] = $rows;
        return $this->ajaxReturn($return);
    }

}
