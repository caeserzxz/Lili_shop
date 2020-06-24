<?php

namespace app\unique\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\mainadmin\model\SettingsModel;
use app\member\model\UsersModel;
use app\unique\model\RedbagModel;
use app\store\model\BusinessGiftModel;
use think\Db;

/**
 * 商家红包和用户红包自动过期
 *
 */

class RedBagOverdue extends Command
{
    protected function configure()
    {
        $this->setName('RedBagOverdue')->setDescription('商家红包和用户红包自动过期');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("商家红包和用户红包自动过期 begin");
        $time = time();
        Db::startTrans();
        #商家红包自动过期
        $BusinessGiftModel = new BusinessGiftModel();
        $where[] = ['status','=',0];
        $where[] = ['end_time','<',$time];
        $map['status'] = 3;
        $res = $BusinessGiftModel->where($where)->update($map);
        if(!$res){
            Db::rollback();
        }
        #用户红包自动过期
        $RedbagModel = new RedbagModel;
        $where1[] = ['status','=',0];
        $where1[] = ['expire_time','<',$time];
        $map1['status'] = 9;
        $res1 = $RedbagModel->where($where1)->update($map1);
        if(!$res1){
            Db::rollback();
        }
        Db::commit();

        $output->writeln("商家红包和用户红包自动过期 end");

    }
}