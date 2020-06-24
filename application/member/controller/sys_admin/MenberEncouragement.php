<?php
namespace app\member\controller\sys_admin;
use app\AdminController;

use app\member\model\AccountLogModel;
use app\member\model\AccountModel;
use app\member\model\UsersModel;
use app\member\model\WithdrawModel;
use app\unique\model\PayRecordModel;

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
}
