<?php

namespace app\cccf\model;


/**
 * 日志操作相关
 *
 * @author netknave
 *
 */
class Log extends Common
{
    const ACTION = "log";
    const COMMENT = "操作日志";
    const FIELD = ["id", "userid", "actionname", "deptname", "username", "logtype", "logaction", "ipaddress", "lognote", "logdata", "isvoid", "createtime", "updatetime", "rank"];
    const FIELD_FILTER = "deptname|actionname|username|logtype|logaction|lognote|logdata"; // 快速搜索字段
    const FIELD_PK = "id"; // 主键
    const FIELD_CHECK = []; //需要检查重复的字段
    const FIELD_CHECK_NOTE = []; // 需要检查重复的字段说明
    const TABLE = "log";
    const TABLE_VISIT = 'visitlog'; // 浏览日志
    const TABLE_DOWNLOG = "downlog"; // 下载日志 

    // 需要记录的Action信息
    const LOG_ACTION = [
        '/user/login' => '登录',
        '/user/adminlogin' => "登录后台",
        "/user/add" => "增加用户", "/user/save" => "编辑用户", "/user/del" => "删除用户",
        '/app/add' => "增加应用", '/app/save' => "修改应用", "/app/del" => "删除应用",
        "/link/add" => "增加链接", "/link/save" => "修改链接", '/link/del' => '删除链接',
        '/dept/add' => "增加部门", '/dept/save' => "修改部门", '/dept/del' => "删除部门",
        '/group/add' => "增加用户组", '/group/save' => '修改用户组', "/group/del" => '删除用户组',
    ];
    const LOG_ACTIONTYPE = [
        'app' => "应用管理",
        'user' => '用户管理',
        'dept' => '部门管理',
        'group' => '权限组管理',
        'link' => '友情链接'
    ];


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 入口程序
     *
     * @param string $action
     * @param array $data
     * @return void
     */
    public function index($action = '', $data = [])
    {
        $rt = $this->_rt();


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";

        $dwid = $this->dwid;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        switch ($action) {
            case "list": // 列表


                $rt = $this->getList($key, $isvoid, $page, $pagesize);
                break;
            case 'down': //下载，同列表
                $pagesize = 99999999;
                $rt = $this->getList($key, $isvoid, $page, $pagesize);
                break;
                // case "save": // 庭室
                //     $id = $data[self::FIELD_PK] ?? '';
                //     if(empty($id)){
                //         $rt['message'] = "主键不能为空！";
                //         return $rt;
                //     }
                //     $rt = $this->save($id,$data);
                // break;
                // case "add": // 新增
                // $rt = $this->save('',$data);
                // break;
                // case "del": // 删除
                //     $id = $data[self::FIELD_PK] ?? '';
                //     if(empty($id)){
                //         $rt['message'] = "不能为空！";
                //         return $rt;
                //     }
                //     $rt = $this->del($id);
                // break;
            case 'info': // 获取明细
                $id = $data[self::FIELD_PK] ?? '';
                if (empty($id)) {
                    $rt['message'] = "不能为空！";
                    return $rt;
                }
                $rt['data'] = $this->getinfo($id);
                break;
            case 'visit': // 访问
                $path = $data['path'] ?? '';
                $rt['data'] = $this->visitLog($path);
                break;
            case 'report': // 统计访问量
                $rt = $this->countVisit();

                break;
            case 'countRead': // 统计阅读量排行
                $limit = $data['limit'] ?? 10;
                $rt = $this->countRead($limit);
                break;
            case 'countLogin': // 统计用户登录排行
                $limit = $data['limit'] ?? 10;
                $rt = $this->countLogin($limit);
                break;
            case 'countonline': // 统计在线情况
                $rt = $this->countUser();
                break;
            case 'downlog': // 下载日志
                $startdate = $data['starttime'] ?? '';
                $enddate = $data['endtime'] ?? '';
                $rt = $this->downLogList($key,$startdate,$enddate,$page,$pagesize);
                break; 

            default:
                $rt['message'] = "操作【/" . $this->ACTION . "/{$action}】并不存在！";
        }

        return $rt;
    }

    /**
     * 获取列表
     */
    public function getList($key = "", $isvoid = 0, $page = 1, $pagesize = 50)
    {



        $field = self::FIELD;
        $order = "updatetime desc";
        $where = [];
        // $where['dwid']=$this->dwid;
        $where['isdel'] = 0;

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        if (!empty($key)) {
            $where[self::FIELD_FILTER] = ['like', "%{$key}%"];
        }


        $db = $this->getdb(self::TABLE);

        $num = $db->where($where)->count();
        $data = $db->field($field)->where($where)->order($order)->page($page, $pagesize)->select();


        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;

        return $rt;
    }


    /**
     * 获取明细信息
     * @param  [type] $id [ID]
     * @return [type]     [description]
     */
    public function getinfo($id)
    {

        $rt = $this->_rt();

        if (empty($id)) {
            $rt['code'] = self::CODE_ERROR;
            $rt['data'] = [];
            $rt['message'] = "ID不能为空";
            return $rt;
        }

        $field = self::FIELD;
        $where = [];
        $where['isdel'] = 0;
        $where[self::FIELD_PK] = $id;
        $data = $this->getdb(self::TABLE)->where($where)->field($field)->find();
        //获取roles
        if (!$data) {
            $rt['message'] = "数据不存在";
            return $rt;
        }
        $rt['data'] = $data;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "";
        return $rt;
    }


    /**
     * 删除
     * @param  integer $id [description]
     * @return [type]          [description]
     */
    public function del($id = 0)
    {
        $rt = $this->_rt();
        if (empty($id)) {
            $rt['code'] = self::CODE_ERROR;
            $rt['data'] = "";
            $rt['message'] = "ID不能为空";
            return $rt;
        }



        $where = [];
        $where[self::FIELD_PK] = $id;
        $where['dwid'] = $this->dwid;

        $data = [];
        $data['isdel'] = 1;
        $data['deltime'] = getNowTime();
        $d = $this->getdb(self::TABLE)->where($where)->update($data);


        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $d;
        $rt['message'] = "删除成功";
        return $rt;
    }

    /**
     * 保存，id=0为新增，有值为修改
     * @param  [type] $userid [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function save($id, $data)
    {

        $check = [];
        $check['table'] = self::TABLE;
        $check['field'] = self::FIELD_CHECK;
        $check['comment'] = self::FIELD_CHECK_NOTE;
        $check['data'] = $data;
        $check['id'] = $id;
        $ck = $this->checkFieldData($check);
        if ($ck['code'] != self::CODE_SUCCESS) {
            return $ck;
        }


        $field = self::FIELD;
        $dwid = $this->dwid;
        $d = [];
        $d['dwid'] = $dwid;
        foreach ($field as $f) {
            if (isset($data[$f])) {
                $d[$f] = $data[$f];
            }
        }
        $rt = [];




        $where = [];
        // $where['dwid']=$dwid;
        $where['isdel'] = 0;
        $where[self::FIELD_PK] = $id;
        $logid = $id;
        if (!empty($id)) {
            //update
            $d['updatetime'] = getNowTime();

            $this->getdb(self::TABLE)->where($where)->update($d);
        } else {
            // 主键使用uuid生成
            // $d[self::FIELD_PK] = uuid();
            $d['createtime'] = getNowTime();
            $logid = $this->getdb(self::TABLE)->insertGetId($d);
        }
        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $logid;
        $rt['message'] = "操作成功";

        return $rt;
    }

    /**
     * 记录Log
     *
     * @param array $action
     * @param array $data
     * @return void
     */
    public function Log($action = [], $data = [])
    {
        $rt = $this->_rt();
        $actionstr = '/' . ($action[1] ?? '') . '/' . ($action[2] ?? '');
        // 判断action是否需要记录日志
        if (!array_key_exists($actionstr, self::LOG_ACTION)) {
            // dump(self::LOG_ACTION);
            $rt['message'] = "本操作[{$actionstr}]不需要记录日志";
            // halt($rt);
            return $rt;
        }

        $userinfo = $this->userinfo;
        $userid = $this->userid ?? 0;
        $dwid = $this->dwid ?? 1;
        $username = $userinfo['username'] ?? '';
        if ($actionstr == '/user/login') {
            $username = $data['username'] ?? '';
        }
        $deptname = $userinfo['deptname'] ?? '';
        $actionname = self::LOG_ACTION[$actionstr] ?? '';
        $ip = get_client_ip();


        // 开始生成 lognote

        $lognote = $this->getLogNote($action, $data);
        $logtype = self::LOG_ACTIONTYPE[$action[1]] ?? '';

        //以下开始插入数据
        $newdata = [];
        $newdata['createtime'] = getNowTime();
        $newdata['updatetime'] = getNowTime();
        $newdata['dwid'] = $dwid;
        $newdata['userid'] = $userid;
        $newdata['deptname'] = $deptname;
        $newdata['username'] = $username;
        $newdata['logaction'] = $actionstr;
        $newdata['actionname'] = $actionname;
        $newdata['ipaddress'] = $ip;
        $newdata['logdata'] = _cv_to_json($data);
        $newdata['lognote'] = $lognote;
        $newdata['logtype'] = $logtype;


        $newid = $this->getdb("log")->insertGetId($newdata);
        $rt['message'] = "OK";
        $rt['data'] = $newid;
        $rt['code'] = self::CODE_SUCCESS;
        return $rt;
    }

    /**
     * 生成日志文本
     *
     * @param string $action
     * @param array $postdata
     * @return string
     */
    protected function getLogNote($action = '', $postdata = [])
    {
        $log = '';
        $id = $postdata['id'] ?? '';
        if ($action[1] == 'add') {
            $log = '新增记录';
            return $log;
        }
        if ($action == 'del') {
            $log = '删除数据';
            return $log;
        }

        $table = '';
        $model = $action[1] ?? '';
        $action = $action[2] ?? '';

        $pk = "id";
        if ($model == 'user') {
            $table = "user";
            $pk = "userid";
        } else if ($model == 'dept') {
            $table = "dept";
            $pk = "deptid";
        } else if ($model == 'group') {
            $table = "usergroup";
        } else if ($model == 'app') {
            $table = "apps";
        } else if ($model == 'link') {
            $table = "links";
        }


        if ($action == 'save') {
            $log = $this->checkData($table, $pk, $id, $postdata);
        }





        return $log;
    }
    /**
     * 检查数据库中数据与当前数据的差异
     *
     * @param string $table
     * @param string $id
     * @param array $newdata
     * @return void
     */
    protected function checkData($table = '', $pk = "id", $id = '', $newdata = [])
    {
        $log = "";
        $detail = [];

        $where = [];
        $where['dwid'] = $this->dwid;
        $where[$pk] = $id;

        $olddata = $this->getdb($table)->where($where)->find();
        foreach ($newdata as $key => $value) {
            $oldvalue = $olddata['{$key}'] ?? '';
            if ($oldvalue != $value) {
                $detail[] = "[{$key}]由【{$oldvalue}】修改成【{$value}】";
            }
        }
        $log = implode("\n", $detail);
        return $log;
    }

    /**
     * 添加浏览记录
     *
     * @param string $path
     * @return void
     */
    protected function visitLog($path = '')
    {

        $data = [];
        $data['dwid'] = $this->dwid;
        $data['userid'] = $this->userid;
        $data['visittime'] = time();
        $data['url'] = $path;
        $this->getdb("visitlog")->insert($data);

        return true;
    }

    /**
     * 查看浏览记录
     *
     * @return void
     */
    protected function countVisit()
    {
        $rt = $this->_rt();
        $data = [];
        $count = [];

        $week =  time() - (date('w', time()) - 1) * 86400;
        $week = strtotime(date('Y-m-d', $week));

        $count[] = ["label" => "today", "time" => strtotime("today")]; // 当天
        $count[] = ["label" => "week", "time" => $week]; // 本周
        $count[] = ["label" => "month", "time" => strtotime(date('Y-m-01', time()))]; // 本月
        $count[] = ["label" => "year", "time" => strtotime(date('Y-01-01', time()))]; // 本年
        $count[] = ["label" => "total", "time" => 0]; // 所有

        $field = [];
        foreach ($count as $f) {
            $time = $f['time'] ?? time();
            $label = $f['label'];
            $str = "sum(case when visittime>={$time} then 1 else 0 end) as {$label}";
            $field[] = $str;
        }

        $where = [];
        $where['dwid'] = $this->dwid;
        $where['url'] = "/Home";
        $data = $this->getdb("visitlog")->field($field)->where($where)->find();


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $data;
        return $rt;
    }

    /**
     * 统计阅读量排行（周、月、年、所有）
     *
     * @return array
     */
    protected function countRead($limit = 10)
    {
        $rt = $this->_rt();


        $count = [];

        $week =  time() - (date('w', time()) - 1) * 86400;
        $week = strtotime(date('Y-m-d', $week));
        $week = strtotime("-7 days");
        // $count[] = ["label"=>"today","time"=>strtotime("today")]; // 当天
        $count[] = ["label" => "week", "time" => $week]; // 本周


        // $count[] = ["label"=>"month","time"=>strtotime(date('Y-m-01',time()))]; // 减1个月
        $count[] = ["label" => "month", "time" => strtotime("-1 month")];

        // $count[] = ["label"=>"year","time"=>strtotime(date('Y-01-01',time()))]; // 本年
        $count[] = ["label" => "year", "time" => strtotime("-1 year")]; // 本年
        // $count[] = ["label"=>"year","time"=>strtotime(date('Y-01-01',time()))]; // 本年
        // $count[] = ["label"=>"total","time"=>0]; // 所有

        $field = "url,detailid,COUNT(*) AS num,(SELECT newstitle FROM web_news WHERE id=web_visitlog.detailid LIMIT 1) AS newstitle";
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['url'] = "/Detail";
        $group = "detailid";
        $order = "num desc";
        $data = [];

        foreach ($count as $f) {

            // 设置时间

            $time = $f['time'] ?? time();
            $where['visittime'] = ['EGT', $time];

            $label = $f['label'];

            $row = $this->getdb("visitlog")->where($where)->group($group)->order($order)->field($field)->page(1, $limit)->select();
            $data[$label] = $row;
        }
        // 添加文章信息







        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $data;
        return $rt;
    }

    /**
     * 统计用户登录排行（周、月、年、所有）
     *
     * @return array
     */
    protected function countLogin($limit = 10)
    {
        $rt = $this->_rt();


        $count = [];

        $week =  time() - (date('w', time()) - 1) * 86400;
        $week = strtotime(date('Y-m-d', $week));
        $week = strtotime("-7 days");
        // $count[] = ["label"=>"today","time"=>strtotime("today")]; // 当天
        $count[] = ["label" => "week", "time" => $week]; // 本周


        // $count[] = ["label"=>"month","time"=>strtotime(date('Y-m-01',time()))]; // 减1个月
        $count[] = ["label" => "month", "time" => strtotime("-1 month")];

        // $count[] = ["label"=>"year","time"=>strtotime(date('Y-01-01',time()))]; // 本年
        $count[] = ["label" => "year", "time" => strtotime("-1 year")]; // 本年
        // $count[] = ["label"=>"year","time"=>strtotime(date('Y-01-01',time()))]; // 本年
        // $count[] = ["label"=>"total","time"=>0]; // 所有

        $field = "url,COUNT(*) AS num,(SELECT username FROM web_user WHERE userid=web_visitlog.userid LIMIT 1) AS username";
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['url'] = "/Login";
        $group = "userid";
        $order = "num desc";
        $data = [];

        foreach ($count as $f) {

            // 设置时间

            $time = $f['time'] ?? time();
            $where['visittime'] = ['EGT', $time];

            $label = $f['label'];

            $row = $this->getdb("visitlog")->where($where)->group($group)->order($order)->field($field)->page(1, $limit)->select();
            $data[$label] = $row;
        }


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $data;
        return $rt;
    }

    /**
     * 统计当前用户登录信息（今天登录过的人员）以及当前用户在线情况（判断2个小时内有过阅读或查看动作的），判断当前用户是当天第几个登录的
     *
     * @return Array
     */
    protected function countUser()
    {
        $rt = $this->_rt();
        $data = [];
        // 取当前在线人数（2小时以内有过活动的），根据onlinehour配置，默认2小时

        $hour = config("onlinehour");
        if (!$hour) {
            $hour = 2;
        }
        $time = time() - $hour * 3600; // 判断2小时以内的时间
        $time_today = strtotime(date('Y-m-d', time()));

        $where = [];
        $where['dwid'] = $this->dwid;
        $where['userid'] = ['neq', 0];
        $where['isvoid'] = 0;
        $where['visittime'] = ['egt', $time];

        $group = "userid";
        $data['online'] = $this->getdb(self::TABLE_VISIT)->where($where)->group($group)->count();

        // 获取今日登录人数
        $where['url'] = '/Login';
        $where['visittime'] = ['egt', $time_today];
        $data['login'] = $this->getdb(self::TABLE_VISIT)->where($where)->group($group)->count();

        // 判断当前用户是第几个登录的
        /**
         * 1.先判断当前用户今天的最早登录时间
         * 2.判断比当前用户登录更早的人员数量
         * 3. 加1
         */
        $field = "userid,min(visittime) as mintime";
        $mylogin = $this->getdb(self::TABLE_VISIT)->where($where)->field($field)->find();
        if (!$mylogin || !$this->userid) {
            $data['mylogin'] = 0;
        } else {
            // 获取最早时间
            $firsttime = $mylogin['mintime'];
            // 介于最早登录时间与今天0点之间的登录人数
            $where['visittime'] = ['between', [$time_today, $firsttime]];
            $where['userid'] = ['neq', $this->userid];
            $other = $this->getdb(self::TABLE_VISIT)->where($where)->group($group)->count();
            $data['mylogin'] = $other + 1;
        }




        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $data;
        return $rt;
    }

    
    /**
     * 查询用户下载日志
     *
     * @param string $key
     * @param string $startdate
     * @param string $enddate
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function downLogList($key='',$startdate='',$enddate='',$page=1,$pagesize=20){
        $rt = $this->_rt();

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        $where_date = $this->_where_date($startdate,$enddate);
        if($where_date){
            $where['downloadtime'] = $where_date;
        }
        if(!empty($key)){
            $where['username|filename|ipaddress|newstitle'] = ['like','%'.$key.'%'];
        }
        $order = "downloadtime desc";

        $num = $this->getdb(self::TABLE_DOWNLOG)->where($where)->count();
        $data = $this->getdb(self::TABLE_DOWNLOG)->where($where)->page($page,$pagesize)->order($order)->select();
        $newdata = [];
        $newdata['items'] = $data;
        $newdata['total'] = $num;

        
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $newdata;
        $rt['total'] = $num;
        $rt['message'] = "OK";
        return $rt;
    }


}
