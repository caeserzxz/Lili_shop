<?php
namespace app\mainadmin\controller;
use app\AdminController;
use \app\mainadmin\model\MenuListModel;
/**
 * 菜单管理
 * Class Index
 * @package app\store\controller
 */
class Menus extends AdminController
{
    public $isUpLevel = false;
    /*------------------------------------------------------ */
    //-- 优先执行
    /*------------------------------------------------------ */
    public function initialize()
    {
        parent::initialize();
        $this->Model = new MenuListModel();
    }
    /*------------------------------------------------------ */
    //--首页
    /*------------------------------------------------------ */
    public function index()
    {
        $rows = $this->Model->order('level DESC,sort_order DESC')->select()->toArray();
        $list = returnRecArr($rows);
        if (empty($list)) $list[0] = array();
        $this->assign('list', $list);
        return $this->fetch('index');
    }
    /*------------------------------------------------------ */
    //-- 信息页调用
    //-- $data array 自动读取对应的数据
    /*------------------------------------------------------ */
    public function asInfo($data)
    {
        if ($data['id'] < 1){
            $data['pid'] = input('pid',0,'intval');
        }
        $this->assign('menusList', $this->Model->getList());
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加前处理
    /*------------------------------------------------------ */
    public function beforeAdd($data)
    {
        if (empty($data['name'])) return $this->error('菜单名称不能为空.');
        $where[] = ['pid', '=', $data['pid']];
        $where[] = ['name', '=', $data['name']];
        $count = $this->Model->where($where)->count();
        if ($count > 0) return $this->error('已存在相同的菜单名称，不允许重复添加.');
        $data['level'] = 0;
        if ($data['pid'] > 0){
            $data['level'] = $this->Model->where('id',$data['pid'])->value('level');
        }
        $data['level'] += 1;
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 添加后处理
    /*------------------------------------------------------ */
    public function afterAdd($data)
    {
        $this->Model->cleanMemcache();
        return $this->success('添加成功.', url('index'));
    }
    /*------------------------------------------------------ */
    //-- 修改前处理
    /*------------------------------------------------------ */
    public function beforeEdit($data)
    {
        if (empty($data['name'])) return $this->error('菜单名称不能为空.');
        //验证数据是否出现变化
        $dbarr = $this->Model->field(join(',', array_keys($data)))->where('id', $data['id'])->find()->toArray();
        $this->checkUpData($dbarr, $data);
        $where[] = ['id', '<>', $data['id']];
        $where[] = ['name', '=', $data['name']];
        $where[] = ['pid', '=', $data['pid']];
        $count = $this->Model->where($where)->count();
        if ($count > 0) return $this->error('已存在相同的菜单名称，不允许重复添加！');
        $pid = $this->Model->where('id', $data['pid'])->value('pid');
        if ($pid == $data['id']) {
            return $this->error('不能转移到自己的下级.');
        }
        if ($data['pid'] != $dbarr['pid']){
            $this->isUpLevel = true;
            $data['level'] = $this->Model->where('id',$data['pid'])->value('level');
            if (empty($data['level'])){
                $data['level'] = 1;
            }else{
                $data['level'] += 1;
            }
        }
        return $data;
    }
    /*------------------------------------------------------ */
    //-- 修改后处理
    /*------------------------------------------------------ */
    public function afterEdit($data)
    {
        if ($this->isUpLevel == true){
            $this->upLevel($data['id'],$data['level']);
        }
        $this->Model->cleanMemcache();
        return $this->success('修改成功.', url('index'));
    }
    /*------------------------------------------------------ */
    //-- 更新下级菜单
    /*------------------------------------------------------ */
    private function upLevel($pid = 0,$level){
        $ids = $this->Model->where('pid',$pid)->column('id');
        if (empty($ids)) return false;
        foreach ($ids as $id){
            $upDate['level'] = $level + 1;
            $this->Model->where('id',$id)->update($upDate);
            $this->UpLevel($id,$upDate['level']);
        }
        return true;
    }
}
