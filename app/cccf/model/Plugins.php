<?php

namespace app\cccf\model;
// 引入excel控件

use PDO;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

use \think\Db;
use \think\View;
use \think\Debug;

/**
 * 增强功能模块
 *
 * @author netknave
 *
 */
class Plugins extends Courtcase
{
    const ACTION = "zxktz";
    const COMMENT = "增强功能模块";
    const FIELD = [];

    const FIELD_FILTER = ""; // 快速搜索字段
    const FIELD_PK = "id"; // 主键
    const FIELD_CHECK = []; //需要检查重复的字段
    const FIELD_CHECK_NOTE = []; // 需要检查重复的字段说明


    const TABLE_ADMIN_SHOUKUAN = "shoukuan";
    const TABLE_ADMIN_TUIKUAN = "tuikuan";
    const TABLE_ADMIN_AKYH = "akyh";

    // 代管款余额的备注
    const TABLE_DGKYE_NOTE = "dgkyenote";


    const DGK_ALERT_DAYS_NEW = 10; // 没有延缓的，超过10天，则做提醒
    const DGK_ALERT_LEFTDAYS_AKYH = 5; // 如果做过案款延缓的，则当剩余天数小于5天的时间，做提醒




    public function __construct()
    {
        $this->__init();
        parent::__construct();
    }

    /**
     * 初始化可用的报表类型
     
     * 
     *
     * @return void
     */
    protected function __init() {}

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

        switch ($action) {

            case 'dgkreport_calc':
                $rt = $this->dgkreport_createtemp($data);
                break;
            case 'dgkreport_count':
                $rt = $this->dgkreport_count($data);
                break;
            case 'dgkreport_getList':
                $rt = $this->dgkreport_getList($data);
                break;
            case 'dgkreport_getendtime':
                $rt = $this->dgkreport_getendtime();
                break;
            case 'dgkreport_getDataRange':
                $rt['code'] = self::CODE_SUCCESS;
                $rt['data'] = $this->dgkreport_getDataRange();
                break;
            case 'getStListByBillCase': // 查询收退情况
                $rt = $this->getStListByBillCase($data);
                break;
            case 'addNote':
                $rt = $this->addNote($data);
                break;
            case 'getNote':
                $rt = $this->getNote($data);
                break;
            case 'sklist':
                $rt = $this->queryList_sk($data);
                break;
            case 'tklist':
                $rt = $this->queryList_tk($data);
                break;
            case 'countCasenum':
                $rt = $this->countCasenum($data);
                break;
            case 'queryList_sk_direct':
                $rt = $this->queryList_sk_direct($data);
                break;
            case 'queryList_sk_tk_summary':
                $rt = $this->queryList_sk_tk_summary($data);
                break;
            default:
                $rt['message'] = "操作【/" . self::ACTION . "/{$action}】并不存在！";
        }

        return $rt;
    }

    /**
     * 获取代管款台账的临时表名
     */
    public function getTablename_dgkreport()
    {
        $fydm = $this->userinfo['dwcode'] ?? '';
        $table = config("database.prefix") . "temp_dgkye_fy" . $fydm;
        return $table;
    }



    /**
     * 根据日期创建临时表
     *
     * @param string $datetype
     * @param string $endtime
     * @return void
     */
    protected function dgkreport_createtemp($param = [])
    {

        $endtime = $param['endtime'] ?? date('Y-m-d');
        $rt = $this->_rt();



        $prefix = config("database.prefix") ?? 'court_';
        $table = $this->getTablename_dgkreport();

        ini_set('max_execution_time', 600); // 设置超时时间 

        $table_noprefix = str_replace($prefix, '', $table);
        $table_dgkyenote = $prefix . self::TABLE_DGKYE_NOTE;

        if (empty($endtime)) {
            $endtime = date('Y-m-d', time());
        }
        $endtime = date('Y-m-d', strtotime($endtime));
        $dwid = $this->dwid ?? 0;
        $fydm = $this->userinfo['dwcode'] ?? '';

        $now = getNowTime();
        $table_sk = "admin_shoukuan";
        $table_tk = "admin_tuikuan";
        $table_yh = "admin_akyh";

        $adddays = 15; // 默认发还时间为入账日期+15天
        $sqls = [];


        // 如果临时表不存在，则创建临时表。
        $sqls[] = "drop table if exists {$table} ";
        $sqls[] = "CREATE TABLE  {$table} AS  SELECT djcode AS billno,ah AS caseinfo,min(kpdate) AS operdate,GROUP_CONCAT(DISTINCT dsr) AS dsr,MAX(cbbm) AS deptname,MAX(cbr) AS cbr,MAX(sjy) AS sjy,SUM(jzje) AS je,MIN(dzdate) AS bankdate,MAX(jzdate) AS jzdate,max(dzdate) as enddate,MAX(yh_zt) AS yhzt,MAX(yh_enddate) AS yh_enddate,0000000000.00 as tje,0000000000.00 AS ye,000 AS days,000 as leftdays,'{$endtime}' as endtime,'{$now}' as exectime FROM {$table_sk} where dwid={$dwid} and fydm='{$fydm}' and dzdate<='{$endtime}' GROUP BY djcode,ah ";
        // 添加字段
        $sqls[] = "ALTER TABLE `{$table}` 	ADD COLUMN `yhlx` VARCHAR(50) NULL DEFAULT NULL COMMENT '延缓类型' AFTER `exectime`,	ADD COLUMN `yhreason` VARCHAR(100) NULL DEFAULT NULL COMMENT '延缓原因' AFTER `yhlx`;";

        // 添加备注字段

        $sqls[] = "ALTER TABLE `{$table}` 	ADD COLUMN `note` VARCHAR(200) NULL DEFAULT NULL COMMENT '备注' ,	ADD COLUMN `notetime` VARCHAR(20) NULL DEFAULT NULL COMMENT '备注时间' ,ADD COLUMN `noteuser` VARCHAR(20) NULL DEFAULT NULL COMMENT '备注用户' ;";

        $sqls[] = "UPDATE {$table} s  SET tje=(SELECT SUM(je) FROM {$table_tk} t WHERE t.dwid={$dwid} AND t.fydm='{$fydm}' AND t.djcode=s.billno AND t.ah=s.caseinfo and czdate<='{$endtime}') WHERE s.tje=0";
        $sqls[] = "UPDATE {$table} s  SET ye=je-tje where ye=0";

        // 如果存在yh_enddate,则将enddate设成yh_date,否则设置成到账日期+15天
        $sqls[] = "update {$table} set enddate=yh_enddate where length(yh_enddate)>1";
        $sqls[] = "update {$table} s set enddate=date_add(s.bankdate,interval {$adddays} day) where length(yh_enddate)<2";
        // 计算日期
        $sqls[] = "update {$table} s set leftdays=DATEDIFF(s.enddate,'{$endtime}'),days=datediff('{$endtime}',s.bankdate) where days=0";

        // 获取延缓状态
        // 单笔延缓的
        $sqls[] = "UPDATE {$table}  t,{$table_yh} a SET t.yhlx=a.yhlx,t.yhreason=a.yhreason WHERE a.yhlx='单笔延缓' AND a.yhzt='延缓' AND a.dsr=t.dsr AND a.ah=t.caseinfo and t.yhreason is null and t.ye>0";
        // 全案延缓的
        $sqls[] = "UPDATE {$table}  t,{$table_yh} a SET t.yhlx=a.yhlx,t.yhreason=a.yhreason WHERE a.yhlx='全案延缓' AND a.yhzt='延缓'  AND a.ah=t.caseinfo and t.yhreason is null and t.ye>0";


        // $sqls = [];
        // 刷新备注信息
        $sqls[] = "UPDATE {$table}  t,{$table_dgkyenote} a SET t.note=a.note,t.notetime=a.updatetime,t.noteuser=a.username WHERE a.caseinfo=t.caseinfo and a.billno=t.billno";

        // 刷新数据

        $exectime = [];

        Debug::remark("dgkreport_start");
        foreach ($sqls as $index => $_sql) {
            $key_start = "sql_{$index}_start";
            Debug::remark($key_start);


            $message = "OK";

            try {
                $this->getdblink()->execute($_sql);
            } catch (\Exception $e) {
                $err = $e->getMessage();
                if (!json_encode($err)) {
                    $err = _cv_gbk_to_utf8($err);
                }
                $message = $err;
            }


            $key_end = "sql_{$index}_end";
            Debug::remark($key_end);
            $time = Debug::getRangeTime($key_start, $key_end);
            $exectime[] = [
                'sql' => $_sql,
                'index' => $index,
                'time' => $time,
                'message' => $message
            ];
        }



        Debug::remark("dgkreport_end");
        $this->FreshNote();

        $info = [];
        $info['table'] =  $table_noprefix;
        $info['exectime'] = Debug::getRangeTime('dgkreport_start', 'dgkreport_end');;
        $info['time'] = $exectime;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $info;

        return $rt;
    }




    protected function dgkreport_getendtime($trynum = 0)
    {


        $rt = $this->_rt();



        $table = $this->getTablename_dgkreport();
        $prefix = config("database.prefix") ?? 'court_';
        $table_noprefix = str_replace($prefix, '', $table);


        $field = ['count(*)' => "num", "max(endtime)" => "endtime", 'max(exectime)' => "lasttime"];


        $info['result'] = false;
        $info['message'] = "OK";
        $info['endtime'] = "";
        $info['lasttime'] = "";
        try {
            $data = $this->getdb($table_noprefix)->field($field)->find();

            $info['endtime'] = $data['endtime'] ?? '';
            $info['lasttime'] = $data['lasttime'] ?? '';
            $info['num'] = $data['num'] ?? '';
            $info['result'] = true;
        } catch (\Exception $e) {

            if ($trynum < 1) {
                $this->dgkreport_createtemp(['endtime' => getNowTime()]);
                return $this->dgkreport_getendtime(1);
            } else {
                $err = $e->getMessage();
                if (!json_encode($err)) {
                    $err = _cv_gbk_to_utf8($err);
                    $rt['message'] = $err;
                }
            }
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $info;
        return $rt;
    }
    /**
     * 汇总统计
     *
     * @param array $param
     * @return void
     */
    protected function dgkreport_count($param = [])
    {

        $rt = $this->_rt();
        // 检查数据是否存在

        $allrange = $this->dgkreport_getDataRange();

        $table = $this->getTablename_dgkreport();
        $prefix = config("database.prefix") ?? 'court_';


        $table_noprefix = str_replace($prefix, '', $table);

        $field = ['count(*)' => "num", 'sum(ye)' => "ye"];



        $alldata = [];

        $where = [];
        $where['ye'] = ['>', 0];


        foreach ($allrange as $item) {
            $day_min = $item['day_min'] ?? 0;
            $day_max = $item['day_max'] ?? 0;
            $showcount = $item['showcount'] ?? false;
            if (!$showcount) {
                continue;
            }
            $where['leftdays'] = ['between', [$day_min, $day_max]];
            $data = $this->getdb($table_noprefix)->where($where)->field($field)->find();
            $row = [];
            $row['code'] = $item['code'] ?? '';
            $row['label'] = $item['label'] ?? '';
            $row['showcount'] = $item['showcount'] ?? false;

            $row['icon'] = $item['icon'] ?? 'money';


            $row['num'] = $data['num'] ?? 0;
            $row['ye'] = $data['ye'] ?? 0;
            $row['ye'] -= 0;
            $alldata[] = $row;
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $alldata;
        return $rt;
    }


    /**
     * 获取明细数据
     *
     * @param array $param
     * @return void
     */
    protected function dgkreport_getList($param = [])
    {
        $rt = $this->_rt();
        $table_full = $this->getTablename_dgkreport();
        $prefix = config("database.prefix") ?? 'court_';
        $table = str_replace($prefix, '', $table_full);

        $alltype = $this->dgkreport_getDataRange();

        $map_type = _cv_array_to_map($alltype, 'code');

        $page = $param['page'] ?? 1;
        $pagesize = $param['pagesize'] ?? 10;
        $keyword = $param['keyword'] ?? '';
        $code = $param['code'] ?? 'ALL';

        if (empty($code)) {
            $code == 'ALL';
        }

        $sort = $param['sort'] ?? '';

        $order = "operdate";
        if (!empty($sort)) {
            $order = $sort;
        }






        $alldata = [];

        $where = [];
        $where['ye'] = ['>', 0];
        if (!empty($keyword)) {
            $where['deptname|cbr|caseinfo|sjy|dsr|billno'] = ['like', '%' . $keyword . '%'];
        }

        $emptyitem = ['total' => ['num' => 0, 'ye' => 0], 'items' => []];


        $field = ['count(*)' => "num", "sum(ye)" => "ye"];
        foreach ($alltype as $item) {

            $itemcode = $item['code'];

            $newitem = $emptyitem;
            $newitem['info'] = $item;



            $alldata[$itemcode] = $newitem;

            if ($code != 'ALL' && $code != $itemcode) {
                // 跳过
                continue;
            }



            $day_min = $item['day_min'] ?? 0;
            $day_max = $item['day_max'] ?? 0;
            $where['leftdays'] = ['between', [$day_min, $day_max]];

            $total = $this->getdb($table)->where($where)->field($field)->find();

            $details = $this->getdb($table)->where($where)->page($page, $pagesize)->order($order)->select();

            $newitem['total'] = $total;
            $newitem['items'] = $details;
            $alldata[$itemcode] = $newitem;
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $alldata;
        return $rt;
    }

    protected function getdblink()
    {
        return db();
    }


    /**
     * 获取时间周期
     *
     * @return array
     */
    protected function dgkreport_getDataRange()
    {
        $alldata = [];

        $alldata[] = ['label' => "所有明细", "day_min" => -999999, 'day_max' => 999999, 'code' => "alldetail", 'showcount' => false];
        $alldata[] = ['label' => "5天内超期", "day_min" => 0, 'day_max' => 5, 'code' => "above5day", 'showcount' => true];
        $alldata[] = ['label' => "10天内超期", "day_min" => 6, 'day_max' => 10, 'code' => "above10day", 'showcount' => true];
        $alldata[] = ['label' => "10天以上未发还", "day_min" => 11, 'day_max' => 99999, 'code' => "over10day", 'showcount' => true];
        $alldata[] = ['label' => "已超期", "day_min" => -999999, 'day_max' => -1, 'code' => "isover", 'showcount' => true];
        return $alldata;
    }


    protected function getStListByBillCase($param = [])
    {

        $rt = $this->_rt();


        $billno = $param['billno'] ?? '';
        $caseinfo = $param['caseinfo'] ?? '';


        $field_count = ['count(*)' => "num", 'sum(jzje)' => "je"];


        $field_sk = ["'收款'" => "st", "kpdate" => "operdate", 'dzdate' => "bankdate", 'djcode' => "billno", "ah" => "caseinfo", 'dsr' => "dwname", 'jzje' => 'sje', 'cbbm' => "deptname", "cbr" => "cbr", "sjy", 'dsr'];
        $order_sk = "dzdate";

        $field_tk = ["'退款'" => "st", "czdate" => "operdate", 'djcode' => "billno", "ah" => "caseinfo", 'dsr' => "dsr", 'skr' => "dwname", 'je' => 'tje', "cbr" => "cbr"];
        $field_count_tk = ['count(*)' => "num", 'sum(je)' => "je"];
        $order_tk = "czdate";

        $where = [];
        $where['dwid']  = $this->dwid;
        // $where['fydm'] = $this->userinfo['dwcode'] ?? '';


        $count = [];
        $data = [];

        // 先根据单据号获取
        $where['djcode'] = $billno;
        $count['bill_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_count)->find();
        $data['bill_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_sk)->order($order_sk)->select();
        $count['bill_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_count_tk)->find();
        $data['bill_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_tk)->order($order_tk)->select();



        $where['ah'] = $caseinfo;
        $count['billcase_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_count)->find();
        $data['billcase_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_sk)->order($order_sk)->select();
        $count['billcase_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_count_tk)->find();
        $data['billcase_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_tk)->order($order_tk)->select();



        unset($where['djcode']);
        $count['case_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_count)->find();
        $data['case_sk'] = $this->getdb()->name(self::TABLE_ADMIN_SHOUKUAN)->where($where)->field($field_sk)->order($order_sk)->select();
        $count['case_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_count_tk)->find();
        $data['case_tk'] = $this->getdb()->name(self::TABLE_ADMIN_TUIKUAN)->where($where)->field($field_tk)->order($order_tk)->select();




        $count_bill = [];
        $count_bill['snum'] = count($data['bill_sk']);
        $count_bill['tnum'] = count($data['bill_tk']);
        $count_bill['sje'] = $count['bill_sk']['je'] ?? 0;
        $count_bill['tje'] = $count['bill_tk']['je'] ?? 0;
        $count_bill['ye'] = $count_bill['sje'] - $count_bill['tje'];

        $data_bill = array_merge($data['bill_sk'], $data['bill_tk']);
        $count_bill['total'] = count($data_bill);

        $billdata = [];
        $billdata['count'] = $count_bill;
        $billdata['items'] = $data_bill;


        $count_billcase = [];
        $count_billcase['snum'] = count($data['billcase_sk']);
        $count_billcase['tnum'] = count($data['billcase_tk']);
        $count_billcase['sje'] = $count['billcase_sk']['je'] ?? 0;
        $count_billcase['tje'] = $count['billcase_tk']['je'] ?? 0;
        $count_billcase['ye'] = $count_billcase['sje'] - $count_billcase['tje'];
        $data_billcase = array_merge($data['billcase_sk'], $data['billcase_tk']);
        $count_billcase['total'] = count($data_billcase);
        $billcase = [];
        $billcase['count'] = $count_billcase;
        $billcase['items'] = $data_billcase;

        $count_case = [];
        $count_case['snum'] = count($data['case_sk']);
        $count_case['tnum'] = count($data['case_tk']);
        $count_case['sje'] = $count['case_sk']['je'] ?? 0;
        $count_case['tje'] = $count['case_tk']['je'] ?? 0;
        $count_case['ye'] = $count_case['sje'] - $count_case['tje'];
        $data_case = array_merge($data['case_sk'], $data['case_tk']);
        $count_case['total'] = count($data_case);

        $casedata = [];
        $casedata['count'] = $count_case;
        $casedata['items'] = $data_case;

        $newdata = [];
        $newdata['billdata'] = $billdata;
        $newdata['billcase'] = $billcase;
        $newdata['casedata'] = $casedata;



        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newdata;
        return $rt;
    }

    // 添加备注
    protected function addNote($param = [])
    {

        $rt = $this->_rt();


        $note = $param['note'] ?? '';

        $st = $param['st'] ?? 'sk';

        $noticenum = $param['noticenum'] ?? '';
        if (empty($note)) {
            $rt['message'] = "备注不能为空";
            return $rt;
        }
        $info = $this->getNote($param);
        $info = $info['data'] ?? [];

        $id = 0;
        if ($info) {
            $id = $info['id'] ?? 0;
        }

        $newdata = [];
        $newdata['dwid'] = $this->dwid;
        $newdata['fydm'] = $this->userinfo['dwcode'] ?? '';
        $newdata['billno'] = $param['billno'] ?? '';
        $newdata['caseinfo'] = $param['caseinfo'] ?? '';
        $newdata['bankdate'] = $param['bankdate'] ?? '';
        $newdata['createtime'] = getNowTime();
        $newdata['note'] = $param['note'] ?? '';
        $newdata['userid'] = $this->userid;
        $newdata['username'] = $this->userinfo['username'] ?? "";
        $newdata['updatetime'] = getNowTime();

        $newdata['st'] = $st;
        if ($st == 'tk') {
            $newdata['noticenum'] = $noticenum;
        }

        if (!empty($id)) {
            $where = [];
            $where['id'] = $id;
            unset($newdata['createtime']);
            $this->getdb(self::TABLE_DGKYE_NOTE)->where($where)->update($newdata);
        } else {
            $this->getdb(self::TABLE_DGKYE_NOTE)->insert($newdata);
        }



        $this->FreshNote_bydata($newdata);

        // $this->FreshNote();

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = null;
        return $rt;
    }

    /**
     * 获取日志
     *
     * @param array $param
     * @return void
     */
    protected function getNote($param = [])
    {

        $rt = $this->_rt();
        $billno = $param['billno'] ?? '';
        $caseinfo = $param['caseinfo'] ?? '';
        $noticenum = $param['noticenum'] ?? '';
        $bankdate = $param['bankdate'] ?? '';

        $st = $param['st'] ?? '';

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;


        // $where['fydm'] = $this->userinfo['dwcode'] ?? '';
        $where['st'] = $st;
        $where['bankdate'] = $bankdate;

        // if ($st == 'tk') {
        //     $where['noticenum'] = $noticenum;
        // }else if($st=='sk'){
        //     $where['bankdate'] = $bankdate;
        // }

        $where['billno'] = $billno;
        $where['caseinfo'] = $caseinfo;


        $info = $this->getdb(self::TABLE_DGKYE_NOTE)->where($where)->find();
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $info;
        return $rt;
    }

    // 根据数据刷新日志情况
    protected function FreshNote_bydata($data = [])
    {
        $rt = $this->_rt();



        $st = $data['st'] ?? 'sk';

        $table = self::TABLE_ADMIN_SHOUKUAN;
        if ($st == 'tk') {
            $table = self::TABLE_ADMIN_TUIKUAN;
        }

        $noticenum = $data['noticenum'] ?? '';



        $newdata = [];
        $newdata['othernote'] = $data['note'] ?? '';
        $newdata['othernote_user'] = $data['username'] ?? "";
        $newdata['othernote_time'] = $data['updatetime'] ?? '';


        $where = [];
        // 更新记录
        $where['dwid'] = $this->dwid;


        $n = 0;
        if ($st == 'sk') {
            // 单据号、案号、日期 来关联
            $where['djcode'] = $data['billno'] ?? '';
            $where['ah'] = $data['caseinfo'] ?? '';
            $where['dzdate'] = $data['bankdate'] ?? '';
            $n = $this->getdb($table)->where($where)->update($newdata);
        } else if ($st == 'tk') {
            $where['djcode'] = $data['billno'] ?? '';
            $where['ah'] = $data['caseinfo'] ?? '';
            $where['czdate'] = $data['bankdate'] ?? '';
            if (!empty($noticenum)) {
                $where['czdh'] = $data['noticenum'] ?? '';
            }
            $n = $this->getdb($table)->where($where)->update($newdata);
        }





        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $n;
        return $rt;
    }


    protected function FreshNote()
    {


        $rt = $this->_rt();

        $table = $this->getTablename_dgkreport();
        $prefix = config("database.prefix") ?? 'court_';

        $table_dgkyenote = $prefix . self::TABLE_DGKYE_NOTE;

        // $sqls = [];
        $sql = "UPDATE {$table}  t,{$table_dgkyenote} a SET t.note=a.note,t.notetime=a.updatetime,t.noteuser=a.username WHERE a.caseinfo=t.caseinfo and a.billno=t.billno";



        $this->getdblink()->execute($sql);
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = null;
        return $rt;
    }


    /**
     * 查询收款记录以及台账
     *
     * @param array $param
     * @return void
     */
    protected function queryList_sk($param = [])
    {

        $rt = $this->_rt();

        $keyword = $param['keyword'] ?? '';
        $starttime = $param['starttime'] ?? '';
        $endtime = $param['endtime'] ?? '';
        $cbr = $param['cbr'] ?? '';
        $je = $param['je'] ?? '';
        $je_min = $param['je_min'] ?? '';
        $je_max = $param['je_max'] ?? '';
        $datetype = $param['datetype'] ?? 'kpdate';

        $page = $param['page'] ?? 1;
        $pagesize = $param['pagesize'] ?? 10;

        $yhstatus = $param['yhstatus'] ?? 0;
        $yestatus = $param['yestatus'] ?? 0;

        $notestatus = $param['notestatus'] ?? 0;



        $order = "dzdate,djcode";

        $sort = $param['sort'] ?? '';

        if (!empty($sort)) {
            $order = $sort;
        }

        if (empty($datetype)) {
            $datetype = 'kpdate';
        }


        $searchkey = "ah|djcode|dsr|cbbm|cbr|sjy|zxyj|ay|othernote";


        $table = self::TABLE_ADMIN_SHOUKUAN; // 收款表查询



        $where = [];
        if (empty($param['ignore_dwid'])) {
            $where['dwid'] = $this->dwid;
        }

        $where_date = $this->_where_date($starttime, $endtime);
        if ($where_date) {
            $where[$datetype] = $where_date;
        }
        if (!empty($keyword)) {
            $where[$searchkey] = ['like', '%' . $keyword . '%'];
        }

        if (!empty($cbr)) {
            $where['cbr'] = ['like', '%' . $cbr . '%'];
        }
        if (!empty($je)) {
            $where['jzje'] = $je;
        }
        if (!empty($je_min)) {
            $where['jzje'] = ['>=', $je_min];
        }
        if (!empty($je_max)) {
            $where['jzje'] = ['<=', $je_max];
        }

        if(!$this->checkAuth(self::RULE_ZXTZ_QUERY_ALL)){
            $where['cbr'] = $this->userinfo['username']??''; // 只能查询本人的
        }



        if (!empty($yhstatus)) {
            // 是否延缓的状态

            $_exp = "";
            switch ($yhstatus) {

                case 1: // 无延缓
                    $_exp = "length(yh_zt)<1";
                    break;
                case 2: // 延缓
                    $_exp = "length(yh_zt)>1";
                    break;
            }
            if (!empty($_exp)) {
                $where['_yhzt_'] = Db::raw($_exp);
            }
        }
        if (!empty($yestatus)) {
            switch ($yestatus) {
                case 1: // 有余额
                    $where['ye'] = ['neq', 0];
                    break;
                case 2: // 无余额
                    $where['ye'] = 0;
                    break;
            }
        }

        // 判断是否超期的状态
        $rowstatus = $param['rowstatus'] ?? 0;

        if (!empty($rowstatus)) {

            $sql_status = "";
            $yhdays = self::DGK_ALERT_LEFTDAYS_AKYH; // 有延缓的，不足5天做提醒
            $days  = self::DGK_ALERT_DAYS_NEW; // 未延缓的，入账超过10天则提示

            switch ($rowstatus) {
                case 1: // 未超期
                    $sql_status = "((length(yh_enddate)>1 and leftdays>{$yhdays}) or (length(yh_enddate)<1 and days<={$days}))";

                    break;
                case 2: // 即将超期
                    $sql_status = "(leftdays>=0 and ((length(yh_enddate)>1 and leftdays<={$yhdays}) or (length(yh_enddate)<1 and days>{$days})))";
                    break;
                case 3: // 已超期
                    $sql_status = "leftdays<0";
                    break;
            }
            if (!empty($sql_status)) {
                $where['ye'] = ['>', 0];
                $where['_leftday_'] = Db::raw($sql_status);
            }
        }



        if ($notestatus) {
            switch ($notestatus) {
                case 1: // 有
                    $where['othernote'] = ['not null', ''];
                    break;
                case 2: // 无
                    $where['othernote'] = ['null', ''];
                    break;
            }
        }
        $field = [
            'count(*)' => "num",
            "SUM(CAST(REPLACE(IFNULL(jzje, '0'), ',', '') AS DECIMAL(18,2)))" => "je"
        ];
        $total = $this->getdb($table)->where($where)->field($field)->find();
        $data = $this->getdb($table)->where($where)->order($order)->page($page, $pagesize)->select();

        $this->_addinfo_days($data);


        $newdata = [];
        $newdata['total'] = $total;
        $newdata['items'] = $data;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newdata;
        return $rt;
    }


    /**
     * 给收款添加时间
     *
     * @param array $data
     * @return void
     */
    protected function _addinfo_days(&$data = [])
    {

        // 今天
        $today = date('Y-m-d');
        $adddays = 15;
        foreach ($data as &$row) {


            $row['rowstatus'] = '';
            $row['rowtip'] = ""; // 行提示
            $leftdays = $row['leftdays'] ?? 0;
            $yhdate = $row['yh_enddate'] ?? '';
            $days = $row['days'] ?? 0;

            $yhdate = trim($yhdate);

            $ye = $row['ye'] ?? 0;
            if ($ye <= 0) {
                $row['leftdays'] = '';
                $row['rowstatus'] = 'success';
                $row['rowtip'] = '已退清';
                continue;
            }
            if (!empty($yhdate)) {
                // 有延缓

                if ($leftdays < self::DGK_ALERT_LEFTDAYS_AKYH) {
                    $row['rowstatus'] = 'warning';
                    $row['rowtip'] = "延缓案件只剩" . $leftdays . '天，请及时处理！';
                    if ($leftdays == 0) {
                        $row['rowtip'] = "款项今天过期，请及时处理";
                    }
                    if ($leftdays < 0) {
                        $leftdays = abs($leftdays);
                        $row['rowstatus'] = 'danger';
                        $row['rowtip'] = "款项已超时{$leftdays}天，请及时处理！";
                    }
                }
            } else {
                // 无延缓

                if ($leftdays < self::DGK_ALERT_DAYS_NEW) {
                    $row['rowstatus'] = 'warning';
                    $row['rowtip'] = "款项已到账{$days}天，即将超期，请及时处理！";
                    if ($leftdays == 0) {
                        $row['rowtip'] = "款项今天过期，请及时处理";
                    }
                    if ($leftdays < 0) {
                        $leftdays = abs($leftdays);
                        $row['rowstatus'] = 'danger';
                        $row['rowtip'] = "款项已超时{$leftdays}天，请及时处理！";
                    }
                }
            }



            // $dzdate = $row['dzdate'] ?? '';

            // // 取与今天的日期差-1
            // $days = (strtotime($today) - strtotime($dzdate)) / 86400;
            // $row['days'] = intval($days);
            // $row['days_text'] = $row['days'] . '天';

            // $enddate = $row['yh_enddate'] ?? '';
            // if (empty($enddate) || $enddate == '0') {
            //     // enddate设为dzdate+15天
            //     $enddate = date('Y-m-d', strtotime($dzdate) + 86400 * $adddays);
            // }

            // // 取今天与$enddate的日期差
            // $leftdays = (strtotime($enddate) - strtotime($today)) / 86400;
            // $leftdays = intval($leftdays);

            // $row['enddate'] = $enddate;
            // $row['leftdays'] = $leftdays;
        }
    }

    protected function queryList_tk($param = [])
    {

        $rt = $this->_rt();

        $keyword = $param['keyword'] ?? '';
        $starttime = $param['starttime'] ?? '';
        $endtime = $param['endtime'] ?? '';
        $cbr = $param['cbr'] ?? '';
        $je = $param['je'] ?? '';
        $je_min = $param['je_min'] ?? '';
        $je_max = $param['je_max'] ?? '';
        $datetype = $param['datetype'] ?? 'czdate';

        $page = $param['page'] ?? 1;
        $pagesize = $param['pagesize'] ?? 10;

        $notestatus = $param['notestatus'] ?? 0;
        $payout_type = $param['payout_type'] ?? '';
        $payee_type = $param['payee_type'] ?? '';

        $order = "czdate,djcode";

        $sort = $param['sort'] ?? '';

        if (!empty($sort)) {
            $order = $sort;
        }

        if (empty($datetype)) {
            $datetype = 'czdate';
        }


        $searchkey = "ah|djcode|dsr|cbbm|cbr|sjy|czdh|skr|skr_accountname|skr_account|skr_bank|note|othernote|ay|zxyj";


        $table = self::TABLE_ADMIN_TUIKUAN; // 退款表查询



        $where = [];
        if (empty($param['ignore_dwid'])) {
            $where['dwid'] = $this->dwid;
        }

        $where_date = $this->_where_date($starttime, $endtime);
        if ($where_date) {
            $where[$datetype] = $where_date;
        }
        if (!empty($keyword)) {
            $where[$searchkey] = ['like', '%' . $keyword . '%'];
        }

        if (!empty($cbr)) {
            $where['cbr'] = ['like', '%' . $cbr . '%'];
        }
        if (!empty($je)) {
            $where['je'] = $je;
        }
        if (!empty($je_min)) {
            $where['je'] = ['>=', $je_min];
        }
        if (!empty($je_max)) {
            $where['je'] = ['<=', $je_max];
        }


        if(!$this->checkAuth(self::RULE_ZXTZ_QUERY_ALL)){
            $where['cbr'] = $this->userinfo['username']??''; // 只能查询本人的
        }

        if ($notestatus) {
            switch ($notestatus) {
                case 1: // 有
                    $where['othernote'] = ['not null', ''];
                    break;
                case 2: // 无
                    $where['othernote'] = ['null', ''];
                    break;
            }
        }

        $this->applyNotaxPayoutTypeWhere($where, $payout_type);
        $this->applyPayeeTypeWhere($where, $payee_type);


        $field = [
            'count(*)' => "num",
            "SUM(CAST(REPLACE(IF(je='' OR je IS NULL, '0', je), ',', '') AS DECIMAL(18,2)))" => "je"
        ];
        $total = $this->getdb($table)->where($where)->field($field)->find();
        $data = $this->getdb($table)->where($where)->order($order)->page($page, $pagesize)->select();
        $newdata = [];
        $newdata['total'] = $total;
        $newdata['items'] = $data;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newdata;
        return $rt;
    }

    protected function applyPayeeTypeWhere(&$where, $payee_type = '')
    {
        if (empty($payee_type)) {
            return;
        }

        $skrNotEmpty = "LENGTH(IFNULL(`skr`, '')) > 0";
        switch ($payee_type) {
            case 'applicant':
                $where['_payee_type_'] = Db::raw("({$skrNotEmpty} AND INSTR(IFNULL(`yg`, ''), `skr`) > 0)");
                break;
            case 'respondent':
                $where['_payee_type_'] = Db::raw("({$skrNotEmpty} AND INSTR(IFNULL(`bg`, ''), `skr`) > 0)");
                break;
            case 'other':
                $where['_payee_type_'] = Db::raw("(LENGTH(IFNULL(`skr`, '')) = 0 OR (INSTR(IFNULL(`yg`, ''), `skr`) = 0 AND INSTR(IFNULL(`bg`, ''), `skr`) = 0))");
                break;
        }
    }

    protected function applyNotaxPayoutTypeWhere(&$where, $payout_type = '')
    {
        if (empty($payout_type)) {
            return;
        }

        $config = config('notax_payout_types');
        if (empty($config) || empty($config['account_name_keyword'])) {
            return;
        }

        $where['skr_accountname'] = ['like', '%' . $config['account_name_keyword'] . '%'];

        $types = $config['types'] ?? [];
        if (empty($types[$payout_type])) {
            return;
        }

        $keywords = $types[$payout_type]['keywords'] ?? [];
        $likes = [];
        foreach ($keywords as $keyword) {
            if (!empty($keyword)) {
                $likes[] = '%' . $keyword . '%';
            }
        }

        if (empty($likes)) {
            $where['_notax_payout_type_'] = Db::raw('1=0');
            return;
        }

        $noteConditions = [];
        foreach ($keywords as $keyword) {
            if (!empty($keyword)) {
                $noteConditions[] = "`note` LIKE '%" . addslashes($keyword) . "%'";
            }
        }
        $where['_notax_payout_note_'] = Db::raw('(' . implode(' OR ', $noteConditions) . ')');
    }

    /**
     * 统计数据，获取超过10天未发还的数量，以及延缓5天内到期的记录
     * 
     *
     * @return void
     */
    public function countCasenum()
    {

        $rt = $this->_rt();


        $count = [];
        $count['new10day'] = 0;
        $count['akyh5day'] = 0;

        $rule_queryall = self::RULE_ZXTZ_QUERY_ALL;


        $userinfo = $this->userinfo;
        // halt($userinfo);
        $query_all = $this->checkAuth($rule_queryall);



        
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['ye'] = ['>', 0];

        // 获取未延缓的，已经超过10天的记录
        $where['days'] = ['>=', self::DGK_ALERT_DAYS_NEW];

        // 只判断未过期的
        $where['_leftday_'] = Db::raw("leftdays>=0");

        if(!$query_all){
            $username = $this->userinfo['username']??'';
            // 取当前用户名
            $where['cbr'] = $username;
        }


        $where['_exp_'] = Db::raw("(length(yh_enddate)<1 or yh_enddate is null)");

        $count['new10day'] = $this->getdb('shoukuan_ye')->where($where)->count();

        $where['_exp_'] = Db::raw("(length(yh_enddate)>1 )");
        unset($where['days']);
        $where['leftdays'] = ['<=', self::DGK_ALERT_LEFTDAYS_AKYH];


        $count['akyh5day'] = $this->getdb('shoukuan_ye')->where($where)->count();




        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $count;
        return $rt;
    }

    /**
     * 判断当前用户是否是管理员
     * 根据用户组判断，groupid=6为管理员组
     *
     * @return bool
     */
    protected function isAdmin()
    {
        $usergroup = $this->userinfo['usergroup'] ?? '';
        if (empty($usergroup)) {
            return false;
        }
        // usergroup 可能是逗号分隔的多个组ID
        $groups = explode(',', $usergroup);
        // 检查是否包含管理员组ID (6)
        return in_array('6', $groups) || in_array(6, $groups);
    }

    /**
     * 直接从admin_shoukuan表获取代管款数据（不创建临时表）
     *
     * @param array $param
     * @return array
     */
    protected function queryList_sk_direct($param = [])
    {
        $rt = $this->_rt();

        $keyword = $param['keyword'] ?? '';
        $yhstatus = $param['yhstatus'] ?? 0;  // 是否延缓：1-无延缓，2-有延缓
        $cqstatus = $param['cqstatus'] ?? 0;  // 超期情况：1-未超期，2-即将超期，3-已超期

        $page = $param['page'] ?? 1;
        $pagesize = $param['pagesize'] ?? 10;

        $table = 'shoukuan_ye';

        $where = [];
        $where['dwid'] = $this->dwid;

        // 非管理员只能查询自己的数据（根据用户组判断，groupid=1为管理员组）
        if (!$this->isAdmin()) {
            $where['cbr'] = $this->userinfo['username'] ?? '';
        }

        // 模糊搜索
        if (!empty($keyword)) {
            $where['ah|cbr|yg|bg|ay|ly'] = ['like', '%' . $keyword . '%'];
        }

        // 是否延缓筛选
        if (!empty($yhstatus)) {
            if ($yhstatus == 1) {
                $where['_yhzt_'] = Db::raw("(length(yh_zt)<1 or yh_zt is null)");
            } else if ($yhstatus == 2) {
                $where['_yhzt_'] = Db::raw("length(yh_zt)>1");
            }
        }

        // 超期情况筛选
        if (!empty($cqstatus)) {
            switch ($cqstatus) {
                case 1: // 未超期
                    $where['leftdays'] = ['>', 5];
                    break;
                case 2: // 即将超期（5天内）
                    $where['leftdays'] = ['between', [0, 5]];
                    break;
                case 3: // 已超期
                    $where['leftdays'] = ['<', 0];
                    break;
            }
        }

        // je和ye字段是varchar类型，存储格式为"395,334.00"，需要去除逗号
        $field = [
            'ly', 'ah', 'cbr',
            Db::raw("REPLACE(je, ',', '') as je"),
            Db::raw("REPLACE(ye, ',', '') as ye"),
            'jzdate',
            'yh_zt', 'yh_reason', 'yh_enddate',
            'ay', 'yg', 'bg',
            'days', 'leftdays'
        ];

        // 获取统计数据（记录数、到账金额总和、余额总和）
        $statField = [
            'count(*)' => 'num',
            Db::raw("SUM(REPLACE(je, ',', '')) as je"),
            Db::raw("SUM(REPLACE(ye, ',', '')) as ye")
        ];
        $stat = $this->getdb($table)->where($where)->field($statField)->find();

        $total = $stat['num'] ?? 0;
        $data = $this->getdb($table)
            ->where($where)
            ->field($field)
            ->page($page, $pagesize)
            ->order('jzdate desc')
            ->select();

        $newdata = [];
        $newdata['total'] = [
            'num' => intval($stat['num'] ?? 0),
            'je' => $stat['je'] ?? 0,
            'ye' => $stat['ye'] ?? 0
        ];
        $newdata['items'] = $data;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newdata;
        return $rt;
    }

    /**
     * 获取执行款台账汇总表临时表名
     *
     * @return string
     */
    public function getTablename_sk_tk_summary()
    {
        $table = config("database.prefix") . "temp_sk_tk_summary";
        return $table;
    }

    /**
     * 获取执行款台账按案号汇总表临时表名
     *
     * @return string
     */
    public function getTablename_sk_tk_ah_summary()
    {
        $table = config("database.prefix") . "temp_sk_tk_ah_summary";
        return $table;
    }

    /**
     * 创建执行款台账汇总表临时表
     *
     * @param array $param
     * @return array
     */
    protected function _createtemp_sk_tk_summary($param = [])
    {
        $rt = $this->_rt();
        $endtime = $param['endtime'] ?? date('Y-m-d');
        if (!empty($endtime) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endtime)) {
            $endtime = date('Y-m-d');
        }
        $force = !empty($param['force']);
        $dwid = $this->dwid ?? 0;

        $prefix = config("database.prefix") ?? 'court_';
        $table = $this->getTablename_sk_tk_summary();
        $table_tk_sum = $table . '_tk';
        $table_sk = $prefix . self::TABLE_ADMIN_SHOUKUAN;
        $table_tk = $prefix . self::TABLE_ADMIN_TUIKUAN;

        // 缓存检查：临时表存在且 endtime 匹配，直接复用（force 时跳过）
        if (!$force) {
            try {
                $cached = $this->getdb()->table($table)->field('endtime')->find();
                if (!empty($cached) && $cached['endtime'] === $endtime) {
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = 'OK (cached)';
                    $rt['data'] = ['table' => str_replace($prefix, '', $table), 'cached' => true];
                    return $rt;
                }
            } catch (\Exception $e) {
                // 表不存在，继续创建
            }
        }

        ini_set('max_execution_time', 600);

        $sqls = [];

        // 1. 清理旧临时表
        $sqls[] = "DROP TABLE IF EXISTS {$table_tk_sum}";
        $sqls[] = "DROP TABLE IF EXISTS {$table}";

        // 2. 创建收款汇总临时表
        $sqls[] = "CREATE TABLE {$table} AS
            SELECT
                djcode, ah, MIN(dzdate) AS dzdate, SUM(CAST(REPLACE(IFNULL(jzje, '0'), ',', '') AS DECIMAL(18,2))) AS sk_je,
                GROUP_CONCAT(DISTINCT dsr) AS dsr, MAX(cbbm) AS cbbm,
                MAX(cbr) AS cbr, MAX(sjy) AS sjy, MAX(yg) AS yg,
                MAX(bg) AS bg, MAX(ay) AS ay, MAX(yh_zt) AS yh_zt,
                0 AS tk_je, '' AS czdate, 0 AS ye,
                '' AS skr, '' AS skr_bank, '' AS skr_account,
                0 AS workdays, '{$endtime}' AS endtime
            FROM {$table_sk}
            WHERE dwid={$dwid} AND dzdate<='{$endtime}'
            GROUP BY djcode, ah";

        // 3. 修改列类型（允许NULL）并添加自增ID、索引
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN czdate VARCHAR(20)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr_bank VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr_account VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN yh_zt VARCHAR(50)";
        $sqls[] = "ALTER TABLE {$table} ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
        $sqls[] = "ALTER TABLE {$table} ADD INDEX idx_djcode_ah (djcode, ah)";
        $sqls[] = "ALTER TABLE {$table} ADD INDEX idx_workdays (workdays)";

        // 4. 创建退款汇总中间表（djcode 不为空的，按 djcode+ah 分组）
        $sqls[] = "CREATE TABLE {$table_tk_sum} AS
            SELECT
                djcode, ah,
                SUM(CAST(REPLACE(IF(je='' OR je IS NULL, '0', je), ',', '') AS DECIMAL(18,2))) AS tk_je,
                MAX(czdate) AS czdate
            FROM {$table_tk}
            WHERE dwid={$dwid} AND czdate<='{$endtime}' AND djcode != '' AND djcode IS NOT NULL
            GROUP BY djcode, ah";
        $sqls[] = "ALTER TABLE {$table_tk_sum} ADD INDEX idx_djcode_ah (djcode, ah)";

        // 5. JOIN UPDATE 第一步：djcode+ah 匹配（处理 djcode 不为空的退款）
        $sqls[] = "UPDATE {$table} s
            LEFT JOIN {$table_tk_sum} t ON s.djcode=t.djcode AND s.ah=t.ah
            SET s.tk_je = COALESCE(t.tk_je, 0), s.czdate = COALESCE(t.czdate, '')";

        // 5b. JOIN UPDATE 第二步：ah 匹配（处理 djcode 为空的退款，累积加到同案号记录）
        $sqls[] = "UPDATE {$table} s
            LEFT JOIN (
                SELECT ah,
                    SUM(CAST(REPLACE(IF(je='' OR je IS NULL, '0', je), ',', '') AS DECIMAL(18,2))) AS tk_je,
                    MAX(czdate) AS czdate
                FROM {$table_tk}
                WHERE dwid={$dwid} AND czdate<='{$endtime}' AND (djcode = '' OR djcode IS NULL)
                GROUP BY ah
            ) t ON s.ah=t.ah
            SET s.tk_je = s.tk_je + COALESCE(t.tk_je, 0),
                s.czdate = IF(t.czdate IS NOT NULL AND (t.czdate > s.czdate OR s.czdate = ''), t.czdate, s.czdate)";

        // 6. 计算余额
        $sqls[] = "UPDATE {$table} SET ye = sk_je - tk_je";

        // 7. 计算停留自然天数
        $sqls[] = "UPDATE {$table}
            SET workdays = GREATEST(DATEDIFF(IF(czdate IS NOT NULL AND czdate != '', czdate, '{$endtime}'), dzdate), 0)";

        // 8. 收款人信息（只对 tk_je>0 的记录，减少子查询次数）
        $sqls[] = "UPDATE {$table} s SET
            skr = (SELECT t.skr FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.djcode=s.djcode AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        $sqls[] = "UPDATE {$table} s SET
            skr_bank = (SELECT t.skr_bank FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.djcode=s.djcode AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        $sqls[] = "UPDATE {$table} s SET
            skr_account = (SELECT t.skr_account FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.djcode=s.djcode AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        // 9. 清理中间表
        $sqls[] = "DROP TABLE IF EXISTS {$table_tk_sum}";

        $exectime = [];
        foreach ($sqls as $index => $_sql) {
            $key_start = "sql_{$index}_start";
            Debug::remark($key_start);
            $message = "OK";

            try {
                $this->getdblink()->execute($_sql);
            } catch (\Exception $e) {
                $err = $e->getMessage();
                if (!json_encode($err)) {
                    $err = _cv_gbk_to_utf8($err);
                }
                $message = $err;
            }

            $key_end = "sql_{$index}_end";
            Debug::remark($key_end);
            $time = Debug::getRangeTime($key_start, $key_end);
            $exectime[] = [
                'sql' => $_sql,
                'index' => $index,
                'time' => $time,
                'message' => $message
            ];
        }

        $info = [];
        $info['table'] = str_replace($prefix, '', $table);
        $info['exectime'] = $exectime;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $info;
        return $rt;
    }

    /**
     * 创建执行款台账按案号汇总临时表
     *
     * @param array $param
     * @return array
     */
    protected function _createtemp_sk_tk_ah_summary($param = [])
    {
        $rt = $this->_rt();
        $endtime = $param['endtime'] ?? date('Y-m-d');
        if (!empty($endtime) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endtime)) {
            $endtime = date('Y-m-d');
        }
        $force = !empty($param['force']);
        $dwid = $this->dwid ?? 0;

        $prefix = config("database.prefix") ?? 'court_';
        $table = $this->getTablename_sk_tk_ah_summary();
        $table_tk_sum = $table . '_tk';
        $table_sk = $prefix . self::TABLE_ADMIN_SHOUKUAN;
        $table_tk = $prefix . self::TABLE_ADMIN_TUIKUAN;

        if (!$force) {
            try {
                $cached = $this->getdb()->table($table)->field('endtime')->find();
                if (!empty($cached) && $cached['endtime'] === $endtime) {
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = 'OK (cached)';
                    $rt['data'] = ['table' => str_replace($prefix, '', $table), 'cached' => true];
                    return $rt;
                }
            } catch (\Exception $e) {
                // 表不存在，继续创建
            }
        }

        ini_set('max_execution_time', 600);

        $sqls = [];

        $sqls[] = "DROP TABLE IF EXISTS {$table_tk_sum}";
        $sqls[] = "DROP TABLE IF EXISTS {$table}";

        $sqls[] = "CREATE TABLE {$table} AS
            SELECT
                '' AS djcode, ah, MIN(dzdate) AS dzdate,
                SUM(CAST(REPLACE(IFNULL(jzje, '0'), ',', '') AS DECIMAL(18,2))) AS sk_je,
                GROUP_CONCAT(DISTINCT dsr) AS dsr, MAX(cbbm) AS cbbm,
                MAX(cbr) AS cbr, MAX(sjy) AS sjy, MAX(yg) AS yg,
                MAX(bg) AS bg, MAX(ay) AS ay, MAX(yh_zt) AS yh_zt,
                0 AS tk_je, '' AS czdate, 0 AS ye,
                '' AS skr, '' AS skr_bank, '' AS skr_account,
                0 AS workdays, '{$endtime}' AS endtime
            FROM {$table_sk}
            WHERE dwid={$dwid} AND dzdate<='{$endtime}'
            GROUP BY ah";

        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN djcode VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN czdate VARCHAR(20)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr_bank VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN skr_account VARCHAR(255)";
        $sqls[] = "ALTER TABLE {$table} MODIFY COLUMN yh_zt VARCHAR(50)";
        $sqls[] = "ALTER TABLE {$table} ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
        $sqls[] = "ALTER TABLE {$table} ADD INDEX idx_ah (ah)";
        $sqls[] = "ALTER TABLE {$table} ADD INDEX idx_workdays (workdays)";

        $sqls[] = "CREATE TABLE {$table_tk_sum} AS
            SELECT
                ah,
                SUM(CAST(REPLACE(IF(je='' OR je IS NULL, '0', je), ',', '') AS DECIMAL(18,2))) AS tk_je,
                MAX(czdate) AS czdate
            FROM {$table_tk}
            WHERE dwid={$dwid} AND czdate<='{$endtime}'
            GROUP BY ah";
        $sqls[] = "ALTER TABLE {$table_tk_sum} ADD INDEX idx_ah (ah)";

        $sqls[] = "UPDATE {$table} s
            LEFT JOIN {$table_tk_sum} t ON s.ah=t.ah
            SET s.tk_je = COALESCE(t.tk_je, 0), s.czdate = COALESCE(t.czdate, '')";

        $sqls[] = "UPDATE {$table} SET ye = sk_je - tk_je";

        $sqls[] = "UPDATE {$table}
            SET workdays = GREATEST(DATEDIFF(IF(czdate IS NOT NULL AND czdate != '', czdate, '{$endtime}'), dzdate), 0)";

        $sqls[] = "UPDATE {$table} s SET
            skr = (SELECT t.skr FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        $sqls[] = "UPDATE {$table} s SET
            skr_bank = (SELECT t.skr_bank FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        $sqls[] = "UPDATE {$table} s SET
            skr_account = (SELECT t.skr_account FROM {$table_tk} t
                WHERE t.dwid={$dwid}
                AND t.ah=s.ah AND t.czdate<='{$endtime}'
                ORDER BY t.czdate DESC LIMIT 1)
            WHERE s.tk_je > 0";

        $sqls[] = "DROP TABLE IF EXISTS {$table_tk_sum}";

        $exectime = [];
        foreach ($sqls as $index => $_sql) {
            $key_start = "sql_{$index}_start";
            Debug::remark($key_start);
            $message = "OK";

            try {
                $this->getdblink()->execute($_sql);
            } catch (\Exception $e) {
                $err = $e->getMessage();
                if (!json_encode($err)) {
                    $err = _cv_gbk_to_utf8($err);
                }
                $message = $err;
            }

            $key_end = "sql_{$index}_end";
            Debug::remark($key_end);
            $time = Debug::getRangeTime($key_start, $key_end);
            $exectime[] = [
                'sql' => $_sql,
                'index' => $index,
                'time' => $time,
                'message' => $message
            ];
        }

        $info = [];
        $info['table'] = str_replace($prefix, '', $table);
        $info['exectime'] = $exectime;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $info;
        return $rt;
    }

    /**
     * 执行款台账汇总表查询
     *
     * @param array $param
     * @return array
     */
    protected function queryList_sk_tk_summary($param = [])
    {
        $rt = $this->_rt();

        $keyword = $param['keyword'] ?? '';
        $cbr = $param['cbr'] ?? '';
        $starttime = $param['starttime'] ?? '';
        $endtime = $param['endtime'] ?? '';
        $datetype = $param['datetype'] ?? 'dzdate';
        $days_range = $param['days_range'] ?? '';
        $balance_filter = $param['balance_filter'] ?? '';
        $summary_mode = $param['summary_mode'] ?? 'bill_case';
        $balance_endtime = $param['balance_endtime'] ?? '';
        $force_recalc = !empty($param['force_recalc']);
        $refresh_summary = !empty($param['refresh_summary']);
        $page = intval($param['page'] ?? 1);
        $pagesize = intval($param['pagesize'] ?? 10);
        $sort = $param['sort'] ?? '';

        $prefix = config("database.prefix") ?? 'court_';
        if ($summary_mode !== 'case_only') {
            $summary_mode = 'bill_case';
        }
        $table_full = $summary_mode === 'case_only'
            ? $this->getTablename_sk_tk_ah_summary()
            : $this->getTablename_sk_tk_summary();
        $table = str_replace($prefix, '', $table_full);

        // 验证余额截止日期格式
        if (!empty($balance_endtime) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $balance_endtime)) {
            $balance_endtime = '';
        }

        // 页面查询只读取当前汇总表；点击“计算”时才刷新汇总表。
        if ($refresh_summary) {
            $tempParam = ['endtime' => $balance_endtime ?: date('Y-m-d'), 'force' => $force_recalc];
            $tempResult = $summary_mode === 'case_only'
                ? $this->_createtemp_sk_tk_ah_summary($tempParam)
                : $this->_createtemp_sk_tk_summary($tempParam);
            if ($tempResult['code'] != self::CODE_SUCCESS) {
                $rt['message'] = '创建临时表失败: ' . $tempResult['message'];
                return $rt;
            }
        }

        // 构建查询条件
        $where = [];

        // 非管理员只能查自己的数据
        if (!$this->isAdmin()) {
            $where['cbr'] = $this->userinfo['username'] ?? '';
        } elseif (!empty($cbr)) {
            $where['cbr'] = ['like', '%' . $cbr . '%'];
        }

        // 关键字模糊搜索
        if (!empty($keyword)) {
            $where['ah|dsr|ay|cbr'] = ['like', '%' . $keyword . '%'];
        }

        // 日期范围
        $datefield = ($datetype === 'czdate') ? 'czdate' : 'dzdate';
        $where_date = $this->_where_date($starttime, $endtime, false);
        if ($where_date) {
            $where[$datefield] = $where_date;
        }

        // 停留时间筛选
        if ($days_range !== '') {
            $where['workdays'] = ['>', intval($days_range)];
        }

        // 余额筛选
        if ($balance_filter !== '') {
            if ($balance_filter === '0') {
                $where['ye'] = 0;
            } else {
                $where['ye'] = ['neq', 0];
            }
        }

        // 排序
        $order = 'dzdate desc';
        if (!empty($sort)) {
            $order = $sort;
        }

        // 统计信息
        $statField = [
            'count(*)' => 'num',
            'sum(sk_je)' => 'sk_je',
            'sum(tk_je)' => 'tk_je',
            'sum(ye)' => 'ye'
        ];
        try {
            $stat = $this->getdb($table)->where($where)->field($statField)->find();

            // 分页查询
            $field = [
                'id', 'djcode', 'ah', 'dzdate', 'czdate',
                'sk_je', 'tk_je', 'ye',
                'workdays', 'dsr', 'cbr', 'cbbm', 'sjy',
                'yg', 'bg', 'ay', 'skr', 'skr_bank', 'skr_account'
            ];

            $data = $this->getdb($table)
                ->where($where)
                ->field($field)
                ->page($page, $pagesize)
                ->order($order)
                ->select();
        } catch (\Exception $e) {
            $err = $e->getMessage();
            if (stripos($err, '1146') === false && stripos($err, 'Base table') === false && stripos($err, "doesn't exist") === false) {
                $rt['message'] = '查询汇总表失败: ' . $err;
                return $rt;
            }
            $stat = [];
            $data = [];
        }

        $newdata = [];
        $newdata['total'] = [
            'num' => intval($stat['num'] ?? 0),
            'sk_je' => $stat['sk_je'] ?? 0,
            'tk_je' => $stat['tk_je'] ?? 0,
            'ye' => $stat['ye'] ?? 0
        ];
        $newdata['items'] = $data;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newdata;
        return $rt;
    }
}
