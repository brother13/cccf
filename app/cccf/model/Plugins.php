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
        $where['dwid'] = $this->dwid;

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
        $field = ['count(*)' => "num", 'sum(jzje)' => "je"];
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
        $where['dwid'] = $this->dwid;

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


        $field = ['count(*)' => "num", 'sum(je)' => "je"];
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

        $count['new10day'] = $this->getdb(self::TABLE_ADMIN_SHOUKUAN)->where($where)->count();

        $where['_exp_'] = Db::raw("(length(yh_enddate)>1 )");
        unset($where['days']);
        $where['leftdays'] = ['<=', self::DGK_ALERT_LEFTDAYS_AKYH];
        

        $count['akyh5day'] = $this->getdb(self::TABLE_ADMIN_SHOUKUAN)->where($where)->count();




        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $count;
        return $rt;
    }
}
