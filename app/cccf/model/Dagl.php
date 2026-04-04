<?php

namespace app\cccf\model;

use PDO;
use \think\Db;

/**
 * 档案管理查询
 *
 * @author netknave
 *
 */
class Dagl extends Common
{
    const ACTION = "cccf";
    const COMMENT = "档案管理";
    const FIELD = "*";
    const FIELD_FILTER = "YGR|BGR|SPZ|SJY"; // 快速搜索字段
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

        // 民事档案
        $table  = [];
        $table['code'] = 'ms'; // 字符
        $table['table'] = "da_msda1"; // 表名
        $table['name'] = "民事案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 150];
        $field[] = ['field' => 'YGR', 'name' => '原告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'BGR', 'name' => '被告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'SPZ', 'name' => '审判长', "filter" => true];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果'];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'CBR', 'name' => '承办人', "filter" => true];
        $field[] = ['field' => 'SPY', 'name' => '审判员', "filter" => true];
        $field[] = ['field' => 'DLSPY', 'name' => '代理审判员'];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $field[] = ['field' => 'DISANREN', 'name' => '第三人'];
        $table['field'] = $field;


        $alltable[$table['code']] = $table;



        // 经济档案
        $table  = [];
        $table['code'] = 'jinji'; // 字符
        $table['table'] = "da_jijida1"; // 表名
        $table['name'] = "经济案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 200];
        $field[] = ['field' => 'YGR', 'name' => '原告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'BGR', 'name' => '被告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果'];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $table['field'] = $field;


        $alltable[$table['code']] = $table;



        // 执行档案
        $table  = [];
        $table['code'] = 'zx'; // 字符
        $table['table'] = "da_zxda1"; // 表名
        $table['name'] = "执行案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 150];
        $field[] = ['field' => 'SQZXR', 'name' => '申请执行人', "filter" => true, "width" => 200];
        $field[] = ['field' => 'BZXR', 'name' => '被执行人', "filter" => true, "width" => 200];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];
        $field[] = ['field' => 'ZXBD', 'name' => '执行标的'];
        $field[] = ['field' => 'SARQ', 'name' => '收案日期'];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $field[] = ['field' => 'JAFS', 'name' => '结案方式'];
        $field[] = ['field' => 'ZXJG', 'name' => '执行结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'ZXY', 'name' => '执行员', "filter" => true];


        $table['field'] = $field;


        $alltable[$table['code']] = $table;



        // 刑事档案
        $table  = [];
        $table['code'] = 'xs'; // 字符
        $table['table'] = "da_xsda1"; // 表名
        $table['name'] = "刑事案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 150];
        $field[] = ['field' => 'QSJG', 'name' => '起诉机关', "width" => 200];
        $field[] = ['field' => 'BGR', 'name' => '被告', "filter" => true, "width" => 150];
        $field[] = ['field' => 'SPZ', 'name' => '审判长', "filter" => true];
        $field[] = ['field' => 'PSY1', 'name' => '陪审员', "filter" => true];
        $field[] = ['field' => 'SARQ', 'name' => '收案日期'];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果', "width" => 200];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'RECSN', 'name' => '记录编码'];
        $field[] = ['field' => 'CBR', 'name' => '承办人', "filter" => true];
        $field[] = ['field' => 'SPY1', 'name' => '审判员1', "filter" => true];
        $field[] = ['field' => 'DLSPY1', 'name' => '代理审判员1', "filter" => true];
        $field[] = ['field' => 'SPY2', 'name' => '审判员2', "filter" => true];
        $field[] = ['field' => 'DLSPY2', 'name' => '代理审判员2', "filter" => true];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];

        $table['field'] = $field;


        $alltable[$table['code']] = $table;



        // 审监档案
        $table  = [];
        $table['code'] = 'sj'; // 字符
        $table['table'] = "da_sjda1"; // 表名
        $table['name'] = "审监案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'RECSN', 'name' => '记录编码'];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 150];
        $field[] = ['field' => 'SSR', 'name' => '申诉人', "filter" => true, "width" => 200];
        $field[] = ['field' => 'BSSR', 'name' => '被申诉人', "filter" => true, "width" => 200];
        $field[] = ['field' => 'SPZ', 'name' => '审判长', "filter" => true];
        $field[] = ['field' => 'SPY', 'name' => '审判员', "filter" => true];
        $field[] = ['field' => 'DLSPY', 'name' => '代理审判员'];
        $field[] = ['field' => 'RMPSY', 'name' => '人民陪审员'];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];
        $field[] = ['field' => 'CBR', 'name' => '承办人', "filter" => true];
        $field[] = ['field' => 'SARQ', 'name' => '收案日期'];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果'];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'ZSJG', 'name' => '终审结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'BEIZHU', 'name' => '备注'];


        $table['field'] = $field;


        $alltable[$table['code']] = $table;


        // 行政档案
        $table  = [];
        $table['code'] = 'xz'; // 字符
        $table['table'] = "da_xzda1"; // 表名
        $table['name'] = "行政案件档案";



        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由', "width" => 150];
        $field[] = ['field' => 'YGR', 'name' => '原告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'BGR', 'name' => '被告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'SPZ', 'name' => '审判长', "filter" => true];
        $field[] = ['field' => 'PSY1', 'name' => '陪审员1'];
        $field[] = ['field' => 'PSY2', 'name' => '陪审员2'];
        $field[] = ['field' => 'SJY', 'name' => '书记员'];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果'];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'SARQ', 'name' => '收案日期'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'SPY1', 'name' => '审判员1'];
        $field[] = ['field' => 'SPY2', 'name' => '审判员2'];
        $field[] = ['field' => 'DISANREN', 'name' => '第三人'];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];

        $table['field'] = $field;


        $alltable[$table['code']] = $table;





        // 少刑档案
        $table  = [];
        $table['code'] = 'sx'; // 字符
        $table['table'] = "da_sxda1"; // 表名
        $table['name'] = "少刑案件档案";

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'RECSN', 'name' => '记录编码'];
        $field[] = ['field' => 'ND', 'name' => '案号年', 'show' => false];
        $field[] = ['field' => 'ZHI', 'name' => '案件字号', 'show' => false];
        $field[] = ['field' => 'HAO', 'name' => '案件序号', 'show' => false, "filter" => true];
        $field[] = ['field' => 'AY', 'name' => '案由'];
        $field[] = ['field' => 'QSJG', 'name' => '起诉机关', "width" => 200];
        $field[] = ['field' => 'BGR', 'name' => '被告', "filter" => true, "width" => 200];
        $field[] = ['field' => 'SPZ', 'name' => '审判长', "filter" => true];
        $field[] = ['field' => 'PSY1', 'name' => '陪审员', "filter" => true];
        $field[] = ['field' => 'SARQ', 'name' => '收案日期'];
        $field[] = ['field' => 'YSJG', 'name' => '一审结果', "width" => 200];
        $field[] = ['field' => 'RSJG', 'name' => '二审结果'];
        $field[] = ['field' => 'GDRQ', 'name' => '归档日期'];
        $field[] = ['field' => 'JARQ', 'name' => '结案日期'];
        $field[] = ['field' => 'GDCS', 'name' => '归档册数'];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'JSR', 'name' => '接收人'];
        $field[] = ['field' => 'RECSN', 'name' => '记录编码'];
        $field[] = ['field' => 'CBR', 'name' => '承办人', "filter" => true];
        $field[] = ['field' => 'SPY1', 'name' => '审判员1', "filter" => true];
        $field[] = ['field' => 'DLSPY1', 'name' => '代理审判员1', "filter" => true];
        $field[] = ['field' => 'SPY2', 'name' => '审判员2', "filter" => true];
        $field[] = ['field' => 'DLSPY2', 'name' => '代理审判员2', "filter" => true];
        $field[] = ['field' => 'SJY', 'name' => '书记员', "filter" => true];

        $table['field'] = $field;


        $alltable[$table['code']] = $table;






        // 文书文件
        $table  = [];
        $table['code'] = 'wswj'; // 字符
        $table['table'] = "da_wswj"; // 表名
        $table['name'] = "文书文件";
        $table['iscase'] = false; // 注意，这是非案件，必须将此值设为 false

        $field = [];
        $field[] = ['field' => 'RECID', 'name' => '内部主键', 'show' => false];
        $field[] = ['field' => 'BGQX', 'name' => '保管期限'];
        $field[] = ['field' => 'ZRZ', 'name' => '责任者', 'filter' => true, "width" => 200];
        // $field[] = ['field' => 'RECSN', 'name' => '记录编码'];
        $field[] = ['field' => 'YH', 'name' => '页号'];
        $field[] = ['field' => 'HH', 'name' => '盒号'];
        $field[] = ['field' => 'JH', 'name' => '卷号'];
        $field[] = ['field' => 'WJBH', 'name' => '文件编号', 'filter' => true, "width" => 180];
        $field[] = ['field' => 'BZ', 'name' => '备注', 'filter' => true];
        $field[] = ['field' => 'TM', 'name' => '题名', 'filter' => true, 'width' => 250];
        $field[] = ['field' => 'SJ', 'name' => '日期'];
        $field[] = ['field' => 'WYH', 'name' => '尾页号'];
        $field[] = ['field' => 'MLH', 'name' => '目录号'];
        $field[] = ['field' => 'ND', 'name' => '年度'];
        $field[] = ['field' => 'YS', 'name' => '页数'];
        $field[] = ['field' => 'SBJH', 'name' => '件号'];
        $field[] = ['field' => 'QZMC', 'name' => '全宗名称', "width" => 200];
        $field[] = ['field' => 'WH', 'name' => '文号'];
        $field[] = ['field' => 'QZH', 'name' => '全宗号'];





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
    public function index($action = '', $data = [])
    {
        $rt = $this->_rt();


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";
        $type = $data['type'] ?? 'ms';



        switch ($action) {
            case "list": // 列表

                $rt = $this->getList($key, $type, $isvoid, $page, $pagesize);
                break;

            case 'down': //下载，同列表
                $pagesize = 99999999;
                $rt = $this->getList($key, $type, $isvoid, $page, $pagesize);
                break;

            case 'info': // 获取明细
                $id = $data[self::FIELD_PK] ?? '';
                if (empty($id)) {
                    $rt['message'] = "不能为空！";
                    return $rt;
                }
                $rt = $this->getinfo($id, $type);
                break;
            case 'type': // 获取数据类型表
                $rt = $this->getTableList();
                break;

            default:
                $rt['message'] = "操作【/" . $this->ACTION . "/{$action}】并不存在！";
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
        $table = "";
        if (empty($type)) {
            $type = 'ms';
        }

        $config = $this->alltable[$type];
        $table = $config['table'];
        // $order = $config['order'] ?? "jarq desc";
        $selectfield = $config['selectfield'] ?? '*';
        $filter = $config['filter'] ?? "";

        $field = $config['field'] ?? '';



        if (!empty($key) && !empty($filter)) {
            $where[$filter] = ['like', "%{$key}%"];
        }


        $db = $this->getdb("user");
        $num = $db->table($table)->where($where)->count();
        $data = $db->table($table)->field($selectfield)->where($where)->page($page, $pagesize)->select();


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
     * 获取明细信息
     * @param  [type] $id 
     * @return [type]     [description]
     */
    public function getinfo($id, $type)
    {

        $rt = $this->_rt();

        if (empty($id)) {
            $rt['code'] = self::CODE_ERROR;
            $rt['data'] = [];
            $rt['message'] = "ID不能为空";
            return $rt;
        }
        if (!array_key_exists($type, $this->alltable)) {
            $rt['message'] = "类型不正确";
            return $rt;
        }

        $config = $this->alltable[$type];
        $pk = $config['pk'] ?? 'RECID';

        $field = '*';
        $where = [];
        $where[$pk] = $id;
        $table = $config['table'];

        $data = $this->getdb("user")->table($table)->where($where)->find();
        //获取roles
        if (!$data) {
            $rt['message'] = "数据不存在";
            return $rt;
        }
        $newdata = [];
        foreach($config['field'] as $f){
            $temp = [];
            $temp['code'] = $f['field'];
            $temp['name'] = $f['name'];
            $temp['data'] = $data[$f['field']] ?? '';
            $newdata[] = $temp;
            // $newdata[$f['code']]
        }
        


        
        $rt['data'] = $newdata;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "";
        return $rt;
    }
}
