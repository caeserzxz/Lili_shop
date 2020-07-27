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
        //获取临时数据
        $list = Cache::get('temp_order_list');
        //处理临时数据
        if(empty($_data['type'])){
            foreach ($_data['data'] as $k=>$v){
                foreach ($list as $key=>$value){
                    if($v['log_id']==$value['log_id']){
                        unset($list[$key]);
                    }
                }
            }
        }
        //更新临时数据
        Cache::set('temp_order_list',$list);

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
        //获取临时数据
        $list = Cache::get('temp_order_list');
        if(empty($list)) $list = [];

        $data['data'] = [];
        for ($i=0;$i<=$buyCount-1;$i++){
            // $str = $redis->Lindex('broadcast',$i);

            $str = $redis->rPop('broadcast');
            $arr = [];
            $arr = object_array(json_decode($str));

            //当发送次数小于3的时候才会继续发送
            if(empty($arr['num'])||$arr['num']<10){
                if(empty($arr['num'])){
                    $arr['num'] = 1;
                }else{
                    $arr['num'] =  $arr['num']+1;
                }
                array_push($list,$arr);

            }

            array_push($data['data'],$arr);
        }
        //存临时数组
        Cache::set('temp_order_list',$list);
        $data['timestamp'] = time();
        $data_json =  json_encode($data,JSON_UNESCAPED_SLASHES);
        return $data_json;

    }
}
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
        //获取临时数据
        $list = Cache::get('temp_order_list');
        //处理临时数据
        if(empty($_data['type'])){
            foreach ($_data['data'] as $k=>$v){
                foreach ($list as $key=>$value){
                    if($v['log_id']==$value['log_id']){
                        unset($list[$key]);
                    }
                }
            }
        }
        //更新临时数据
        Cache::set('temp_order_list',$list);

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
        //获取临时数据
        $list = Cache::get('temp_order_list');
        if(empty($list)) $list = [];

        $data['data'] = [];
        for ($i=0;$i<=$buyCount-1;$i++){
            // $str = $redis->Lindex('broadcast',$i);

            $str = $redis->rPop('broadcast');
            $arr = [];
            $arr = object_array(json_decode($str));

            //当发送次数小于3的时候才会继续发送
            if(empty($arr['num'])||$arr['num']<10){
                if(empty($arr['num'])){
                    $arr['num'] = 1;
                }else{
                    $arr['num'] =  $arr['num']+1;
                }
                array_push($list,$arr);

            }

            array_push($data['data'],$arr);
        }
        //存临时数组
        Cache::set('temp_order_list',$list);
        $data['timestamp'] = time();
        $data_json =  json_encode($data,JSON_UNESCAPED_SLASHES);
        return $data_json;

    }
}
