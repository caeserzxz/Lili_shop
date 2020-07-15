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
}
