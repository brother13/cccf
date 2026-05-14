<?php

namespace app\cccf\model;

use PDO;
use \think\View;

use \think\Db;
use \think\Debug;
use \think\Log;


/**
 * 案款系统自动升级模块
 * 1、列出可用的升级包，高于当前版本的版本号
 * 2、可下载升级包并逐渐升级。若有版本合并，可直接升级
 * 3、系统记录当前系统版本号，后台统计，并于服务器比对
 * 4、下载安装包至临时目录，并自动升级。同时保留升级包（不删除）
 * 5、升级操作需要做日志记录
 

 *
 * @author netknave
 *
 */
class Update extends Courtcase
{
    const ACTION = "update";
    const COMMENT = "系统升级";









    public function __construct()
    {
        $this->__init();
        parent::__construct();
    }


    /**
     * 初始化收款相关的一些配置及操作
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


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";


        switch ($action) {
            default:
                $rt['message'] = "操作【/" . self::ACTION . "/{$action}】并不存在！";
        }

        return $rt;
    }



    public function updateDBVersion($version = '')
    {


        switch ($version) {
            case '20260130':
                return $this->update_20260130();
                break;
            case 'updateajjbxx': // 更新案件基本信息
                return $this->updateAjjbxxStk();
                break;
            case '20260303': // 增加查询所有人的权限
                return $this->update20260303();
                break;
            case '20260320': // 增加查询所有人的权限
                return $this->update_20260320();
                break;
            default:
                break;
        }

        $rt = $this->_rt();

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = '未找到对应的版本号';
        $rt['data'] = null;
        return $rt;
    }






    // 根据案号刷新收款、退款表的 立案日期、立案标的、案号、执行依据信息
    protected function updateAjjbxxStk()
    {

        $sqls = [];


        $table_shoukuan = "admin_shoukuan";
        $table_shoukuan_ye = "admin_shoukuan_ye";
        $table_tuikuan = "admin_tuikuan";
        $table_ajjbxx = "admin_ajjbxx";

        $table_dgknote = "admin_dgkyenote";

        $table_akyh = "admin_akyh";


        $today = date("Y-m-d");

        $adddays = 15; // 默认增加15天
        $sqls[] = "UPDATE {$table_shoukuan} s,{$table_ajjbxx} a SET s.zxyj=a.zxyj,s.ay=a.ay,s.labd=a.labd,s.larq=a.larq WHERE a.ah=s.ah AND s.zxyj IS NULL;";
        $sqls[] = "UPDATE {$table_shoukuan_ye} s,{$table_ajjbxx} a SET s.zxyj=a.zxyj,s.ay=a.ay,s.labd=a.labd,s.larq=a.larq WHERE a.ah=s.ah AND s.zxyj IS NULL;";
        $sqls[] = "UPDATE {$table_tuikuan} s,{$table_ajjbxx} a SET s.zxyj=a.zxyj,s.ay=a.ay,s.labd=a.labd,s.larq=a.larq WHERE a.ah=s.ah AND s.zxyj IS NULL;";

        // 计算结束时间
        $sqls[] = "update {$table_shoukuan} set enddate=yh_enddate,days=DATEDIFF(dzdate,'{$today}') where yh_enddate is not null and length(yh_enddate)>1 and yh_enddate<>ifnull(enddate,'')";

        // 如果yh_enddate不为空的
        // enddate+15天
        // $sqls[] = "update {$table_shoukuan} set enddate=date_add(enddate,interval {$adddays} day) where enddate is not null and length(enddate)>1 and enddate<>yh_enddate";
        $sqls[] = "update {$table_shoukuan_ye} set calctime = '{$today}',enddate=date_add(dzdate,interval {$adddays} day),days=DATEDIFF(dzdate,'{$today}') where (calctime <>'{$today}' or calctime is null) and length(yh_enddate)<1 ";

        // 计算结束时间
        $sqls[] = "update {$table_shoukuan_ye} set leftdays=DATEDIFF(enddate,'{$today}') where nvl(leftdays,0)<>DATEDIFF(enddate,'{$today}') or leftdays is null";


        // 刷新days，持有时间
        $sqls[] = "update {$table_shoukuan_ye} set days=DATEDIFF('{$today}',dzdate) where nvl(days,0)<>DATEDIFF('{$today}',dzdate) or days is null";



        // 刷新原告被告
        $sqls[] = "update {$table_shoukuan} set yg=SUBSTRING_INDEX(dsr, ';', 1),bg=SUBSTRING_INDEX(dsr, ';', -1) where dsr is not null and (yg is null or bg is null);";
        $sqls[] = "update {$table_shoukuan_ye} set yg=SUBSTRING_INDEX(dsr, ';', 1),bg=SUBSTRING_INDEX(dsr, ';', -1) where dsr is not null and (yg is null or bg is null);";
        $sqls[] = "update {$table_tuikuan} set yg=SUBSTRING_INDEX(dsr, ';', 1),bg=SUBSTRING_INDEX(dsr, ';', -1) where dsr is not null and (yg is null or bg is null);";


        // 刷新备注信息
        // 收款表的
        $sqls[] = "update {$table_shoukuan_ye} s,{$table_dgknote} t set s.othernote=t.note,s.othernote_time=t.updatetime,s.othernote_user=t.username where s.ah=t.caseinfo and s.djcode=t.billno and s.dzdate=t.bankdate and t.st='sk';";
        // 退款表
        $sqls[] = "update {$table_tuikuan} s,{$table_dgknote} t set s.othernote=t.note,s.othernote_time=t.updatetime,s.othernote_user=t.username where s.ah=t.caseinfo and s.djcode=t.billno and s.czdate=t.bankdate and t.st='tk';";



        // 增加延缓的理由刷新

        // 全案延缓

        $sqls[] = "UPDATE {$table_shoukuan_ye} s ,{$table_akyh} t SET s.yh_reason=t.yhreason WHERE s.ah=t.ah  AND  nvl(s.yh_reason,'')<>nvl(t.yhreason,'') AND t.yhlx='全案延缓' AND t.yhzt='延缓';";
        // 单笔延缓，需要根据案号+金额匹配
        $sqls[] = "UPDATE {$table_shoukuan} s ,{$table_akyh} t SET s.yh_reason=t.yhreason WHERE s.ah=t.ah AND s.je=t.je AND  nvl(s.yh_reason,'')<>nvl(t.yhreason,'') AND t.yhlx='单笔延缓' AND t.yhzt='延缓';";

        // 全案延缓，根据案号匹配
        $sqls[] = "update {$table_shoukuan_ye} set yh_reason=null where yh_reason='0' ";

        return $this->updateSQL($sqls);
    }
    // 更新收表款的内容
    protected function update_20260130()
    {

        $sqls = [];
        $sqls[] = "ALTER TABLE `admin_shoukuan`
	ADD COLUMN `ay` VARCHAR(200) NULL DEFAULT NULL COMMENT '案由' AFTER `note`,
	ADD COLUMN `labd` VARCHAR(30) NULL DEFAULT NULL COMMENT '立案标的' AFTER `ay`,
    ADD COLUMN `larq` VARCHAR(30) NULL DEFAULT NULL COMMENT '立案日期' AFTER `labd`,
	ADD COLUMN `zxyj` VARCHAR(100) NULL DEFAULT NULL COMMENT '执行依据' AFTER `labd`,
	ADD COLUMN `yg` VARCHAR(200) NULL DEFAULT NULL COMMENT '原告' AFTER `zxyj`,
	ADD COLUMN `bg` VARCHAR(200) NULL DEFAULT NULL COMMENT '被告' AFTER `yg`,
	ADD COLUMN `othernote` VARCHAR(200) NULL DEFAULT NULL COMMENT '补充说明' AFTER `bg`,
	ADD COLUMN `othernote_time` VARCHAR(20) NULL DEFAULT NULL COMMENT '补充说明填写时间' AFTER `othernote`,
	ADD COLUMN `othernote_user` VARCHAR(20) NULL DEFAULT NULL COMMENT '补充说明填写人' AFTER `othernote_time`;
";
        $sqls[] = "ALTER TABLE `admin_tuikuan`
	ADD COLUMN `ay` VARCHAR(200) NULL DEFAULT NULL COMMENT '案由' AFTER `note`,
	ADD COLUMN `labd` VARCHAR(30) NULL DEFAULT NULL COMMENT '立案标的' AFTER `ay`,
    ADD COLUMN `larq` VARCHAR(30) NULL DEFAULT NULL COMMENT '立案日期' AFTER `labd`,
	ADD COLUMN `zxyj` VARCHAR(100) NULL DEFAULT NULL COMMENT '执行依据' AFTER `labd`,
	ADD COLUMN `yg` VARCHAR(200) NULL DEFAULT NULL COMMENT '原告' AFTER `zxyj`,
	ADD COLUMN `bg` VARCHAR(200) NULL DEFAULT NULL COMMENT '被告' AFTER `yg`,
	ADD COLUMN `othernote` VARCHAR(200) NULL DEFAULT NULL COMMENT '补充说明' AFTER `bg`,
	ADD COLUMN `othernote_time` VARCHAR(20) NULL DEFAULT NULL COMMENT '补充说明填写时间' AFTER `othernote`,
	ADD COLUMN `othernote_user` VARCHAR(20) NULL DEFAULT NULL COMMENT '补充说明填写人' AFTER `othernote_time`;
";


        // 给收款表退款表增加超期日期、超期天数、已存续日期
        $sqls[] = "ALTER TABLE `admin_shoukuan`
	ADD COLUMN `enddate` VARCHAR(20) NULL DEFAULT NULL COMMENT '结束日期' AFTER `othernote_user`,
	ADD COLUMN `leftdays` INT(11) NULL DEFAULT NULL COMMENT '超期天数' AFTER `enddate`;";



        $sqls[] = "ALTER TABLE `admin_shoukuan` ADD COLUMN `calctime` VARCHAR(20) NULL DEFAULT NULL COMMENT '计算时间' AFTER `enddate`";
        $sqls[] = "ALTER TABLE `admin_shoukuan` ADD COLUMN `days` int NULL DEFAULT NULL COMMENT '保有时间' AFTER `calctime`";


        $sqls[] = "
        CREATE TABLE `admin_dgkyenote` (
	`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
	`fydm` VARCHAR(50) NULL DEFAULT NULL COMMENT '法院代码' COLLATE 'utf8mb4_unicode_ci',
	`st` VARCHAR(10) NULL DEFAULT NULL COMMENT '收退类型，sk收款，tk退款' COLLATE 'utf8mb4_unicode_ci',
	`noticenum` VARCHAR(50) NULL DEFAULT NULL COMMENT '出账单号' COLLATE 'utf8mb4_unicode_ci',
	`dwid` INT(11) NULL DEFAULT NULL COMMENT '法院主键',
	`billno` VARCHAR(50) NULL DEFAULT NULL COMMENT '收据号' COLLATE 'utf8mb4_unicode_ci',
	`caseinfo` VARCHAR(50) NULL DEFAULT NULL COMMENT '案号' COLLATE 'utf8mb4_unicode_ci',
	`bankdate` VARCHAR(50) NULL DEFAULT NULL COMMENT '到账日期' COLLATE 'utf8mb4_unicode_ci',
	`note` VARCHAR(200) NULL DEFAULT NULL COMMENT '备注内容' COLLATE 'utf8mb4_unicode_ci',
	`userid` INT(11) NULL DEFAULT NULL COMMENT '用户ID',
	`username` VARCHAR(50) NULL DEFAULT NULL COMMENT '记录人' COLLATE 'utf8mb4_unicode_ci',
	`isvoid` INT(11) NULL DEFAULT '0' COMMENT '0-正常，1-停用',
	`createtime` VARCHAR(20) NULL DEFAULT NULL COMMENT '创建时间' COLLATE 'utf8mb4_unicode_ci',
	`updatetime` VARCHAR(20) NULL DEFAULT NULL COMMENT '修改时间' COLLATE 'utf8mb4_unicode_ci',
	`isdel` INT(11) NULL DEFAULT '0' COMMENT '0-正常，1-已删除',
	`deltime` VARCHAR(20) NULL DEFAULT NULL COMMENT '删除时间' COLLATE 'utf8mb4_unicode_ci',
	`rank` INT(11) NULL DEFAULT '0' COMMENT '从小到大排序',
	PRIMARY KEY (`id`) USING BTREE
)
COMMENT='案款未发还备注表'
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB

;
        ";
        // 给备注表增加字段，收款类型以及出账单号
        $sqls[] = "ALTER TABLE `admin_dgkyenote` ADD COLUMN `st` VARCHAR(10) NULL DEFAULT NULL COMMENT '收退类型，sk收款，tk退款' AFTER `fydm`";
        $sqls[] = "ALTER TABLE `admin_dgkyenote` ADD COLUMN `noticenum` VARCHAR(50) NULL DEFAULT NULL COMMENT '出账单号' AFTER `st`";



        return $this->updateSQL($sqls);
    }

    protected function updateSQL($sqls = [])
    {
        $rt = $this->_rt();




        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';



        Debug::remark("updatesql_start");

        // halt($allfydm);
        $results = [];

        foreach ($sqls as $sql) {
            $item = [];
            $item['sql'] = $sql;
            $item['num'] = 0;
            Debug::remark("sql_start");

            try {

                $n = $this->getdb()->execute($sql);
                $item['num'] = $n;
                $item['message'] = "OK";
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();

                // dump($sql);
                // halt($errmsg);
                if (!json_encode($errmsg)) {
                    $errmsg = _cv_gbk_to_utf8($errmsg);
                }
                $item['message'] = $errmsg;
            }
            Debug::remark("sql_end");
            $item['exectime'] = Debug::getRangeTime("sql_start", "sql_end") . ' s';
            $results[] = $item;
        }


        Debug::remark("updatesql_end");

        $exectime = Debug::getRangeTime("updatesql_start", 'updatesql_end') . 's';

        $rt['exectime'] = $exectime;
        $rt['data'] = $results;
        return $rt;
    }

    // 增加台账查询所有人的权限
    protected function update20260303()
    {

        $sqls = [];


        $sqls[] = "REPLACE INTO `admin_rule` VALUES (25, 'ZXTZ_QUERY_ALL', '台账-查询所有人', '', '', '', 0, '', '', 0, '', 0);";


        return $this->updateSQL($sqls);
    }

    protected function update_20260320()
    {

        $sqls = [];
        $sqls[] = "ALTER TABLE `admin_cflist`
	ADD COLUMN `zxay` VARCHAR(50) NULL DEFAULT NULL COMMENT '案由' AFTER `sjkhje`,
	ADD COLUMN `zxyjah` VARCHAR(50) NULL DEFAULT NULL COMMENT '执行依据案号' AFTER `zxay`;
";

        // $sqls[] = "drop table if exists admin_postlog;";
        $sqls[] = "
        create table admin_postlog
(
   id                   int not null auto_increment comment '主键',
   fydm                 varchar(10) comment '法院代码',
   posttime             varchar(20) comment '提交时间',
   postdata             longtext comment '提交内容',
   ckinfo               text comment '解析后的数据',
   clientip             varchar(20) comment '客户端IP地址',
   caseinfo             varchar(50) comment '案号',
   datasize             int comment '数据大小',
   datahash             varchar(50) comment '数据校验',
   isused               int default 0,
   usetime              varchar(20),
   username             varchar(50),
   isvoid               int default 0 comment '0-正常，1-停用',
   createtime           varchar(20) comment '创建时间',
   updatetime           varchar(20) comment '修改时间',
   isdel                int default 0 comment '0-正常，1-已删除',
   deltime              varchar(20) comment '删除时间',
   rank                 int default 0 comment '从小到大排序',
   primary key (id)
);
        ";
        $sqls[] = "alter table admin_postlog comment '提交信息日志';";
        $sqls[] = "REPLACE INTO `admin_rule` VALUES (26, 'CFTZ_EDIT_OTHER', '查封台账-允许编辑他们记录', '', '', '', 0, '', '', 0, '', 0);";

        return $this->updateSQL($sqls);
    }
}
