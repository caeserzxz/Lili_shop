<?php
namespace app\mainadmin\controller;
use app\AdminController;
use app\mainadmin\model\SettingsModel;
/**
 * 设置
 * Class Index
 * @package app\store\controller
 */
class Setting extends AdminController
{
	/*------------------------------------------------------ */
	//-- 优先执行
	/*------------------------------------------------------ */
	public function initialize(){
        parent::initialize();
        $this->Model = new SettingsModel();		
    }
	/*------------------------------------------------------ */
	//-- 首页
	/*------------------------------------------------------ */
    public function index(){
        $nums = [];
		for ($i=1;$i<101;$i++){
            array_push($nums,$i);
        }
        $setting = $this->Model->getRows();
        $profits = unserialize($setting['profits']);

        $this->assign('profits',$profits);
        $this->assign('nums',$nums);
		$this->assign("setting", $this->Model->getRows());
        return $this->fetch();
    }
	/*------------------------------------------------------ */
	//-- 保存配置
	/*------------------------------------------------------ */
    public function save(){
        $set = input('post.setting');
        $profits = [];
        for ($i=1;$i<101;$i++){
            $profits[$i]['trans'] = $set['trans'][$i];
            $profits[$i]['hearten'] = $set['hearten'][$i];
            $profits[$i]['agent'] = $set['agent'][$i];
        }
        unset($set['trans']);
        unset($set['hearten']);
        unset($set['agent']);
        $set['profits'] = serialize($profits);
		$res = $this->Model->editSave($set);
		if ($res == false) return $this->error();
		return $this->success('设置成功.');
    }
    /*------------------------------------------------------ */
    //-- 上传app
    /*------------------------------------------------------ */
    public function uploadapp(){
        if($_FILES['file']['size'] > 100000000){
            $result['code'] = 0;
            $result['msg'] = '最大支持 100M MB上传.';
            return $this->ajaxReturn($result);
        }
        if (strstr($_FILES["file"]['type'],'application') == false) {
            $result['code'] = 0;
            $result['msg'] = '未能识别文件格式，请核实.';
            return $this->ajaxReturn($result);
        }
        $file_type = end(explode('.',$_FILES['file']['name']));
        if (in_array($file_type,['apk']) == false){
            $result['code'] = 0;
            $result['msg'] = '格式不对，只支持 (apk格式)，请核实.';
            return $this->ajaxReturn($result);
        }
        $dir = config('config._upload_').'download/';
        makeDir($dir);
        $file_name = random_str(16).'.'.$file_type;
        move_uploaded_file($_FILES['file']['tmp_name'],$dir.$file_name);
        $result['code'] = 1;
        $result['filename'] = trim($dir.$file_name,'.');
        return $this->ajaxReturn($result);
    }
}
