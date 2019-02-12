<?php

namespace app\weixin\model;
use app\BaseModel;
use think\facade\Cache;
//*------------------------------------------------------ */
//-- 素材库
/*------------------------------------------------------ */
class WeiXinKeywordsModel extends BaseModel
{
	protected $table = 'weixin_keywords';
	public  $pk = 'id';
	protected $mkey =  'mem_weixin_keywords_';
	 /*------------------------------------------------------ */
	//-- 清除缓存
	/*------------------------------------------------------ */ 
	public function cleanMemcache($keyword){
		Cache::rm(self::$mkey.$keyword);
	}
    /*------------------------------------------------------ */
	//-- 验证回复关键字
	//-- 
	//-- $get			获取url参数
	//-- $qrcode
	//-- 	msg			预定义文本回复内容
	//-- $keyword		请求类型（qrscene为二维码请求）
	//-- $fromUsername	微信用户openid
	/*------------------------------------------------------ */
    public function checkKey($keyword,$fromUsername){
        $map['status'] = 1;
		$map['_string'] = "FIND_IN_SET('".$keyword."',keyword)";
		// 关注回复
        if ($keyword == 'subscribe'){
			unset($map['_string']);
            $map['subscribe'] = 1;
			$map['pid'] = 0;
			unset($map['keyword']);
		}
        $_mkey = $this->mkey.'_'.$keyword;
		$row = Cache::get($_mkey);
		if ($row) return $row;
        $row = $this->where($map)->find();
		unset($map);
		// 调用默认回复
		if (empty($row) == true){
			$map['default'] = 1;
			$map['status'] = 1;
			$map['pid'] = 0;
			$row = $this->where($map)->find();
			unset($map);
		}
		if ($row['type'] == 'text'){
			$arr['MsgType'] = 'text';
			$arr['Content'] = $row['data'];
		}elseif ($row['type'] == 'news'){
			$arr['MsgType'] = 'news';
			$mapb['pid'] = $row['id'];
        	$rows = $this->where($mapb)->select();
			if ($rows){ 
				array_unshift($rows,$row);
			}else{
				$rows[] = $row;
			}
			//$domain = $this->request->domain();
			$domain = \Request::instance()->domain();
			$Articles = array();
			foreach ($rows as $key=>$row)
			{
				$Articles[$key]['Title'] = $row['title'];
				if ($key == 0) $Articles[$key]['Description'] = $row['description'];
				if (strstr($row['imgurl'],'http://') == false){
					$Articles[$key]['PicUrl'] = $domain .$row['imgurl'];
				}
				switch($row['bind_type']){
					case 'link':
						$Articles[$key]['Url'] = $row['data'];
					break;
					case 'article':
						$Articles[$key]['Url'] = $domain .url('Shop/Article/info',array('id'=>$row['ext_id'],'plc_id'=>$plc_arr['plc_id']));
					break;
					case 'tel':
						$Articles[$key]['Url'] = 'tel:'.$row['data'];
					break;
					default:
					break;
				}
			}
			$arr['Articles'] = $Articles;			
		}
		
		Cache::set($_mkey,$arr,30);
		return $arr;
    }
}