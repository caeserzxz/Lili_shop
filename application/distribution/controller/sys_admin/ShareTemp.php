<?php
namespace app\distribution\controller\sys_admin;
use app\AdminController;
use app\distribution\model\ShareTempModel;
/*------------------------------------------------------ */
//-- 分享模板
/*------------------------------------------------------ */
class ShareTemp extends AdminController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize(){
        parent::initialize();
        $this->Model = new ShareTempModel();
    }
    /*------------------------------------------------------ */
    //-- 首页
    /*------------------------------------------------------ */
    public function index(){
        $this->getList(true);
        return $this->fetch();
    }
    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false,$is_delete=0) {
        $this->sqlOrder = 'id DESC';
        $data = $this->getPageList($this->Model,$where);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content'] = $this->fetch('list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }
    /*------------------------------------------------------ */
    //-- 详细页调用
    /*------------------------------------------------------ */
    public function asInfo($data) {
        if (empty($data['images']) == false){
            $data['images'] = explode(',',$data['images']);
        }
        return $data;
    }

    /*------------------------------------------------------ */
    //-- 验证数据
    /*------------------------------------------------------ */
    private function checkData($data) {
        if (empty($data['title'])){
            return $this->error('请填写模板标题.');
        }
        if (empty($data['images'])){
            return $this->error('请上传分享图片.');
        }
        if ($data['share_type'] == 1){
            if (empty($data['nick_name'])){
                return $this->error('请填写分享昵称.');
            }
            if (empty($data['avatar'])){
                return $this->error('请填写上传分享头像.');
            }
        }
        $data['images'] = join(',',$data['images']['path']);
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data) {
        $data['add_time'] = time();
        return $this->checkData($data);
    }
    /*------------------------------------------------------ */
    //-- 添加后处理
    /*------------------------------------------------------ */
    public function afterAdd($data) {
        $this->_Log($data['id'],'添加分享模板'.$data['title']);
    }

    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){
        return $this->checkData($data);
    }
    /*------------------------------------------------------ */
    //-- 添加后处理
    /*------------------------------------------------------ */
    public function afterEdit($data) {
        $this->_Log($data['id'],'修改分享模板'.$data['title']);
    }
    /*------------------------------------------------------ */
    //-- 快捷修改
    /*------------------------------------------------------ */
    public function afterAjax($id, $data)
    {
        $info = $this->Model->find($id);
        $this->_Log($id,'快速修改状态：'.$info['title']);
        return $this->success('修改成功');
    }
    /*------------------------------------------------------ */
    //-- 删除
    /*------------------------------------------------------ */
    public function delete()
    {
        $id = input('id/d');
        if ($id < 1){
            return $this->error('传参错误.');
        }
        $data = $this->Model->where('id',$id)->find();
        if (empty($data) == true){
            return $this->error('没有找到相关模板.');
        }
        $res = $this->Model->where('id',$id)->delete();
        if ($res < 1) return $this->error('删除失败，请重试.');
        $this->_Log($data['id'],'删除分享模板:'.$data['title']);
        return $this->success('删除成功.');
    }
    /*------------------------------------------------------ */
    //-- 上传分享图片
    /*------------------------------------------------------ */
    public function uploadShareImg(){
        $result = $this->_upload($_FILES['file'],'share_temp/');
        if ($result['error']) {
            return $this->error('上传失败，请重试.');
        }
        $file_url = str_replace('./','/',$result['info'][0]['savepath'].$result['info'][0]['savename']);
        $data['code'] = 1;
        $data['msg'] = "上传成功";
        $data['image'] = array('thumbnail'=>$file_url,'path'=>$file_url);
        return $this->ajaxReturn($data);
    }
    /*------------------------------------------------------ */
    //-- 删除图片
    /*------------------------------------------------------ */
    public function removeImg() {
        $file = input('post.url','','trim');
        unlink('.'.$file);
        return $this->success('删除成功.');
    }
}
