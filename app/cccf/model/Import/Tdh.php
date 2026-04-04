<?php

namespace app\docprint\model\import;

use \think\Db;
use \think\Debug;

use \app\docprint\model\Common;

/**
 * 通达海全国通用版导入数据功能
 */
class Tdh extends Common
{
    const FIELD_AJJBXX = [
        "状态"=>"status",
        "系列案" => "xlabh",
        "案号" => "caseinfo", "执行主体" => "dsr",
        "案由" => "laay", "承办部门" => "deptname",
        "承办人" => "cbrmc", "书记员" => "sjymc",
        "立案日期" => "larq", "申请执行标的" => "labd",
        "原案法院" => "oldfyname", "执行依据文号" => "oldcaseinfo"
    ];
    const FIELD_DSR = [
        '案号' => "ahqc", "姓名" => "dsrmc",
        "法律地位" => "ssdw", "类型" => "dsrlx",
        "地址" => "address", "联系电话" => "mobile",
        "出生日期" => "csrq", '国籍/地区' => "country",
        "民族" => "nation", "文化程度" => "cultrue",
        "职业" => "work", "证件种类" => "cardtype",
        "证件号码" => "cardno", "邮政编码" => "post",
        "单位机构代码" => "cardno", "法定代表人姓名" => "fddbr",
        "法定代表人证件号码" => "fddbrzjhm"
    ];
    const FIELD_CASEINFO = "案号";
    const CONFIG_IMPORT_AJXX = ['rowstart' => 1, 'columnstart' => 1, 'keycell' => '', 'keytext' => '', 'ext' => 'xls', "table" => 0];
    const CONFIG_IMPORT_DSR = ['rowstart' => 1, 'columnstart' => 1, 'keycell' => '', 'keytext' => '', 'ext' => 'xls', "table" => 1];
    const APPID="tdh";
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取配置信息
     *
     * @return void
     */
    public function getajconfig()
    {
        return self::CONFIG_IMPORT_AJXX;
    }
    public function getdsrconfig()
    {
        return self::CONFIG_IMPORT_DSR;
    }
    /**
     * 判断新增多少案件，修改多少案件，总共多少案件多少当事人
     *
     * @param array $data
     * @return void
     */
    public function checkInfo($ajdata = [], $dsrdata = [])
    {
        $data = [];
        $data['casenum'] = count($ajdata);
        $data['dsrnum'] = count($dsrdata);

        $ahlist = [];

        foreach ($ajdata as $aj) {
            $ahlist[] = $aj[self::FIELD_CASEINFO];
        }
        $where =  [];
        $where['dwid'] = $this->dwid;
        $where['isdel'] = 0;

        if (count($ahlist) > 0) {
            $where['caseinfo'] = ['in', $ahlist];
            $data['casehad'] = $this->getdb("ajjbxx")->where($where)->count();
        } else {
            $data['casehad'] = 0;
        }

        $data['casenot'] = $data['casenum'] - $data['casehad'];

        return $data;
    }

    /**
     * 检查文件是否合规
     *
     * @param string $file
     * @return void
     */
    public function checkFile($file = '')
    {
        if (!file_exists($file)) {
            return false;
        }
        // 判断文件是否是excel文件并且首列为 序号，次列为 期限

        $filedata = self::util_getExcelData($file, 0, self::CONFIG_IMPORT_AJXX['rowstart'], self::CONFIG_IMPORT_AJXX['columnstart'], 1);
        $title1 = $filedata['field'][0]['title'] ?? '';
        $title2 = $filedata['field'][1]['title'] ?? '';

        return ($title1 == '序号' && $title2 == '期限');
    }


    /**
     * 导入数据
     *
     * @param array $ajdata
     * @param array $dsrdata
     * @return void
     */
    public function import($ajdata = [], $dsrdata = [])
    {
        // 要区分已存在的案件，以及不存在的案件
        $ah = [];
        foreach ($ajdata as $aj) {
            $tempah = $aj[self::FIELD_CASEINFO] ?? '';
            if (empty($tempah)) {
                continue;
            }
            if (!in_array($tempah, $ah)) {
                $ah[] = $tempah;
            }
        }
        foreach ($dsrdata as $aj) {
            $tempah = $aj[self::FIELD_CASEINFO] ?? '';
            if (empty($tempah)) {
                continue;
            }
            if (!in_array($tempah, $ah)) {
                $ah[] = $tempah;
            }
        }
        // 找到已存在的案件
        $ahlist = [];
        if (count($ah)>0) {
            $where = [];
            $where['dwid'] = $this->dwid;
            $where['isdel'] = 0;
            $where['caseinfo'] = ['in', $ah];
            $field = "id,ajbs,caseinfo";
            $data = $this->getdb("ajjbxx")->field($field)->where($where)->select();
            foreach ($data as $d) {
                $ah = $d['caseinfo'] ?? '';
                if (!empty($ah)) {
                    $ahlist[$ah] = $d;
                }
            }
        }

        // dump($ahlist);
        // exit();


        $newajdata = [];
        // 区分有案号的数据以及无案号的数据
        foreach ($ajdata as &$aj) {
            // 添加ajbs
            $ah = $aj[self::FIELD_CASEINFO] ?? "";
            if (empty($ah)) {
                // 如果案号为空，则跳过
                continue;
            }

            if (array_key_exists($ah, $ahlist)) {
                // 存在案号，则寻找手工登录ajbs
                $aj['ajbs'] = $ahlist[$ah]['ajbs'];
                $aj['id'] = $ahlist[$ah]['id'];
            } else {
                $aj['ajbs'] = $this->genUid();
                $aj['id'] = 0;
                // 不存在案号，则手工创建新的ajbs
            }
            $newajdata[$ah] = $aj;
        }
        $newdsrdata = [];
        foreach ($dsrdata as &$dsr) {
            $ah = $dsr[self::FIELD_CASEINFO] ?? '';
            if (empty($ah)) {
                continue;
            }
            // 添加ajbs
            if (array_key_exists($ah, $newajdata)) {
                $dsr['ajbs'] = $newajdata[$ah]['ajbs'];
            }
        }

        

        // 获取所有承办人ID

        $userinfo = $this->getDwUserList(true); //获取最新的数据
        $userinfo = $userinfo['name'] ?? [];
        $deptinfo = $this->getDwDeptList(true);
        $deptinfo = $deptinfo['name'] ?? [];

        $total = [];
        $total['caseadd'] = 0;
        $total['caseupdate'] = 0;
        $total['dsradd'] = 0;
        $total['dsrupdate'] = 0;
        
        $db = $this->getdb('ajjbxx');
        foreach ($newajdata as $aj) {
            $newcase = [];
            $newcase['ajbs'] = $aj['ajbs'];
            $newcase['dwid'] = $this->dwid;
            $newcase['userid'] = $this->userid;
            $newcase['fycode'] = $this->dwcode;
            $newcase['appid'] = self::APPID;
            $newcase['ajlb']='8';
            $newcase['fyname'] = $this->userinfo['dwname'] ?? '';


            foreach (self::FIELD_AJJBXX as $key => $value) {
                if (array_key_exists($key, $aj)) {
                    $newcase[$value] = $aj[$key];
                }
            }
            // 拆分案号

            $caseinfo = explodeCaseinfo($newcase['caseinfo']);
            $newcase['caseyear'] = $caseinfo['caseyear'];
            $newcase['casetypename'] = $caseinfo['casetype'];
            $newcase['casenum'] = $caseinfo['casenum'];
            $newcase['id'] = $aj['id'] ?? 0;


            // 补齐 承办人ID，承办部门代码、书记员ID



            $cbrmc = $newcase['cbrmc'] ?? '';
            if (!empty($cbrmc) && array_key_exists($cbrmc, $userinfo)) {
                $newcase['cbr'] = $userinfo[$cbrmc]['userid'] ?? '';
            } else {
                $newcase['cbr'] = "";
            }
            $sjymc = $newcase['sjymc'] ?? '';
            if (!empty($sjymc) && array_key_exists($sjymc, $userinfo)) {
                $newcase['sjy'] = $userinfo[$sjymc]['userid'] ?? '';
            } else {
                $newcase['sjy'] = "";
            }

            // 补上部门
            $deptname = $newcase['deptname'];

            if (!empty($deptname) && array_key_exists($deptname, $deptinfo)) {
                $newcase['deptcode'] = $deptinfo[$deptname]['deptcode'];
            } else {
                $newcase['deptcode'] = "";
            }

            $id = $newcase['id'] ?? 0;
            // dump($newcase);
            // exit();
            unset($newcase['id']);
            

            
            if ($id != 0) {
                // 更新数据
                $where = [];
                $where['id'] = $id;
                $where['dwid'] = $this->dwid;
                $newcase['updatetime'] = getNowTime();
                $db->where($where)->update($newcase);
                $total['caseupdate']++;
            } else {
                $newcase['createtime'] = getNowTime();
                $db->insert($newcase);
                $total['caseadd']++;
            }
        }

        //开始循环插入或更新 当事人

        //先判断当事人是否存在
        $ajbs = [];
        $where=[];
        $where['dwid']=$this->dwid;
        foreach($dsrdata as $dsrrow){
            $tajbs=$dsrrow['ajbs'] ?? '';
            if(!empty($ajbs)){
                $ajbs[] = $dsrrow['ajbs'];
            }
        }
        if(count($ajbs)>0){
            $where['ajbs']=['in',$ajbs];
        }
        // 判断当事人 ajbs+ssdw+dsrmc
        $field ="dsrid,ajbs,ssdw,dsrmc";
        $data = $this->getdb("dsr")->where($where)->field($field)->select();
        $dsrlist = [];
        foreach($data as $dsrinfo){
            $key = $dsrinfo['ajbs'].'_'.$dsrinfo['ssdw']."_".$dsrinfo['dsrmc'];
            $dsrlist[$key] = $dsrinfo;
        }
        $db=$this->getdb("dsr");
        foreach($dsrdata as &$dsr){
            
            // 判断当事人是否已存在，如果已存在则修改，如果不存在则新增
            $newdata=[];
            $newdata['dwid'] = $this->dwid;
            $newdata['userid']=$this->userid;
            $newdata['appid'] = self::APPID;
            $newdata['fycode'] = $this->dwcode;
            $newdata['ajbs'] = $dsr['ajbs'];
            
            foreach(self::FIELD_DSR as $key => $value){
                if(array_key_exists($key,$dsr)){
                    $newdata[$value] = $dsr[$key];
                }else{
                    $newdata[$value] = "";
                }
            }


            $key = $newdata['ajbs'].'_'.$newdata['ssdw']."_".$newdata['dsrmc'];
            if(array_key_exists($key,$dsrlist)){
                $newdata['id'] = $dsrlist[$key]['dsrid'];
            }else{
                $newdata['id'] = 0;
            }

            $caseinfo = explodeCaseinfo($newdata['ahqc']);
            $newdata['casetype'] = $caseinfo['casetype'];
            $newdata['casenum'] = $caseinfo['casenum'];
            $id = $newdata['id'] ?? 0;
            unset($newdata['id']);
            
            if($id!=0){
                $where = [];
                $where['dsrid'] = $id;
                $where['dwid'] = $this->dwid;
                $newdata['updatetime'] = getNowTime();
                $db->where($where)->update($newdata);
                $total['dsrupdate']++;
            }else{
                $newdata['createtime'] = getNowTime();
                $db->insert($newdata);
                $total['dsradd']++;
            }


        }




        $rt = $this->_rt();
        $rt['data'] = $total;

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";

        return $rt;
    }
}
