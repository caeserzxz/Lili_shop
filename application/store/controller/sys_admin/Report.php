<?php
namespace app\store\controller\sys_admin;
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
        $this->Model = new UserBusinessModel();

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

        $PayRecordModel = new PayRecordModel();
        $AccountLogModel = new AccountLogModel();
        $CategoryModel = new CategoryModel();
        $report['transboundarys'] = $report['redbag_amounts'] = $report['amounts'] = $report['bill_moneys'] = 0;
        foreach ($data['list'] as $key=>$row){
            $_row = $row;
            // 业绩总累积 代理下面所有商家相关订单
            $where_o = $where_a = [];

            $where_o[] = ['business_id','=',$row['business_id']];
            $where_o[] = ['status','=',1];
            $store_report = $PayRecordModel->field('sum(amount) amounts,sum(redbag_amount) redbag_amounts,sum(amount)-(sum(amount)*sum(profits)*0.01) as bill_moneys')->where($where_o)->find();
            
            $_row['amounts'] = $store_report['amounts'];
            $_row['redbag_amounts'] = $store_report['redbag_amounts'];
            $_row['bill_moneys'] = $store_report['bill_moneys'];

            // 奖项总累计
            $where_a[] = ['user_id','=',$row['user_id']];
            $where_a[] = ['change_type','IN',[11,12,13,14]];
            $bonus_total = $AccountLogModel->where($where_a)->sum('balance_money');
            $_row['bonus_total'] = $bonus_total;

            $_row['cate_name'] = $CategoryModel->where(['id' => $row['category_id']])->value('name');

            // 跨界奖
            $transboundarys = $AccountLogModel->where(['user_id' => $row['user_id'],'change_type' => 12])->sum('balance_money');

            $report['transboundarys'] += $transboundarys;
            $report['redbag_amounts'] += $_row['redbag_amounts'];
            $report['amounts'] += $_row['amounts'];
            $report['bill_moneys'] += $_row['bill_moneys'];

            $data['list'][$key] = $_row;
        }
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
        $CategoryModel = new CategoryModel();
        
        $with_sum = $wait_sum = 0;
        foreach ($data['list'] as $key=>$row){
            // 累计已提
            $with_total = $WithdrawModel->where(['user_id' => $row['user_id'],'status' => 9])->sum('amount');
            // 未提
            $userInfo = $UsersModel->info($row['user_id']);
            $wait_total = $userInfo['account']['balance_money'];

            $data['list'][$key]['with_total'] = $with_total;
            $data['list'][$key]['wait_total'] = $wait_total;

            $data['list'][$key]['cate_name'] = $CategoryModel->where(['id' => $row['category_id']])->value('name');

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
}
