<?php
namespace app\agent\controller\sys_admin;
use app\AdminController;
use app\member\model\UsersModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
/**
 * 行业分类
 * Class Index
 * @package app\store\controller
 */
class Agent extends AdminController
{
    //*------------------------------------------------------ */
    //-- 初始化
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new AgentModel();
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

        $keyword = input("keyword");
        if($keyword) $where[] = ['name','=',$keyword];

//        $type = input("type");
//        if($type) $where[] = ['type','=',$type];
        $data = $this->getPageList($this->Model, $where);
        $this->assign("data", $data);
        if ($runData == false){
            $data['content']= $this->fetch('list');
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
        $UsersModel = new UsersModel();
        $store_user = $UsersModel->field('mobile,nick_name')->where('user_id','=',$data['user_id'])->find();
        $data['nick_name'] = ' ' .$data['user_id'].'-'. $store_user['mobile'] . ' - ' . $store_user['nick_name'] . ' ';

        return $data;
    }

    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data) {
        $info = $this->Model->where('user_id',$data['user_id'])->find();
        if(empty($info)==false){
            return $this->error('操作失败:该用户已存在代理申请，不允许重复添加！');
        }

        if($data['status']==1){
            $data['token'] = $this->getToken();
        }
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加后调用
    /*------------------------------------------------------ */
    public function afterAdd($data)
    {
        $UsersModel = new UsersModel();
        if($data['status']==1){
            $map['is_agent'] = 1;
            $UsersModel->where('user_id',$data['user_id'])->update($map);
        }

        $logInfo = '添加代理，代理帐号状态：';
        $logInfo .= $data['is_ban'] == 1 ? '封禁':'正常';
        $this->_Log($data['agent_id'],$logInfo);
        return $this->success('添加成功.',url('index'));
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data){
        #地址处理
        if($data['city']){
            $regionModel = new RegionModel();
            $city_info = $regionModel->where('id',$data['city'])->find();
            if($city_info){
                $data['merger_name'] = $city_info['merger_name'];
            }
        }
        if($data['status']==1){
            $info = $this->Model->where('agent_id',$data['agent_id'])->find();
            if(empty($info['token'])){
                #生成token
                $data['token'] = $this->getToken();
            }
        }
        $data['update_time'] = time();
        $logInfo = '修改代理信息，状态：'.($data['is_ban'] == 1 ? '封禁':'正常');
        $this->_log($data['agent_id'], $logInfo );
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 修改后调用
    /*------------------------------------------------------ */
    public function afterEdit($data){
        $logInfo = '代理商帐号状态：';
        #更新users表的代理状态
        $UsersModel = new UsersModel();
        if($data['status']==1){
            $map['is_agent'] = 1;
            $UsersModel->where('user_id',$data['user_id'])->update($map);
        }
        $this->_Log($data['agent_id'],$logInfo);
        return $this->success('修改成功.',url('index'));
    }

    /*------------------------------------------------------ */
    //-- 地址区域获取
    /*------------------------------------------------------ */
    public function getRegion($province,$city,$district){
        $regionModel = new RegionModel();
        $province_name = $regionModel->where('id',$province)->value('name');
        $city_name = $regionModel->where('id',$city)->value('name');
        $district_name = $regionModel->where('id',$district)->value('name');
        $merger_name = "$province_name $city_name $district_name";
        return $merger_name;
    }

    /*------------------------------------------------------ */
    //-- 生成用户唯一标识,主要用于分享后身份识别
    /*------------------------------------------------------ */
    public function getToken()
    {
        $token = random_str(6);
        $count = $this->Model->where('token', $token)->count('agent_id');
        if ($count >= 1) return $this->getToken();
        return $token;
    }
}
