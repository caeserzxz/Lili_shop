<?php
namespace app\store\controller\sys_admin;
use app\AdminController;
use app\store\model\BusinessQrcodeModel;
use app\store\model\UserBusinessModel;

/**
 * 收款码
 * Class Index
 * @package app\store\controller
 */
class Qrcode extends AdminController
{
    //*------------------------------------------------------ */
    //-- 初始化
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new BusinessQrcodeModel();
    }
    /*------------------------------------------------------ */
    //--首页
    /*------------------------------------------------------ */
    public function index()
    {
        $this->getList(true);

        return $this->fetch('index');
    }

    /*------------------------------------------------------ */
    //-- 获取列表
    //-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false) {
        $where = [];
        $where[] = ['is_del','=','0'];
        $search['keyword'] = input('keyword','','trim');
        $search['type'] = input('type','','intval');
        $UserBusinessModel = new UserBusinessModel();
        if (empty($search['keyword']) == false){
            $uids = $UserBusinessModel->where(" business_name LIKE '%".$search['keyword']."%' OR business_id = '".$search['keyword']."'")->column('business_id');
            $uids[] = -1;//增加这个为了以上查询为空时，限制本次主查询失效
            $where[] = ['bussiness_id','in',$uids];
        }
        if($search['type'] == '1'){
            $where[] = ['bussiness_id','=',null];
        }else if($search['type'] == '2'){
            $where[] = ['bussiness_id','>',0];
        }
        $data = $this->getPageList($this->Model, $where);

        foreach ($data['list'] as $key=>$row){
            $row['business'] = $UserBusinessModel->field('business_id,user_id,business_name')->where('business_id','=',$row['bussiness_id'])->find();
            $data['list'][$key] = $row;
        }
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('list')->getContent();
            unset($data['list']);
            return $this->success('','',$data);
        }
        return true;
    }

    /*------------------------------------------------------ */
    //-- 信息页调用
    //-- $data array 自动读取对应的数据
    /*------------------------------------------------------ */
    public function asInfo($data)
    {
        return $data;
    }

    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data) {
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加后调用
    /*------------------------------------------------------ */
    public function afterAdd($data)
    {
        return $this->success('添加成功.',url('index'));
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 修改后调用
    /*------------------------------------------------------ */
    public function afterEdit($data){
        return $this->success('修改成功.',url('index'));
    }

    /**
     * 删除记录
     */
    public function del() {
        $id = input('id', 0, 'intval');
        $res = $this->Model->where('id',$id)->update(['is_del'=>1]);
        if($res){
            return $this->success('删除成功.', 'reload');
        }else{
            return $this->success('删除失败.');
        }
    }

    /*------------------------------------------------------ */
    //-- 增发收款码
    /*------------------------------------------------------ */
    public function addcode(){
        if ($this->request->isPost()){
            $addnum = input('addnum', 0, 'intval');
            if(empty($addnum) || $addnum <= 0){
                return $this->error('请填写大于0的数量');
            }
            $time = time();
            $web_path = config('config.host_path');
            include EXTEND_PATH . 'phpqrcode/phpqrcode.php';//引入PHP QR库文件
            $QRcode = new \phpqrcode\QRcode();//实例化二维码类
            for($i=0;$i<$addnum;$i++){
                $codeid = $this->Model->insertGetId(['add_time'=>$time]);
                $url = $web_path.'/unique/store/qrcode/id/'.$codeid.'.html';//网址或者是文本内容
                $pathname = config('config._upload_') . "./bqrcode";
                if(!is_dir($pathname)) { //若目录不存在则创建之
                    mkdir($pathname);
                }
                $ad = $pathname . "/qrcode_" . $codeid . ".png";
                $ad_url = $web_path . "/upload/bqrcode/qrcode_" . $codeid . ".png";
                $QRcode->png($url, $ad, 3, 4, 2);
                $this->Model->where('id',$codeid)->update(['ecode_url'=>$ad_url]);
            }
            return $this->success('操作成功','reload');
        }
        return $this->fetch();
    }
}
