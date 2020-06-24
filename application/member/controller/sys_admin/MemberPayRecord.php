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
}
