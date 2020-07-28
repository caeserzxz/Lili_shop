<?php
namespace app\http\controller;
use think\worker\Server;
use Workerman\Lib\Timer;
use think\facade\Cache;
use think\cache\driver\Redis;
use app\unique\model\PayRecordModel;

class OrderMessage extends Server
{
    protected $host = '0.0.0.0';
    protected $port = 8787;
    protected $connection;
    public function __construct()
    {
        $this->option = [
            'count'		=> 1,
            'name'		=> 'OrderMessage'];
        parent::__construct();
    }
    // 启动执行
    public function onWorkerStart($worker) {
        // 每N秒执行一次
        $time_interval = 5;
        Timer::add($time_interval, function()use($worker,$time_interval){
            $data = $this->getBroadcast();

            foreach($worker->connections as $connection) {
                $connection->send($data);
            }

        });
    }
    // 接收数据
    public function onMessage($connection, $data)
    {

        $this->connection = $connection;
        $_data = json_decode($data, true);
        if(!$_data) {
            return ;
        }
        // 根据类型执行不同的业务
        switch($_data['type']) {
            // 登录
            case 'login':
                $data = $this->getBroadcast();
            // $connection->send($data);
        }


        if(empty($_data['type'])){
            $redis = new Redis();
            //获取临时数据
            $list = Cache::get('temp_order_list');

            //这里接受安卓的调用成功的数据,删除临时数组里面已成功调起的数据
            foreach ($_data['data'] as $k=>$v){
                foreach ($list as $key=>$value){
                    if($v['log_id']==$value['log_id']){
                        $list[$k]['num'] = 4;
                    }
                }
            }
            //更新临时数据
            Cache::set('temp_order_list',$list);

        }



    }
    //断开连接
    public static function onClose($connection)
    {

    }
    // 请求获取区块行情数据
    public function getData(){
        $mkey = 'blockchain_quotes';
        $data = Cache::get($mkey);
        if (empty($data)){
            $data = file_get_contents('https://api.coincap.io/v2/assets');
            Cache::set($mkey, $data, 3);
        }
        return $data;
    }

    //获取当前需要语音播报的订单
    public function getBroadcast(){
        $redis = new Redis();
        //获取临时数据
        $list = Cache::get('temp_order_list');
        if(empty($list)) $list = [];

        //$data是最后返回广播的数组
        $data['data'] = [];

        $buyCount = $redis->Llen('broadcast');
        for ($i=0;$i<=$buyCount-1;$i++){
            // $str = $redis->Lindex('broadcast',$i);
            //取出并删除
            $str = $redis->rPop('broadcast');
            $arr = [];
            $arr = object_array(json_decode($str));

            if(empty($arr['log_id'])){
                continue;
            }

            if(empty($arr['num'])){
                $arr['num'] =1;
            }else{
                $arr['num'] = $arr['num']+1;
            }

            //如果当前播报次数超过限制则不再播报
            if($arr['num']>=4){
                //删除在临时数组中的数据
                foreach ($list as $key=>$value){
                    if($value['log_id']==$arr['log_id']){
                        unset($list[$key]);
                    }
                }
            }else{
                //增加到广播的数组中
                array_push($data['data'],$arr);

                //更新临时数组中的次数 如果存在就更新  不存在就新增
                $is_update = 1;
                foreach ($list as $k=>$v){
                    if($arr['log_id']==$v['log_id']){
                        $list[$k] = $arr;
                        $is_update = 2;
                        break;
                    }
                }
                if($is_update==1){
                    array_push($list,$arr);
                }
            }

        }
        //存临时数组
        Cache::set('temp_order_list',$list);
        //重新进入队列
        foreach ($list as $k=>$v){
            $str = json_encode($v);
            $redis->rPush('broadcast',$str);
        }

        $data['timestamp'] = time();
        $data_json =  json_encode($data,JSON_UNESCAPED_SLASHES);
        return $data_json;

    }
    //取出队列里面的数组
    //当次数小于3就播报并存数组
    //再将数组里面的数据存入队列
}
