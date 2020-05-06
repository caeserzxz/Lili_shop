<?php
namespace lib;

class OrderMessage{
	private static $sname = 'order_message';

	public static function set($header_img ,$nickname){
		
		$header_img = empty($header_img) ? settings('logo') : $header_img;
		$value = json_encode(['headimgurl'=>$header_img,'user_name'=>$nickname,'add_time'=>time()]);
		$r = new \lib\Redis();
		$r->sadd(self::$sname,$value);
	}

	public static function get(){

	}

	public static function select($limit = 10){
		$r = new \lib\Redis();
		$lists = $r->smembers(self::$sname);
		//过滤过期的
		if(empty($lists))
			return  [];
		$data = [];
		foreach ($lists as $key => $value) {
			# code...
			$v = json_decode($value,true);
			if( time() > $v['add_time'] + $limit ){
				$r->srem(self::$sname,$value);
				continue;
			}
			$data[] = $v;

		}
		return $data;
	}



}