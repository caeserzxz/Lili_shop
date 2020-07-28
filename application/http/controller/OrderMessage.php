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
    protected $max_num;

    public function __construct()
    {
        $this->option = [
            'count'		=> 1,
            'name'		=> 'OrderMessage'];
        $this->max_num = 4;
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


        if(empty($_data['type'])){
            $redis = new Redis();
            //获取临时数据
            $list = Cache::get('temp_order_list');

            //这里接受安卓的调用成功的数据,删除临时数组里面已成功调起的数据
            foreach ($_data['data'] as $k=>$v){
                foreach ($list as $key=>$value){
                    if($v['log_id']==$value['log_id']){
                        $list[$key]['num'] =  $this->max_num;
                    }
                }
            }
            //更新临时数据
            Cache::set('temp_order_list',$list);

        }else{
            // 根据类型执行不同的业务
            switch($_data['type']) {
                // 登录
                case 'login':
                    $data = $this->getBroadcast();
                // $connection->send($data);
            }

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

            $temp_arr = [];
            $temp_key = '';
            foreach ($list as $k=>$v){
                if($arr['log_id']==$v['log_id']){
                    $temp_arr = $v;
                    $temp_key = $k;
                    break;
                }
            }

            if(empty($temp_arr)==false){
                if($temp_arr['num']< $this->max_num){
                    //增加到广播的数组中
                    array_push($data['data'],$arr);
                    //广播次数+1
                    $temp_arr['num'] = $temp_arr['num']+1;
                    //更新临时数组的数据
                    $list[$temp_key] = $temp_arr;
                }else{
                    //删除在临时数组中的数据
                    unset($list[$temp_key]);
                }
            }else{
                //存入临时数组
                $arr['num'] = 1;
                array_push($list,$arr);
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
