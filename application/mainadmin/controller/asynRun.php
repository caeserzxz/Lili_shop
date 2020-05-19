<?php
namespace app\mainadmin\controller;

use app\BaseController;

/**
 * 异步执行
 */
class asynRun extends BaseController
{
	/*------------------------------------------------------ */
	//-- 运行
    //-- 异步处理必须返回return true,否则都视为执行错误，返回字符串则作为错误内容记录
    //-- 方法名必须包含asynRun，否则不执行
    //-- 注意传参，不要传递过多的数据
    //-- 调用方法 synRun('shop/orderModel/asynRunPaySuccessEval',['order_id'=>$info['order_id']]);
	/*------------------------------------------------------ */
    public function run()
    {
        ignore_user_abort(true);  //忽略客户端中断
        set_time_limit(600);//最大执行10分钟
        $post = $this->checkPost();
        if ($post === false){
            $this->log("验证失败");
            exit;
        }
        try {
            $modelArr = explode('/',$post['rule']);
            $model = str_replace('/', '\\', "/app/{$modelArr[0]}/model/{$modelArr[1]}");
            unset($post['rule']);
            $fun = $modelArr[2];
            if (strstr($fun,'asynRun') == false){
                exit;//方法名不包含asynRun的不执行
            }
            $res = (new $model)->$fun($post);
            if ($res !== true){
                $this->log("执行失败:".$res);
            }
        } catch (\Exception $e) {
            $this->log("执行异常：".$e->getMessage());
        }
        exit;
    }
    /*------------------------------------------------------ */
    //-- 验证请求
    /*------------------------------------------------------ */
    private function checkPost(){
        $post = $_POST;
        //验证请求是否有效
        $sign = $_POST['sign'];
        if (empty($sign)){
           return false;
        }
        unset($post['sign']);
        if ($_POST['postsigntime'] < time() - 2){
           return false;
        }
        $query = http_build_query($post);
        $_sign = md5($query.config('config.apikey'));
        if ($_sign != $sign){
           return false;
        }
        unset($post['postsigntime']);
        return $post;
    }
    /*------------------------------------------------------ */
    //-- 记录日志
    /*------------------------------------------------------ */
    private function log($str = ''){
        $inArr['rule'] = $_POST['rule'];
        $inArr['param'] = json_encode($_POST,JSON_UNESCAPED_UNICODE);
        $inArr['add_time'] = time();
        $inArr['log_info'] = $str;
        (new \app\mainadmin\model\AsynRunErrorLog)->save($inArr);
    }
}
