<?php
namespace app\unique\controller\api;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
/*------------------------------------------------------ */
//-- 首页相关API
/*------------------------------------------------------ */

class Agent extends ApiController
{
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new AgentModel();
    }

    /*------------------------------------------------------ */
    //-- 申请代理
    /*------------------------------------------------------ */
    public function add_agent(){
        $this->checkLogin();//验证登陆
        $RegionModel = new RegionModel();
        $data = input('post.');

        if(empty($data['agent_user_name'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写真实姓名']);
        if(empty($data['agent_mobile'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写代理手机号']);
        if(empty($data['agent_level'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择代理等级']);
        if(empty($data['reason'])) return $this->ajaxReturn(['code' => 0,'msg' => '请填写申请理由']);
        if(empty($data['data_code'])) return $this->ajaxReturn(['code' => 0,'msg' => '请选择代理区域']);

        #获取代理信息
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        #如果存在token,则绑定上下级关系
        if(empty($data['token'])==false){
            $top_info = $this->Model->where('token',$data['token'])->find();
            if(empty($top_info)==false){
                $inArr['agent_pid'] = $top_info['agent_id'];
            }
        }

        #地区处理
        if(empty($data['data_codes'])==false){
            $codes = explode(",",$data['data_codes']);
            #市
            $city = $RegionModel->where('id',$codes[0])->find();
        }

        #组装数据
        $inArr['user_id'] = $this->userInfo['user_id'];
        $inArr['agent_user_name'] = trim($data['agent_user_name'],'');
        $inArr['agent_mobile'] = trim($data['agent_mobile'],'');
        $inArr['agent_level'] = $data['agent_level'];
        $inArr['reason'] = trim($data['reason'],'');
        $inArr['province'] = $codes[0];
        $inArr['city'] = $codes[1];
        $inArr['merger_name'] = $city['merger_name'];
        $inArr['add_time'] = time();
        $inArr['update_time'] = time();
        $inArr['status'] = 0;

        if(empty($info)){
            $res = $this->Model->insert($inArr);
        }else{
            $res = $this->Model->where('agent_id',$info['agent_id'])->update($inArr);
        }

        if($res){
            return $this->ajaxReturn(['code' => 1,'msg' => '申请成功']);
        }else{
            return $this->ajaxReturn(['code' => 0,'msg' => '申请失败']);
        }

    }



}