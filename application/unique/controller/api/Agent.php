<?php
namespace app\unique\controller\api;
use think\Db;
use think\facade\Cache;
use app\ApiController;
use app\store\model\UserBusinessModel;
use app\mainadmin\model\RegionModel;
use app\agent\model\AgentModel;
use app\unique\model\PayRecordModel;
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

    /*------------------------------------------------------ */
    //-- 获取代理的商家
    /*------------------------------------------------------ */
    public function getmyteam(){
        $limit = 12;
        $limit_start = input('limit_start', 0, 'trim');
        $UserBusinessModel = new UserBusinessModel();
        $user_id = $this->userInfo['user_id'];
        $info = $this->Model->where('user_id',$user_id)->find();
        $where[] = ['agent_id','=',$info['agent_id']];
        $rows = $UserBusinessModel->field("*,FROM_UNIXTIME(bind_agent_time, '%Y-%m-%d' ) as _time")->where($where)->order('business_id DESC')->limit($limit_start,$limit)->select()->toArray();
        if(count($rows) < $limit){
            $return['is_over'] = 1;
        }
        $return['list'] = $rows;
        $return['limit_start'] = $limit_start + $limit;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取代理的商家总数
    /*------------------------------------------------------ */
    public function getteamnum(){
        $UserBusinessModel = new UserBusinessModel();
        $info = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        $where[] = ['agent_id','=',$info['agent_id']];
        $allnum = $UserBusinessModel->where($where)->count();
        $return['allnum'] = $allnum;
        $return['code'] = 1;
        return $this->ajaxReturn($return);
    }

    /*------------------------------------------------------ */
    //-- 获取代理所有商家的本月业绩 今日业绩
    /*------------------------------------------------------ */
    public function getAchievement(){
        $PayRecordModel = new PayRecordModel();
        $UserBusinessModel = new UserBusinessModel();
        $user_id = $this->userInfo['user_id'];
        #代理信息
        $agent = $this->Model->where('user_id',$user_id)->find();
        #符合条件的商家
        $where[] = ['agent_id','=',$agent['agent_id']];
        $business = $UserBusinessModel->where($where)->column('business_id');
//        $agent_sales_count = Cache::get('agent_sales_mkey' . $agent['agent_id']);
        if(empty($agent_sales_count)){
            $arr = [];
            #本月业绩
            $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
            $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $where1[] = ['business_id','in',$business];
            $where1[] = ['status','=',1];
            $where1[] = ['add_time', 'between', array($beginThismonth, $endThismonth)];

            $this_month_count =  $PayRecordModel->where($where1)->sum('amount');

            #今日业绩
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where2[] = ['business_id','in',$business];
            $where2[] = ['status','=',1];
            $where2[] = ['add_time', 'between', array($beginToday, $endToday)];
            $today_count =  $PayRecordModel->where($where2)->sum('amount');
            #更新时间
            $arr['count_time'] = date('Y-m-d',time());
            $arr['this_month_count'] = sprintf("%.2f",$this_month_count);
            $arr['today_count'] = sprintf("%.2f",$today_count);
            $agent_sales_count = $arr;
//            Cache::set('agent_sales_mkey' . $agent['agent_id'], $arr, 600);
        }

        return $agent_sales_count;
    }

    /*------------------------------------------------------ */
    //-- 获取代理下面所有商家业绩
    /*------------------------------------------------------ */
    public function getSales()
    {
        $date = input('date', date('Y-m'), 'trim');
        $date1 = strtotime($date.'-1 '." 00:00:00");
        $date2 = strtotime(date('Y-m-t', $date1)) + 86399;
        $return['date'] = $date;
        $return['code'] = 1;
        $PayRecordModel = new PayRecordModel();
        $UserBusinessModel = new UserBusinessModel();
        #代理信息
        $agent = $this->Model->where('user_id',$this->userInfo['user_id'])->find();
        #符合条件的商家
        $where[] = ['agent_id','=',$agent['agent_id']];
        $business = $UserBusinessModel->where($where)->column('business_id');

        $where = [];
        $where[] = ['business_id', 'in', $business];
        $where[] = ['add_time', 'between', array($date1, $date2)];
        //SELECT sum(amount) all_amount,FROM_UNIXTIME(add_time,"%Y.%m.%d") addtime FROM `users_pay_record` group by addtime order by addtime asc
        $rows = $PayRecordModel->field('sum(amount) all_amount,FROM_UNIXTIME(add_time,"%Y.%m.%d") addtime')->where($where)->group('addtime')->order('addtime ASC')->select()->toArray();
        $return['all_amount'] = 0;
        foreach ($rows as $key => $row) {
            $return['all_amount'] += $row['all_amount'];
            $return['list'][] = $row;
        }

        return $this->ajaxReturn($return);
    }


}