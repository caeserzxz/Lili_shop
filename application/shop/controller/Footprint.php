<?php
/*------------------------------------------------------ */
//-- 商品足迹相关
//-- Author: zc
/*------------------------------------------------------ */
namespace app\shop\controller;
use app\ClientbaseController;



class Footprint extends ClientbaseController{
	/*------------------------------------------------------ */
	//-- 首页
	/*------------------------------------------------------ */
	public function index(){
		$this->assign('title', '我的足迹');
		return $this->fetch('index');
	}
   
}?>