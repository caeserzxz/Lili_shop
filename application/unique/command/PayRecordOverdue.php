<?php

namespace app\unique\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;


use app\unique\model\PayRecordModel;
use think\Db;

/**
 * 线下订单自动取消
 *
 */

class PayRecordOverdue extends Command
{
    protected function configure()
    {
        $this->setName('PayRecordOverdue')->setDescription('线下订单自动取消');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("线下订单自动取消 begin");
        $PayRecordModel = new PayRecordModel();
        $add_time = time()-(settings('pay_over_time')*60);
        $config = config('config.');
        $time = time();

        $where[] = ['status','=',0];
        $where[] = ['add_time','<',$add_time];
        #获取超时的订单
        $list = $PayRecordModel->where($where)->select();

        foreach ($list as $k=>$v){
            $upData['log_id'] = $v['log_id'];
            $upData['status'] = $config['XX_CANCEL'];
            $upData['end_time'] = $time;
            $res = $PayRecordModel->upInfo($upData);

            $_log = '用户取消订单';
            $orderInfo =$PayRecordModel->where('log_id',$v['log_id'])->find();
            $PayRecordModel->_log($orderInfo,$_log);
        }

        $output->writeln("线下订单自动取消 end");

    }
}