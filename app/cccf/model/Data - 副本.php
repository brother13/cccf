<?php

namespace app\cccf\model;

use app\cccf\model\Log;
use app\cccf\model\User;

/**
 * 基础数据相关
 *
 * @author netknave
 *
 */
class Data extends Common
{
    const ACTION = "data";

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

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $page = input("param.page", 1);
        $pagesize = input("param.pagesize", 10);
        $key = isset($data['key']) ? $data['key'] : "";
        $isvoid = $data['isvoid'] ?? "";

        switch ($action) {
            case 'list': //兼容旧入口
                $type = $data['type'] ?? "";
                $order = $data['order'] ?? "";
                $data = $this->getData($type, $key, $page, $pagesize, $order);
                $rt['total'] = $data['total'] ?? 0;
                $rt['data'] = $data['data'];
                break;
            case "dwlist": // 单位列表
                $data = $this->getDwlist($key, $page, $pagesize);
                $rt['total'] = $data['total'];
                $rt['data'] = $data['data'];
                break;
            case "labellist": // 标签列表
                $data = $this->getLabellist($key, $page, $pagesize);
                $rt['total'] = $data['total'];
                $rt['data'] = $data['data'];
                break;
            case "userlist": // 用户列表
                $dept = isset($data['dept']) ? $data['dept'] : "";
                $data = $this->getUserlist($key, $dept, $page, $pagesize);
                $rt['total'] = $data['total'];
                $rt['data'] = $data['data'];
                break;
            case 'deptlist': //部门列表
                $data = $this->getDeptlist($key, $page, $pagesize);
                $rt['total'] = $data['total'];
                $rt['data'] = $data['data'];
                break;
            case 'grouplist': //权限组列表
                $data = $this->getGrouplist($key, $page, $pagesize);
                $rt['total'] = $data['total'];
                $rt['data'] = $data['data'];
                break;
            case 'countdata': //统计使用情况
                $startdate = $data['startdate'] ?? date('Y-m-d', time() - 7 * 86400);
                $enddate = $data['enddate'] ?? date('Y-m-d', time());
                $data = $this->countReport($startdate, $enddate);
                $rt['data'] = $data;
                break;
            case 'savecccf': //多个保存
                $data = $this->savecccfall($data);
                $rt['data'] = $data;
                break;
            case "cflist": //
                $cflist = $this->getCfList($data);
                // dump($userlist);

                if ($cflist) {

                    $rt['data'] = $cflist;
                    $rt['total'] = $cflist['total'];
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "数据为空";
                }
                break;
            case "cflist_total": //用户列表
                $rt['data'] = $this->getCfList_total($data);
                break;
            case 'cflistdel': // 删除用户
                $id = $data['cflistid'] ?? 0;
                $rt = $this->delCflis($id);
                break;

            case 'getCfListbyId': // 删除用户
                $id = $data['id'] ?? 0;
                $rt = $this->getCfListbyId($id);
                break;
            case "cflistupdate": //修改个人信息
                // todo
                $cflistid = $data['cflistid'] ?? 0;
                if ($cflistid == 0) {
                    $rt['message'] = "用户ID无效" + $cflistid;
                    return $rt;
                }
                $rt = $this->saveCflistone($cflistid, $data);
                break;
            case "saveCflistusername":
                $cflistid = $data['cflistid'] ?? 0;
                $rt = $this->saveCflistusername($cflistid, $data);
                break;

            case "rpalist": //
                $cflist = $this->getrpalist($data);
                // dump($userlist);

                if ($cflist) {

                    $rt['data'] = $cflist;
                    $rt['total'] = $cflist['total'];
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "数据为空";
                }
                break;
            case "rpalist_total": //用户列表
                //$rt['data'] = $this->getCfList_total($data);
                break;

            case "xdlist": //
                $cflist = $this->getxdlist($data);
                // dump($userlist);

                if ($cflist) {

                    $rt['data'] = $cflist;
                    $rt['total'] = $cflist['total'];
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "数据为空";
                }
                break;
            case "xdlistdel": //用户列表
                $id = $data['xdlistid'] ?? 0;
                $rt['data'] = $this->delXdlis($id);
                break;

            case "xdlistupdate": //修改个人信息
                // todo
                $xdlistid = $data['xdlistid'] ?? 0;
                if ($xdlistid == 0) {
                    $rt['message'] = "用户ID无效" + $xdlistid;
                    return $rt;
                }
                $rt = $this->saveXdlistone($xdlistid, $data);
                break;

            case "zxklist": //
                $cflist = $this->getZxklist($data);
                // dump($userlist);

                if ($cflist) {

                    $rt['data'] = $cflist;
                    $rt['total'] = $cflist['total'];
                    $rt['code'] = self::CODE_SUCCESS;
                    $rt['message'] = "OK";
                } else {
                    $rt['message'] = "数据为空";
                }
                break;
            case "uploadfile": //
                $rt = $this->uploadfile($data);
                break;
            case "deluploadfile": //
                $rt = $this->deluploadfile($data);
                break;
            case "getuploadfile": //
                // todo
                $cflistid = $data['cflistid'] ?? 0;
                $rt = $this->getuploadfile($cflistid);
                break;
            case "cflistadd": //添加数据
                $rt = $this->saveCflistone(0, $data);
                break;
            case "urlcflistadd": //网页数据导入
                $rt = $this->saveCflistone(0, $data);
                if ($data["baseurl"] != '') {
                    $id = $rt['id'];
                    $url = $data["baseurl"] . "index.html#/tz/xzjl?id=" . $id;
                    header("Location: " . $url);
                    exit(); // 确保脚本执行完毕后停止
                }
                break;
            case "cftype": // 单位列表
                $data = $this->getCftype();
                $rt['data'] = $data['data'];
                break;
            case "getajjbxx": // 单位列表
                $ah = $data ?? 0;
                $data = $this->getajjbxx($ah);
                $rt['data'] = $data['data'];
                break;
            case "sendmsg":
                $rt = $this->sendmsg($data);
                break;
            default:
                $rt['code'] = self::CODE_ERROR;
                $rt['message'] = "操作【/" . self::ACTION . "/{$action}】并不存在！";
        }
        return $rt;
    }

    protected function checkCbr($username = '')
    {
        $rt = $this->_rt();
        if (empty($username)) {
            $rt['message'] = "办案人不能空";
            return $rt;
        }

        //开始判断用户是否存在

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['username'] = $username;

        $usernum = $this->getdb("user")->where($where)->count();
        if ($usernum < 1) {
            $rt['message'] = "办案人不存在，请添加用户【" . $username . "】";
            return $rt;
        }
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        return $rt;
    }

    public function sendmsg($data)
    {
        $rt = [];
        $url = "https://renapp.top/cccf/qcloudsms/demo/simple/app.php?data=" . $data; // 指定的页面URL
        $retdata = file_get_contents($url);
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $retdata;
        $retdata = json_decode($retdata, true);
        $logModel = new Log();
        $logModel->Log($retdata);
        if ($retdata['result'] == 0 & $retdata['errmsg'] == 'OK') {
            $rt['message'] = "短信发送成功";
            $rt['code'] = parent::CODE_SUCCESS;
        } else {
            //dump($retdata);//detail);
            $tipsarr = explode("，", $retdata['errmsg']);
            $tips = $tipsarr[0];
            if (count($tipsarr) > 1) {
                $tips = $tips . $tipsarr[1];
            }
            $tips = str_replace("短信/语音", "", $tips);
            $rt['message'] = $tips; //detail
            $rt['code'] = parent::CODE_SUCCESS;

        }
        // $rt['input'] = input("param.");
        return $rt;
    }

    public function delCflis($cflistid = 0)
    {
        $rt = [];
        $where = [];
        $where['id'] = $cflistid;
        $d = $this->getdb("cflist")->where($where)->delete();
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $d;
        $rt['message'] = "删除成功";
        // $rt['input'] = input("param.");
        return $rt;
    }

    public function uploadfile($data)
    {

        $field = ['cflistid', 'filename', 'filepath'];
        $d = [];
        foreach ($field as $f) {
            if (isset($data[$f])) {
                $d[$f] = $data[$f];
            } else {
                $d[$f] = null;
            }
        }
        $this->getdb("uploadfile")->insert($d);
        $rt = $this->getuploadfile($data['cflistid']);
        return $rt;
    }

    public function deluploadfile($data)
    {
        $rt = [];
        $where = [];
        $where['id'] = $data['id'];
        if ($data['id'] > 0) {
            $d = [];
            $d['isdel'] = 1;
            $d['deltime'] = getNowTime();
            $this->getdb("uploadfile")->where($where)->update($d);
        }
        $this->getdb("uploadfile")->where($where)->update($d);
        $rt = $this->getuploadfile($data['cflistid']);
        return $rt;
    }

    public function getuploadfile($cflistid)
    {
        $where = [];
        $where['cflistid'] = $cflistid;
        $where['isdel'] = 0;
        //$field = ['id','filename', "concat('http://192.168.51.106/cccf/public/',filepath) url",'cflistid'];
        $field = ['id', 'filename', "concat('/cccf/',filepath) url", 'cflistid'];

        $data = $this->getdb('uploadfile')->where($where)->field($field)->order('inserttime')->select();

        $rt = [];
        $rt['code'] = 20000;
        $rt['data'] = $data;
        return $rt;

    }

    public function saveCflistusername($cflistid, $data)
    {
        if ($cflistid > 0 && strlen($data['username']) > 0) {
            $where['id'] = $cflistid;
            $d['username'] = $data['username'];
            $this->getdb("cflist")->where($where)->update($d);
        }

    }

    public function saveCflistone($cflistid, $data)
    {
        // $check = $this->checkCbr($data['cbr']);
        // if ($check['code'] != self::CODE_SUCCESS) {
        //     return $check;
        // }

        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'isvoid', 'ccqk', 'cfsf', 'ycbr', 'autocf', 'leixing', 'zbah'];
        $d = [];
        foreach ($field as $f) {
            if (isset($data[$f])) {
                if (($f == 'startdate' || $f == 'enddate') && $data[$f] == "") { //日期格式不支持传空字符串
                    $d[$f] = null;
                } else {
                    $d[$f] = $data[$f];
                }
            } else {
                $d[$f] = null;
            }
        }

        $where = [];
        if ($cflistid > 0) {
            $where['id'] = $cflistid;
        }
        $rt = $this->_rt();
        if ($cflistid != 0) {
            $this->getdb("cflist")->where($where)->update($d);
        } else {
            $d['username'] = $data['username'];
            $inret = $this->getdb("cflist")->insert($d, false, true);
            $rt['id'] = $inret;
        }


        $rt['code'] = parent::CODE_SUCCESS;
        $cflistid = $data['cflistid'] ?? 0;
        $rt['data'] = $cflistid;
        $rt['message'] = "操作成功";
        if ($cflistid == 0) {
            $rt['message'] = "添加成功！";
        }

        // $rt['input'] = $data;

        return $rt;
    }

    protected function getAllDeptCbr($dept = [])
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

        $where1['deptcode'] = ['in', $deptid];

        $where1['isvoid'] = 0;
        $where1['isdel'] = 0;
        $cbr = [];
        $cbrlist = $this->getdb("user")->where($where1)->field("username")->cache(self::CACHE_TIME)->select();
        foreach ($cbrlist as $row) {
            $cbr[] = $row['username'];
        }
        //dump($cbr);
        return $cbr;
    }

    public function getCfList($data)
    {
        $deptcode = $data['deptcode'] ?? [];
        $key = $data['keyword'] ?? '';
        $pagesize = $data['pagesize'] ?? '';
        $page = $data['page'] ?? '';
        $enddate = $data['enddate'] ?? '';
        $cfsf = $data['cfsf'] ?? '';
        $startdate = $data['startdate'] ?? '';
        $isvoid = $data['isvoid'] ?? "";
        $datekeyword = $data['datekeyword'] ?? "enddate";
        $myusername = $data['myusername'] ?? '';
        $desc = '';
        $id = $data['id'] ?? '';//根据id查询时，为取唯一值，忽略其他条件
        $where = [];
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|cbr|sqzxr|bzxr|note'] = ['like', '%' . $key . '%'];
        }
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|cbr|sqzxr|bzxr|note'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
            $desc = ' desc ';
        }
        if (!empty($cfsf) && strlen($cfsf) > 1) {
            $where['cfsf'] = $cfsf;
        }

        if (!empty($startdate) && strlen($startdate) > 2) { //开始时间只有inserttime时查询会使用
            $where['inserttime'] = ['>=', $startdate . ' 00:00:00'];
        }

        // if (is_string($deptcode)) {
        //     if ($deptcode != '') {
        //         $deptcode = explode(",", $deptcode);
        //         $where['deptcode'] = ['in', $deptcode];
        //     }
        // }

        // 获取带子节点的deptcode
        $allcbr = [];
        if (is_array($deptcode) && count($deptcode) > 0) {
            // 获取承办人相关列表
            $allcbr = $this->getAllDeptCbr($deptcode);
        }
        if ($myusername != "Admin" && strlen($myusername) > 0) {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $where['cbr|username'] = ['in', $allcbr];
        }

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        if (strlen($id) > 0) {//根据id查询时，为取唯一值，忽略其他条件
            $where = [];
            $where['id'] = $id;
        }




        $order = $datekeyword . $desc . " ,id";
        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'id cflistid', 'isvoid', 'inserttime', 'ccqk', 'cfsf', 'ycbr', 'autocf', 'leixing', 'zbah'];
        $num = $this->getdb('cflist_v')->where($where)->count();
        $data = $this->getdb('cflist_v')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();
        $alldata = $this->getdb('cflist_v')->where($where)->field($field)->order($order)->page(1, $num)->select();

        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $d['allitems'] = $alldata;
        $rt['data'] = $d;
        return $d;
    }

    public function getCfList_total($data)
    {
        $where = [];
        $allcbr = [];
        if ($data['myusername'] != "Admin") {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $where['cbr|username'] = ['in', $allcbr];
        }

        $where['isvoid'] = '0';
        $currentTimestamp = time();

        //$num1 = $this->getdb('cflist_v')->where($where)->("enddate","<=",date('Y-m-d', time() + 7 * 86400))->count();
        $yzyncount = $this->getdb('cflist_v')->where("enddate", "<=", date('Y-m-d', time() + 7 * 86400))->where($where)->count();
        $yyyncount = $this->getdb('cflist_v')->where("enddate", "<=", date('Y-m-d', strtotime("+1 month", $currentTimestamp)))->where($where)->count();
        //$yyyncount = $this->getdb('cflist_v')->where("enddate", "<=", date('Y-m-d', time() + 30 * 86400))->where($where)->count();
        $yyyn2count = $this->getdb('cflist_v')->where("enddate", "<=", date('Y-m-d', strtotime("+2 month", $currentTimestamp)))->where($where)->count();
        $d = [];
        $d['yzyncount'] = $yzyncount;
        $d['yyyncount'] = $yyyncount;
        $d['yyyn2count'] = $yyyn2count;
        return $d;
    }

    public function getCfListbyId($id)
    {

        $where = [];
        $where['id'] = $id;

        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'id cflistid', 'isvoid', 'inserttime', 'ccqk', 'ycbr', 'autocf', 'leixing', 'zbah'];
        $num = $this->getdb('cflist_v')->where($where)->count();
        $data = $this->getdb('cflist_v')->where($where)->field($field)->find();

        $rt = [];
        $rt['code'] = 20000;
        $rt['data'] = $data;

        return $rt;
    }

    public function isDateValid($date)
    {
        if (!empty($date)) {
            return (date('Y-m-d', strtotime($date)) == $date);
        } else {
            return false;
        }

    }

    public function savecccfall($data)
    {
        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'username', 'ccqk', 'isvoid', 'ycbr'];
        $fieldName = ['承办人', '案号', '申请执行人', '被执行人', '财产类型', '查封状态', '查封生效日期', '查封届满日期', '备注', '操作员', '类型及控制财产情况', '是否停用', '原承办人'];
        $jsondata = $data['data'];
        $myusername = $data['myusername'];
        $cnum = 0;
        $checkcbr = [];
        $rt['code'] = parent::CODE_ERROR;
        $newdata = [];
        $notupload = 0;
        foreach ($jsondata as &$jdata) { //&表示引用该变量$jdata
            //车辆,房产,工资卡,股权,执行款,银行卡,支付宝
            $checktype = ['车辆', '房产', '工资卡', '股权', '执行款', '银行', '支付宝'];
            $newtype = '';

            foreach ($checktype as $tmp) {
                if (isset($jdata['有财产（有何财产）']) && strpos($jdata['有财产（有何财产）'], $tmp) !== false) {
                    $newtype = $tmp;
                }
            }
            $jdata['有财产（有何财产）'] = $newtype;

            if (isset($jdata['查封届满日期']) == false || empty(trim($jdata['查封届满日期']))) {
                $jdata['是否停用'] = '1';
                $jdata['查封届满日期'] = null;
            }

            if ($this->isDateValid($jdata['查封届满日期']) == false) {
                $jdata['是否停用'] = '1';
                $jdata['查封届满日期'] = null;
            }
            //date('Y-m-d', time() - 90 * 86400)
            //(date('Y-m-d', strtotime($date)) == $date);

            $days = floor((time() - strtotime($jdata['查封届满日期'])) / 3600 / 24);
            if ($days > 90) { //超过3个月的记录记为停用
                $jdata['是否停用'] = '1';
            }

            //承办人为空，则取当前导入用户为经办人
            if (isset($jdata['承办人']) == false || empty(trim($jdata['承办人'])) || trim($jdata['承办人']) == '') {
                $jdata['承办人'] = $myusername;
            }
            $jdata['操作员'] = $myusername;
            $checkcbr[] = $jdata['承办人'];
            $newdata[] = $jdata;
        }
        unset($jdata); //取消对$jdata的引用

        $checkcbr = array_unique($checkcbr); //去除重复值
        $okcbr = $this->getdb("user")->where('username', 'in', $checkcbr)->column('username');
        $errcbr = array_diff($checkcbr, $okcbr);
        if (count($errcbr) > 0) {
            $textString = implode(',', $errcbr);
            $rt['message'] = "操作失败，承办人【" . $textString . '】不存在用户，请先添加该用户';
            return $rt;
        }

        foreach ($newdata as $jdata) {
            $d = [];

            foreach ($jdata as $key1 => $j1data) {
                foreach ($fieldName as $index => $key2) {
                    if ($key1 == $key2) {
                        $d[$field[$index]] = $j1data;

                    }
                }
                if ($key1 !== '备注' && strpos($key1, '备注') !== false) {
                    if (isset($d['note'])) {
                        $d['note'] = $d['note'] . ';' . $j1data;
                    } else {
                        $d['note'] = $j1data;
                    }

                }

            }
            $d['inserttime'] = getNowTime();
            $incount = $this->getdb("cflist")->insert($d);
            if ($incount > 0) {
                $cnum = $cnum + 1;

            }

        }
        $tips = '操作成功，成功上传' . $cnum . "条记录。";
        if ($notupload > 0) {
            $tips = $tips . '忽略' . $notupload . '条记录。';
        }

        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['cnum'] = $cnum;
        $rt['notupload'] = $notupload;
        $rt['message'] = $tips;

        return $rt;
    }

    /**
     * 获取单位列表
     *
     * @param string $key 搜索关键字
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function getDwlist($key = '', $page = 1, $pagesize = 100)
    {

        $where = [];
        $where['isvoid&isdel'] = 0;
        if ($key != '') {
            $where['dwname'] = ['like', '%' . $key . '%'];
        }
        $order = "rank";
        $field = ["dwid", "dwcode", "dwname", "address", "telphone"];
        $data = [];
        $db = $this->getdb("dwlist");
        $num = $db->where($where)->count();
        $list = $db->where($where)->field($field)->order($order)->select();

        $data['total'] = $num;
        $data['data'] = $list;
        return $data;

    }
    protected function getCftype()
    {

        $where = [];
        $where['isvoid'] = 0;
        $order = "rank";
        $data = [];
        $list = $this->getdb("cftype")->where($where)->order($order)->select();
        $data['data'] = $list;
        return $data;

    }

    protected function getajjbxx($ah)
    {

        $where = [];
        $where['caseinfo'] = $ah;
        $data = [];
        $list = $this->getdb("ajjbxx_v")->where($where)->find();
        $data['data'] = $list;
        return $data;

    }

    /**
     * 获取单位列表
     *
     * @param string $key 搜索关键字
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function getLabellist($key = '', $page = 1, $pagesize = 100)
    {

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        if ($key != '') {
            $where['labeltext'] = ['like', '%' . $key . '%'];
        }
        $order = "rank";
        $field = ["labeltext", "note"];
        $data = [];
        $db = $this->getdb("label");
        $num = $db->where($where)->count();
        $list = $db->where($where)->field($field)->order($order)->select();

        $data['total'] = $num;
        $data['data'] = $list;
        return $data;

    }

    /**
     * 获取用户列表
     *
     * @param string $key 搜索关键字
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function getUserlist($key = '', $dept = '', $page = 1, $pagesize = 100)
    {

        $userModel = new User();
        $userdata = $userModel->getUserlist($key, $dept, 0, $page, $pagesize);
        // dump($userdata);
        $data = [];
        $data['total'] = $userdata['total'];
        $data['data'] = $userdata['items'];
        foreach ($data['data'] as &$user) {
            unset($user['usergroup']);
        }
        return $data;

    }

    /**
     * 获取部门列表
     *
     * @param string $key 搜索关键字
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function getDeptlist($key = '', $page = 1, $pagesize = 100)
    {

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        if ($key != '') {
            $where['deptcode|deptname'] = ['like', '%' . $key . '%'];
        }
        $order = "rank";
        $field = ["deptid", "deptcode", "deptname", "isvoid", "telphone"];
        $data = [];
        $db = $this->getdb("dept");
        $num = $db->where($where)->count();
        $list = $db->where($where)->field($field)->order($order)->select();

        $data['total'] = $num;
        $data['data'] = $list;
        return $data;
    }
    /**
     * 获取权限组列表
     *
     * @param string $key 搜索关键字
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    protected function getGrouplist($key = '', $page = 1, $pagesize = 100)
    {

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $this->dwid;
        if ($key != '') {
            $where['groupcode|groupname'] = ['like', '%' . $key . '%'];
        }
        $order = "rank";
        $field = ["groupid", "groupcode", "groupname", "note"];
        $data = [];
        $db = $this->getdb("group");
        $num = $db->where($where)->count();
        $list = $db->where($where)->field($field)->order($order)->select();

        $data['total'] = $num;
        $data['data'] = $list;
        return $data;
    }
    /**
     * 获取基础数据信息，大部分来自于 classes表中
     *
     * @param string $type
     * @param string $key
     * @param integer $page
     * @param integer $pagesize
     * @param string $order
     * @return void
     */
    protected function getData($type = '', $key = '', $page = 1, $pagesize = 50, $order = "")
    {
        $data = [];
        $dwid = $this->dwid;
        switch ($type) {
            case 'deptlist':
                # code...
                $data = $this->getDeptList($key, $page, $pagesize);
                break;
            case 'dwlist':
                $data = $this->getDwList();
                break;
            case 'authlist':
                $auth = new Auth();
                $data = $auth->authList($key);
                break;
            case 'grouplist':
                $model = new Group();
                $data = $model->getList();
                break;

            default:
                # code...
                $data = $this->getClass($type, $key, $page, $pagesize, $order);
                break;
        }
        return $data;

    }
    /**
     * 获取基础信息列表
     * @param  string  $classtype [description]
     * @param  array   $w         [条件]
     * @param  integer $page      [description]
     * @param  integer $pagesize  [description]
     * @param  array   $order     [description]
     * @return [type]             [description]
     */
    public function getClass($classtype = '', $w = "", $page = 1, $pagesize = 50, $order = [])
    {

        $field = "classid,classtype,classcode,classname,classnote,isvoid";
        $order = "rank";
        $where = [];
        // $where['dwid']=$dwid;
        $where['isdel'] = 0;
        $where['classtype'] = $classtype;
        $num = $this->getdb("class")->where($where)->count();
        $data = $this->getdb("class")->field($field)->where($where)->order($order)->select();

        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;
        $rt['total'] = $num;

        return $rt;

    }

    /**
     * 统计使用情况，统计以下内容

     *
     * @param string $startdate
     * @param string $enddate
     * @return void
     */
    protected function countReport($startdate = '', $enddate = '')
    {

        $data = [];

        $where = [];

        return $data;
    }


    public function getrpalist($data)
    {
        $key = $data['keyword'] ?? '';
        $pagesize = $data['pagesize'] ?? '';
        $page = $data['page'] ?? '';
        $enddate = $data['enddate'] ?? '';
        $startdate = $data['startdate'] ?? '';
        $datekeyword = $data['datekeyword'] ?? "querytime";
        $where = [];
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|bzxr'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
        }

        if (!empty($startdate) && strlen($startdate) > 2) { //开始时间只有inserttime时查询会使用
            $where[$datekeyword] = ['>=', $startdate . ' 00:00:00'];
        }

        $allcbr = [];
        if ($data['myusername'] != "Admin") {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $where['cbr'] = ['in', $allcbr];
        }


        $order = $datekeyword . " ,ah";
        $field = ['querytime', 'ah', 'bzxr', 'zhanghu'];
        $num = $this->getdb('xd_v')->where($where)->count();
        $data = $this->getdb('xd_v')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();
        $alldata = $this->getdb('xd_v')->where($where)->field($field)->order($order)->page(1, $num)->select();

        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $d['allitems'] = $alldata;
        $rt['data'] = $d;
        return $d;
    }

    public function getxdlist($data)
    {
        $key = $data['keyword'] ?? '';
        $pagesize = $data['pagesize'] ?? '10';
        $page = $data['page'] ?? '1';
        $enddate = $data['enddate'] ?? '';
        $startdate = $data['startdate'] ?? '';
        $datekeyword = $data['datekeyword'] ?? "lastdate";
        $xkzjeflag = $data['xkzjeflag'] ?? false;
        $isvoid = $data['isvoid'] ?? '';
        $myusername = $data['myusername'] ?? '';
        $id = $data['id'] ?? '';//根据xdlistid查询时，为取唯一值，忽略其他条件
        $where = [];
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|bzxr'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
        }

        if (!empty($startdate) && strlen($startdate) > 2) { //开始时间只有inserttime时查询会使用
            $where[$datekeyword] = ['>=', $startdate . ' 00:00:00'];
        }

        $allcbr = [];
        if ($myusername != "Admin" && strlen($myusername) > 0) {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $where['cbr'] = ['in', $allcbr];
        }
        if ($xkzjeflag) {
            $where["xzje"] = [">", 0];
        }
        if (strlen($isvoid) > 0) {
            $where['isvoid'] = $isvoid;
        }
        if (strlen($id) > 0) {//根据xdlistid查询时，为取唯一值，忽略其他条件
            $where = [];
            $where['id'] = $id;
        }

        $order = $datekeyword . " ,ah";
        $field = ['ah', 'zt', 'bzxr', 'zhanghu', 'FORMAT(je,2) as je', 'danwei', 'lastdate', 'thisdate', 'cbr', 'querytime', 'FORMAT(newje,2) as newje,id as xdlistid', 'isvoid'];
        $num = $this->getdb('xd_v')->where($where)->count();
        //$newjenum = $this->getdb('xd_v')->where($where)->where("newje", ">", "je")->count();
        $data = $this->getdb('xd_v')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();
        $alldata = $this->getdb('xd_v')->where($where)->field($field)->order($order)->page(1, $num)->select();

        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        //$d['newjenum'] = $newjenum;
        $d['items'] = $data;
        $d['allitems'] = $alldata;
        $rt['data'] = $d;
        return $d;
    }


    public function delXdlis($xdlistid = 0)
    {
        $rt = [];
        $where = [];
        $where['id'] = $xdlistid;
        $d = $this->getdb("xd")->where($where)->delete();
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $d;
        $rt['message'] = "删除成功";
        return $rt;
    }


    public function saveXdlistone($xdlistid, $data)
    {
        $field = ['ah', 'zt', 'bzxr', 'zhanghu', 'je', 'danwei', 'lastdate', 'thisdate', 'cbr', 'querytime', 'newje', 'isvoid'];
        $d = [];
        foreach ($field as $f) {
            if (isset($data[$f])) {
                if (($f == 'lastdate' || $f == 'thisdate' || $f == 'querytime') && $data[$f] == "") { //日期格式不支持传空字符串
                    $d[$f] = null;
                } else {
                    $d[$f] = $data[$f];
                }
            } else {
                $d[$f] = null;
            }
        }

        $where = [];
        if ($xdlistid > 0) {
            $where['id'] = $xdlistid;
        }
        if ($xdlistid != 0) {
            $this->getdb("xd")->where($where)->update($d);
        } else {
            $d['username'] = $data['username'];
            $xdlistid = $this->getdb("xd")->insert($d);
        }

        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $xdlistid = $data['xdlistid'] ?? 0;
        $rt['data'] = $xdlistid;
        $rt['message'] = "操作成功";
        if ($xdlistid == 0) {
            $rt['message'] = "添加成功！";
        }

        // $rt['input'] = $data;

        return $rt;
    }

    public function getZxklist($data)
    {
        $key = $data['keyword'] ?? '';
        $pagesize = $data['pagesize'] ?? '';
        $page = $data['page'] ?? '';
        $enddate = $data['enddate'] ?? '';
        $startdate = $data['startdate'] ?? '';
        $datekeyword = $data['datekeyword'] ?? "dzdate";
        $where = [];
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|djcode|cbr'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
        }

        if (!empty($startdate) && strlen($startdate) > 2) { //开始时间只有inserttime时查询会使用
            $where[$datekeyword] = ['>=', $startdate . ' 00:00:00'];
        }

        $allcbr = [];
        if ($data['myusername'] != "Admin") {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $where['cbr'] = ['in', $allcbr];
        }


        $order = $datekeyword . " ,ah";
        $field = ['djcode', 'dzdate', 'cqts', 'ah', 'FORMAT(sje,2) sje', 'FORMAT(tje,2) tje', 'FORMAT(ye,2) ye', 'cbr'];
        $num = $this->getdb('djye_v')->where($where)->count();
        $_count = $this->getdb('djye_v')->where($where)->field("count(1) as cnum,FORMAT(sum(sje),2) as sje,FORMAT(sum(tje),2) as tje ,FORMAT(sum(ye),2) as ye")->find();
        $data = $this->getdb('djye_v')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();
        $alldata = $this->getdb('djye_v')->where($where)->field($field)->order($order)->page(1, $num)->select();

        $rt = [];
        $rt['code'] = 20000;
        $d = [];
        $d['total'] = $num;
        $d['totalcount'] = $_count;
        $d['items'] = $data;
        $d['allitems'] = $alldata;
        $rt['data'] = $d;
        return $d;
    }

}
