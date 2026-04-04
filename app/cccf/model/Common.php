<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
use \think\Cache; //引入作为token管理用

use \app\cccf\model\Token;

class Common
{

    //定义一些常量
    const CODE_SUCCESS = 20000;
    const CODE_ERROR = 0;
    const APP_NAME = "website";

    const DWID_ALL = 0; //dwid为0时，指所有法院可见

    const CACHE_TIME = 120;
    const SMS_TIME = 60; //短信默认发送间隔
    const SMS_TIMEOUT = 300; //默认短信的有效时间

    // 定义一些用户相关的session名称
    const SESSION_USERAUTH = "userauth";
    const SESSION_USERID = "userid";
    const SESSION_DWID = "dwid";
    const SESSION_USERINFO = "userinfo";
    const SESSION_DWCODE = "dwcode";

    const QRCODE_PREFIX = "NKJZLZ"; //二维码的头必须是这个标志，不然信息不正确
    const QRCODE_VERSION = "01"; //标注版本号信息，以防后面有问题。此为初始版本。以后如果有需要，可以根据此版本来决定数据如何读取
    const QRCODE_MAX_DSR = 5; //最多只编码前5个当事人
    const QRCODE_DSR_PREFIX = "dsr:"; //当事人的前缀
    //以下是变量


    const AUTH_TYPE_AUTH = 0;
    const AUTH_TYPE_URL = 1;

    const THUMB_WIDTH = 400;
    const THUMB_HEIGHT = 400;

    const UPLOADTYPE_NEWS = "news";
    const UPLOADTYPE_NOTICE = "notice";

    const FILETYPE_MP3 = "mp3"; // 请注意文件
    const FILETYPE_IMAGE = "image"; // 内容中插入的图片
    const FILETYPE_FILE = "file"; // 附件内容
    const FILETYPE_THUMB = "thumb"; // 图片新闻的小图


    protected $dwid = 0;
    protected $dwcode = ''; //单位代码
    protected $userid = 0;
    protected $userinfo = null;
    protected $token = "";
    protected $tokenModel = null;


    // 保存的批量数据
    const TABLE_POSTLOG = "postlog";

    // 表-查封清单
    const TABLE_CFLIST = "cflist";


    const RULE_ZXTZ_QUERY_ALL = 'ZXTZ_QUERY_ALL' ;// 执行款台账，允许查询所有人


    const TABLE_CATALOG = "news_catalog";

    const USER_GUEST = ['userid' => 0, 'username' => 'guest', 'deptname' => '游客'];

    public function __construct()
    {


        $this->tokenModel = new Token();
        $this->token = $this->tokenModel->getToken();

        $this->dwid = $this->tokenModel->getData($this->token, self::SESSION_DWID, 1);
        $this->dwcode = $this->tokenModel->getData($this->token, self::SESSION_DWCODE, 0);
        $this->userid = $this->tokenModel->getData($this->token, self::SESSION_USERID, 0);
        $this->userinfo = $this->tokenModel->getData($this->token, self::SESSION_USERINFO, self::USER_GUEST);
        $this->username = $this->userinfo['username'] ?? '';
        
    }



    /**
     * 根据表名，获取表的备注信息
     *
     * @param string $tablename
     * @return multitype:unknown
     */
    public function db_table_comments($tablename = '', $datebase = '')
    {
        $field = array(
            "column_name",
            "data_type",
            "column_comment"
        );
        $where = array();

        if (empty($datebase)) {
            $datebase = config("database.database");
        }
        if (empty($tablename)) {
            return null;
        }
        $where['table_Name'] = $tablename;

        $data = $this->getdb("user")->table("Information_schema.columns")->where($where)->field($field)->select();
        $comments = array();

        foreach ($data as $value) {
            $comments[$value["column_name"]] = $value['column_comment'];
        }

        return $comments;
    }





    /**
     * 创建通用的返回数据结果
     *

     * @return multitype:boolean number string
     */
    protected function _create_return()
    {
        $rt = array();
        $rt['code'] = self::CODE_ERROR;
        $rt['action'] = input("param.action", "/sys/info");
        $rt['message'] = "";
        $rt['page'] = input("param.page", 1);
        $rt['pagesize'] = input("param.pagesize", 100);
        $rt['time'] = getNowTime();
        $rt['total'] = 1;
        $rt['data'] = '';
        return $rt;
    }

    /**
     * 创建标准返回格式的快捷方式
     *
     * @return Array
     */
    public function _rt()
    {
        return $this->_create_return();
    }

    /**
     * 根据两个日期返回条件内容
     *
     * @param string $date_start
     * @param string $date_end
     * @param string $hastime
     * @return Ambigous <multitype:, multitype:string >
     */
    protected function _where_date($date_start = '', $date_end = '', $hastime = true)
    {
        $rt = array();
        if (!empty($date_start) && !empty($date_end)) {
            if ($hastime) {
                $rt = array(
                    "between",
                    array(
                        $date_start,
                        $date_end . " 23:59:59"
                    )
                );
            } else {
                $rt = array(
                    "between",
                    array(
                        $date_start,
                        $date_end
                    )
                );
            }
        }
        if (!empty($date_start) && empty($date_end)) {
            $rt = array(
                "egt",
                $date_start
            );
        }
        if (empty($date_start) && !empty($date_end)) {
            if ($hastime) {
                $rt = array(
                    "elt",
                    $date_end . " 23:59:59"
                );
            } else {
                $rt = array(
                    "elt",
                    $date_end
                );
            }
        }

        if (empty($date_start) && empty($date_end)) {
            return null;
        }

        return $rt;
    }



    /**
     * 获取当前的数据库连接
     *
     * @param string $table
     * @return Query
     */
    public static function getdb($table = '')
    {

        return db($table);
    }

    /**
     * 将传入的参数转换成一维数组
     * @method param_to_array
     * @param  string         $param [description]
     * @return [type]                [description]
     */
    public function param_to_array($param = "")
    {
        $data = [];

        if (is_string($param)) {
            $data = explode(",", $param);
        }
        if (is_array($param)) {
            $data = $param;
        }
        return $data;
    }


    /**
     * 检查是否有数据重复
     *
     * @param integer $id
     * @param string $table
     * @param array $field
     * @param array $data
     * @return void
     */
    public function checkFieldData($config = [])
    {
        $rt = $this->_rt();
        $rt['message'] = "没有错误";

        $field = ["pk", "table", "id" => 0, "field", "data", "comment"]; //判断默认值
        $checkConfig = [];
        foreach ($field as $key => $value) {
            if (is_numeric($key)) {
                $checkConfig[$value] = isset($config[$value]) ? $config[$value] : "";
            } else {
                $checkConfig[$key] = isset($config[$key]) ? $config[$key] : $value;
            }
        }
        if (empty($checkConfig['pk'])) {
            $checkConfig['pk'] = $this->getPk($checkConfig['table']);
        }

        $comment = $checkConfig['comment'];
        $where = [];
        $pk = $checkConfig['pk'];
        if ($checkConfig['id'] > 0) {
            $where[$pk] = ['neq', $checkConfig['id']]; //排除本身
        }

        $total = 0;
        $message = [];
        foreach ($checkConfig['field']  as  $f) {
            $data = $checkConfig['data'];
            $value = isset($data[$f]) ? $data[$f] : "";
            if (empty($value)) {
                continue;
            }
            $w = $where;
            $w[$f] = $value;
            // 增加判断单位ID
            $w['dwid'] = $this->dwid;
            $num = $this->getdb($checkConfig['table'])->where($w)->count();
            if ($num > 0) {
                $total++;
                $tip = $f;
                if (is_array($comment) && isset($comment[$f]) && !empty($comment[$f])) {
                    $tip = $comment[$f];
                }
                $message[] = "【{$tip}】数据【{$data[$f]}】出现重复，请重新检查数据；";
            }
        }

        if ($total > 0) {
            $rt['code'] = self::CODE_ERROR;
            $rt['message'] = implode("\n", $message);
            return $rt;
        }
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "没有错误";

        return $rt;
    }

    /**
     * 获取数据库表的主键名
     *
     * @param string $table
     * @return string
     */
    protected function getPk($table = '')
    {
        $tablename = config("database.prefix") . $table;
        $pk = Db::getTableInfo($tablename, "pk");
        return $pk;
    }

    /**
     * 记录当前日志
     *
     * @param string $type
     * @return void
     */
    protected function log($type = '', $note = '')
    {

        $data = [];
        $data['userid'] = $this->userid;
        $data['dwid'] = $this->dwid;
        $data['createtime'] = getNowTime();
        $data['ipaddress'] = get_client_ip();
        $data['logaction'] = input("param.action", "");
        $data['lognote'] = $note;
        $data['logtype'] = $type;
        $id = $this->getdb("log")->insertGetId($data);
        return $id;
    }


    /**
     * 根据单位代码获取单位信息
     *
     * @param string $dwcode
     * @return void
     */
    protected function getDwByCode($dwcode = '')
    {

        if (empty($dwcode)) {
            return 0;
        }
        $dwlist = $this->getAllDwlist();
        $dwlist = $dwlist['dwcode'];
        $row = $dwlist[$dwcode] ?? null;
        return $row;
    }

    /**
     * 获取所有的单位信息并存储
     *
     * @param boolean $flesh 是否强制刷新
     * @return void
     */
    protected function getAllDwlist($flesh = false)
    {
        $data = [];
        $where = [];
        $where['isvoid&isdel'] = 0;
        $dwlist = $this->getCache("dwlist");

        if (!$dwlist || $flesh) {
            $info = $this->getdb("dwlist")->where($where)->select();
            $dwlist_id = [];
            $dwlist_code = [];
            $dwlist_name = [];
            foreach ($info as $dw) {
                $id = $dw['dwid'] . "";
                $code = $dw['dwcode'];
                $name = $dw['dwname'];
                $dwlist_id[$id] = $dw;
                $dwlist_code[$code] = $dw;
                $dwlist_name[$name] = $dw;
            }
            $data['dwid'] = $dwlist_id;
            $data['dwcode'] = $dwlist_code;
            $data['dwname'] = $dwlist_name;
            $this->setCache("dwlist", $dwlist);
        } else {
            $data = $dwlist;
        }

        return $data;
    }
    /**
     * 根据dwid获取单位信息
     *
     * @param integer $dwid
     * @return void
     */
    protected function getDwinfo($dwid = 0)
    {
        $dwlist = $this->getAllDwlist();
        $dwid = $dwid . "";
        $dwlist = $dwlist['dwid'];
        $row = $dwlist[$dwid] ?? null;
        return $row;
    }
    /**
     * 获取缓存数据
     *
     * @param string $key
     * @return void
     */
    protected function getCache($key = '')
    {
        $value = Cache::store("default")->get($key);
        return $value;
    }
    /**
     * 写入缓存
     *
     * @param [type] $key
     * @param [type] $value
     * @return void
     */
    protected function setCache($key, $value)
    {
        return Cache::store("default")->set($key, $value, self::CACHE_TIME);
    }

    /**
     * 获取本单位的用户信息
     *
     * @return void
     */
    protected function getDwUserList($flesh = false)
    {
        $key = "userlist_" . $this->dwid;

        $userlist = $this->getCache($key);
        if (!$userlist || $flesh) {
            // 无法取到数据，或者强制刷新的情况下，需要重新写入本单位的用户信息

            $where = [];
            $where['dwid'] = $this->dwid;
            $where['isdel'] = 0;

            $data = $this->getdb("user")->where($where)->select();
            $userinfo = [];
            $userinfo_code = [];
            $userinfo_name = [];
            $userinfo_id = [];
            foreach ($data as $d) {
                $code = $d['usercode'] ?? '';
                $id = $d['userid'] . "";
                $name = $d['username'] ?? '';
                if (!empty($code)) {
                    $userinfo_code[$code] = $d;
                }
                if (!empty($id)) {
                    $userinfo_id[$id] = $d;
                }
                if (!empty($name)) {
                    $userinfo_name[$name] = $d;
                }
            }
            $userinfo['code'] = $userinfo_code;
            $userinfo['id'] = $userinfo_id;
            $userinfo['name'] = $userinfo_name;
            $userinfo['list'] = $data;
            $this->setCache($key, $userinfo);
        }
        return $userlist;
    }
    /**
     * 根据时间秒数，显示中文的文本
     *
     * @param integer $second
     * @return void
     */
    protected function getTimeText($second = 0, $full = true)
    {
        $text = "";
        $fullsecond = $second;
        $year = intdiv($second, 86400 * 365);
        $second = $second - $year * 86400 * 365;
        $month = intdiv($second, 86400 * 30);
        $second = $second - $month * 86400 * 30;
        $day = intdiv($second, 86400);
        $second = $second - $day * 86400;
        $hour = intdiv($second, 3600);
        $second = $second - $hour * 3600;
        $minute = intdiv($second, 60);
        $second = $second - $minute * 60;
        if ($year > 0) {
            $text .= "{$year}年";
        }
        if ($month > 0) {
            $text .= "{$month}个月";
        }
        if ($day > 0) {
            $text .= "{$day}天";
        }

        if (!$full) {
            $theday = date('Y-m-d 00:00:00', time() - $fullsecond);
            $fullsecond = time() - strtotime($theday);

            $day = intdiv($fullsecond, 86400);
            if ($day == 0) {
                $text = '今天';
            }
            if ($day == 1) {
                $text = "昨天";
            }
            if ($day == 2) {
                $text = '前天';
            }
            if ($day >= 3) {
                $text = "{$day}天前";
            }

            return $text;
        }
        if ($hour > 0) {
            $text .= "{$hour}小时";
        }
        if ($minute > 0) {
            $text .= "{$minute}分钟";
        }
        if ($second > 0) {
            $text .= "{$second}秒";
        }
        // if($second<60){
        //     $text = "1分钟内";
        // }
        return $text;
    }

    /**
     * 生成最新的人员、部门代码等
     *
     * @param string $table
     * @param string $field
     * @param number $dwid 单位ID
     * @return void
     */
    public function genNewCode($table = '', $field = '', $dwid = 0,$pluswhere=[])
    {
        if (empty($table) || empty($field)) {
            return null;
        }

        if ($dwid == 0) {
            $dwid = $this->dwid;
        }
        $where = [];
        $where['dwid'] = $dwid;
        foreach($pluswhere as $key =>$value){
            $where[$key] = $value;
        }

        //先判断当前字段的最大宽度
        $f = ["max(length(" . $field . "))" => "maxlen", "max(" . $field . ")" => "maxcode"];
        $maxlen = 0;
        $maxcode = "000000";
        try {
            $row = $this->getdb($table)->where($where)->field($f)->find();
            if ($row) {
                $maxcode = $row['maxcode'];
                $maxlen = $row['maxlen'];
            } else {
                $maxlen = 6; //默认长度为6
                $maxcode = str_pad("1", $maxlen, "0", STR_PAD_LEFT);
            }
            if ($maxcode == null) {
                $maxcode = str_pad("0", 6, "0", STR_PAD_LEFT);
            }

            // dump($maxcode);
            // 取出maxcode中，末几位数（数字的），前面的非数字的保留不变
            preg_match('/\d+$/', $maxcode, $temparray);
            if (count($temparray) == 0) {
                // 表示是空的
                $maxcode = str_pad("", $maxlen, "0", STR_PAD_LEFT);
            }
            $leftstr = substr($maxcode, 0, strlen($maxcode) - strlen($temparray[0]));

            $maxcode = $temparray[0];
            $maxlen = strlen($maxcode);

            $newcode = str_pad($maxcode + 1, $maxlen, "0", STR_PAD_LEFT);
            $newcode = $leftstr . $newcode;
            return $newcode;
        } catch (\Exception $e) {


            dump($e->getMessage());
            return null;
        }
    }

    /**
     * 生成唯一标志符
     *
     * @return void
     */
    protected function genUid()
    {
        $prefix = $this->dwcode . date('Ymd');
        $uuid = uniqid($prefix);
        return $uuid;
    }



    /**
     * 获取本单位的用户信息
     *
     * @return void
     */
    protected function getDwDeptList($flesh = false)
    {
        $key = "deptlist_" . $this->dwid;

        $deptlist = $this->getCache($key);
        if (!$deptlist || $flesh) {
            // 无法取到数据，或者强制刷新的情况下，需要重新写入本单位的用户信息

            $where = [];
            $where['dwid'] = $this->dwid;
            $where['isdel'] = 0;

            $data = $this->getdb("dept")->where($where)->select();
            $deptlist = [];
            $deptlist_code = [];
            $deptlist_name = [];
            $deptlist_id = [];
            foreach ($data as $d) {
                $code = $d['deptcode'] ?? '';
                $id = $d['deptid'] . "";
                $name = $d['deptname'] ?? '';
                if (!empty($code)) {
                    $deptlist_code[$code] = $d;
                }
                if (!empty($id)) {
                    $deptlist_id[$id] = $d;
                }
                if (!empty($name)) {
                    $deptlist_name[$name] = $d;
                }
            }
            $deptlist['code'] = $deptlist_code;
            $deptlist['id'] = $deptlist_id;
            $deptlist['name'] = $deptlist_name;
            $deptlist['list'] = $data;
            $this->setCache($key, $deptlist);
        }
        return $deptlist;
    }

    /**
     * 创建金额查询语句，支持%的搜索方式
     *
     * @param array $param
     * @param string $field
     * @param string $min
     * @param string $max
     * @param string $bdfield
     * @return void
     */
    protected function _where_je($param = [], $field = 'kyye', $min = 'ye_min', $max = 'ye_max', $bdfield = 'labd')
    {

        $search = "";
        $je_min = $param[$min] ?? '';
        $je_max = $param[$max] ?? '';

        if (!empty($je_min)) {
            if (instr($param[$min], '%')) {
                $je_min = str_replace('%', '', $je_min);
                $je_min = $je_min / 100;
                if (!empty($je_min)) {
                    $search = ">=({$bdfield}*{$je_min})";
                }
            } else {
                $search = ">={$je_min}";
            }
        }
        if (!empty($je_max)) {
            if (!empty($search)) {
                $search .= " and {$field} ";
            }
            if (instr($param[$max], '%')) {
                $je_max = str_replace('%', '', $je_max);
                $je_max = $je_max / 100;
                if (!empty($je_max)) {
                    $search .= "<=({$bdfield}*{$je_max})";
                }
            } else {
                $search .= "<={$je_max}";
            }
        }

        if (!empty($search)) {
            return ['exp', Db::raw($search)];
        } else {
            return null;
        }
    }

    /**
     * 获取目录结构
     *
     * @param array $data
     * @return array
     */
    protected function getCatalog($focus = false)
    {
        $where = [];
        $where['isvoid&isdel'] = 0;
        // $where['dwid'] = $this->dwid;
        $cache = 3600;
        if ($focus) {
            $cache = 1;
        }
        $list = $this->getdb(self::TABLE_CATALOG)->where($where)->cache($cache)->select();
        $catalog = [];
        foreach ($list as $row) {
            $id = 'cat_' . $row['id'];
            $catalog[$id] = $row;
        }


        return $catalog;
    }

    /**
     * 获取目录路径
     *
     * @param integer $catid
     * @return Array
     */
    protected function getCatalogPath($catid = 0)
    {
        $catalog = $this->getCatalog();

        $id = $catid;
        $path = [];

        while ($id != 0) {
            $str = "cat_" . $id;
            $cat = $catalog[$str] ?? null;
            $id = $cat['parentid'] ?? 0;
            if (!$cat) {
                break;
            }
            array_unshift($path, $cat);
        }

        return $path;
    }

    
    /**
     * 生成缩略图的位置
     *
     * @param [type] $filepath
     * @return void
     */
    protected function getThumbPath($filepath='../uploads/20210623/d43c13ec0dcb9973e371419463b988cc.JPG')
    {
        if(!file_exists($filepath)){
            return $filepath;
        }

        $fileinfo = pathinfo($filepath);

        $thumb = "_thumb";
        $ext = $fileinfo['extension'];
        $filename = $fileinfo['filename'];
        $dir = $fileinfo['dirname'];
        $filepath_thumb = $dir."/".$filename.$thumb.".".$ext;

        if (!file_exists($filepath_thumb)) {
            // 生成缩略图
            $image = \think\Image::open($filepath);
            $image->thumb(self::THUMB_WIDTH, self::THUMB_HEIGHT)->save($filepath_thumb);
        }

        if (file_exists($filepath_thumb)) {
            $filepath = $filepath_thumb;
        }

        return $filepath;
    }

    /**
     * 判断用户权限是否存在
     *
     * @param string $rule 权限名称
    
     * @return boolean
     */
    public function checkAuth($rule=''){


        if(empty($rule)){
            return false;
        }

        
        $rules = $this->userinfo['roles'] ?? [];
        
        $hasAuth = in_array($rule,$rules);

        return $hasAuth; 

    }

    /**
     * 指定的操作，刷新时间
     *
     * @return void
     */
    protected function updateLoginStatus($token=''){
        return $this->tokenModel->updateLoginTime($token);
    }
}
