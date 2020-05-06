<?php
// +----------------------------------------------------------------------
// | redis扩展
// | Author: iqgmy
// +----------------------------------------------------------------------
namespace lib;
use think\facade\Cache;
 
class Redis{
    private  $Redis;
    private  $prefix;
    /**
     * 架构函数
     */
    public function __construct() {
        $cache = Cache::init();
        $this->Redis = $cache->handler();
        $this->prefix = config('cache.prefix');
    }

    /**
     * 将value添加到链表key的左边（头部）
     * @param $key string 队列名
     * @param $val string/array 内容
     * @return mixed
     */
    public function lpush($key,$val){
        return $this->Redis->lpush($this->prefix.$key,$val);
    }
    /**
     * 将value添加到链表key的右边（尾部）
     * @param $key string 队列名
     * @param $val string/array 内容
     * @return mixed
     */
    public function rpush($key,$val){
        return $this->Redis->rpush($this->prefix.$key,$val);
    }
    /**
     * 将链表key的左边（头部）元素删除并取出
     * @param $key string 队列名
     * @return mixed
     */
    public function lpop($key){
        return $this->Redis->lpop($this->prefix.$key);
    }
    /**
     * 将链表key的右边（头部）元素删除并取出
     * @param $key string 队列名
     * @return mixed
     */
    public function rpop($key){
        return $this->Redis->rpop($this->prefix.$key);
    }
    /**
     * 返回链表key中有多少个元素
     * @param $key string 队列名
     * @return mixed
     */
    public function count($key){
        return $this->Redis->lSize($this->prefix.$key);
    }

    /**
     * 上锁，用于并发处理
     * @param $key string 锁名
     * @param $time int 锁的有效时间（单位秒）
     * @return mixed
     */
    public function look($key,$time = 5){
        $mkey = $this->prefix.$key;
        $lock_time = $this->Redis->setnx($mkey,time()+$time);
        if ($lock_time == false){
            $lock_time = $this->Redis->get($mkey);
            if(time()>$lock_time){
                $this->Redis->del($mkey);
                $lock_time = $this->Redis->setnx($mkey,time()+$time);
                if ($lock_time == false) return false;
            }else{
                return false;
            }
        }
        return true;
    }

     /**
     * 返回集合中的一个或多个随机元素
     * @param  string $set   集合名称
     * @param  int $count 要返回的元素个数，0表示返回单个元素，大于等于集合基数则返回整个元素数组。默认0
     * @return string|array   返回随机元素，如果是返回多个则为数组返回
     */
    public function srand($set, $count=0)
    {
        $set = $this->prefix.$set;
        return ((int)$count==0) ? $this->Redis->srandmember($set) : $this->Redis->srandmember($set, $count);
    }

    /**
     * 将一个或多个元素加入到无序集合中，已经存在于集合的元素将被忽略.如果集合不存在，则创建一个只包含添加的元素作成员的集合。
     * @param  string $set  集合名称
     * @param  string|array $value 元素值（唯一）,如果要加入多个元素请传入多个元素的数组
     * @param  int $expire 过期时间, 如果不填则不设置过期时间
     * @return int  返回被添加元素的数量.如果$set不是集合类型时返回0
     */
    public function sadd($set, $value)
    {
        $num = 0;
        $set = $this->prefix . $set;
        if(is_array($value)){
            foreach ($value as $key =>$v) {
                $num += $this->Redis->sadd($set, $v);
            }
        }else{
            $num += $this->Redis->sadd($set, $value);
        }

        //if((int)$expire){
          //  $this->Redis->expire($set, $expire);
      //  } 

        return $num;
    }

     /**
     * 移除集合中的一个或多个成员元素，不存在的成员元素会被忽略
     * @param  string $set 集合名称
     * @param  string|array $member 要移除的元素，如果要移除多个请传入多个元素的数组
     * @return int  返回被移除元素的个数
     */
    public function srem($set, $member)
    {
        $num = 0;
        $set =  $this->prefix. $set;
        if(is_array($member)){
            foreach ($member as $value) {
                $num += $this->Redis->srem($set, $value);
            }
        }else{
            $num += $this->Redis->srem($set, $member);
        }
        return $num;
    }

    /**
     * 返回无序集合中的所有的成员
     * @param  string $set 集合名称
     * @return  array  返回包含所有成员的数组
     */
    public  function smembers($set)
    {
        $set = $this->prefix . $set;

        return $this->Redis->smembers($set);
    }



}
