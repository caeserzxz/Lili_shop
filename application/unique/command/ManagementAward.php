<?php

namespace app\unique\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;


use app\unique\model\PayRecordModel;
use app\agent\model\AgentModel;
use app\member\model\UsersModel;
use app\member\model\AccountLogModel;
use think\Db;

/**
 * 管理奖
 *
 */

class ManagementAward extends Command
{
    protected function configure()
    {
        $this->setName('ManagementAward')->setDescription('管理奖');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("管理奖 begin");
        Db::startTrans();//启动事务
        $AgentModel = new AgentModel();
        $AccountLogModel = new AccountLogModel();
        #本月起始时间
        $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
        $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
        #获取所有代理
        $agent_list = $AgentModel->where('status',1)->select();
        #管理奖分佣比例
        $award = settings('money_award');

        foreach ($agent_list as $k=>$v){
            #获取所有下属代理
            $agent_lower = $AgentModel->where(['agent_pid'=>$v['agent_id'],'status'=>1])->column('user_id');
            $where= [];
            $where[] = ['change_type','=',13];
            $where[] = ['user_id','in',$agent_lower];
            $where[] = ['change_time', 'between', array($beginThismonth, $endThismonth)];
            $amount = $AccountLogModel->where($where)->sum('balance_money');
            $money_award =  sprintf("%.2f",$amount*$award/100);
            if($money_award>0){
                $changedata['change_desc'] = '管理奖';
                $changedata['change_type'] = 14;
                $changedata['by_id'] = '';
                $changedata['balance_money'] = $money_award;
                $res1 = $AccountLogModel->change($changedata, $v['user_id'], false);
                if(!$res1){
                    Db::rollback();// 回滚事务
                    return '更新管理奖失败.';
                }
            }
        }
        Db::commit();// 提交事务
        $output->writeln("管理奖 end");

    }

}