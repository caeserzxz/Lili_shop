<?php
namespace app\member\controller\sys_admin;
use app\unique\model\PayRecordModel;
use think\Db;

use app\AdminController;
use app\member\model\UsersModel;
/*------------------------------------------------------ */
//-- 消费统计
/*------------------------------------------------------ */
class MemberPayRecord extends AdminController
{
    /*------------------------------------------------------ */
	//-- 初始化
	/*------------------------------------------------------ */
    public function initialize(){
   		parent::initialize();
		$this->Model = new PayRecordModel();
    }
	/*------------------------------------------------------ */
	//-- 主页
	/*------------------------------------------------------ */
    public function index(){
        $this->getList(true);
        $res = $this->Model->field('user_id,sum(amount) amounts,sum(amount_actual) amount_actuals')->select();
        $this->assign("res", $res);
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 获取列表
	//-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false) {
		$this->userWithdrawType = $this->getDict('UserWithdrawType');	
		$search['keyword'] = input('keyword','','trim');
        $data['page'] = $p = input('p',1,'trim') * 1;
        $page_size = input('page_size',10,'trim') * 1;
        if($page_size <= 0)$page_size = 10;
        $data['page_size'] = $page_size;
        $page_start = $page_size * ($p - 1);
		$where = [];
		if (empty($search['keyword']) == false){
			 $UsersModel = new UsersModel();
			 $uids = $UsersModel->where(" mobile LIKE '%".$search['keyword']."%' OR user_name LIKE '%".$search['keyword']."%' OR nick_name LIKE '%".$search['keyword']."%' OR user_id = '".$search['keyword']."'")->column('user_id');
			 $uids[] = -1;//增加这个为了以上查询为空时，限制本次主查询失效			 
			 $where[] = ['user_id','in',$uids];
		}
        $is_export = input('is_export',0,'intval');
        if ($is_export > 0) {
            return $this->export($where);
        }
        $data['total_count'] = $this->Model->group('user_id')->where($where)->count();
        $data['page_count'] = ceil($data['total_count'] / $data['page_size']);
        $data['list'] = $this->Model->field('user_id,sum(amount) amounts,sum(amount_actual) amount_actuals')->group('user_id')->where($where)->limit($page_start,$page_size)->select()->toArray();
        foreach ($data['list'] as $key=>$row){
            $this_user = userInfo($row['user_id'] ,false);
            $row['user_id'] = $this_user['user_id'];
            $row['nick_name'] = $this_user['nick_name'];
            $row['mobile'] = $this_user['mobile'];
            $data['list'][$key] = $row;
        }
		$this->assign("search", $search);
		$this->assign("data", $data);
		if ($runData == false){
			$data['content']= $this->fetch('list')->getContent();
			unset($data['list']);
			return $this->success('','',$data);
		}
        return true;
    }
    /*------------------------------------------------------ */
    //-- 导出
    /*------------------------------------------------------ */
    public function export(&$where)
    {
        $export_arr['会员ID'] = 'user_id';
        $export_arr['会员名称'] = 'user_name';
        $export_arr['申请日期'] = 'add_time';
        $export_arr['提现金额'] = 'amount';
        $export_arr['处理状态'] = 'status';
        $export_arr['提现方式'] = 'account_type';

        $export_arr['支付宝账户姓名'] = 'alipay_account';
        $export_arr['支付宝帐号'] = 'alipay_user_name';

        $export_arr['银行'] = 'bank_name';
        $export_arr['持卡人'] = 'bank_cardholder';
        $export_arr['卡号'] = 'bank_card_number';
        $export_arr['持卡人电话'] = 'bank_cardholder_phone';
        $export_arr['网点所在地'] = 'bank_location_outlet';
        $export_arr['支行名称'] = 'bank_branch_name';

        $page = 0;
        $page_size = 500;
        $page_count = 100;
        $title = join("\t", array_keys($export_arr)) . "\t";
        $lang = lang('withdraw');
        $data = '';
        $withdraw_account_type = config('config.withdraw_account_type');
        do {
            $rows = $this->Model->where($where)->limit($page * $page_size, $page_size)->select();
            if (empty($rows)) break;
            foreach ($rows as $row) {
                $account_info = json_decode($row['account_info'],true);
                foreach ($export_arr as $val) {
                    if (strstr($val, '_time')) {
                        $data .= dateTpl($row[$val]) . "\t";
                    }elseif ($val == 'status'){
                        $data .= $lang[$row[$val]]. "\t";
                    } elseif ($val == 'user_name') {
                        $data .= userInfo($row['user_id']). "\t";
                    } elseif ($val == 'account_type') {
                        $data .= $withdraw_account_type[$row['account_type']]. "\t";
                    }elseif (strstr($val, 'alipay_')) {
                        $data .= $account_info[$val]. "\t";
                    }elseif (strstr($val, 'bank_')) {
                        if ($val == 'bank_card_number') {
                            $data .= "'".$account_info['bank_card_number']. "\t";
                        }else{
                            $data .= $account_info[$val]. "\t";
                        }
                    }else{
                        $data .= str_replace(array("\r\n", "\n", "\r"), '', strip_tags($row[$val])) . "\t";
                    }
                }
                $data .= "\n";
            }
            $page++;
        } while ($page <= $page_count);

        $filename = '资料_' . date("YmdHis") . '.xls';
        $filename = iconv('utf-8', 'GBK//IGNORE', $filename);
        header("Content-type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename");
        echo iconv('utf-8', 'GBK//IGNORE', $title . "\n" . $data) . "\t";
        exit;
    }

    /*------------------------------------------------------ */
    //-- 单个用户鼓励金流水
    /*------------------------------------------------------ */
    public function log(){
        $user_id = input('user_id', 0, 'intval');
        $this->assign('user_id',$user_id);
        $this->assign("start_date", date('Y/m/d', strtotime("-1 months")));
        $this->assign("end_date", date('Y/m/d'));
        $this->getOrderList(true);

        $UsersModel = new UsersModel();
        $userInfo = $UsersModel->info($user_id);
        $outline['user_id'] = $user_id;
        $outline['nick_name'] = $userInfo['nick_name'];
        $outline['mobile'] = $userInfo['mobile'];
        // 实付款金额
        $where[] = ['user_id','=',$user_id];
        $where[] = ['status','=',1];
        $outline['amount_actual'] = $this->Model->where($where)->sum('amount_actual');
       
        $this->assign("outline", $outline);
        return $this->fetch('order_index');
    }
    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getOrderList($runData = false){

        $user_id = input('user_id');
        $where[] = ['user_id','=',$user_id];

        $reportrange = input('reportrange');
        if (empty($reportrange) == false){
            $dtime = explode('-',$reportrange);
            $where[] = ['add_time','between',[strtotime($dtime[0]),strtotime($dtime[1])+86399]];
        }else{
            $where[] = ['add_time','between',[strtotime("-1 months"),time()]];
        }
        $keyword = input('keyword');
        if ($keyword) $where[] = ['order_sn','=',$keyword];

        $this->order_by = 'log_id';
        $this->sort_by = 'DESC';
        $export = input('export', 0, 'intval');
        if ($export > 0) return $this->exportOrderList($where,$user_id);
        $data = $this->getPageList($this->Model, $where);

        foreach ($data['list'] as $key => $value) {  
            $_value = $value;      
            $_value['profits_money'] = round($value['amount'] * $value['profits'] / 100,2);
            $_value['bill_money'] = round($value['amount'] - $_value['profits_money'],2);
            $data['list'][$key] = $_value;
        }

        $this->assign("data", $data);
        $this->assign("search",$this->search);
        if ($runData == false) {
            $data['content'] = $this->fetch('sys_admin/member_pay_record/order_list')->getContent();
            return $this->success('', '', $data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 导出单用户鼓励金流水
    /*------------------------------------------------------ */
    public function exportOrderList($where,$user_id){

        $count = $this->Model->where($where)->count();
        if ($count < 1) return $this->error('没有找到可导出的日志资料！');
        $filename = '会员实付流水_'.$user_id.'_' . date("YmdHis") . '.xls';

        $export_arr['下单用户ID'] = 'user_id';
        $export_arr['订单流水号'] = 'order_sn';
        $export_arr['订单金额'] = 'amount';
        $export_arr['红包优惠'] = 'redbag_amount';
        $export_arr['订单实付金额'] = 'amount_actual';
        $export_arr['鼓励金抵扣'] = 'balance_amount';
        $export_arr['让利率'] = 'profits';
        $export_arr['货款'] = 'bill_money';
        $export_arr['下单时间'] = 'add_time';

        $export_field = $export_arr;
        $page = 0;
        $page_size = 500;
        $page_count = 100;

        $title = join("\t", array_keys($export_arr)) . "\t";
        $field = join(",", $export_field);
        $data = '';
        do {
            $rows = $this->Model->where($where)->limit($page * $page_size, $page_size)->select();
            if (empty($rows))return;
            foreach ($rows as $row) {
                foreach ($export_arr as $val) {
                    $profits_money = round($row['amount'] * $row['profits'] / 100,2);

                    if (strstr($val, '_time')) {
                        $data .= dateTpl($row[$val]) . "\t";
                    } elseif($val == 'profits'){
                        $data .= $row['profits'] . '%（'.$profits_money.'元）'. "\t";
                    } elseif($val == 'bill_money'){
                        $data .= round($row['amount'] - $profits_money,2). "\t";
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
}
