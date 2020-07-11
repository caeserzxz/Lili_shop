<?php
namespace app\http\controller;
use think\worker\Server;
use Workerman\Lib\Timer;
use think\facade\Cache;
use think\cache\driver\Redis;

class OrderMessage extends Server
{
    protected $host = '0.0.0.0';
    protected $port = 8787;
    protected $connection;
    public function __construct()
    {
        $this->option = [
            'count'		=> 4,
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
        $buyCount = $redis->Llen('broadcast');

        $data['data'] = [];
        for ($i=0;$i<=$buyCount-1;$i++){
            $str = $redis->Lindex('broadcast',$i);

//            $id = $redis->rPop('broadcast');
            array_push($data['data'],json_decode($str));
        }
        $data['timestamp'] = time();
        $data_json =  json_encode($data,JSON_UNESCAPED_SLASHES);
        return $data_json;

    }
}
