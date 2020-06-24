<?php
namespace app\member\controller\sys_admin;
use app\AdminController;

use app\unique\model\FeedbackModel;
/**
 * 操作日志
 * Class Index
 * @package app\store\controller
 */
class Feedback extends AdminController
{
	

   //*------------------------------------------------------ */
    //-- 初始化
	/*------------------------------------------------------ */
    public function initialize()
    {	
   		parent::initialize();
		$this->Model = new FeedbackModel(); 
    }
	/*------------------------------------------------------ */
    //--首页
    /*------------------------------------------------------ */
    public function index()
    {
		$this->assign("start_date", date('Y/m/01',strtotime("-1 months")));
        $this->assign("start_date", date('Y/m/d',strtotime("-1 months")));
		$this->assign("end_date",date('Y/m/d'));
		$this->getList(true);		
        return $this->fetch('index');
    }  
	/*------------------------------------------------------ */
    //-- 获取列表
	//-- $runData boolean 是否返回模板
    /*------------------------------------------------------ */
    public function getList($runData = false) {
		$this->search['user_id'] = input('user_id/d');
		$this->search['edit_id'] = input('edit_id/d');
		
		$this->assign("search", $this->search);
		$reportrange = input('reportrange');
		$where = [];
		if (empty($reportrange) == false){
			$dtime = explode('-',$reportrange);
			$where[] = ['create_time','between',[strtotime($dtime[0]),strtotime($dtime[1])+86399]];
		}else{
			$where[] = ['create_time','between',[strtotime("-1 months"),time()]];
		}
		if ($this->search['user_id'] > 0){
			$where[] = ['user_id','=',$this->search['user_id'] ];
		}
        $data = $this->getPageList($this->Model,$where);

		$this->assign("data", $data);
		if ($runData == false){
			$data['content']= $this->fetch('list')->getContent();
			unset($data['list']);
			return $this->success('','',$data);
		}
        return true;
    }
    /*------------------------------------------------------ */
    //-- 添加修改前调用
    /*------------------------------------------------------ */
    public function asInfo($row){
        $row['imgArr'] = unserialize($row['imgurl']);
        
        return $row;
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){  

        if (!$data['reply']) return $this->error('回复内容不能为空！');
        if (strlen($data['reply']) > 500) return $this->error('回复内容过长.');

        $info = $this->Model->where(['id' => $data['id']])->find();
        if (!$info) return $this->error('数据错误');
        $data['update_time'] = time();
        $data['admin_id'] = AUID;

        return $data;
    }
}
