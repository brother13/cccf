<?php

namespace app\cccf\model;

use think\Log;
use \think\Cache;


/**
 * 案款管理基础模块
 *
 * @author netknave
 *
 */
class Courtcase extends Common
{

    const TABLE_CASESK = "casesk";
    const TABLE_CASETK = "casetk";
    const TABLE_CASEBANK = "casebank";

    const TABLE_CASEBILL = "casebill";


    const TABLE_KMSET = "kmset";

    const TABLE_CASETYPE = "casetype"; // 案件字号

    // 打印模板表
    const TABLE_TEMPLATE = "template"; // 打印模板
    // 日志表
    const TABLE_OPERLOG = "operlog";

    // 通知书表（新）

    const TABLE_ATTACHMENT = "attachment";

    // 导出文件记录


    // 币种表
    // 科目设置

    // 批量支付通知书表


    // 收退款方式
    const TABLE_ACCOUNT  = "account";

    const TABLE_CLASS = "class";

    const TABLE_CURRENCY = "currencys";



    const TABLE_DEPT = "dept";
    const TABLE_USER = "user";
    // 高院认领日志


    // 同步高院数据日志信息

    // 代管款情况表
    const VIEW_ALLDGK = "alldgk";



    const VIEW_STLIST = "stlist"; // 所有收退情况

    // 案件基本信息表
    const TABLE_AJJBXX = "ajjbxx";


    // 本地存储的文件表
    const FIELD_LOGINMETHOD = "isjudge"; // 使用这个字段来表示是否允许登录
    const FIELD_HY_USERCODE = "jobpost"; // 用这个字段表示 华宇的ID，如2141374222
    const FIELD_HY_USERID = "cardno"; // 使用这个字段保存 华宇的职员编码



    const TEMPLATE_NOTICE_GZTZS = "gztzs"; // 模板类型里的更正通知书

    const TEMPLATE_NOTICE_FMKGZ = "fmkgz"; // 罚没款告之单
    const TEMPLATE_NOTICE_FMKRL = "fmkrl"; // 罚没款认领

    const TEMPLATE_NOTICE_FMKKP = "fmkkp"; // 罚没款开票








    const TYPEID_SK_DGK = 101; // 代管款收款
    const TYPEID_SK_SSF = 104; // 诉讼费收款
    const TYPEID_SK_SHSF = 105; // 上诉费
    const TYPEID_SK_BZJ = 102; // 保证金收款
    const TYPEID_SK_FMK = 103; // 罚没款收款
    const TYPEID_SK_GGF = 108; // 公告费收款
    const TYPEID_SK_SECSSF = 109; // 二审诉讼费
    const TYPEID_SK_JAILFMK = 111; // 监狱罚没款

    // 诉讼费移入

    const TYPEID_SK_SSFYR = 113;



    const TYPEID_SK_PETTYMONEY = 121; // 诉讼费备用金入账


    // 新的银行流水类型

    const TYPEID_BANK_DETAIL = 310;





    const TYPEID_TK_SSF = 201; // 诉讼费退款
    const TYPEID_TK_DGK = 203; // 代管款退款
    const TYPEID_TK_BZJ = 204; // 保证金/拍卖保证金退款
    const TYPEID_TK_YSSSF = 205; // 移出诉讼费
    const TYPEID_TK_SHSF = 202; // 上诉费退款




    const TYPEID_CASEBILL_DGK = 401; // 收款_暂存款入账
    const TYPEID_CASEBILL_SSF = 403; // 收款_诉讼费入账
    const TYPEID_CASEBILL_PMBZJ = 404; // 收款_拍卖保证金入账
    const TYPEID_CASEBILL_FMK = 405; // 收款_罚没款入账
    const TYPEID_CASEBILL_SHSF = 407; // 收款_上诉费入账




    const TYPEID_CASEBILL_UPLOAD = 501; // 不明款上缴




    const TYPEID_BANK_DAIJI = 301; // 贷记凭证
    const TYPEID_BANK_DIANHUI = 302; // 电汇凭证
    const TYPEID_BANK_ZHIPIAO = 303; // 支票
    const TYPEID_BANK_JINZHANG = 304; // 进账单
    const TYPEID_BANK_JIAOKUAN = 305; // 现金缴款单



   






    const TYPEID_SK_ALL = [self::TYPEID_SK_BZJ, self::TYPEID_SK_DGK, self::TYPEID_SK_FMK, self::TYPEID_SK_GGF, self::TYPEID_SK_SHSF, self::TYPEID_SK_SSF, self::TYPEID_SK_JAILFMK, self::TYPEID_SK_SECSSF];
    const TYPEID_TK_ALL = [self::TYPEID_TK_BZJ, self::TYPEID_TK_DGK, self::TYPEID_TK_SSF, self::TYPEID_TK_YSSSF, self::TYPEID_TK_SHSF];
    const TYPEID_CASEBILL_ALL = [self::TYPEID_CASEBILL_DGK, self::TYPEID_CASEBILL_SSF, self::TYPEID_CASEBILL_PMBZJ, self::TYPEID_CASEBILL_FMK, self::TYPEID_CASEBILL_SHSF];
    const TYPEID_BANK_ALL = [self::TYPEID_BANK_DAIJI, self::TYPEID_BANK_DIANHUI, self::TYPEID_BANK_JIAOKUAN, self::TYPEID_BANK_JINZHANG, self::TYPEID_BANK_ZHIPIAO, self::TYPEID_BANK_DETAIL];


    const TEMPLATE_RL = "rl";





    // 以下是日志相关的常量
    const LOG_FIELD_UNCHECK = ["createtime", "updatetime"];

    const LOG_ACTION_UPDATE = "update";
    const LOG_ACTION_DELETE = "delete";
    const LOG_ACTION_INSERT = "insert";
    const LOG_ACTION_VOID = "void";
    const LOG_ACTION_UNVOID = "unvoid";
    const LOG_ACTION_BANKTP = "banktp";


    const CACHE_TIME = 60;




    const USERTYPE_CW = "cw"; // 财务
    const USERTYPE_ADMIN = "admin"; // 管理员
    const USERTYPE_CBR = "cbr"; // 承办人
    const USERTYPE_SJY = "sjy"; // 书记员
    const USERTYPE_TLD = "tld"; // 庭长
    const USERTYPE_YZ = "yz"; // 院长 / 院领导
    const USERTYPE_OTHER = "other"; // 其它

    // 增加类型 庭长、院长

    // 代管款退回来的暂存户
    const TYPEID_SK_DGKGK = 131; // 代管款国库收款
    const TYPEID_TK_DGKGK = 231; // 代管款国库退回款做转

    // 不明款退回国库
    const TYPEID_SK_GKCASEBILL = 132; // 国库退回款项入账
    const TYPEID_TK_GKCASEBILL = 232; // 转出国库款项入账



    const YE_KINGZERO = 0.00001; // 人大金仓大于0，需要用一个小数，不能用0.不然会有浮点差

    const TABLE_USERCONFIG = "userconfig"; // 用户配置











    protected $cache_time = 60;


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
    protected function __init()
    {

        $config = config("courtcase") ?? [];
        $this->cache_time = $config['cache_time'] ?? self::CACHE_TIME;
    }


    protected function getTypeList()
    {
        $where = [];
        $where['dwid'] = ['in',[0,$this->dwid]];
        $where['isvoid&isdel'] = 0;
        $where['classtype'] = "typeid";
        $order = "classcode";


        $data = $this->getdb("class")->where($where)->order($order)->select();

        $newdata = [];
        foreach ($data as $row) {
            $typeid = $row['classcode'];
            $id = "typeid_" . $typeid;
            $newdata[$id] = $row['classname'];
        }

        return $newdata;
    }
    

    protected function getUserTypeList()
    {

        $rt = $this->_rt();

        $newdata = [];
        $newdata[] = ["code" => self::USERTYPE_CBR, "label" => "承办人"];
        $newdata[] = ["code" => self::USERTYPE_TLD, "label" => "庭长"];
        $newdata[] = ["code" => self::USERTYPE_YZ, "label" => "院领导"];
        $newdata[] = ["code" => self::USERTYPE_SJY, "label" => "书记员"];
        $newdata[] = ["code" => self::USERTYPE_CW, "label" => "财务"];
        $newdata[] = ["code" => self::USERTYPE_ADMIN, "label" => "管理员"];
        $newdata[] = ["code" => self::USERTYPE_OTHER, "label" => "其它"];

        $map = [];
        foreach ($newdata as $row) {
            $code = $row['code'];
            $map[$code] = $row;
        }
        $rtdata = [];
        $rtdata['data'] = $newdata;
        $rtdata['map'] = $map;

        $rt['code'] = self::CODE_SUCCESS;

        $rt['message'] = "OK";
        $rt['data'] = $rtdata;
        return $rt;
    }

    
    /**
     * 根据单据ID获取表名
     *
     * @param [type] $typeid
     * @return string
     */
    protected function getTableByTypeid($typeid)
    {

        $table = "";

        if (in_array($typeid, self::TYPEID_SK_ALL)) {
            $table = self::TABLE_CASESK;
        }
        if (in_array($typeid, self::TYPEID_TK_ALL)) {
            $table = self::TABLE_CASETK;
        }
        if (in_array($typeid, self::TYPEID_CASEBILL_ALL)) {
            $table = self::TABLE_CASEBILL;
        }
        if (in_array($typeid, self::TYPEID_BANK_ALL)) {
            $table = self::TABLE_CASEBANK;
        }

        // 更正通知书
        if ($typeid == self::TEMPLATE_NOTICE_GZTZS) {
            $table = self::TABLE_NOTICE_TK;
        }

        // 罚没款告知单 罚没款认领
        if (in_array($typeid, [self::TEMPLATE_NOTICE_FMKGZ, self::TEMPLATE_NOTICE_FMKRL, self::TEMPLATE_NOTICE_FMKKP])) {
            $table = self::TABLE_NOTICE_SK;
        }



        return $table;
    }

    /**
     * 对获取到的收款、退款、银行、款项入账 等数据，添加以下信息
     * 1、bigje - 大写金额
     * 2、jetext - 金额栏（*100，去掉小数，并前面加￥）
     * 3、operdate_year - 操作日期年份
     * 4、operdate_month - 操作日期月份
     * 5、operdate_day - 操作日期日期
     * 6、printtime - 打印日期
     *
     * @param array $data
     * @return void
     */
    protected function _add_printinfo(&$data = [])
    {

        $je = $data['je'] ?? 0;
        $typeid = $data['typeid'] ?? '';


        $data['bigje'] = $this->num_to_rmb($je);


        // 如果是罚没款，则额外判断一个字段

        // 以下是罚没款相关的几个大写款项的配置
        $fmktype = $data['fmktype'] ?? '';
        $data['bigje_fj'] = ""; // 罚金,罚款
        $data['bigje_msk'] = ""; // 没收款
        $data['bigje_sjk'] = ""; // 收缴款
        if ($fmktype == '罚金' || $fmktype == '罚款' || $fmktype == '') { // 为空默认是罚金
            $data['bigje_fj'] = $this->getChineseJeString($je, 9);
        } else if ($fmktype == '没收款') {
            $data['bigje_msk'] = $this->getChineseJeString($je, 9);
        } else if ($fmktype == '收缴款') {
            $data['bigje_sjk'] = $this->getChineseJeString($je, 9);
        }


        // 判断如果是fmktype为“多项组合”，仅针对罚没款

        if (in_array($typeid, [self::TYPEID_SK_FMK, self::TYPEID_SK_JAILFMK])) {

            $fmkdetail = $data['fmkdetail'] ?? '';
            $fmkinfo = _cv_to_array($fmkdetail);
            if (!$fmkinfo) {
                $fmkinfo = [];
            }

            $fmkinfo['fj'] = $fmkinfo['fj'] ?? 0;
            $fmkinfo['msk'] = $fmkinfo['msk'] ?? 0;
            $fmkinfo['sjk'] = $fmkinfo['sjk'] ?? 0;
            $data['fmkdetail'] = $fmkinfo;



            if ($fmktype == '多项组合' && !empty($fmkdetail)) {

                if ($fmkinfo) {
                    $je_fk = trim($fmkinfo['fj'] ?? '');
                    $je_msk = trim($fmkinfo['msk'] ?? '');
                    $je_sjk = trim($fmkinfo['sjk'] ?? '');
                    if (!empty($je_fk)) {
                        $data['bigje_fj'] = $this->getChineseJeString($je_fk, 9);
                    }
                    if (!empty($je_msk)) {
                        $data['bigje_msk'] = $this->getChineseJeString($je_msk, 9);
                    }
                    if (!empty($je_sjk)) {
                        $data['bigje_sjk'] = $this->getChineseJeString($je_sjk, 9);
                    }
                }
            }
        }




        $data['jetext'] = '￥' . round($je * 100);


        $data['printtime'] = getNowTime();
        $data['nowtime'] = date('H:i:s'); // 增加打印时间信息，浦东需要
        $time = strtotime($data['operdate'] ?? getNowTime());
        $data['operdate_year'] = date('Y', $time);
        $data['operdate_year_short'] = date('y', $time);
        $data['operdate_month'] = date('m', $time);
        $data['operdate_day'] = date("d", $time);
        $data['je2'] = number_format($je, 2); // 带千分位金额 

        // 罚没款专用金额栏
        $data['bigjetext'] = $this->getChineseJeString($je, 9);

        $fmkje = $data['bigje'] . " (" . $data['je2'] . ')';
        $data['fmkje'] = $fmkje;

        if (array_key_exists("opertime", $data)) {
            $opertime_chs = date('Y年m月d日', strtotime($data['opertime']));
            $data['opertime_chs'] = $opertime_chs;
        }

        if (array_key_exists("cbrname", $data)) {
            $cbr = $data['cbr'] ?? $data['cbrname'];

            if (empty($data['cbr'])) {
                $data['cbr'] = $cbr;
            }
            if (empty($data['cbrname'])) {
                $data['cbrname'] = $cbr;
            }
        }








        $data['operdate_year_chs'] = $this->changeDateToChinese($data['operdate_year'], 'year');
        $data['operdate_month_chs'] = $this->changeDateToChinese($data['operdate_month'], 'month');
        $data['operdate_day_chs'] = $this->changeDateToChinese($data['operdate_day'], 'date');

        $typeid = $data['typeid'] ?? self::TYPEID_SK_DGK;
        if ($typeid > 300 && $typeid < 400) {
            $banktime = strtotime($data['bankdate']);
            $data['bankdate_year'] = date('Y', $banktime);
            $data['bankdate_year_short'] = date('y', $banktime);
            $data['bankdate_month'] = date('m', $banktime);
            $data['bankdate_day'] = date("d", $banktime);
            $data['bankdate_year_chs'] = $this->changeDateToChinese($data['bankdate_year'], 'year');
            $data['bankdate_month_chs'] = $this->changeDateToChinese($data['bankdate_month'], 'month');
            $data['bankdate_day_chs'] = $this->changeDateToChinese($data['bankdate_day'], 'date');
        }


        // ["field"=>"bigje","title"=>"大写金额","fontsize"=>18,"fontwidth"=>700],
        // ["field"=>"jetext","title"=>"金额栏","align"=>"right","fontsize"=>18,"fontwidth"=>700,"space"=>12], 
        // ["field"=>"operdate_year","title"=>"操作日期年"],
        // ['field'=>"operdate_month","title"=>"操作日期月"],
        // ['field'=>"operdate_day","title"=>"操作日期日"],
        // ['field'=>'printtime',"title"=>"打印时间"]

        // 如果是退款，则添加原收金额信息

        $typeid = $data['typeid'] ?? '';
        $frombill = $data['frombill'] ?? '';
        $data['ysje'] = "";
        if (in_array($typeid, [203, 204, 201])) {
            // 退款，需要获取原收金额
            $where = [];
            $where['dwid']  = $this->dwid;
            $where['isvoid&isdel'] = 0;
            $sktypeid = 0;
            switch ($typeid) {
                case self::TYPEID_TK_DGK:
                    $sktypeid = self::TYPEID_SK_DGK;
                    break;
                case self::TYPEID_TK_BZJ:
                    $sktypeid = self::TYPEID_SK_BZJ;
                    break;
                case self::TYPEID_TK_SSF:
                    $sktypeid = ['in', [self::TYPEID_SK_SSF, self::TYPEID_SK_SECSSF]];
                    break;
            }
            if (empty($typeid)) {
                return false;
            }
            // 获取金额
            $where['typeid'] = $sktypeid;
            $where['billno'] = $frombill;
            $field_sk = "je";
            $skinfo = $this->getdb(self::TABLE_CASESK)->where($where)->find();
            $ysje = $skinfo['je'] ?? '';
            if (!empty($ysje)) {
                $data['ysje'] = number_format($ysje, 2);
            }
        }
    }

    
    /**
     * 生成大写金额
     *
     * @param [number] $num
     * @return string
     */
    public function num_to_rmb($num)
    {

        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿拾佰仟";
        //精确到分后面就不要了，所以只留两个小数位
        // dump($num);
        $num = round($num, 2);

        // dump($num);

        // halt($num);
        //将数字转化为整数
        $num = $num * 100;
        // dump($num);
        if (strlen($num) > 12) {
            return "金额太大，请检查";
        }

        $i = 0;
        $c = "";
        // dump($num);
        while (1) {
            if ($i == 0) {
                //获取最后一位数字 
                // dump($i." : ".$num);       
                $n = substr($num, strlen($num) - 1, 1);
                // dump($n);
            } else {
                // dump($i.' : '.$num);
                $n = $num % 10;
                // dump($n);
            }    //每次将最后一位数字转化为中文    
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            // dump($p1);dump($p2);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了    
            // dump($num);
            // $num = $num / 10;
            // @todo hacker 我也不知道为什么会这样，直接除10再int的话，45469/10 再int ，会变成45468 ，只能使用黑科技+0.000001
            $num = $num / 10 + 0.0000001;
            // dump('before'.$num);
            $num = floor($num);
            $num = (int) $num;
            // dump($num);
            //结束循环    
            if ($num == 0) {
                break;
            }
        }

        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            //utf8一个汉字相当3个字符    
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”    
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }


        //这个是为了去掉类似23.0中最后一个“零”字

        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }


        // 将特殊字去掉，如 亿万元
        $c = str_replace(['亿万元'], ['亿元'], $c);



        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元整";
        } else {
            // 判断是否包含角分
            if (strpos($c, '角') || strpos($c, '分')) {
                return $c;
            } else {
                return $c . "整";
            }
        }
    }
    
    /**
     * 生成大写金额栏，如 零零零零壹零零零零，表示 100元
     *
     * @param string $je
     * @param integer $num
     * @return void
     */
    protected function getChineseJeString($je = '', $num = 9)
    {
        $str = number_format($je, 2, '', ''); // 生成金额位数
        $str = str_pad($str, $num, '0', STR_PAD_LEFT);

        // 开始替换
        $arr = ['0' => '零', '1' => "壹", '2' => '贰', '3' => '叁', '4' => '肆', '5' => '伍', '6' => '陆', '7' => '柒', '8' => '捌', '9' => '玖'];

        $newstr = $str;
        foreach ($arr as $key => $value) {
            $newstr = str_replace($key, $value, $newstr);
        }
        return $newstr;
    }
    protected function changeDateToChinese($date = '', $type = 'year')
    {

        // 年份 直接四个数字转大写
        // 月份 如果小于10,则前面补零,超过10  壹拾壹
        // 日期 如果小于10,前面补零,如零柒,如果,10-壹拾,11 壹拾壹,
        // 将数字的日期转换成中文大写
        $str = ['零', "壹", '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖', '拾'];
        $str2 = $date;
        if ($type == 'year') {
            $field = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
            foreach ($field as $k => $f) {
                $str2 = str_replace($f, $str[$k] ?? '', $str2);
            }
        }
        if ($type == 'month') {
            // 1、2、10 前面加零
            $num = $date - 0;
            $str2 = "";
            if ($num <= 10) {
                $str2 = $str[$num];
            }
            if ($num < 3 || $num == 10) {
                $str2 = "零" . $str2;
            }
            if ($num == 10) {
                $str2 = "零壹拾";
            }
            if ($num == 11) {
                $str2 = "壹拾壹";
            }
            if ($num == 12) {
                $str2 = "壹拾贰";
            }
        }

        if ($type == 'date') {
            $num = $date - 0;

            if ($num < 10) {
                $str2 = '零' . $str[$num];
            } else {
                $num1 = floor($num / 10);  // 十位数
                $num2 = $num % 10; // 个数位
                $str2 = $str[$num1] . '拾' . $str[$num2];
                $str2 = str_replace('零', '', $str2);


                // 日的话，1-10，20，30 前面加零
                if ($num2 == 0) {
                    // 整十数前面加零
                    $str2 = '零' . $str2;
                }
            }
        }




        return $str2;
    }

     /**
     * 获取账号名称
     *
     * @return array
     */
    protected function getAccountList($fydm = '')
    {
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $order = "rank";


        $data = null;
        if (empty($fydm)) {
            $data = $this->getdb("account")->where($where)->order($order)->select();
        } else {
            $data = $this->getdb("account")->where($where)->order($order)->select();
        }

        $newdata = [];
        foreach ($data as $row) {
            $typeid = $row['id'];
            $id = "id_" . $typeid;
            $newdata[$id] = $row;
        }

        $rtdata = [];
        $rtdata['data'] = $data;
        $rtdata['map'] = $newdata;

        return $rtdata;
    }

    /**
     * 获取案件字号列表
     *
     * @return void
     */
    protected function getCaseTypeList($dwid = 1)
    {
        $where = [];
        $where['dwid'] = $dwid;
        $where['isvoid&isdel'] = 0;
        $order = "rank";


        $data = $this->getdb("casetype")->where($where)->order($order)->select();

        $newdata = [];
        $namelist = [];
        $namemap = [];

        foreach ($data as $row) {
            $typeid = $row['id'];
            $id = "id_" . $typeid;
            $newdata[$id] = $row;
            $casetypename = $row['casetypename'] ?? '';
            $namelist[$casetypename] = $row['id'];
            $namemap[$casetypename] = $row;
        }

        $rtdata = [];
        $rtdata['data'] = $data;
        $rtdata['map'] = $newdata;
        $rtdata['name'] = $namelist;
        $rtdata['namemap'] = $namemap;

        return $rtdata;
    }


    protected function getCaseTypeClassList()
    {
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $order = "rank";


        $data = $this->getdb("casetypeclass")->where($where)->order($order)->select();

        $newdata = [];
        foreach ($data as $row) {
            $typeid = $row['id'];
            $id = "id_" . $typeid;
            $newdata[$id] = $row;
        }

        $rtdata = [];
        $rtdata['data'] = $data;
        $rtdata['map'] = $newdata;

        return $rtdata;
    }

    protected function genCaseinfo($caseyear = '', $casetype = '', $casenum = '')
    {
        $text = "";
        if (!empty($caseyear) && $caseyear < 2016) {
            $text = "字第";
        }
        // if (!empty($casetype) && !(strpos($casetype, '沪') > -1)) {
        //     $text = "字第";
        // }
        return '（' . $caseyear . '）' . $casetype . $text . $casenum . '号';
    }
 /**
     * 修改数据，并检测数据是否有不一样的，并记录下来
     *
     * @param string $table
     * @param string $pk
     * @param string $id
     * @param string $action 动作类型，是更新，还是删除del，还是作废void或取消作废unvoid
     * @param array $newdata 要更新的数据
     * @param array $dict 字典
     * @return void
     */
    protected function logChange($table = '', $pk = 'id', $id = '', $action = 'update', $newdata = [], $dict = [])
    {


        $rt = $this->_rt();
        // Log::record("logChange 开始记录日志");

        // 判断是否启用了日志

        $config = config('log');
        $enable = $config['enable'] ?? false;





        if (empty($action) || empty($table) || empty($pk)) {
            $rt['message'] = "数据不能为空";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }

        if (empty($id) && $action != 'insert') {
            $rt['message'] = "ID主键不能为空";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }
        if (!$config) {
            $rt['message'] = "未找到配置项";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }

        if (!$enable) {
            $rt['message'] = "配置中日志的开关为关闭状态";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }
        $actionconfig = $config['action'] ?? [];
        $enable = $actionconfig[$action] ?? false;
        if (!$enable) {
            $rt['message'] = "未启用【{$action}】的日志";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }


        // 判断表名是否支持做日志
        $tables = $config['table'] ?? [];
        if (!is_array($tables) || !in_array($table, $tables)) {

            $rt['message'] = "当前表名【{$table}】未启用日志";
            Log::record("logChange " . $rt['message']);
            return $rt;
        }


        $logdata = [];
        $logdata['dwid'] = $this->dwid;
        $logdata['userid'] = $this->userid;
        $logdata['username'] = $this->userinfo['username'] ?? '<无>';
        $logdata['opertime'] = getNowTime();
        $logdata['createtime'] = $logdata['opertime'];
        $logdata['updatetime'] = $logdata['opertime'];
        $logdata['opertype'] = $action;
        $logdata['tablename'] = $table;
        $logdata['pkid'] = $id;
        $logdata['ipaddress'] = get_client_ip();
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $useragent = substr($useragent, 0, 200); // 取前200位长度，防止超出字段范围
        $logdata['useragent'] = $useragent;
        $logdata['newdata'] = _cv_to_json($newdata);



        // 获取获取原数据，判断是否存在
        $olddata = [];
        if (in_array($action, ['update', 'delete', 'void', 'unvoid', 'banktp']) && $id) {

            $where = [];
            $where[$pk] = $id;
            $olddata = $this->getdb($table)->where($where)->find();
            if (!$olddata) {
                $rt['message'] = "表【{$table}】记录ID【{$id}】不存在数据";
                Log::record("logChange " . $rt['message']);

                return $olddata;
            }
            $logdata['olddata'] = _cv_to_json($olddata);
        }
        $logdata['typeid'] = $olddata['typeidd'] ?? '';
        if ($action == self::LOG_ACTION_INSERT) {
            $logdata['billno'] = $newdata['billno'] ?? '';
        } else {
            $logdata['billno'] = $olddata['billno'] ?? '';
        }




        // 检查变更情况，并生成数据
        $changeData = [];

        foreach ($newdata as $key => $value) {

            if (in_array($key, self::LOG_FIELD_UNCHECK)) {
                continue;
            }


            $note = $dict[$key] ?? '';
            $oldvalue = $olddata[$key] ?? '';
            $newvalue = $value;
            if ($oldvalue != $newvalue) {
                $row = [];
                $row['oldvalue'] = $oldvalue;
                $row['newvalue'] = $newvalue;
                $row['note'] = $note;
                $row['field'] = $key;
                $changeData[] = $row;
            }
        }

        $lognote = "";

        if (count($changeData) > 0) {

            foreach ($changeData as $row) {
                $note = $row['note'];
                if (empty($note)) {
                    $note = '字段' . $row['field'];
                }
                // $lognote .= "{$note}【{$row['field']}】的值由【{$row['oldvalue']}】修改为【{$row['newvalue']}】\n";
                $lognote .= "{$note}的值由【{$row['oldvalue']}】修改为【{$row['newvalue']}】\n";
            }
        } else {
            $rt['message'] = "未发生数据变更";
            return $rt;
        }

        switch ($action) {
            case self::LOG_ACTION_UPDATE:
                $logdata['logtext'] = $lognote;
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                if ($table == self::TABLE_CASETK) {
                    $logdata['billno'] = $olddata['frombill'] ?? '';
                } else {
                    $logdata['billno'] = $olddata['billno'] ?? '';
                }

                break;
            case self::LOG_ACTION_INSERT:
                $logdata['logtext'] = '首次生成';
                $logdata['typeid'] = $newdata['typeid'] ?? '';
                $logdata['billno'] = $newdata['billno'] ?? '';
                if ($table == self::TABLE_CASETK) {
                    $logdata['billno'] = $newdata['frombill'] ?? '';
                } else {
                    $logdata['billno'] = $newdata['billno'] ?? '';
                }

                break;
            case self::LOG_ACTION_DELETE:
                $logdata['logtext'] = "删除数据";
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                $logdata['billno'] = $olddata['billno'] ?? '';
                if ($table == self::TABLE_CASETK) {
                    $logdata['billno'] = $olddata['frombill'] ?? '';
                } else {
                    $logdata['billno'] = $olddata['billno'] ?? '';
                }

                break;
            case self::LOG_ACTION_VOID:
                $logdata['logtext'] = "作废单据";
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                $logdata['billno'] = $olddata['billno'] ?? '';
                if ($table == self::TABLE_CASETK) {
                    $logdata['billno'] = $olddata['frombill'] ?? '';
                } else {
                    $logdata['billno'] = $olddata['billno'] ?? '';
                }
                break;
            case self::LOG_ACTION_UNVOID:
                $logdata['logtext'] = "取消作废";
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                $logdata['billno'] = $olddata['billno'] ?? '';
                if ($table == self::TABLE_CASETK) {
                    $logdata['billno'] = $olddata['frombill'] ?? '';
                } else {
                    $logdata['billno'] = $olddata['billno'] ?? '';
                }
                break;
            case self::LOG_ACTION_BANKTP:
                $logdata['logtext'] = "银行退票";
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                $logdata['billno'] = $olddata['frombill'] ?? '';

                break;
            default:
                $logdata['logtext'] = $lognote;
                $logdata['typeid'] = $olddata['typeid'] ?? '';
                $logdata['billno'] = $olddata['billno'] ?? '';
        }




        $this->getdb(self::TABLE_OPERLOG)->insert($logdata);




        $rt['message'] = "OK";
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = 1;
        return $rt;
    }

    
    /**
     * 刷新收款表中的案款余额情况
     * 
     *
     * @param [type] $id
     * @param integer $typeid
     * @return void
     */
    protected function fleshCaseYe($id = 0, $typeid = 101)
    {
        $rt = $this->_rt();

        $casebilltype = [self::TYPEID_CASEBILL_DGK, self::TYPEID_CASEBILL_FMK];

        if (in_array($typeid, $casebilltype)) {
            $rt = $this->fleshCaseBillYe($id, $typeid);
            return $rt;
        }

        // 获取收款情况
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['id'] = $id;
        $where['typeid'] = $typeid;
        $where['isvoid&isdel'] = 0;

        $table = self::TABLE_CASESK;

        $billno = "";
        $typeid_sk = "";
        $typeid_tk = [];

        $newdata = [];
        $newdata['updatetime'] = getNowTime();

        if ($typeid > 200 && $typeid < 300) {
            // 退款

            $tkinfo = $this->getdb(self::TABLE_CASETK)->where($where)->find();

            $billno = $tkinfo['frombill'] ?? '';
            if (empty($billno)) {
                $rt['message'] = "退款原单据号为空，不刷新收款数据";
                return $rt;
            }
            $typeid_sk = $this->getTkTypeid($typeid);


            if (empty($typeid_sk)) {
                $rt['message'] = "未找到【{$typeid}】对应的收款单据类型";
                return $rt;
            }


            $where_sk = [];
            $where_sk['dwid'] = $this->dwid;
            $where_sk['isvoid&isdel'] = 0;
            $where_sk['billno'] = $billno;
            if (is_array($typeid_sk)) {
                $where_sk['typeid'] = ['in', $typeid_sk];
            } else {
                $where_sk['typeid'] = $typeid_sk;
            }
            // $where_sk['typeid'] = $typeid_sk;
            $row = $this->getdb(self::TABLE_CASESK)->where($where_sk)->find();

            $id = $row['id'] ?? 0;

            $where['id'] = $id;

            if ($typeid == self::TYPEID_TK_DGKGK) {
                $typeid = self::TYPEID_SK_DGKGK;
                $where['typeid']  = ['in', [self::TYPEID_SK_GKCASEBILL, self::TYPEID_SK_DGKGK]];
            } else {
                $typeid = $typeid_sk;
                $where['typeid'] = $typeid;
            }
        }


        // 处理收款的余额情况 


        if ($typeid < 200) {
            // 数据为收款信息。取收信息，再取出退款信息，并求合计数
            $table = self::TABLE_CASESK;
            $skinfo = $this->getdb($table)->where($where)->find();

            $caseinfo = $skinfo['caseinfo'] ?? '';

            // dump($where);
            // halt($skinfo);
            // 获取billno,typeid
            $billno = $skinfo['billno'] ?? '';

            // 获取收款合并数

            $where_sk = [];
            $where_sk['dwid'] = $this->dwid;
            $where_sk['isvoid&isdel'] = 0;
            $where_sk['billno'] = $billno;
            $where_sk['typeid'] = $typeid;
            $field = ["count(*)" => "num", "sum(je)" => "je"];
            $skcount = $this->getdb(self::TABLE_CASESK)->where($where_sk)->field($field)->find();
            $newdata['sje'] = $skcount['je'] ?? 0;
            $newdata['snum'] = $skcount['num'] ?? 0;




            $typeid_tk = $this->getTkTypeid($typeid);

            $where_tk = [];
            $where_tk['isvoid&isdel'] = 0;
            $where_tk['dwid'] = $this->dwid;
            $where_tk['frombill'] = $billno;

            if (empty($typeid_tk)) {
                // 如果不存在退款
                $rt['message'] = "该款项【{$typeid}】不需要退款";
                return $rt;
            }
            if (is_array($typeid_tk)) {
                // 诉讼费会涉及到 201退诉讼费 和 205 移送诉讼费
                $where_tk['typeid'] = ['in', $typeid_tk];
            } else {
                // 其它款项一一对应
                $where_tk['typeid'] = $typeid_tk;
            }

            $tkcount = $this->getdb(self::TABLE_CASETK)->where($where_tk)->field($field)->find();

            $newdata['tje'] = $tkcount['je'] ?? 0;
            $newdata['tnum'] = $tkcount['num'] ?? 0;
            $newdata['ye'] = $newdata['sje'] - $newdata['tje'];

            //更新收款表中记录
            $this->getdb(self::TABLE_CASESK)->where($where_sk)->update($newdata);

            // 更新收款余额数据，此处不记录日志

            // 判断收款表中是否存在 frombill，有的话刷新casebill中的金额


            $frombill = $skinfo['frombill'] ?? '';
            if (!empty($frombill)) {
                // 刷新casebill中的余额情况

                $where = [];
                $where['dwid'] = $this->dwid;
                $where['isvoid&isdel'] = 0;
                $where['billno'] = $frombill;
                $where['typeid'] = self::TYPEID_CASEBILL_DGK;
                //

                $field = ["count(*)" => "num", "sum(je)" => "je"];

                $row = $this->getdb(self::TABLE_CASEBILL)->where($where)->find();
                if ($row) {
                    unset($where['billno']);
                    $where['frombill'] = $frombill;

                    $where['typeid'] = ['in', [self::TYPEID_SK_BZJ, self::TYPEID_SK_DGK, self::TYPEID_SK_FMK, self::TYPEID_SK_GGF]];

                    $skcount = $this->getdb(self::TABLE_CASESK)->field($field)->where($where)->find();
                    // 找到对应的 款项入账编号
                    $where = [];
                    $where['dwid'] = $this->dwid;
                    $where['billno'] = $frombill;
                    $where['isvoid&isdel'] = 0;


                    // 找到数据，则刷新金额
                    $billid = $row['id'];
                    $data = [];
                    $data['updatetime'] = getNowTime();
                    $sje = $skcount['je'] ?? 0;
                    $data['ye'] = $row['je'] - $sje;
                    $data['iskp'] = 1; // 刷新 已开票状态
                    $data['claimdate'] = getNowTime(); // 开票日期
                    if (!empty($caseinfo)) {
                        $data['caseinfo'] = $caseinfo;
                    }


                    $where['id'] = $billid;
                    $this->getdb(self::TABLE_CASEBILL)->where($where)->update($data);
                    $data['typeid'] = self::TYPEID_CASEBILL_DGK; // 加快速度

                    // 需要刷新款项入账的开票记录
                    $this->logChange(self::TABLE_CASEBILL, "id", $billid, self::LOG_ACTION_UPDATE, $data, Casesk::FIELD_DICT);
                }
            }
        }






        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = 1;
        return $rt;
    }
    
    /**
     * 获取款项对应的 退款（或收款）的单据类型
     *
     * @param [type] $typeid
     * @return void
     */
    protected function getTkTypeid($typeid)
    {
        if (is_array($typeid) && count($typeid) > 0) {
            $typeid = $typeid[0];
        }
        $tktypeid = "";


        switch ($typeid) {
            case self::TYPEID_SK_DGK: // 收代管款
                $tktypeid = self::TYPEID_TK_DGK;
                break;
            case self::TYPEID_SK_BZJ: // 收保证金
                $tktypeid = self::TYPEID_TK_BZJ;
                break;
            case self::TYPEID_SK_SSF: // 收诉讼费
                $tktypeid = [self::TYPEID_TK_SSF, self::TYPEID_TK_YSSSF]; // 退诉讼费，移送诉讼费
                break;
            case self::TYPEID_SK_SECSSF: // 收二审诉讼费
                $tktypeid = [self::TYPEID_TK_SSF, self::TYPEID_TK_YSSSF]; // 退诉讼费，移送诉讼费
                break;
            case self::TYPEID_TK_BZJ: // 退保证金
                $tktypeid = self::TYPEID_SK_BZJ;
                break;
            case self::TYPEID_TK_DGK: // 退代管款
                $tktypeid = self::TYPEID_SK_DGK;
                break;
            case self::TYPEID_TK_SSF: // 退诉讼费
                // $tktypeid = self::TYPEID_SK_SSF;
                $tktypeid = [self::TYPEID_SK_SSF, self::TYPEID_SK_SECSSF];
                break;
            case self::TYPEID_TK_YSSSF: // 移送诉讼费
                $tktypeid = [self::TYPEID_SK_SSF, self::TYPEID_SK_SECSSF];
                break;

      
            default:
                $tktypeid = 0;
        }
        return $tktypeid;
    }
    
    /**
     * 将浮点数转换成两位小数，防止有浮点偏差
     *
     * @param integer $num
     * @return void
     */
    protected function convertFloat($num = 0)
    {
        return number_format($num, 2, '.', '') - 0;
    }
    protected function getMapByField($data = [], $field = '')
    {

        $map = [];
        foreach ($data as $row) {
            $key = $row[$field];
            $map[$key] = $row;
        }

        return $map;
    }
       protected function getAllTypeList()
    {
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $where['classtype'] = "typeid";
        $order = "classcode";


        $data = $this->getdb("class")->where($where)->order($order)->cache($this->cache_time)->select();

        return $data;
    }
}
