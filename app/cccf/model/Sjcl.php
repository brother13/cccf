<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
use \app\cccf\model\Data;
vendor('\phpoffice/PHPExcel/Classes/PHPExcel.php');

/**
 * 部门操作相关
 *
 * @author netknave
 *
 */
class Sjcl extends Common
{
    const ACTION = "cccf";
    const COMMENT = "数据处理";
    const FIELD = "*";
    const FIELD_FILTER = ""; // 快速搜索字段
    const FIELD_PK = "id"; // 主键
    const FIELD_CHECK = []; //需要检查重复的字段
    const FIELD_CHECK_NOTE = []; // 需要检查重复的字段说明
    const DEFAULT_WIDTH = "100"; // 列默认宽度
    protected $alltable = []; // 所有的表格数据
    public function __construct()
    {
        $this->__init();
        parent::__construct();
    }

    protected function __init()
    {

        $alltable = [];

        // 刑事拘留
        $table  = [];
        $table['code'] = 'xsjl'; // 字符
        $table['table'] = "ksssj"; // 表名
        $table['name'] = "刑事拘留";

        $field = [];
        $field[] = ['field' => 'snbh', 'name' => '所内编号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'name', 'name' => '姓名', "width" => 150, "filter" => true];
        $field[] = ['field' => 'sex', 'name' => '性别', "filter" => true, "width" => 200];
        $field[] = ['field' => 'adress', 'name' => '户籍地', "filter" => true, "width" => 200];
        $field[] = ['field' => 'indate', 'name' => '入所日期', "filter" => true];
        $field[] = ['field' => 'intype', 'name' => '入所性质', "filter" => true];
        $field[] = ['field' => 'closedate', 'name' => '羁押日期', "filter" => true];
        $field[] = ['field' => 'closelimit', 'name' => '关押期限', "filter" => true];
        $field[] = ['field' => 'ah', 'name' => '案由', "filter" => true];
        $field[] = ['field' => 'bahj', 'name' => '办案环节', "filter" => true];
        $field[] = ['field' => 'jsh', 'name' => '监室号', "filter" => true];
        $field[] = ['field' => 'badw', 'name' => '办案单位', "filter" => true, "filter" => true];
        $table['field'] = $field;
        $alltable[$table['code']] = $table;

        //审查逮捕
        $table  = [];
        $table['code'] = 'scdb'; // 字符
        $table['name'] = "审查逮捕";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;


        //刑事侦查
        $table  = [];
        $table['code'] = 'xszc'; // 字符
        $table['name'] = "刑事侦查";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;

        //审查起诉
        $table  = [];
        $table['code'] = 'scqs'; // 字符
        $table['name'] = "审查起诉";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;


        //一审
        $table  = [];
        $table['code'] = 'ys'; // 字符
        $table['name'] = "一审";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;

        //二审
        $table  = [];
        $table['code'] = 'es'; // 字符
        $table['name'] = "二审";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;


        //全部
        $table  = [];
        $table['code'] = 'qb'; // 字符
        $table['name'] = "全部";
        $table['field'] = $field;
        $alltable[$table['code']] = $table;

        // 循环所有表格数据，并生成filter字段

        foreach ($alltable as &$table) {
            $filter = [];
            $field = $table['field'];
            $allfield = [];
            foreach ($table['field'] as &$f) {
                if (array_key_exists('filter', $f) && $f['filter']) {
                    $filter[] = $f['field'];
                }
                $allfield[] = $f['field'];

                if (!array_key_exists("width", $f)) {
                    $f['width'] = self::DEFAULT_WIDTH;
                }
                if (!array_key_exists("show", $f)) {
                    $f['show'] = true;
                }
            }
            if (count($filter) > 0) {
                $table['filter'] = implode('|', $filter);
            }
            if (!array_key_exists("iscase", $table)) {
                $table['iscase'] = true;
            }
            $table['selectfield'] = $allfield;
        }

        $this->alltable = $alltable;
    }
    /**
     * 入口程序
     *
     * @param string $action
     * @param array $data
     * @return void
     */
    public function index($action='',$data=[]){
        $rt = $this->_rt();


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";
        $type = $data['type'] ?? 'xsjl';
        
        switch($action){
            case "save": // 处理数据
                $rt = $this->saveKsssj($data);
            break;
            case 'type': // 获取数据类型表
                $rt = $this->getTableList();
                break;
            case "list": // 列表
                $rt = $this->getList($key, $type, $isvoid, $page, $pagesize);
                break;
            default:
                $rt['message']="操作【{$action}】并不存在！";
        }

        return $rt;
    }



    /**
     * 获取可用的报表类型
     *
     * @return void
     */
    protected function getTableList()
    {
        $rt = $this->_rt();
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $this->alltable;
        return $rt;
    }


    /**
     * 获取列表
     */
    public function getList($key = "", $type = '', $isvoid = 0, $page = 1, $pagesize = 50)
    {


        $where = [];
        $config = $this->alltable[$type];//获取配置信息
        if (empty($type)) {
            $type = 'xsjl';
        }
        if($type!='qb'){
            $where['type'] = $type;
            $where['dayout'] = 0;
        }

        // $order = $config['order'] ?? "jarq desc";
        $selectfield = $config['selectfield'] ?? '*';
        $filter = $config['filter'] ?? "";

        $field = $config['field'] ?? '';

        $this->log1($key);
        $this->log1($filter);

        if (!empty($key) && !empty($filter)) {
            $where[$filter] = ['like', "%{$key}%"];
        }


        $db = $this->getdb("ksssj_list");
        $num = $db->where($where)->count();
        $data = $db->field($selectfield)->where($where)->page($page, $pagesize)->select();


        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $d = [];
        $d['total'] = $num;
        $d['field'] = $field;
        $d['items'] = $data;
        $rt['data'] = $d;
        $rt['total'] = $num;

        return $rt;
    }



    /**
     * 保存用户，userid=0为新增，有值为修改
     * @param  [type] $userid [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function saveKsssj($data){
        $field=['snbh','name','sex','adress','indate','intype','closedate','closelimit','ah','bahj','jsh','badw'];
        $fieldName=['所内编号','姓名','性别','户籍地','入所日期','入所性质','羁押日期','关押期限','案由','办案环节','监室号','办案单位'];
        $jsondata=json_decode($data);
        $cnum=0;
        // 删除旧数据
        $incount = $this->getdb("ksssj")->delete(true);
        foreach($jsondata as  $jdata){
            $d = [];
            foreach($jdata as $key1 =>$j1data){
                foreach($fieldName as $index =>$key2){
                    if($key1==$key2){
                        $d[$field[$index]] = $j1data;
                        // if($key1=='羁押日期'|| $key1=='入所日期' || $key1=='关押期限'){
                        //     $unixTimestamp=new Date((j1data - (25567 + 1)) * 86400 * 1000);
                        //     $d[$field[$index]]=date('Y-m-d', $unixTimestamp);
                        // }
                    }
                }

            }
            $d['createtime']=getNowTime();
            $incount = $this->getdb("ksssj")->insert($d);
            if($incount>0){
                $cnum=$cnum+1;

            }
            
        }
        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data']=$cnum;
        $rt['message']="操作成功";

        return $rt;
    }

    public function log1($data){
        $msg=strval($data);
        $msg = '['.date("Y-m-d H:i:s").']'.'[info]：'.$msg;

        // 日志文件名：日期.txt
        $path = ROOT_PATH.'public'. DS .'logs'. DS .date("Ymd").'.log';
    
        file_put_contents($path, $msg.PHP_EOL, FILE_APPEND);

    }





    /**
     * 在部门列表中，增加 用户的数据统计
     *
     * @param [type] $data
     * @return void
     */
    protected function _add_user_count($data){
        $deptlist = $data;
        $deptcode = [];
        $deptcode[] = "-1";
        foreach($data as $d){
            $deptcode[] = $d['deptid'];
        }
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $where['deptcode']=['in',$deptcode];
        $field = ["deptcode","count(*)"=>"num"];
        $group="deptcode";
        $usercount = $this->getdb("user")->where($where)->group($group)->field($field)->select();
        $deptuser = [];
        foreach($usercount as $user){
            $code = 'id_'.$user['deptcode']."";
            $deptuser[$code] = $user['num']; 
        }

        foreach($deptlist as &$dept){
            $code = 'id_'.$dept['deptid'];
            if(array_key_exists($code,$deptuser)){
                $dept['usernum'] = $deptuser[$code];
            }else{
                $dept['usernum'] = 0;
            }
        }

        return $deptlist;

    }
}
