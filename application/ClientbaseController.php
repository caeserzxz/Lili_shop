<?php
namespace app;


/*------------------------------------------------------ */
//-- 前端客户控制器基类
//-- Author: iqgmy
/*------------------------------------------------------ */   
class ClientbaseController extends BaseController{
    /* @var array $loginInfo 登录信息 */
    protected $userInfo;
    public $is_wx = 0;
    /* @var array $allowAllAction 登录验证白名单 */
    protected $allowAllAction = [
        'activity/index/index',// 活动页
        'member/passport/login',// 登录页面
        'member/passport/wxlogin',// 微信登录页面
        'member/passport/register',// 注册页面
        'member/passport/forgetpwd',// 注册页面
        'shop/index',//商城首页
        'shop/goods',//商城商品相关
        'shop/flow/cart',//购物车
        'shop/article',//文章相关
        'publics/payment',//支付相关
        'fightgroup',//拼团相关
        'second',//秒杀相关
        'publics/download',//app下载
    ];

    /* @var array $notLayoutAction 无需全局layout */
    protected $notLayoutAction = [
        'memmber/mpassport/login',// 登录页面
        'memmber/mpassport/logout',//退出登陆
    ];

	/*------------------------------------------------------ */
	//-- 初始化
	/*------------------------------------------------------ */
    public function initialize()
    {
		global $userInfo;
		parent::initialize();
		//判断是否微信访问
        $this->is_wx = session('is_wx');
		if (empty($this->is_wx)){
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
                $this->is_wx = 1;//微信访问
            }else{
                $this->is_wx = -1;
            }
            session('is_wx',$this->is_wx);
        }//end

        //$this->is_wx = 1;//本地测试使用
        $userInfo = $this->getLoginInfo();
        $this->userInfo = $userInfo;
         // 当前路由信息
        $this->getRouteinfo();
        //验证登陆
        $this->checkLogin();
    } 
	/*------------------------------------------------------ */
	//-- 重新额外定义模板地址
	/*------------------------------------------------------ */
    protected function fetch($template = '', $vars = [], $config = []){
		 // 全局layout
        $this->layout();
		return parent::fetch($template, $vars, $config);
    }

	/*------------------------------------------------------ */
	//-- 全局layout模板输出址
	/*------------------------------------------------------ */
    private function layout(){
        // 输出到view
        $this->assign('userInfo',$this->userInfo);//登陆会员信息
		$this->assign('setting',settings());// 系统配置
        $this->assign('navFootList', (new \app\shop\model\NavMenuModel)->getRows(2));//获取底部菜单
		$this->assign([
			'routeUri' => $this->routeUri,  // 当前uri			
		]);
        $this->assign('is_wx',$this->is_wx);//是否微信访问

    }

    /*------------------------------------------------------ */
    //-- 验证登录状态
    /*------------------------------------------------------ */
    protected function checkLogin($isAllow = true){
        if ($this->checkLoginUser() === true){
            return true;
        }
        // 验证当前请求是否在白名单
        if ($isAllow == true && (in_array($this->module, $this->allowAllAction) || in_array($this->module.'/'.$this->routeUri, $this->allowAllAction) || in_array($this->module.'/'.$this->controller,$this->allowAllAction)))
        {
            return true;
        }
        if (empty($this->userInfo)){
		    session('REQUEST_URI',$_SERVER['REQUEST_URI']);
            if ($this->request->isAjax()){
                return $this->error('请登陆后操作.');
            }
            return $this->redirect('member/passport/login');
        }
		
        return true;
    }

    /**
     * 验证登陆用户信息
     * @return bool
     */
    protected function checkLoginUser(){
        //获取邀请码
        $share_token = input('share_token', '', 'trim');
        $se_share_token = session('share_token');
        $isSaveLog = false;//是否记录微信分享日志
        if (empty($share_token) == false && $se_share_token != $share_token) {
            $isSaveLog = true;
            session('share_token', $share_token);
        }
        $dev_openid = input('dev_openid', '', 'trim');
        if (empty($dev_openid) == false){//记录当前小程序的openid，小程序支付时调用
            session('dev_openid', $dev_openid);
        }

        if (empty($this->userInfo) == true) {
            if ($this->is_wx == 1) {//微信网页访问执行
                $wxInfo = session('wxInfo');
                $WeiXinModel = new \app\weixin\model\WeiXinModel();
                if (empty($wxInfo) == true) {
                    if (strpos($_SERVER['HTTP_USER_AGENT'], 'webview') || strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram')) {
                        $access_token = $WeiXinModel->getWxOpenId(true);// 小程序嵌套时静默获取微信用户WxOpenId
                    }else{
                        $access_token = $WeiXinModel->getWxOpenId();// 获取微信用户WxOpenId
                    }
                    $wxInfo = $this->wxAutologin($access_token);
                }

                if ($isSaveLog == true && empty($wxInfo['user_id']) == true) {//未注册，判断是否来自分享,记录分享来源
                    $wxlog['wxuid'] = $wxInfo['wxuid'];
                    $wxlog['user_id'] =  (new \app\member\model\UsersModel)->getShareUser($share_token);
                    $wxlog['share_token'] = $share_token;
                    $wxlog['add_time'] = time();
                    $res = (new \app\weixin\model\WeiXinInviteLogModel)->save($wxlog);

                }

            }
        }
        if (empty($this->userInfo) == false){
            return true;
        }
        return false;
    }
    /*------------------------------------------------------ */
    //-- 微信自动登陆
    /*------------------------------------------------------ */
    protected function wxAutologin($access_token,$isLogin = false)
    {
        global $userInfo;
        $wxInfo = [];
        if (empty($access_token['openid']) == true) {
            return $wxInfo;
        }
        $wxInfo = (new \app\weixin\model\WeiXinUsersModel)->login($access_token);//用户存在进行登陆，否则进行注册操作
        if (empty($wxInfo) == true) {
            return $wxInfo;
        }
        session('wxInfo', $wxInfo);
        if (empty($wxInfo['user_id']) == false) {
            if (settings('weixin_auto_login') == 0 || $isLogin == true){
                $_userInfo = (new \app\member\model\UsersModel)->info($wxInfo['user_id']);
                if ($_userInfo['is_ban'] == 0){
                    session('userId', $wxInfo['user_id']);
                    $this->userInfo = $userInfo = $_userInfo;
                }
            }
        }
        return $wxInfo;
    }
    /*------------------------------------------------------ */
    //-- 退出
    /*------------------------------------------------------ */
    public function logout()
    {
        session('userId', null);
        if ($this->request->isAjax()){
            return $this->success('退出成功.');
        }
        return $this->fetch('member@center/logout');
    }
}
