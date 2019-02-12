<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 驼峰命名转下划线命名
 * @param $str
 * @return string
 */
function toUnderScore($str)
{
    $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
        return '_' . strtolower($matchs[0]);
    }, $str);
    return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
}

/**
 * 自定义URL
*/
function _url($url,$arr,$isNotHtml=true){
	return str_replace(array('%E3%80%90','%E3%80%91','%5B%5B','%5D%5D'),array("'+","+'",'{{','}}'),url($url,$arr,$isNotHtml));
}
/**
 * 后台生成密码hash值
 * @param $password
 * @return string
 */
function _hash($password)
{
    return md5(md5($password) . 'main_salt_zpTRx');
}

/**
 * 前台生成密码hash值
 * @param $password
 * @return string
 */
function f_hash($password)
{
    return md5('@by_'.md5(md5($password).'pwd@2019'));
}
//验证手机号码
function checkMobile($phone = ''){
    $preg_phone='/^1[34578]\d{9}$/ims';
    if(preg_match($preg_phone,$phone)){
        return true;
    }
    return false;

}
/*------------------------------------------------------ */
//-- 过滤掉emoji表情
/*------------------------------------------------------ */ 
function repEmoji($str){
    $str = preg_replace_callback( '/./u',function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },$str);
     return $str;
}
/*------------------------------------------------------ */
//-- 获取会员信息
/*------------------------------------------------------ */ 
function userInfo($user_id,$returnName=true){
	static $userList;
	static $userModel;
	if ($user_id < 1) return $returnName == true ? '--' : [];
	if (!isset($userModel)){
		 $userModel = model('app\member\model\UsersModel');	
	}
	if (!isset($userList[$user_id])){
		$userList[$user_id] = $userModel->info($user_id);
	}
	if (empty($userList[$user_id])) return $returnName == true ? '--' : [];
	$info = $userList[$user_id];
	if ($returnName == true) return $info['user_name'];
	return $info;
}
/*------------------------------------------------------ */
//-- 获取会员等级
/*------------------------------------------------------ */ 
function userLevel($integral,$returnName=true){
	static $userLevelList;	
	if (!isset($userLevelList)){
		 $Model = model('app\member\model\UsersLevelModel');	
		 $userLevelList = $Model->getRows();
	}
	$level = array();
	foreach ($userLevelList as $row){
		if ($integral >= $row['min'] && $integral <= $row['max']){
			$level = $row;		
			break;
		}elseif ($row['max'] == 0){
			$level = $row;
			break;
		}
	}
	if ($returnName == true) return $level['level_name'];
	return $level;
}
/*------------------------------------------------------ */
//-- 格式化价格
//-- @access  public
//-- @param   float   $price  价格
//-- @return  string
/*------------------------------------------------------ */
function priceFormat($price,$show_yuan = false,$type=0){    
	switch ($type){
		case 0:
			$price = number_format($price, 2, '.', '');
			break;
		case 1: // 保留不为 0 的尾数
			$price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));
			if (substr($price, -1) == '.') $price = substr($price, 0, -1);
			break;
		case 2: // 不四舍五入，保留1位
			$price = substr(number_format($price, 2, '.', ''), 0, -1);
			break;
		case 3: // 直接取整
			$price = intval($price);
			break;
		case 4: // 四舍五入，保留 1 位
			$price = number_format($price, 1, '.', '');
			break;
		case 5: // 先四舍五入，不保留小数
			$price = round($price);
			break;
	}   

    if($show_yuan == false) return sprintf("%s", $price);
	else return sprintf("￥%s元", $price);
}
/**
 * 写入日志
 * @param string|array $values
 * @param string $dir
 * @return bool|int
 */
function _log($values, $dir)
{
    if (is_array($values))
        $values = print_r($values, true);
    // 日志内容
    $content = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL . $values . PHP_EOL . PHP_EOL;
    try {
        // 文件路径
        $filePath = $dir . '/logs/';
        // 路径不存在则创建
        !is_dir($filePath) && mkdir($filePath, 0755, true);
        // 写入文件
        return file_put_contents($filePath . date('Ymd') . '.log', $content, FILE_APPEND);
    } catch (\Exception $e) {
        return false;
    }
}
/**
 * curl请求指定url
 * @param $url
 * @param array $data
 * @return mixed
 */
function curl($url, $data = [])
{
    // 处理get数据
    if (!empty($data)) {
        $url = $url . '?' . http_build_query($data);
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}
/*------------------------------------------------------ */
//-- 模板自定义判断
//-- val 传入值
//-- dval  判断值
//-- rval 处理返回
//-- default 是否默认
/*------------------------------------------------------ */
function tplckval($val='',$dval='',$rval='',$default = false){	
	if ($val !== 0){
		if (empty($val) == true && $default === true) return $rval;
		if (empty($dval) == true) return '';
	}
	
	if (is_array($dval)) return (in_array($val,$dval))?$rval:'';
	
	if ($val === $dval) return $rval;
	
	if (strstr($dval,'=')){
		$dval = explode('=',$dval);	
		return ($val == $dval[1]) ? $rval : $default;
	}
	if (strstr($dval,'<>')){
		$dval = explode('<>',$dval);
		return ($val <> $dval[1]) ? $rval : $default;
	}
	if (strstr($dval,'>=')){
		$dval = explode('>=',$dval);
		return ($val >= $dval[1]) ? $rval : $default;
	}
	if (strstr($dval,'>')){
		$dval = explode('>',$dval);
		return ($val > $dval[1]) ? $rval : $default;
	}
	if (strstr($dval,'<=')){
		$dval = explode('<=',$dval);
		return ($val <= $dval[1]) ? $rval : $default;
	}
	if (strstr($dval,'<')){
		$dval = explode('<',$dval);
		return ($val < $dval[1]) ? $rval : $default;
	}
	
}
/*------------------------------------------------------ */
//-- 模板中调用，将GMT时间戳格式化为用户自定义时区日期
/*------------------------------------------------------ */
function dateTpl($time = '',$format = '',$return_false = false){    
	if ($format === true){ 
		$time = time();
		$format = 'Y-m-d H:i';
	}elseif (empty($time)){
		 return ($return_false == false) ? '没有记录' : '';
	}
	if (empty($format)){
		$format = 'Y-m-d H:i';
	}
    return date($format, $time);
}
/*------------------------------------------------------ */
//-- 返回一个带子级别的数组
//--@Param $rows 数组源; 设置：必要;
//--@Param $parent_id 顶级pid; 设置：不需要;
//--@Param $leve 默认层级;设置; 不需要;
//--@Param $newrows 递归子类的id; 设置：不需要;
/*------------------------------------------------------ */
function returnRows($rows,$pid = 0,$level = 1){	
	static $newrows = array();
	if ($level == 1) $newrows = array();  
	$icon = array('&nbsp;&nbsp;│ ','&nbsp;&nbsp;├─ ','&nbsp;&nbsp;└─ ');
	$now_id = 0;
	foreach ($rows as $key=>$row){
		$_pid = $row['pid']; 
		if ($pid != $_pid) continue;	
		if (isset($newrows[$row['id']])) continue;						
		$children = returnChildren($rows,$row['id']);
		$row['children'] = ($children) ? $row['id'].','.join(',',$children) : $row['id'];
		$row['level'] = $level;
		if ($level > 1){
			$now_icon = '';
			for($i=1;$i<$level;$i++){
				$now_icon .= ($i == $level-1) ? $icon[1] : $icon[0];
			}
			$row['icon'] = $now_icon;
		}else{
			$row['icon'] = '';	
		}
		
		$now_id = $row['id'];
		$newrows[$now_id] = $row;
		unset($rows[$key]);		
		$nc = count($newrows);
		if ($rows){
			 $newrows = returnRows($rows,$now_id,($level+1));
		}
		if (count($newrows) > $nc){
			$end_arr = end($newrows);
			if ($end_arr['icon']) $newrows[$end_arr['id']]['icon'] = str_replace($icon[1],$icon[2],$end_arr['icon']);
		}
	}
	if ($now_id > 0) $newrows[$now_id]['icon'] = str_replace($icon[1],$icon[2],$newrows[$now_id]['icon']);
	unset($rows);
	return $newrows;
}
function returnChildren(&$rows,$pid = 0){
	$newrows = array();
	foreach ($rows as $key=>$row){  
		if ($pid != $row['pid']) continue;	
		$children = returnChildren($rows,$row['id']);
		if ($children) $row['id'] .= ','.join(',',$children);
		$newrows[] = $row['id'];
	}
	return $newrows;
}
/*------------------------------------------------------ */
//-- 返回一个带有缩进级别的数组
/*------------------------------------------------------ */
function returnRecArr(&$rows){
	$newrows = array();
	foreach ($rows as $key=>$row){
		$newrows[$row['pid']][$row['id']] = $row;
	}
	return $newrows;
}
/*------------------------------------------------------ */
//-- 将数组转换组下拉选项
//-- @param   array   $arr             所有的数组
//-- @param   int     $selected        选中项
//-- @param   boolean     $islimit     是否判断限制不可选
//-- @param   int     $level           返回等级
//-- @return  string
/*------------------------------------------------------ */
function arrToSel(&$rows = array(), $selected = 0, $islimit = false, $level = 0 ){
	$select = '';
	$selected = explode(',',$selected);
	foreach ($rows AS $val){
		if ($level > 0 && $val['level'] > $level) continue;		
		$select .= '<option ';
		if ($islimit === true && $val['children'] != $val['id'] ){
			$val['id'] = '';
			$select .=  ' style="background:#999;" ';
		}
		
		if (isset($val['status']) && $val['status'] == 0){
			$select .=  ' style="color:#CCC;" ';
		}elseif (isset($val['is_sys']) && $val['is_sys'] == 1){
			$select .=  ' style="color:#ff0000;"  ';
		}
	    $text = htmlspecialchars(strip_tags($val['name']));
		if (empty($val['ext_val']) && $val['id'] != 0){
			$select .= ' value="'.$val['id'].'" ';
			$selval = $val['id'];
		}else{
			$select .= ' value="'.$val['ext_val'].'"  ';
			$selval = $val['ext_val'];
		}
		$select .= (in_array($selval,$selected)) ? "selected='selected'" : '';
		$select .= ' data-text="'.$text.'" label="'.$text.'" >';		
		if (isset($val['icon'])) $select .= $val['icon'];
		$select .= $text;
		$select .= '</option>';
	}
	return $select;
}
/*------------------------------------------------------ */
//-- 判断是否json，是返回数组
/*------------------------------------------------------ */
function isJson($string) {
 $arr = json_decode($string,true);
 return (json_last_error() == JSON_ERROR_NONE) ? $arr : $string;
}

/*------------------------------------------------------ 
 * 校验日期格式是否正确
 * @param string $date 日期
 * @param string $formats 需要检验的格式数组
 * @return boolean
------------------------------------------------------ */
function checkDateIsValid($date, $formats = array("Y-m-d H:i:s","Y-m-d H:i")){
    $unixTime = strtotime($date);
    if (!$unixTime) return false;  //strtotime转换不对，日期格式显然不对
	 //校验日期的有效性，只要满足其中一个格式就OK
	if (!is_array($formats)) $formats = explode(',',$formats);
    foreach ($formats as $format) {
    	if (date($format, $unixTime) == $date)  return true;
	}
    return false;
}
/*------------------------------------------------------ */
//-- 系统配置读取
/*------------------------------------------------------ */
function settings($key = ''){
	static $settings;
	if (!isset($settings)){
		 $settings = model('app\mainadmin\model\SettingsModel')->getRows();
	}
	if (empty($key) == false){
		return isJson($settings[$key]);		
	}
	return $settings;
}
/*------------------------------------------------------ */
//-- 生成指定长度的随机字符串(包含大写英文字母, 小写英文字母, 数字)
//-- @author yxb
//--
//-- @param int $length 需要生成的字符串的长度
//-- @return string 包含 大小写英文字母 和 数字 的随机字符串
/*------------------------------------------------------ */
function random_str($length,$isupper = false){
    //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
    $arr = $isupper ? array_merge(range('A','H'),range('J','M'),range('P','Z'),range(0,9)) : array_merge(range('A', 'Z'),range(0, 9), range('a', 'z'));
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++){
        $rand = mt_rand(0, $arr_len-1);
        $str.=$arr[$rand];
    }
    return $str;
}