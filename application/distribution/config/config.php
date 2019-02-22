<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    
   //提成状态
	'DD_UNCONFIRMED' => 0, // 待处理	
	'DD_SHIPPED' => 1, // 已发货
	'DD_SIGN' => 2, // 已签收,待分成
	'DD_DIVVIDEND' => 9, // 已分成
	'DD_CANCELED' => 10, // 已取消
	'DD_RETURNED' => 20, // 退货		
];
