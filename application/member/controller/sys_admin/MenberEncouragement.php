<?php
namespace app\member\controller\sys_admin;
use app\AdminController;

use app\member\model\AccountLogModel;
use app\member\model\AccountModel;
use app\member\model\UsersModel;
use app\member\model\WithdrawModel;
use app\unique\model\PayRecordModel;
use app\shop\model\OrderModel;

/**
 * 鼓励金记录
 */
class MenberEncouragement extends AdminController
{
    //*------------------------------------------------------ */
    //-- 初始化
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new UsersModel();
    }
    /*------------------------------------------------------ */
    //--首页
    /*------------------------------------------------------ */
    public function index()
    {
        $this->getList(true);
        $AccountLogModel = new AccountLogModel();
        $AccountModel = new AccountModel();
        $WithdrawModel = new WithdrawModel();
        $PayRecordModel = new PayRecordModel();
        $res['all_balance_money'] = $AccountLogModel->where('balance_money','>',0)->sum('balance_money');
        $res['all_balance_moneys'] = $AccountModel->sum('balance_money');
        $res['all_withdraw_money'] = $WithdrawModel->where('status','=',9)->sum('amount');
        $res['all_pay_money'] = $PayRecordModel->sum('balance_amount');
        $this->assign("res", $res);
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false)
    {
        $this->search['keyword'] = input("keyword");
        $this->assign("search", $this->search);
        if (empty($this->search['keyword']) == false) {
            if (is_numeric($this->search['keyword'])) {
                $where[] = "  u.user_id = '" . ($this->search['keyword']) . "' or mobile like '" . $this->search['keyword'] . "%'";
            } else {
                $where[] = " ( u.user_name like '" . $this->search['keyword'] . "%' or u.nick_name like '" . $this->search['keyword'] . "%' )";
            }
        }
        $export = input('export', 0, 'intval');
        if ($export > 0) {
            return $this->exportOrder($where);
        }
        $sort_by = input("sort_by", 'DESC', 'trim');
        $order_by = 'u.user_id';
        $viewObj = $this->Model->alias('u')->join("users_account uc", 'u.user_id=uc.user_id', 'left')->where(join(' AND ', $where))->field('u.*,uc.balance_money,uc.use_integral,uc.total_integral')->order($order_by . ' ' . $sort_by);
        $data = $this->getPageList($this->Model, $viewObj);
        $data['order_by'] = $order_by;
        $data['sort_by'] = $sort_by;
        $AccountLogModel = new AccountLogModel();
        $WithdrawModel = new WithdrawModel();
        $PayRecordModel = new PayRecordModel();
        foreach ($data['list'] as $key=>$row){
            $row['all_balance_money'] = $AccountLogModel->where('user_id','=',$row['user_id'])->where('balance_money','>',0)->sum('balance_money');
            $row['all_withdraw_money'] = $WithdrawModel->where('user_id','=',$row['user_id'])->where('status','=',9)->sum('amount');
            $row['all_pay_money'] = $PayRecordModel->where('user_id','=',$row['user_id'])->sum('balance_amount');
            $data['list'][$key] = $row;
        }
        $this->assign("data", $data);
        $this->assign("search",$this->search);
        if ($runData == false) {
            $data['content'] = $this->fetch('sys_admin/menber_encouragement/list')->getContent();
            return $this->success('', '', $data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 单个用户鼓励金流水
    /*------------------------------------------------------ */
    public function log(){
        $user_id = input('user_id', 0, 'intval');

        $this->assign('user_id',$user_id);
        $this->assign("start_date", date('Y/m/d', strtotime("-1 months")));
        $this->assign("end_date", date('Y/m/d'));
        $this->getAccountList(true);

        $AccountLogModel = new AccountLogModel();
        $WithdrawModel = new WithdrawModel();

        $userInfo = $this->Model->info($user_id);
        $outline['user_id'] = $user_id;
        $outline['nick_name'] = $userInfo['nick_name'];
        $outline['mobile'] = $userInfo['mobile'];
        // 鼓励金总额
        $where_1[] = ['user_id','=',$user_id];
        $where_1[] = ['balance_money','>',0];
        $outline['total_money'] = $AccountLogModel->where($where_1)->sum('balance_money');
        // 鼓励金抵扣总额 扣除负数退回正数 用的同一type
        $where_2[] = ['user_id','=',$user_id];
        $where_2[] = ['change_type','IN',[3,16]];
        $where_2[] = ['balance_money','<>',0];
        $deduction_total = $AccountLogModel->where($where_2)->sum('balance_money');
        $deduction_total = $deduction_total * -1;
        $outline['total_money_deduction'] = $deduction_total > 0 ? $deduction_total : 0;
        // 当前鼓励金
        $outline['balance_money'] = $userInfo['account']['balance_money'];
        // 已提现鼓励金
        $where_3[] = ['user_id','=',$user_id];
        $where_3[] = ['status','=',9];
        $outline['with_money'] = $WithdrawModel->where($where_3)->sum('amount');

        $this->assign("outline", $outline);
        return $this->fetch('log_index');
    }
    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getAccountList($runData = false){
        $OrderModel = new OrderModel();
        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();

        $user_id = input('user_id');
        $where[] = ['user_id','=',$user_id];
        $where[] = ['balance_money','<>',0];

        $reportrange = input('reportrange');
        if (empty($reportrange) == false){
            $dtime = explode('-',$reportrange);
            $where[] = ['change_time','between',[strtotime($dtime[0]),strtotime($dtime[1])+86399]];
        }else{
            $where[] = ['change_time','between',[strtotime("-1 months"),time()]];
        }
        $change_type = input('change_type');
        switch ($change_type) {
            case '1':
                $where[] = ['change_type','IN',[11,12,13,14,15]];
                break;
            case '2':
                $where[] = ['change_type','IN',[3,16]];
                break;
            case '3':
                $where[] = ['change_type','IN',[5]];
                break;
            default:
                break;
        }
        
        $search_type = input('search_type');
        $keyword = input('keyword');

        if ($search_type == 1) {
            $orderID = $OrderModel->where(['order_sn' => $keyword])->value('order_id');
            $where[] = ['by_id','=',$orderID];
        }elseif ($search_type == 2) {
            $orderID = $PayRecordModel->where(['order_sn' => $keyword])->value('log_id');
            $where[] = ['by_id','=',$orderID];
        }

        $this->order_by = 'log_id';
        $this->sort_by = 'DESC';
        $export = input('export', 0, 'intval');
        if ($export > 0) return $this->exportAccountList($where,$user_id);
        $data = $this->getPageList($AccountLogModel, $where);
        foreach ($data['list'] as $key => $value) {        
            $data['list'][$key] = $this->getForAccount($value);
        }

        $this->assign("data", $data);
        $this->assign("search",$this->search);
        if ($runData == false) {
            $data['content'] = $this->fetch('sys_admin/menber_encouragement/log_list')->getContent();
            return $this->success('', '', $data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 导出单用户鼓励金流水
    /*------------------------------------------------------ */
    public function exportAccountList($where,$user_id){
        $OrderModel = new OrderModel();
        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();

        $count = $AccountLogModel->where($where)->count();
        if ($count < 1) return $this->error('没有找到可导出的日志资料！');
        $filename = '鼓励金流水_'.$user_id.'_' . date("YmdHis") . '.xls';
        $export_arr['流水号'] = 'order_sn';
        $export_arr['类型'] = 'typeText';
        $export_arr['金额'] = 'balance_money';
        $export_arr['变动时间'] = 'change_time';

        $export_field = $export_arr;
        $page = 0;
        $page_size = 500;
        $page_count = 100;

        $title = join("\t", array_keys($export_arr)) . "\t";
        $field = join(",", $export_field);
        $data = '';
        do {
            $rows = $AccountLogModel->where($where)->limit($page * $page_size, $page_size)->select();
            if (empty($rows))return;
            foreach ($rows as $row) {
                $row = $this->getForAccount($row);
                foreach ($export_arr as $val) {
                    if (strstr($val, '_time')) {
                        $data .= dateTpl($row[$val]) . "\t";
                    } else {
                        $data .= str_replace(array("\r\n", "\n", "\r"), '', strip_tags($row[$val])) . "\t";
                    }
                }
                $data .= "\n";
            }
            $page++;
        } while ($page <= $page_count);

        $filename = iconv('utf-8', 'GBK//IGNORE', $filename);
        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename");
        echo iconv('utf-8', 'GBK//IGNORE', $title . "\n" . $data) . "\t";
        exit;
    }
    /*------------------------------------------------------ */
    //-- 获取鼓励金流水详情
    /*------------------------------------------------------ */
    public function getForAccount($value){
        $OrderModel = new OrderModel();
        $PayRecordModel = new PayRecordModel();
        $_value = $value;
    
        $typeText = [1 => '人工调整',3 => '商城购物',4 => '佣金收入',5 => '提现',6 => '充值',8 => '兑换',9 => '售后',11 => '鼓励金奖',12 => '跨界奖',13 => '代理奖',14 => '管理奖',15 => '自购返',16 => '线下消费',17 => '货款'];
        $_value['typeText'] = $typeText[$value['change_type']];
        $_value['order_sn'] = $value['by_id'];

        if (in_array($value['change_type'],[3,15])) {
            $_value['order_sn'] = $OrderModel->where(['order_id' => $value['by_id']])->value('order_sn');
            if ($value['change_type'] == 3 && $value['balance_money'] > 0) {
                $_value['typeText'] .= ' - 退回';
            }elseif ($value['change_type'] == 3 && $value['balance_money'] < 0) {
                $_value['typeText'] .= ' - 抵扣';
            }
        }
        if (in_array($value['change_type'],[11,12,13,14,16])) {
            $_value['order_sn'] = $PayRecordModel->where(['log_id' => $value['by_id']])->value('order_sn');
            if ($value['change_type'] == 16 && $value['balance_money'] > 0) {
                $_value['typeText'] .= ' - 退回';
            }elseif ($value['change_type'] == 16 && $value['balance_money'] < 0) {
                $_value['typeText'] .= ' - 抵扣';
            }
        }
        return $_value;
    }
}
