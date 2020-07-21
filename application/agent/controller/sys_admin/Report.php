<?php
namespace app\agent\controller\sys_admin;
use app\unique\model\PayRecordModel;
use think\Db;
use app\AdminController;
use app\member\model\UsersModel;
use app\agent\model\AgentModel;
use app\store\model\UserBusinessModel;
use app\member\model\AccountLogModel;
use app\member\model\WithdrawModel;
use app\store\model\CategoryModel;

/*------------------------------------------------------ */
//-- 消费统计
/*------------------------------------------------------ */
class Report extends AdminController
{
    /*------------------------------------------------------ */
	//-- 初始化
	/*------------------------------------------------------ */
    public function initialize(){
   		parent::initialize();
        $this->Model = new AgentModel();

    }
    /*------------------------------------------------------ */
    //-- 代理商总额列表
    /*------------------------------------------------------ */
    public function total(){
        $this->getTotalList(true);
        return $this->fetch('total_index');
    }
    /*------------------------------------------------------ */
    //-- 获取代理商总额统计列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getTotalList($runData = false) {
        $search['keyword'] = input('keyword','','trim');
        $search['search_type'] = input('search_type','','trim');
        $where = [];
        if (empty($search['keyword']) == false && empty($search['search_type']) == false){
            $where[] = [$search['search_type'],'=',$search['keyword']];
        }
        $data = $this->getPageList($this->Model,$where);
        $storeModel = new UserBusinessModel();
        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();
        $achievement_sum = $bonus_sum = 0;
        foreach ($data['list'] as $key=>$row){
            // 业绩总累积 代理下面所有商家相关订单
            $store = $storeModel->where(['agent_id' => $row['agent_id']])->column('business_id');
            $achievement_total = 0;
            $where_o = $where_a = [];
            if ($store) {
                $where_o[] = ['business_id','IN',$store];
                $where_o[] = ['status','=',1];
                $achievement_total = $PayRecordModel->where($where_o)->sum('amount');
            }
            $data['list'][$key]['achievement_total'] = $achievement_total;

            // 奖项总累计
            $where_a[] = ['user_id','=',$row['user_id']];
            $where_a[] = ['change_type','IN',[11,12,13,14]];
            $bonus_total = $AccountLogModel->where(['user_id' => $row['user_id']])->sum('balance_money');
            $data['list'][$key]['bonus_total'] = $bonus_total;

            $achievement_sum += $achievement_total;
            $bonus_sum += $bonus_total;
        }
        $report['achievement_sum'] = $achievement_sum;
        $report['bonus_sum'] = $bonus_sum;
        $report['report_time'] = date('Y-m-d H:i',time());
        $this->assign("report", $report);

        $this->assign("search", $search);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('total_list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }
    
    /*------------------------------------------------------ */
    //-- 代理商提现列表
    /*------------------------------------------------------ */
    public function with_total(){
        $this->getWithList(true);
        return $this->fetch('with_index');
    }
    /*------------------------------------------------------ */
    //-- 获取代理商提现总额统计列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getWithList($runData = false) {
        $search['keyword'] = input('keyword','','trim');
        $search['search_type'] = input('search_type','','trim');
        $where = [];
        if (empty($search['keyword']) == false && empty($search['search_type']) == false){
            $where[] = [$search['search_type'],'=',$search['keyword']];
        }
        $data = $this->getPageList($this->Model,$where);
        $WithdrawModel = new WithdrawModel();
        $UsersModel = new UsersModel();
        $with_sum = $wait_sum = 0;
        foreach ($data['list'] as $key=>$row){
            // 累计已提
            $with_total = $WithdrawModel->where(['user_id' => $row['user_id'],'status' => 9,'type' => 1])->sum('amount');
            // 未提
            $userInfo = $UsersModel->info($row['user_id']);
            $wait_total = $userInfo['account']['balance_money'];

            $data['list'][$key]['with_total'] = $with_total;
            $data['list'][$key]['wait_total'] = $wait_total;

            $with_sum += $with_total;
            $wait_sum += $wait_total;
        }
        $report['with_sum'] = $with_sum;
        $report['wait_sum'] = $wait_sum;
        $report['report_time'] = date('Y-m-d H:i',time());
        $this->assign("report", $report);

        $this->assign("search", $search);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('with_list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 单个代理商奖项列表
    /*------------------------------------------------------ */
    public function bonus(){
        $agent_id = input('agent_id','','trim');
        $this->assign("agent_id", $agent_id);

        $business_id = input('business_id','','trim');
        $this->assign("business_id", $business_id);

        $this->getBonusList(true);
        $this->assign("start_date", date('Y/m/d', strtotime("-1 months")));
        $this->assign("end_date", date('Y/m/d'));
        return $this->fetch('bonus_index');
    }
    /*------------------------------------------------------ */
    //-- 获取单个代理商奖项列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getBonusList($runData = false) {
        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();
        $storeModel = new UserBusinessModel();

        $agent_id = input('agent_id','','trim');
        if ($agent_id) {
            $agent = $this->Model->where(['agent_id' => $agent_id])->find();
            $user_id = $agent['user_id'];
            $exportType = 1;
            $report['agent_id'] = $agent['agent_id'];
            $report['agent_name'] = $agent['agent_user_name'];
            $report['agent_mobile'] = $agent['agent_mobile'];
        }
        $business_id = input('business_id','','trim');
        if ($business_id) {
            $business = $storeModel->where(['business_id' => $business_id])->find();
            $user_id = $business['user_id'];
            $exportType = 2;
            $report['business_id'] = $business['business_id'];
            $report['business_name'] = $business['business_name'];
            $report['business_mobile'] = $business['business_mobile'];
        }
        if ($agent_id || $business_id) $where[] = ['user_id','=',$user_id];

        $change_type = input('change_type','','trim');
        if ($change_type) {
            $where[] = ['change_type','=',$change_type];
        }else{
            $where[] = ['change_type','IN',[11,12,13,14]];
        }
        $reportrange = input('reportrange');
        $change_time = ['change_time','between',[date('Y-m-d',strtotime("-1 months")),time()]];
        if (empty($reportrange) == false){
            $dtime = explode('-',$reportrange);
            $change_time = ['change_time','between',[strtotime($dtime[0]),strtotime($dtime[1])+86399]];
        }
        $where[] = $change_time;

        $search['keyword'] = input('keyword','','trim');
        if (empty($search['keyword']) == false){
            $log_id = $PayRecordModel->where(['order_sn' => $search['keyword']])->value('log_id');
            $where[] = ['by_id','=',$log_id];
        }
        $export = input('export', 0, 'intval');
        if ($export > 0) return $this->exportBonusList($where,$exportType);

        $data = $this->getPageList($AccountLogModel,$where);

        $bonusText = [11 => '鼓励金奖',12 => '跨界奖',13 => '代理奖',14 => '管理奖'];
        foreach ($data['list'] as $key=>$row){
            $_row = $row;
            // 订单流水号
            $_row['order_sn'] = $PayRecordModel->where(['log_id' => $row['by_id']])->value('order_sn');
            $_row['typeText'] = $bonusText[$row['change_type']];
            $data['list'][$key] = $_row;
        }
        $whereA[] = ['user_id','=',$user_id];
        $whereA[] = ['change_type','IN',[12,13,14]];
        // 代理奖累计总额 && 管理奖累计总额 && 跨界奖累计总额
        $awardAll = $AccountLogModel->field("sum('balance_money') balance_moneys,change_type")->where($whereA)->group('change_type')->column('sum(balance_money) balance_moneys','change_type');
        $report['agentAwardAll'] = $awardAll[13] > 0 ? $awardAll[13] : 0;
        $report['managedAwardAll'] = $awardAll[14] > 0 ? $awardAll[14] : 0;
        $report['strideAwardAll'] = $awardAll[12] > 0 ? $awardAll[12] : 0;

        // 代理奖总额 && 管理奖总额
        $whereA[] = $change_time;
        $awardTimeAll = $AccountLogModel->field("sum('balance_money') balance_moneys,change_type")->where($whereA)->group('change_type')->column('sum(balance_money) balance_moneys','change_type');
        $report['agentAwardTimeAll'] = $awardTimeAll[13] > 0 ? $awardTimeAll[13] : 0;
        $report['managedAwardTimeAll'] = $awardTimeAll[14] > 0 ? $awardTimeAll[14] : 0;
        $report['strideAwardTimeAll'] = $awardTimeAll[12] > 0 ? $awardTimeAll[12] : 0;

        $this->assign("report", $report);
        $this->assign("search", $search);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('bonus_list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 导出单代理商奖项流水
    /*------------------------------------------------------ */
    public function exportBonusList($where,$exportType = 1){
        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();

        $count = $AccountLogModel->where($where)->count();
        if ($count < 1) return $this->error('没有找到可导出的日志资料！');
        $filename = '商家奖项流水_' . date("YmdHis") . '.xls';
        if ($exportType == 1) $filename = '代理商奖项流水_' . date("YmdHis") . '.xls';
        
        $bonusText = [11 => '鼓励金奖',12 => '跨界奖',13 => '代理奖',14 => '管理奖'];

        $export_arr['订单流水号'] = 'order_sn';
        $export_arr['奖项类型'] = 'typeText';
        $export_arr['奖项金额'] = 'balance_money';
        $export_arr['奖项到账时间'] = 'change_time';

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
                $row['order_sn'] = $PayRecordModel->where(['log_id' => $row['by_id']])->value('order_sn');
                $row['typeText'] = $bonusText[$row['change_type']];
                foreach ($export_arr as $val) {
                    if (strstr($val, '_time')) {
                        $data .= dateTpl($row[$val],'Y/m/d H:i:s') . "\t";
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
    //-- 单个代理商奖项列表
    /*------------------------------------------------------ */
    public function chievement(){
        $agent_id = input('agent_id','','trim');
        $this->assign("agent_id", $agent_id);

        $this->getChievementList(true);
        return $this->fetch('chievement_index');
    }
    /*------------------------------------------------------ */
    //-- 获取单个代理商业绩列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getChievementList($runData = false) {
        $storeModel = new UserBusinessModel();
        $PayRecordModel = new PayRecordModel();
        $CategoryModel = new CategoryModel();

        $agent_id = input('agent_id','','trim');
        if ($agent_id) {
            $agent = $this->Model->where(['agent_id' => $agent_id])->find();
            $where[] = ['agent_id','=',$agent_id];
        }
        $search['keyword'] = input('keyword','','trim');
        $search['search_type'] = input('search_type','','trim');
        if (empty($search['keyword']) == false && empty($search['search_type']) == false){
            $where[] = [$search['search_type'],'=',$search['keyword']];
        }
        $export = input('export', 0, 'intval');
        if ($export > 0) return $this->exportChievementList($where,$agent_id);

        $data = $this->getPageList($storeModel,$where);
        $report['achievement'] = 0;
        $bonusText = [11 => '鼓励金奖',12 => '跨界奖',13 => '代理奖',14 => '管理奖'];
        foreach ($data['list'] as $key=>$row){
            // 类目
            $row['cate_name'] = $CategoryModel->where(['id' => $row['category_id']])->value('name');
            // 业绩
            $row['achievement'] = $PayRecordModel->where(['business_id' => $row['business_id'],'status' => 1])->sum('amount');
            $data['list'][$key] = $row;
            $report['achievement'] += $row['achievement'];
        }
        $report['agent_id'] = $agent['agent_id'];
        $report['agent_name'] = $agent['agent_user_name'];
        $report['agent_mobile'] = $agent['agent_mobile'];
        $report['report_time'] = date('Y-m-d H:i');

        $this->assign("report", $report);
        $this->assign("search", $search);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('chievement_list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 导出单代理商业绩流水
    /*------------------------------------------------------ */
    public function exportChievementList($where,$agent_id = 0){
        $storeModel = new UserBusinessModel();
        $PayRecordModel = new PayRecordModel();
        $CategoryModel = new CategoryModel();

        $count = $storeModel->where($where)->count();
        if ($count < 1) return $this->error('没有找到可导出的日志资料！');
        if ($agent_id) {
            $filename = '代理商业绩流水_'.$agent_id.'_' . date("YmdHis") . '.xls';
        }else{
            $filename = '代理商业绩流水_' . date("YmdHis") . '.xls';
        }
        $export_arr['商家ID'] = 'business_id';
        $export_arr['商家名称'] = 'business_name';
        $export_arr['类目'] = 'cate_name';
        $export_arr['商家手机号'] = 'business_mobile';
        $export_arr['商家业绩总累计'] = 'achievement';

        $export_field = $export_arr;
        $page = 0;
        $page_size = 500;
        $page_count = 100;

        $title = join("\t", array_keys($export_arr)) . "\t";
        $field = join(",", $export_field);
        $data = '';
        do {
            $rows = $storeModel->where($where)->limit($page * $page_size, $page_size)->select();
            if (empty($rows))return;
            foreach ($rows as $row) {
                $row['cate_name'] = $CategoryModel->where(['id' => $row['category_id']])->value('name');
                $row['achievement'] = $PayRecordModel->where(['business_id' => $row['business_id'],'status' => 1])->sum('amount');
                foreach ($export_arr as $val) {
                    if (strstr($val, '_time')) {
                        $data .= dateTpl($row[$val],'Y/m/d H:i:s') . "\t";
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
