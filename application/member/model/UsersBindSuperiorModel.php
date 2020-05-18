<?php
namespace app\member\model;
use app\BaseModel;
use think\Db;
//*------------------------------------------------------ */
//-- 会员上级汇总表
/*------------------------------------------------------ */
class UsersBindSuperiorModel extends BaseModel
{
	protected $table = 'users_bind_superior';
	public  $pk = 'user_id';

    /**
     * 处理会员上级汇总
     * @param $user_id 操作会员ID
     * @param $pid 上级ID
     * @param $is_edit 是否修改
     */
	public function treat($user_id = 0,$pid = 0,$is_edit = false){
        $data = $this->where('user_id',$user_id)->find();

        if ($pid > 0){
            $superior = $this->where('user_id',$pid)->value('superior');
            if (empty($superior) == true) {
                $superior = $user_id.','.$pid;
            }else {
                $superior = $user_id.','.$superior;
            }
        }else{
            $superior = $user_id;
        }
        $superior_sub = substr($superior,0,800);//以防值过大，转换数组内存溢出
        $superiorArr = explode(',',$superior_sub);
        if (empty($data)){//不存在数据时执行
            $inData['user_id'] = $user_id;
            $inData['pid'] = $pid;
            if (empty($superiorArr[2]) == false){
                $inData['pid_b'] = $superiorArr[2];
            }
            if (empty($superiorArr[3]) == false) {
                $inData['pid_c'] = $superiorArr[3];
            }
            $inData['superior'] = $superior;
            $res = $this->save($inData);
            if ($res < 1){
                return false;
            }
            return true;
        }
        $upData['pid'] = $pid;
        if (empty($superiorArr[2]) == false){
            $upData['pid_b'] = $superiorArr[2];
        }else{
            $upData['pid_b'] = 0;
        }
        if (empty($superiorArr[3]) == false) {
            $upData['pid_c'] = $superiorArr[3];
        }else{
            $upData['pid_c'] = 0;
        }
        $upData['superior'] = $superior;
        $res = $this->where('user_id',$user_id)->update($upData);
        if ($res < 1){
            return false;
        }
        if ($is_edit == false) return true;//非修改不执行以下操作

        $where[] = ['','exp',Db::raw("FIND_IN_SET('".$user_id."',superior)")];
        $where[] = ['user_id','<>',$user_id];
        $allCount = $this->where($where)->count('user_id');

        if ($allCount < 1) return true;//没有下级不执行

        $upDataAll['superior'] = Db::raw("REPLACE(superior,'{$data['superior']}','{$superior}')");
        $res = $this->where($where)->update($upDataAll);
        if ($allCount != $res){
            return false;
        }

        $where = [];
        $where[] = ['pid|pid_b','=',$user_id];
        $rows = $this->where($where)->select();
        foreach ($rows as $row){
            $upDate = [];
            if ($row['pid'] == $user_id) {
                if (empty($superiorArr[1]) == false) {
                    $upDate['pid_b'] = $superiorArr[1];
                } else {
                    $upDate['pid_b'] = 0;
                }
                if (empty($superiorArr[2]) == false) {
                    $upDate['pid_c'] = $superiorArr[2];
                } else {
                    $upDate['pid_c'] = 0;
                }
            }elseif ($row['pid_b'] == $user_id){
                if (empty($superiorArr[1]) == false) {
                    $upDate['pid_c'] = $superiorArr[1];
                } else {
                    $upDate['pid_c'] = 0;
                }
            }else{
                continue;
            }
            $res = $this->where('user_id',$row['user_id'])->update($upDate);
            if ($res < 1){
                return false;
            }
        }
        return true;
    }
}
