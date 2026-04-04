<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
use \think\Session;

use \app\cccf\model\Auth;
use \app\cccf\model\Token;
use \think\Cache;

/**
 * 用户登录相关
 *
 * @author netknave
 *
 */
class User extends Common
{
    protected $default_avatar = "";

    const AUTH_JZLZ_LOGIN = "JZLZ_LOGIN";
    const AUTH_JZLZ_ADMIN_LOGIN = "JZLZ_ADMIN_LOGIN";

    const PASSWORD_SALT = "_RLF2020";

    const PASSWORD_DEFAULT = "123456";

    // 申请解锁类型
    const APPLYTYPE_CONTACT = "contact";
    const APPLYTYPE_TOOLS = "tool";
    const allowType = ['contact', 'tool']; // 允许的类型

    // 判断哪些操作需要刷新时间
    const updateAction = ["save", "add", "del","update","updateMyinfo","applyauth"];


    protected $authmodel;
    public function __construct()
    {
        $this->authmodel = new Auth();
        parent::__construct();
    }


    /**
     * user相关的入口程序
     *
     * @param string $action
     * @param array $data
     * @return void
     */
    public function index($action = '', $data = [], $page = 1, $pagesize = 100)
    {
        $rt = $this->_rt();
        $isvoid = $data['isvoid'] ?? "";
        $key = $data['keyword'] ?? "";
        $page = $data['page'] ?? $page;
        $pagesize = $data['pagesize'] ?? ($data['limit'] ?? $pagesize);
        $dwid = $data['dwid'] ?? 0;
        switch ($action) {
            case "login": //登录
                $username = $data['username'] ?? ''; //php7 的写法
                $password = $data['password'] ?? '';
                $mobile = $data['mobile'] ?? '';
                $dwid = $data['dwid'] ?? 0;
                $rt = $this->login($dwid, $username, $password, $mobile);
                break;

            case "logout": //退出
                $this->logout();
                $rt['code'] = self::CODE_SUCCESS;
                $rt['message'] = "您已退出登录";
                break;
            case "info": //我的信息
                //todo


                if ($this->userid == 0) {
                    $rt['message'] = "您未登录";
                } else {
                    $userinfo = $this->getUserinfo($this->userid);
                    if ($userinfo) {
                        $rt['data'] = $userinfo;
                        $rt['code'] = self::CODE_SUCCESS;
                        $rt['message'] = "OK";
                    } else {
                        $rt['message'] = "您未登录";
                    }
                }

                break;
            case "userinfo": //他人信息
                // todo
                $userid = $data['userid'] ?? 0;
                if ($userid == 0) {
                    $rt['message'] = "用户ID不能为空";
                    return $rt;
                }
                $userinfo = $this->getUserinfo($userid);
                if ($userinfo) {
                    $rt['data'] = $userinfo;
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "用户不存在或您没有权限查询";
                }
                break;

            case "update": //修改个人信息
                // todo
                $userid = $data['userid'] ?? 0;
                if ($userid == 0) {
                    $rt['message'] = "用户ID无效";
                    return $rt;
                }
                $rt = $this->saveUser($userid, $data);
                break;

            case "add": //增加用户
                $rt = $this->saveUser(0, $data);
                break;
            case "list": //用户列表
                $deptcode = $data['deptcode'] ?? [];
                $myusername = $data['myusername'] ?? [];

                $userlist = $this->getUserList($key, $deptcode, $isvoid, $page, $pagesize,$myusername);
                // dump($userlist);


                if ($userlist) {
                    $data = [];
                    $data['items'] = $userlist['items'];
                    $data['total'] = $userlist['total'];
                    $rt['data'] = $data;
                    $rt['total'] = $userlist['total'];
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "数据为空";
                }
                break;
            case 'del': // 删除用户
                $id = $data['userid'] ?? 0;
                $rt = $this->delUser($id);
                break;
            case 'newcode': //获取新用户代码
                $code = $this->getnewcode();
                $rt['code'] = self::CODE_SUCCESS;
                $rt['data'] = $code;
                break;

            case 'userroom':
                $username = $data['username'] ?? '';
                $rt['data'] = $this->getUserRoom($username);
                $rt['code'] = self::CODE_SUCCESS;
                $rt['message'] = "OK";

                break;
            case 'joblevel': // 获取领导级别列表
                $rt = $this->getJobLevelList();
                break;
            case 'jobauth': // 获取编制列表
                $rt = $this->getJobAuthList();
                break;
            case 'jobpost': // 获取岗位列表
                $rt = $this->getJobPostList();
                break;
            case 'resetPwd':
                $userid = $data['userid'] ?? 0;
                $rt = $this->resetPwd($userid);
                break;
            case 'changePwd': // 修改密码
                $userid = $this->dwid;
                $oldpass = $data['oldpass'] ?? '';
                $newpass1 = $data['newpass1'] ?? '';
                $newpass2 = $data['newpass2'] ?? '';

                $rt = $this->changePassword($userid, $oldpass, $newpass1, $newpass2);
                break;
            case 'updateMyinfo': // 更新我的信息
                $rt = $this->updateMyInfo($data);
                break;
            case 'applyauth': // 申请授权
                $type = $data['type'] ?? '';
                $pass = $data['pass'] ?? '';
                $rt = $this->applyAuth($type, $pass);
                break;
            case 'checkauth': // 检查授权
                $type = $data['type'] ?? '';
                $rt = $this->checkSafeAuth($type);
                break;
            case 'status': // 判断当前状态
                $rt = $this->checkLoginStatus();
                break;

            default:
                $rt['message'] = "操作【/user/{$action}】并不存在！";
        }

        if($rt['code'] == self::CODE_SUCCESS && in_array($action,self::updateAction)){
            $this->updateLoginStatus($this->token);
        }


        return $rt;
    }


    /**
     * 根据用户名获取使用的庭室
     *
     * @param string $username
     * @return void
     */
    protected function getUserRoom($username = '')
    {
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        $where['username'] = $username;
        $field = "useroom";
        $data = $this->getdb("user")->where($where)->field($field)->find();
        if (!$data) {
            return "";
        } else {
            return $data['useroom'];
        }
    }

    /**
     * 使用手机号码+校验码登录
     *
     * @param string $mobile 手机号码
     * @param string $code 校验码
     * @param number $dwid 单位代码
     * @return void
     */
    public function login_by_mobile($mobile = '', $code = '', $dwid = 0, $adminmode = false)
    {
        $rt = $this->_rt();

        //判断手机号码是否合法，是否存在用户
        $check = $this->checkMobile($mobile, true);
        if ($check['code'] != self::CODE_SUCCESS) {
            return $check;
        }

        $check2 = $this->checkSmsCode($mobile, $code);
        if ($check2['code'] != self::CODE_SUCCESS) {
            return $check2;
        }


        // 开始登录操作
        $where = [];
        $where['mobile'] = $mobile;
        $where['isvoid&isdel'] = 0;
        if ($dwid != 0) {
            $where['dwid'] = $dwid;
        }

        $db = $this->getdb("user");




        // 先假设只有一个手机号码。获取用户登录信息，并生成token，并登录系统保存session
        $order = "userid desc";
        $field = "userid,dwid,deptcode,usercode,username,usergroup,joblevel,mobile,userlabel,logintime,loginip,note";
        $user = $db->where($where)->field($field)->order($order)->find();

        if (!$user) {
            $rt['message'] = "用户不存在!";
            return $rt;
        }
        $userid = $user['userid'];
        $user = $this->getUserinfo($userid);


        //获取到用户信息之后，才能判断用户是否有权限进行后台登录
        //判断权限
        $rule = $adminmode ? self::AUTH_JZLZ_ADMIN_LOGIN : self::AUTH_JZLZ_LOGIN;
        $token = $this->getToken($userid);
        $user['token'] = $token;
        //填充用户信息
        $this->setUserSession($user);
        $this->authmodel->setToken($token);
        //必须先设置token



        if (!$this->authmodel->checkAuth($rule)) {

            $rt['message'] = "您的用户没有权限登录本系统，请联系系统管理员";
            $this->logout();
            return $rt;
        }







        //登录成功之后，将code设置为已使用，并加上使用时间

        //$this->clearMobileCode($mobile);


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $user;
        return $rt;
    }

    /**
     * 获取当前用户信息
     *
     * @return void
     */
    public function getUserinfo($id)
    {
        $where = [];
        $where['userid'] = $id;
        $where['isvoid&isdel'] = 0;
        // $where['dwid'] = $this->dwid;


        $field = "userid,dwid,deptcode,usercode,username,usergroup,joblevel,jobpost,jobauth,zzmm,licenseplate,gender,mobile,mobile2,telphone,telphone2,useroom,userlabel,logintime,loginip,note,avatar,photo";
        $userinfo = $this->getdb("user")->field($field)->where($where)->find();


        //添加部门信息
        // $dept = $this->getDeptCodeList();
        // $dept = $this->getDeptCodeList($userinfo['dwid']);
        $dept = $this->getDeptIdList($userinfo['dwid']);
        if ($userinfo) {
            // $dwid = $userinfo['dwid'];
            // $dwinfo = $this->getDwinfo($dwid);
            // $userinfo['deptname'] = $dept[$userinfo['deptcode']]['name'];
            // $userinfo['dwname'] = $dwinfo['dwname'] ?? "";
            $dwid = $userinfo['dwid'];
            $dwinfo = $this->getDwinfo($dwid);

            $code = "id_" . $userinfo['deptcode'];
            $deptinfo = $dept[$code] ?? [];
            // halt($deptinfo);
            $userinfo['deptid'] = $deptinfo['deptid'] ?? 0;
            $userinfo['deptname'] = $deptinfo['fullname'] ?? "";
            $userinfo['dwname'] = $dwinfo['dwname'] ?? "";
            $userinfo['dwcode'] = $dwinfo['dwcode'] ?? "";
            $userinfo['roles'] = $this->getRole($id);
            $userinfo['userlabel'] = explode(",", $userinfo['userlabel']);
        }


        $sysModel = new System();
        $module = $sysModel->getModule($userinfo['dwid']);
        $userinfo['module'] = $module['module'];
        $userinfo['modulelist'] = $module['modulelist'];

        $_config = $this->getConfiginfo($userinfo['dwid']);
        $userinfo['ahmc'] = $_config['ahmc'];
        return $userinfo;
    }
    /**
     * 获取单位信息
     *
     * @param integer $dwid
     * @return void
     */
    protected function getDwinfo($dwid = 0)
    {
        $data = [];
        $where = [];
        $where['dwid'] = $dwid;
        $where['isvoid&isdel'] = 0;
        $info = $this->getdb("dwlist")->where($where)->cache()->find();
        return $info;
    }

        /**
     * 获配置信息
     *
     * @param integer $dwid
     * @return void
     */
    protected function getConfiginfo($dwid = '')
    {
        $data = [];
        $where = [];
        $where['dwid'] = $dwid;
        $info = $this->getdb("config")->where($where)->find();
        return $info;
    }


    /**
     * 将用户的一些信息放到内存中
     *
     * @param array $userinfo
     * @return void
     */
    protected function setUserSession($userinfo = [])
    {
        $userid = $userinfo['userid'] ?? 0;
        $dwid = $userinfo['dwid'] ?? 0;
        $token = $userinfo['token'] ?? "";
        $dwcode = $userinfo['dwcode'] ?? "";
        $this->token = $token;

        // dump($token);
        // exit();
        $model = $this->tokenModel;
        $model->setData($this->token, self::SESSION_USERID, $userid);
        $model->setData($this->token, self::SESSION_USERINFO, $userinfo);
        $model->setData($this->token, self::SESSION_DWID, $dwid);
        $model->setData($this->token, self::SESSION_DWCODE, $dwcode);

        // Session::set(self::SESSION_USERID, $userid);
        // Session::set(self::SESSION_USERINFO, $userinfo);
        // Session::set(self::SESSION_DWID, $dwid);

        $this->updateLoginStatus($token); // 登录之后刷新操作
    }
    /**
     * 清除session
     *
     * @return void
     */
    protected function clearUserSession()
    {
        $token = $this->token;
        $model = $this->tokenModel;
        $model->removeData($this->token, self::SESSION_USERID);
        $model->removeData($this->token, self::SESSION_USERINFO);
        $model->removeData($this->token, self::SESSION_DWID);
        Session::delete(self::SESSION_USERID);
        Session::delete(self::SESSION_USERINFO);
        Session::delete(self::SESSION_DWID);

        //删除所有的key
        $model->removeToken($this->token);
        $this->removeToken($token);
    }
    /**
     * 移除token
     *
     * @param string $token
     * @return void
     */
    protected function removeToken($token = '')
    {
        $where = [];
        $where['token'] = $token;
        $this->getdb("token")->where($where)->delete();
    }
    /**
     * 清除某手机的校验码
     *
     * @param string $mobile
     * @param string $code
     * @return void
     */
    // protected function clearMobileCode($mobile = '')
    // {
    //     $where = [];
    //     $where['mobile'] = $mobile;
    //     $where['isvoid&isdel'] = 0;
    //     $where['isused'] = 0;

    //     $data = [];
    //     $data['updatetime'] = getNowTime();
    //     $data['isused'] = 1;
    //     $data['usedtime'] = time();
    //     $num = $this->getdb("smsverify")->where($where)->update($data);
    //     return $num;
    // }
    /**
     * 检查并判断校验码是否可用
     *
     * @param string $mobile
     * @param string $code
     * @return void
     */
    protected function checkSmsCode($mobile = '', $code = '')
    {
        $rt = $this->_rt();
        if (empty($code)) {
            $rt['message'] = "校验码不能为空！";
            return $rt;
        }
        // 1.判断有没有有效的时间
        $where = [];
        $where['mobile'] = $mobile;
        $where['expiretime'] = ['gt', time()];
        $where['isused'] = 0;
        $where['isvoid&isdel'] = 0;
        // 只取最新的一行
        $order = "sendtime desc";
        $data = $this->getdb("smsverify")->where($where)->order($order)->find();
        //判断数据是否存在
        if (!$data) {
            $rt['message'] = "校验码不存在";
            return $rt;
        }
        // 判断校验码是否正确
        if ($code != $data['verifycode']) {
            $rt['message'] = "校验码不正确";
            return $rt;
        }
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        return $rt;
    }

    /**
     * 检查手机号码是否正确
     *
     * @param string $mobile 手机号码
     * @param boolean $checksend 检查是否发送过，默认不检查
     * @return void
     */
    protected function checkMobile($mobile = '', $checkuser = false)
    {
        $rt = $this->_rt();
        if (empty($mobile)) {
            $rt['message'] = "手机号码不能空";
            return $rt;
        }
        if (strlen($mobile) != 11) {
            $rt['message'] = "手机长度不正确";
            return $rt;
        }
        //判断号码是否是1开头，并且长度为11位的
        $g = "/^1\d{10}$/";
        if (!preg_match($g, $mobile)) {
            $rt['message'] = "手机格式不正确";
            return $rt;
        }

        if (!$checkuser) {
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = "OK";
            return $rt;
        }


        //开始判断用户是否存在

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['mobile'] = $mobile;

        $usernum = $this->getdb("user")->where($where)->count();
        if ($usernum < 1) {
            $rt['message'] = "手机号码不存在";
            return $rt;
        }


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        return $rt;
    }
    /**
     * 登录系统
     * @param  integer $dwid     [单位ID]
     * @param  string  $username [用户名]
     * @param  string  $password [前端传过来的密码，此密码已通过 md5(str+"PASSWORD_SALT") 加密处理 ]
     * @return [type]            [登录状态]
     */
    protected function login($dwid = 0, $username = '', $password = '', $mobile = '')
    {
        $rt = $this->_rt();
        if ((empty($username) && empty($mobile)) || empty($password)) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = ["token" => ""];
            $rt['message'] = "手机号码及密码不能为空！";

            return $rt;
        }
        $field = ["*"];

        $where = [];
        $where['isdel'] = 0;
        $where['dwid'] = $dwid;
        if (empty($mobile)) {
            $where['username|bankcard|cardno|usercode|mobile'] = $username;
        } else {
            $where['mobile'] = $mobile;
        }


        $field = "userid,dwid,deptcode,usercode,username,userpass,salt,avatar,photo,usergroup,joblevel,mobile,mobile2,telphone,telphone2,jobauth,jobpost,zzmm,licenseplate,userlabel,logintime,loginip,note,isvoid";

        $userdata = $this->getdb("user")->where($where)->field($field)->find();
        // dump($userdata);
        //不排除可能有多条记录的可能性，比如说usercode和cardno重复了
        if (!$userdata) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = ["token" => ""];
            if (empty($mobile) && !empty($username)) {
                $rt['message'] = "用户[$username]不存在";
            }
            if (!empty($mobile)) {
                $rt['message'] = "手机号码[$mobile]不存在";
            }

            return $rt;
        }

        if ($userdata['isvoid'] == 1) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = ["token" => ""];
            $rt['message'] = "用户[$username]已停用";
            return $rt;
        }


        $newpass = $this->genPassword($password, $userdata['salt']);

        if ($newpass != $userdata['userpass']) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = ["token" => ""];
            $rt['message'] = "用户密码不正确";
            return $rt;
        }

        //登录成功，以后需要记录IP地址和登录时间


        $userid = $userdata['userid'];
        $user = $this->getUserinfo($userid);


        //获取到用户信息之后，才能判断用户是否有权限进行后台登录
        //判断权限
        $token = $this->getToken($userid);
        $user['token'] = $token;
        //填充用户信息
        $this->token = $token;
        $this->authmodel->setToken($token);
        $this->setUserSession($user);

        //$this->loginLog($userid);

        //必须先设置token


        $rule = '';
        // if(!$this->authmodel->checkAuth($rule)){

        //     $rt['message'] = "您的用户没有权限登录本系统，请联系系统管理员";
        //     $this->logout();
        //     return $rt;
        // }



        session("userid",$userdata['userid']);
        session("dwid",$userdata['dwid']);
        $userid = $userdata['userid'];


        $data['code']=parent::CODE_SUCCESS;
        $token = $this->getToken();
        session("token",$token);
        $data['data']=["token"=>$token];
        $data['message'] = "登录成功";

            //获取用户ID以及记录session

        session("token",$token);

        $this->log("登录系统");
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $user;

        $this->updateLoginStatus($this->token);

        return $rt;
        // return $data;

    }
    /**
     * 注销登录
     */
    public function logout()
    {
        $this->clearUserSession();
    }

    /**
     * 生成随机的token值
     * @return [type] [description]
     */
    public function genToken()
    {

        $token = "";

        $token = md5("_wilson_" . uniqid() . "_token_");


        return $token;
    }

    /**
     * 获取当前用户的token值
     * @return [type] [description]
     */
    public function getToken($uid = 0)
    {

        $where = [];
        // $uid = $this->userid;
        if (empty($uid)) {
            return null;
        }
        $where['userid'] = $uid;
        $expiretime = 120; //默认120分钟


        $expiretime = time() + $expiretime * 60;

        $data = $this->getdb("token")->where($where)->find();
        $token = "";
        if ($data) {
            //创建token

            $expiretime = $expiretime;
            $token = $data['token'];
            $d = [];
            $d['expiretime'] = $expiretime;

            $this->getdb("token")->where($where)->update($d);
            return $token;
        } else {
            $d = [];
            $token = $this->genToken();
            $d['expiretime'] = $expiretime;
            $d['createtime'] = time();
            $d['token'] = $token;
            $d['userid'] = $uid;
            $this->getdb("token")->insert($d);
            return $token;
        }

        return $token;
    }

    /**
     * 根据token获取用户基本信息，用于登录
     * @param  string $token [description]
     * @return [type]        [description]
     */
    public function getTokenUser($token = '')
    {
        if (empty($token)) {
            $token = $this->token;
        }
        if (empty($token)) {
            $d = [];
            $d['name'] = "";
            $d['avatar'] = "";
            $d['introduction'] = "";
            $d['roles'] = [""];
            $d['module'] = [""];
            return $d;
        }
        $where = [];
        $where['expiretime'] = ['gt', time()];
        $where['token'] = $token;

        $data = $this->getdb("token")->where($where)->find();
        if ($data) {
            $userinfo = $this->getUser($data['userid']);
            if (empty($userinfo['avatar'])) {
                $userinfo['avatar'] = $this->default_avatar;
            }
            $d = [];
            $d['name'] = $userinfo['username'];
            $d['avatar'] = $userinfo['avatar'];
            $d['introduction'] = $userinfo['note'];



            $d['roles'] = $userinfo['roles'];
            return $d;
        } else {

            $d = [];
            $d['name'] = "";
            $d['avatar'] = "";
            $d['introduction'] = "";
            $d['roles'] = [""];
            $d['module'] = [""];
            return $d;
        }
    }

    /**
     * 获取用户列表
     */
    public function getUserList($key = '', $deptcode = '', $isvoid = '', $page = 1, $pagesize = 20,$myusername='')
    {
        $where = [];
        $where['isdel'] = 0;
        $where['dwid'] = $this->dwid;
        if (!empty($myusername) && strlen($myusername) > 1) {
            $where['username']=$myusername;
        }
        if (!empty($key) && strlen($key) > 2) {
            $where['usercode|username|bankcard|cardno|mobile|note|joblevel|jobpost|jobauth|useroom'] = ['like', '%' . $key . '%'];
        }

        if (is_string($deptcode)) {
            if ($deptcode != '') {
                $deptcode = explode(",", $deptcode);
                $where['deptcode'] = ['in', $deptcode];
            }
        }

        // 获取带子节点的deptcode
        if (is_array($deptcode) && count($deptcode) > 0) {
            // 获取二级目录
            $deptcode = $this->getAllDeptId($deptcode);
        }

        if (is_array($deptcode) && count($deptcode) > 0) {
            $where['deptcode'] = ['in', $deptcode];
        }

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        $order = "rank";
        $field = ["userid", "dwid", "usercode", "username", "gender", "deptcode", "joblevel", "avatar", "photo", "cardno", "mobile", "mobile2", "isvoid", "usergroup", "userlabel", "userpass", "telphone", "telphone2", "useroom", "jobauth", "jobpost", "zzmm", "licenseplate", "note"];
        $num = $this->getdb('user')->where($where)->count();
        $data = $this->getdb('user')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();

        //添加部门信息
        // $dept = $this->getDeptCodeList();
        // $dept = $this->getDeptIdList();
        $dept = $this->getDeptIdList($this->dwid);
        // halt($dept);
        foreach ($data as &$row) {

            $row['userpass'] = '';
            $row['userpass2'] = '';
            if (trim($row['userlabel']) != '') {
                $row['userlabel'] = explode(",", $row['userlabel']);
            } else {
                $row['userlabel'] = [];
            }

            $group = explode(",", $row['usergroup']);
            $groups = [];
            foreach ($group as &$g) {
                if (!empty($g)) {
                    $groups[] = $g + 0;
                }
            }
            $row['usergroup'] = $groups;

            $code = $row['deptcode'];
            $code = "id_" . $code;
            if (isset($dept[$code]['fullname'])) {
                $row['deptname'] = $dept[$code]['fullname'];
            } else {
                $row['deptname'] = "";
            }
        }


        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;

        return $d;
    }
    /**
     * 获取用户列表
     */
    protected function getUserList_bak($key = '', $deptcode = '', $isvoid = '', $page = 1, $pagesize = 20)
    {
        $where = [];
        $where['isdel'] = 0;
        $where['dwid'] = $this->dwid;
        if (!empty($key) && strlen($key) > 2) {
            $where['usercode|username|bankcard|cardno|mobile'] = ['like', '%' . $key . '%'];
        }

        if (is_string($deptcode)) {
            if ($deptcode != '') {
                $deptcode = explode(",", $deptcode);
                $where['deptcode'] = ['in', $deptcode];
            }
        }
        if (is_array($deptcode) && count($deptcode) > 0) {
            $where['deptcode'] = ['in', $deptcode];
        }

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        $order = "rank";
        $field = ["userid", "dwid", "usercode", "username", "deptcode", "joblevel", "cardno", "mobile", "isvoid", "usergroup", "userlabel"];
        $num = $this->getdb('user')->where($where)->count();
        $data = $this->getdb('user')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();

        // $this->addDeptName($data);

        //添加部门信息
        $dept = $this->getDeptIdList($this->dwid);
        halt($dept);
        foreach ($data as &$row) {
            $group = explode(",", $row['usergroup']);
            $groups = [];
            foreach ($group as &$g) {
                if (!empty($g)) {
                    $groups[] = $g + 0;
                }
            }
            $row['usergroup'] = $groups;

            $code = 'id_' . $row['deptcode'];
            if (isset($dept[$code]['fullname'])) {
                $row['deptname'] = $dept[$code]['fullname'];
            } else {
                $row['deptname'] = "";
            }
        }


        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;

        return $d;
    }



    /**
     * 生成索引键为部门代码的部门列表
     *
     * @return void
     */
    public function getDeptCodeList($dwid = 0)
    {
        $data = [];
        $deptlist = $this->getDeptList($dwid);
        $deptlist = $deptlist['data']['items'];

        foreach ($deptlist as $r) {
            $data[$r['code']] = $r;
        }
        return $data;
    }

    /**
     * 生成索引键为部门代码的部门列表
     *
     * @return void
     */
    public function getDeptIdList($dwid = 0)
    {
        $data = [];
        // $deptlist = $this->getDeptList($dwid);
        // $deptlist = $deptlist['data']['items'];

        // 改成读取tree
        $model = new Dept();
        $deptlist = $model->getList_Tree();

        $deptlist = $deptlist['data']['list'];
        // halt($deptlist);
        foreach ($deptlist as $r) {
            $id = "id_" . $r['deptid'];
            $data[$id] = $r;
        }
        return $data;
    }

    /**
     * 获取部门列表
     * @param  integer $dwid [description]
     * @param  array   $w    [description]
     * @return [type]        [description]
     */
    protected function getDeptList($dwid = 0, $w = "", $isvoid = 0, $page = 1, $pagesize = 100, $order = [])
    {
        if ($dwid == 0) {
            $dwid = $this->dwid;
        }
        $field = ["deptid", "deptcode" => "code", "deptname" => "name", "isvoid"];
        $order = "rank";
        $where = [];
        $where['dwid'] = $dwid;
        $where['isdel'] = 0;
        if (!empty($isvoid)) {
            $where['isvoid'] = $isvoid;
        }
        $num = $this->getdb("dept")->where($where)->count();
        $data = $this->getdb("dept")->field($field)->where($where)->order($order)->select();


        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;

        return $rt;
    }

    /**
     * 获取用户信息
     * @param  [type] $id [用户ID，对应 userid]
     * @return [type]     [description]
     */
    public function getUser($userid)
    {
        if (empty($userid)) {
            $userid = session("userid");
        }
        $data = [];

        if (empty($userid)) {
            $data['code'] = parent::CODE_ERROR;
            $data['data'] = [];
            $data['message'] = "用户ID不能为空";
            return $data;
        }

        $field = "*";
        $where = array();
        $where['isdel'] = 0;
        $where['userid'] = $userid;
        $user = $this->getdb('user')->where($where)->field($field)->find();

        if (!$user) {
            return null;
        }

        //获取roles

        $roles = $this->getRole($user['userid']);
        $user['roles'] = $roles;

        return $user;
    }


    /**
     * 计算加密后的密码
     *
     * @param string $password
     * @param string $salt
     * @return string
     */

    public function genPassword($password = '', $salt = '')
    {
        //密码不加密
        return $password;
        
        $key = "_wilson_";
        $str = md5($password) . $key . $salt;
        $str = md5($str);
        // 如果盐值是空的，则取当前密码
        if (empty($salt)) {
            return $password;
        }

        return $str;
    }


    /**
     * 生成随机盐值
     *
     * @return string
     */
    public function genSalt()
    {
        $str = uniqid();
        return $str;
    }


    /**
     * 检查当前用户是否拥有某权限
     * @param  string $action [description]
     * @return [type]         [description]
     */
    public function checkAuth($action = '')
    {

        $data = [];
        $data['code'] = 20000;
        $data['message'] = "OK";
        return $data;
    }



    /**
     * 重置用户密码
     * @param  integer $userid    [description]
     * @param  string  $userpass  [description]
     * @param  string  $userpass2 [description]
     * @return [type]             [description]
     */
    public function resetPwd($userid = 0)
    {

        $where = [];
        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = "";
        $rt['message'] = "成功";
        //以下是一些校验

        if ($userid == 0) {
            $rt['message'] = "用户不能为空！";
            $rt['code'] = parent::CODE_ERROR;
            return ($rt);
        }

        $where = [];
        $where['userid'] = $userid;
        $where['isdel'] = 0;
        // $where['dwid'] = $this->dwid;
        $user = $this->getdb("user")->where($where)->find();
        // dump($where);
        if (!$user) {
            $rt['message'] = "未找到用户信息";
            return ($rt);
        }
        $newdata = [];
        $salt = $user['salt'];
        $newdata['updatetime'] = getNowTime();
        if (empty($salt)) {
            $salt = $this->genSalt();
            $newdata['salt'] = $salt;
        }
        $pass = self::PASSWORD_DEFAULT;
        $pass = $this->_encrypt($pass);
        // $pass = md5($pass."_salary"); //前端加密算法
        $pass = $this->genPassword($pass, $salt);
        $newdata['userpass'] = $pass;
        $id = $this->getdb("user")->where($where)->update($newdata);
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "密码已重置为初始密码【" . self::PASSWORD_DEFAULT . "】";

        return $rt;
    }
    /**
     * 获取用户的权限信息
     * @param  [type] $userid [description]
     * @return [type]         [description]
     */
    public function getRole($userid = 0)
    {

        if ($userid == 0) {
            $userid = $this->userid;
        }

        if (empty($userid)) {
            return null;
        }

        $auth = $this->authmodel->getAuth($userid);

        $data = [];
        if ($auth['code'] == self::CODE_SUCCESS) {
            $auths = $auth['data']['rulecode'];
            $data = $auths;
        }


        return $data;
    }
    /**
     * 删除用户
     * @param  integer $userid [description]
     * @return [type]          [description]
     */
    public function delUser($userid = 0)
    {
        $rt = [];
        $uid = $this->userid;

        if (empty($uid)) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = "";
            $rt['message'] = "没有权限！";
            return $rt;
        }
        if ($userid == $uid) {
            $rt['code'] = parent::CODE_ERROR;
            $rt['data'] = "";
            $rt['message'] = "无法删除自己";
            return $rt;
        }

        $where = [];
        $where['userid'] = $userid;
        $data = [];
        $data['isdel'] = 1;
        $data['deltime'] = getnowtime();
        $d = $this->getdb("user")->where($where)->update($data);


        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $d;
        $rt['message'] = "删除成功";
        // $rt['input'] = input("param.");
        return $rt;
    }

    /**
     * 保存用户，userid=0为新增，有值为修改
     * @param  [type] $userid [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function saveUser($userid, $data)
    {
        //判断数据重复

        $check = [];
        $check['table'] = "user";
        $check['field'] = ["usercode", "mobile", "cardno"];
        $check['comment'] = ['usercode' => "用户代码", "mobile" => "手机号码", "cardno" => "证件号码"];
        $check['data'] = $data;
        $check['id'] = $userid;
        $ck = $this->checkFieldData($check);
        if ($ck['code'] != self::CODE_SUCCESS) {
            return $ck;
        }
        // 判断用户密码是否有，如果有则修改密码，如果有一个有，则判断两者是否一致

        $userpass = $data['userpass'] ?? '';
        $userpass2 = $data['userpass2'] ?? '';
        $rt = $this->_rt();
        if (!empty($userpass) || !empty($userpass2)) {
            if ($userpass != $userpass2) {
                $rt['message'] = "密码校验不正确！";
                return $rt;
            }
        }





        $field = ["usercode", "username", "gender", "bankcard", "cardno", "deptcode", "joblevel", "jobpost", "jobauth", "mobile", "mobile2", "avatar", "photo", "email", "note", "isvoid", "userpass", "usergroup", "userlabel", "useroom", "telphone", "telphone2", "zzmm", "licenseplate"];




        $d = [];

        foreach ($field as $f) {
            if (isset($data[$f])) {
                $d[$f] = $data[$f];
            }
        }
        $d['dwid'] = $this->dwid;

        if (empty($userpass) && $userid == 0) {
            $userpass = self::PASSWORD_DEFAULT;
            $userpass = $this->_encrypt($userpass);
            // $userpass = md5($userpass . "_salary");
            $d['userpass'] = $userpass;
        }



        $d['usergroup'] = implode(",", $d['usergroup']);
        $d['userlabel'] = implode(",", $d['userlabel']);
        $rt = $this->_rt();

        //判断用户信息是否冲突，usercode 不能冲突。bankcard、cardno，mobile不能冲突

        //@todo 需要判断usercode等信息不重复



        $where = [];
        if ($userid > 0) {
            $where['userid'] = $userid;
        }
        $salt = $this->genSalt();
        if ($userid != 0) {
            //update
            $d['updatetime'] = getNowTime();
            if (!empty($d['userpass'])) {
                //密码不为空时，修改密码以及salt值
                $d['salt'] = $salt;
                $d['userpass'] = $this->genPassword($d['userpass'], $salt);
            } else {
                unset($d['userpass']);
            }
            $this->getdb("user")->where($where)->update($d);
        } else {
            //insert，密码已经经过md5加密了
            $d['createtime'] = getNowTime();
            $d['salt'] = $salt;
            $d['userpass'] = $this->genPassword($d['userpass'], $salt);
            $userid = $this->getdb("user")->insert($d);
        }


        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $userid = $data['userid'] ?? 0;
        $rt['data'] = $userid;
        $rt['message'] = "操作成功";
        if ($userid == 0) {
            $rt['message'] = "用户创建成功！初始密码为【" . self::PASSWORD_DEFAULT . "】";
        }

        // $rt['input'] = $data;
        $this->log(($userid == 0 ? '新增' : '修改') . "用户信息", _cv_to_json($d));
        return $rt;
    }
    /**
     * 将密码进行加密，和前端的加密方式一样
     *
     * @param string $text
     * @return array
     */
    public function _encrypt($text = '')
    {
        return md5($text . self::PASSWORD_SALT);
    }


    /**
     * 在用户信息中添加单位名称
     *
     * @param [type] $userinfo
     * @return void
     */
    protected function adduserDwname($userinfo)
    {

        $dwid = $userinfo['dwid'] ?? 0;
        $deptcode = $userinfo['deptcode'] ?? '';

        $deptname = "";
        $dwname = "";

        $userinfo['deptname'] = $userinfo['deptname'] ?? $deptname;
        $userinfo['dwname'] = $userinfo['dwname'] ?? $dwname;

        return $userinfo;
    }

    /**
     * 检查登录用户的手机，并判断用户是否可用。如果不可用，则返回提示，如果可用，则返回用户所在单位。如果有多个单位，则返回多条记录
     *
     * @param string $mobile
     * @return void
     */
    protected function checkUserMobile($mobile = '')
    {
        $rt = $this->_rt();

        $check = $this->checkMobile($mobile);
        if ($check['code'] != self::CODE_SUCCESS) {
            return $check;
        }


        //添加单位代码信息
        $where = [];
        $where['isvoid'] = 0;
        $where['mobile'] = $mobile;
        $group = "dwid";
        $field = "dwid";
        $userlist = $this->getdb("user")->where($where)->group($group)->field($field)->select();

        $dwid = [];
        $dwid[] = "-1";
        foreach ($userlist as $user) {
            $dwid[] = $user['dwid'];
        }

        $where = [];
        $where['dwid'] = ['in', $dwid];
        $field = "dwid,dwcode,dwname,address,telphone";
        $order = "rank";

        $dwlist = $this->getdb("dwlist")->where($where)->field($field)->order($order)->select();

        if (count($dwlist) == 0) {
            $rt['message'] = "未找到用户的单位信息";
            return $rt;
        }
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $dwlist;
        return $rt;
    }


    /**
     * 获取用户的token值
     *
     * @param integer $userid
     * @return void
     */
    protected function getUserToken($userid = 0)
    {
        $where = [];
        $where['userid'] = $userid;
        $where['expiretime'] = ['lt', time()];
        $data = $this->getdb("token")->where($where)->find();
        $token = $data['token'] ?? "";
        return $token;
    }

    /**
     * 获取人员代码
     *
     * @return void
     */
    protected function getnewcode()
    {
        $newcode = $this->genNewCode("user", "usercode", $this->dwid);
        return $newcode;
    }


    protected function getJobAuthList()
    {
        return $this->getJobClassList("jobauth");
    }

    protected function getJobPostList()
    {
        return $this->getJobClassList("jobpost");
    }
    protected function getJobLevelList()
    {
        return $this->getJobClassList("joblevel");
    }
    protected function getJobClassList($type = 'joblevel')
    {
        $rt = $this->_rt();
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $where['classtype'] = $type;
        $field = ["classname" => "text"];
        $data = $this->getdb("class")->where($where)->field($field)->select();


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $data;
        return $rt;
    }

    protected function changePassword($userid = 0, $oldpass = '', $newpass1 = '', $newpass2 = '')
    {

        $rt = $this->_rt();

        if ($userid == 0) {
            $rt['message'] = "请重新登录";
            $rt['code'] = parent::CODE_ERROR;
            return ($rt);
        }

        $where = [];
        $where['userid'] = $userid;
        $where['isdel'] = 0;
        $where['dwid'] = $this->dwid;
        $user = $this->getdb("user")->where($where)->find();
        // dump($where);
        if (!$user) {
            $rt['message'] = "未找到用户信息";
            return ($rt);
        }
        $newdata = [];
        $salt = $user['salt'];
        $newdata['updatetime'] = getNowTime();
        if (empty($salt)) {
            $salt = $this->genSalt();
            $newdata['salt'] = $salt;
        }

        // 判断新旧数据
        if ($oldpass == '') {
            $rt['message'] = "旧密码不能为空！";
            return $rt;
        }
        $oldpass = $this->genPassword($oldpass, $salt);
        if ($oldpass != $user['userpass']) {
            $rt['message'] = "旧密码不正确！";
            return $rt;
        }
        if ($newpass1 != $newpass2) {
            $rt['message'] = "两次密码不一致";
            return $rt;
        }
        // $pass = self::PASSWORD_DEFAULT;
        // $pass = $this->_encrypt($pass);
        // $pass = md5($pass."_salary"); //前端加密算法
        $pass = $this->genPassword($newpass1, $salt);
        $newdata['userpass'] = $pass;
        $id = $this->getdb("user")->where($where)->update($newdata);
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "密码修改成功";

        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = "OK";
        return $rt;
    }


    /**
     * 更新自己的数据
     *
     * @param array $data
     * @return void
     */
    protected function updateMyInfo($data = [])
    {


        $rt = $this->_rt();
        // 允许修改的字段信息，		○ 房间		○ 办公电话		○ 小号		○ 备用电话		○ 备注
        $field = ["useroom", "telphone", "telphone2", "mobile2", "note", "avatar"];
        $userid = $this->userid;
        if (!$userid) {
            $rt['message'] = "请先登录！";
            return $rt;
        }
        $newdata = [];
        foreach ($field as $f) {
            if (isset($data[$f])) {
                $newdata[$f] = $data[$f] ?? '';
            }
        }
        if (count($newdata) < 1) {
            $rt['message'] = "数据不能为空！";
            return $rt;
        }

        // 开始更新数据
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        $where['userid'] = $userid;

        $newdata['updatetime'] = getNowTime();

        $info = $this->getdb("user")->where($where)->update($newdata);

        $rt['message'] = "修改成功";
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = "OK";

        $this->updateLoginStatus($this->token);
        return $rt;
    }

    /**
     * 根据获取部门的子部门信息
     *
     * @param array $dept
     * @return void
     */
    protected function getAllDeptId($dept = [])
    {

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['deptid|parentid'] = ['in', $dept];
        $where['dwid'] = $this->dwid;
        $deptlist = $this->getdb("dept")->where($where)->field("deptid")->cache(self::CACHE_TIME)->select();
        $deptid = [];
        foreach ($deptlist as $row) {
            $deptid[] = $row['deptid'];
        }
        return $deptid;
    }


    /**
     * 申请授权
     *
     * @param string $type 类型
     * @param string $pass 密码
     * @return void
     */
    protected function applyAuth($type = '', $pass = '')
    {
        $rt = $this->_rt();

        if (!$this->checkPass($this->userid, $pass)) {
            $rt['message'] = "密码不正确，请重新输入";
            return $rt;
        }

        // 填写申请的信息
        if (empty($type) || !in_array($type, self::allowType)) {
            $rt['message'] = "请求【{$type}】不合法";
            return $rt;
        }
        $config = config("safeauth.{$type}");
        $time = $config['time'] ?? 180; // 默认3分钟
        $enable = $config['enable'] ?? false; // 停用
        $time2 = $time + time();
        $key = $this->genSafeKey($type);


        if ($enable) {
            Cache::set($key, $time2, $time);
        } else {
        }




        $rt['message'] = "申请成功";
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $time;

        $this->updateLoginStatus($this->token);
        return $rt;
    }
    protected function genSafeKey($type)
    {
        $token = $this->token;
        $key = "safeauth_{$type}_{$token}";
        return $key;
    }



    /**
     * 检查密码是否正确
     *
     * @param integer $userid
     * @param string $pass
     * @return void
     */
    protected function checkPass($userid = 0, $pass = '')
    {
        // 检查密码是否正确
        if (empty($userid) || empty($pass)) {
            return false;
        }
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['userid'] = $this->userid;
        $field = "userpass,salt";
        $userinfo = $this->getdb("user")->where($where)->field($field)->find();
        if (!$userinfo) {
            return false;
        }
        $salt = $userinfo['salt'];
        $userpass = $userinfo['userpass'];
        $checkpass = $this->genPassword($pass, $salt);


        return $userpass == $checkpass;
    }

    /**
     * 检查是否有权限
     */

    protected function checkSafeAuth($type = '')
    {
        $rt = $this->_rt();

        // 填写申请的信息
        if (empty($type) || !in_array($type, self::allowType)) {
            $rt['message'] = "请求【{$type}】不合法";
            return $rt;
        }
        $config = config("safeauth.{$type}");
        $time = $config['time'] ?? 180; // 默认3分钟
        $enable = $config['enable'] ?? false; // 停用
        if (!$enable) { // 如果未启用，直接返回时间
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = "OK";
            $rt['data'] = $time;
            return $rt;
        }

        $key = $this->genSafeKey($type);
        $data = Cache::get($key);
        if (!$data) {
            $data = 0;
        } else {
            $data = $data - time();
        }

        if ($data > 0) {
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = "OK";
        } else {
            $rt['code'] = self::CODE_ERROR;
            $rt['message'] = "已超时";
        }





        $rt['data'] = $data;
        return $rt;
    }


    /**
     * 添加登录记录
     *
     * @param string $path
     * @return void
     */
    protected function loginLog($userid)
    {

        $path = "/Login";
        $data = [];
        $data['dwid'] = $this->dwid;
        $data['userid'] = $userid;
        $data['visittime'] = time();
        $data['url'] = $path;
        $this->getdb("visitlog")->insert($data);

        return true;
    }

    /**
     * 检查当前用户登录状态，如果已经超时了，则自动注销用户，并返回0
     */
    protected function checkLoginStatus()
    {
        $rt = $this->_rt();

        $status = $this->tokenModel->checkAutoLogoff();
        if ($status['result']) {
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = "online";
            $rt['data'] = $status;
            return $rt;
        }else{
            $rt['code'] = 0;
            $rt['message'] = "请重新登录！";
            $rt['data'] = $status;

            // $this->logout();
            return $rt;


        }

        return $rt;
    }
    
}
