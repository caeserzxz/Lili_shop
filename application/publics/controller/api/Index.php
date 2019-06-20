<?php
namespace app\publics\controller\api;
use app\ApiController;

/*------------------------------------------------------ */
//-- 公共调用
/*------------------------------------------------------ */
class Index extends ApiController
{
	/*------------------------------------------------------ */
	//-- 获取全站设置
	/*------------------------------------------------------ */
 	public function setting(){
		$key_str = input('key_str', '');
		$data['code'] = 1;
		$data['data'] = settings($key_str);
		return $this->ajaxReturn($data);
	}

   	public function uploaderimages(){
		if ($_FILES['file']){
			$result = $this->_upload($_FILES['file'],'image/');
			if ($result['error']) {
				$this->error($result['info']);
			}
			$savepath = trim($result['info'][0]['savepath'],'.');
			$file_url = $savepath.$result['info'][0]['savename'];
			$data['code'] = 1;
			$data['msg'] = "上传成功";
			$data['savename'] = $result['info'][0]['savename'];
			$data['src'] = $file_url;
			$data['src_thumb'] = $result['info'][0]['savename'];
			return $this->ajaxReturn($data);
		}
	}

    public function get_bank(){
        $config = config('config.');
        $result['bank'] = $config['bank'];
        $result['other_bank'] = $config['other_bank'];
        $all_bank = array_merge($config['bank'],$config['other_bank']);
        $temp_key = array_column($all_bank,'code');  //键值
        $arr = array_combine($temp_key,$all_bank) ;
        $result['code_bank'] = $arr;
        $result['code'] = 1;
        return $this->ajaxReturn($result);
    }
}
