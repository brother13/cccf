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
            case "cflist_grouped": //
                $cflist = $this->getCfListGrouped($data);
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
            case 'saveCflistusername': // 删除用户
                $id = $data['cflistid'] ?? 0;
                $data = $this->saveCflistusername($id, $data);
                $rt['data'] = $data;
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
            case "xdztcount": //自动化执行提醒统计
                $rt = $this->getXdZtCount($data);
                break;
            case "xdlisttotz": //续冻结果更新到台账
                $rt = $this->updateXdToCflist($data);
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
                    $url = $data["baseurl"] . "index.html#/tz/txcl?id=" . $id;
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
            case "urlcflistadd_batch": //网页数据导入，批量的，做自动筛选
                $rt = $this->saveCflistone_batch(0, $data);
                if ($data["baseurl"] != '') {
                    $id = $rt['data'];
                    $url = $data["baseurl"] . "index.html#/tz/txcl?batch=1&batchid=" . $id;
                    header("Location: " . $url);
                    exit(); // 确保脚本执行完毕后停止
                }
                break;
            case 'getCfBatchList': // 获取提交的查封批量数据
                $rt = $this->getCfBatchList($data);
                break;
            case 'batch_save': // 生成批量的数据
                $rt = $this->batch_save($data);
                break;
            case 'getDocTemplateList': // 获取文书模板列表
                $rt = $this->getDocTemplateList($data);
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

    public function saveCflistusername($cflistid, $data)
    {
        $rt = [];
        if ($cflistid > 0 && strlen($data['username']) > 0) {
            $where['id'] = $cflistid;
            $d['username'] = $data['username'];
            $rt = $this->getdb("cflist")->where($where)->update($d);
        }
        return  $rt;
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

    public function saveCflistone($cflistid, $data)
    {
        // $check = $this->checkCbr($data['cbr']);
        // if ($check['code'] != self::CODE_SUCCESS) {
        //     return $check;
        // }

        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'isvoid', 'ccqk', 'cfsf', 'ycbr', 'autocf', 'leixing', 'zbah', 'account', 'sjdjje', 'sjkhje', 'khljje'];
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
            $d['username'] = $this->userinfo['username'] ?? '';
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
        $filterByUsername = config('cflist_filter_by_username');
        $desc = '';
        $id = $data['id'] ?? ''; //根据id查询时，为取唯一值，忽略其他条件
        $where = [];
        // if (!empty($key) && strlen($key) > 0) {
        //     $where['ah|cbr|sqzxr|bzxr|note|ycbr'] = ['like', '%' . $key . '%'];
        // }
        if (!empty($key) && strlen($key) > 0) {
            $where['ah|cbr|sqzxr|bzxr|note|ycbr'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
            $desc = '  ';
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
        if ($myusername != "Admin"  && strlen($myusername) > 0) {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $filterField = $filterByUsername ? 'username' : 'cbr|ycbr';
            $where[$filterField] = ['in', $allcbr];
        }

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        if (strlen($id) > 0) { //根据id查询时，为取唯一值，忽略其他条件
            $where = [];
            $where['id'] = $id;
        }




        $order = $datekeyword . $desc . " ,id";
        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'id cflistid', 'isvoid', 'inserttime', 'ccqk', 'cfsf', 'ycbr', 'autocf', 'leixing', 'zbah', 'account', 'sjdjje', '0 as sjkhje', 'khljje', 'khljje as khljjebck'];
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

    public function getCfListGrouped($data)
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
        $filterByUsername = config('cflist_filter_by_username');
        $desc = '';
        $id = $data['id'] ?? '';
        $where = [];

        if (!empty($key) && strlen($key) > 0) {
            $where['ah|cbr|sqzxr|bzxr|note|ycbr'] = ['like', '%' . $key . '%'];
        }

        if (!empty($enddate) && strlen($enddate) > 2) {
            $where[$datekeyword] = ['<=', $enddate . ' 23:59:59'];
            $desc = '  ';
        }
        if (!empty($cfsf) && strlen($cfsf) > 1) {
            $where['cfsf'] = $cfsf;
        }

        if (!empty($startdate) && strlen($startdate) > 2) {
            $where['inserttime'] = ['>=', $startdate . ' 00:00:00'];
        }

        $allcbr = [];
        if (is_array($deptcode) && count($deptcode) > 0) {
            $allcbr = $this->getAllDeptCbr($deptcode);
        }
        if ($myusername != "Admin"  && strlen($myusername) > 0) {
            $allcbr[] = $data['myusername'];
        }
        if (is_array($allcbr) && count($allcbr) > 0) {
            $filterField = $filterByUsername ? 'username' : 'cbr|ycbr';
            $where[$filterField] = ['in', $allcbr];
        }

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        if (strlen($id) > 0) {
            $where = [];
            $where['id'] = $id;
        }

        $order = "enddate asc,id desc";
        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'id cflistid', 'isvoid', 'inserttime', 'ccqk', 'cfsf', 'ycbr', 'autocf', 'leixing', 'zbah', 'account', 'sjdjje', '0 as sjkhje', 'khljje', 'khljje as khljjebck'];
        $num = $this->getdb('cflist_v')->where($where)->count();

        $d = [];
        if ($num < 1) {
            $d['total'] = 0;
            $d['items'] = [];
            $d['allitems'] = [];
            return $d;
        }

        $alldata = $this->getdb('cflist_v')->where($where)->field($field)->order($order)->page(1, $num)->select();
        $grouped = [];

        foreach ($alldata as $row) {
            $ah = $row['ah'] ?? '';
            $caseKey = trim($ah) === '' ? 'empty_' . $row['cflistid'] : $ah;
            if (!isset($grouped[$caseKey])) {
                $grouped[$caseKey] = [
                    'case_key' => $caseKey,
                    'ah' => $ah,
                    'cbr' => $row['cbr'] ?? '',
                    'sqzxr' => $row['sqzxr'] ?? '',
                    'bzxr' => $row['bzxr'] ?? '',
                    'ycbr' => $row['ycbr'] ?? '',
                    'isvoid' => $row['isvoid'] ?? '',
                    'min_enddate' => $row['enddate'] ?? '',
                    'max_enddate' => $row['enddate'] ?? '',
                    'item_count' => 0,
                    'children' => []
                ];
            }

            if (!empty($row['enddate'])) {
                if (empty($grouped[$caseKey]['min_enddate']) || strtotime($row['enddate']) < strtotime($grouped[$caseKey]['min_enddate'])) {
                    $grouped[$caseKey]['min_enddate'] = $row['enddate'];
                }
                if (empty($grouped[$caseKey]['max_enddate']) || strtotime($row['enddate']) > strtotime($grouped[$caseKey]['max_enddate'])) {
                    $grouped[$caseKey]['max_enddate'] = $row['enddate'];
                }
            }

            $grouped[$caseKey]['children'][] = $row;
            $grouped[$caseKey]['item_count']++;
        }

        $dateSortValue = function ($value) {
            if (empty($value)) {
                return PHP_INT_MAX;
            }
            $time = strtotime($value);
            return $time === false ? PHP_INT_MAX : $time;
        };

        $caseItems = array_values($grouped);
        foreach ($caseItems as &$caseItem) {
            usort($caseItem['children'], function ($a, $b) use ($dateSortValue) {
                $dateCompare = $dateSortValue($a['enddate'] ?? '') <=> $dateSortValue($b['enddate'] ?? '');
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }
                return intval($b['cflistid'] ?? 0) <=> intval($a['cflistid'] ?? 0);
            });
        }
        unset($caseItem);

        usort($caseItems, function ($a, $b) use ($dateSortValue) {
            $dateCompare = $dateSortValue($a['min_enddate'] ?? '') <=> $dateSortValue($b['min_enddate'] ?? '');
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp((string)($a['case_key'] ?? ''), (string)($b['case_key'] ?? ''));
        });

        $total = count($caseItems);
        $page = intval($page) > 0 ? intval($page) : 1;
        $pagesize = intval($pagesize) > 0 ? intval($pagesize) : 10;
        $offset = ($page - 1) * $pagesize;

        $d['total'] = $total;
        $d['items'] = array_slice($caseItems, $offset, $pagesize);
        $d['allitems'] = $alldata;
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
            $where['cbr|username|ycbr'] = ['in', $allcbr];
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

        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'status', 'startdate', 'enddate', 'note', 'id cflistid', 'isvoid', 'inserttime', 'ccqk', 'ycbr', 'autocf', 'leixing', 'zbah', 'account', 'sjdjje', 'sjkhje', 'khljje'];
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
        $field = ['cbr', 'ah', 'sqzxr', 'bzxr', 'type', 'cfsf', 'startdate', 'enddate', 'note', 'username', 'ccqk', 'isvoid', 'ycbr', 'sqzxr'];
        $fieldName = ['承办人', '案号', '申请执行人', '被执行人', '财产类型', '查封状态', '查封生效日期', '查封届满日期', '备注', '操作员', '财产情况', '是否停用', '原承办人', '申请人'];
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

    protected function getajjbxx($ah = '')
    {

        // $where = [];
        // $where['caseinfo'] = $ah;
        // $data = [];
        // $list = $this->getdb("ajjbxx_v")->where($where)->find();
        // $data['data'] = $list;

        $where = [];
        $where['ah'] = $ah;
        $order = "inserttime desc";

        $field = ['ah' => 'caseinfo', 'sqzxr', 'bzxr', 'zxay' => "laay", 'zxyjah' => "zxyjah"];

        $info = $this->getdb(self::TABLE_CFLIST)->field($field)->where($where)->find();
        $data = [];
        $data['data'] = $info;
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
        $zt = $data['zt'] ?? '';
        $myusername = $data['myusername'] ?? '';
        $id = $data['id'] ?? ''; //根据xdlistid查询时，为取唯一值，忽略其他条件
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
        if (strlen($id) > 0) { //根据xdlistid查询时，为取唯一值，忽略其他条件
            $where = [];
            $where['id'] = $id;
        }
        if (strlen($zt) > 0) {
            $where['zt'] = $zt;
        }

        $order = $datekeyword . " ,ah";
        $field = ['ah', 'zt', 'bzxr', 'zhanghu', 'FORMAT(je,2) as je', 'danwei', 'startdate', 'lastdate', 'thisdate', 'cbr', 'querytime', 'FORMAT(newje,2) as newje,id as xdlistid', 'isvoid', 'enddate', 'reason', 'ledgerUpdated'];
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

    protected function getXdZtCount($data = [])
    {
        $rt = $this->_rt();
        $myusername = $data['myusername'] ?? '';
        $statusList = ['继续冻结成功', '继续冻结失败'];
        $count = [
            'continueFreezeSuccess' => 0,
            'continueFreezeFail' => 0
        ];

        foreach ($statusList as $status) {
            $where = [];
            $where['zt'] = $status;
            if (strlen($myusername) > 0) {
                $where['cbr'] = $myusername;
            }

            $num = $this->getdb('xd_v')->where($where)->count();
            if ($status == '继续冻结成功') {
                $count['continueFreezeSuccess'] = $num;
            } else {
                $count['continueFreezeFail'] = $num;
            }
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $count;
        return $rt;
    }

    protected function updateXdToCflist($data = [])
    {
        $rt = $this->_rt();
        $ah = trim($data['ah'] ?? '');
        $account = trim($data['zhanghu'] ?? '');
        $startdate = $this->formatDateValue($data['startdate'] ?? '');
        $enddate = $this->formatDateValue($data['enddate'] ?? '');

        if ($ah == '' || $account == '') {
            $rt['message'] = '案号或续冻账号为空，无法更新台账';
            return $rt;
        }
        if ($enddate == '') {
            $rt['message'] = '届满日期为空，未更新台账';
            return $rt;
        }

        $where = [
            'ah' => $ah,
            'account' => $account
        ];
        $matched = $this->getdb('cflist')->where($where)->count();
        if ($matched < 1) {
            $rt['message'] = '台账中未找到匹配的案号和续冻账号';
            return $rt;
        }

        $saveData = [
            'enddate' => $enddate
        ];
        if ($startdate != '') {
            $saveData['startdate'] = $startdate;
        }

        $updated = $this->getdb('cflist')->where($where)->update($saveData);
        $xdlistid = $data['xdlistid'] ?? 0;
        if ($xdlistid > 0) {
            $this->getdb('xd')->where(['id' => $xdlistid])->update(['ledger_updated' => 1]);
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = [
            'matched' => $matched,
            'updated' => $updated
        ];
        $rt['message'] = $updated > 0 ? '更新台账成功' : '台账日期已一致，无需更新';
        return $rt;
    }

    protected function formatDateValue($value = '')
    {
        if (empty($value)) {
            return '';
        }
        $time = strtotime($value);
        if ($time === false) {
            return '';
        }
        return date('Y-m-d', $time);
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
        $field = ['ah', 'zt', 'bzxr', 'zhanghu', 'je', 'danwei', 'startdate', 'lastdate', 'thisdate', 'cbr', 'querytime', 'newje', 'isvoid', 'enddate', 'reason'];
        $d = [];
        foreach ($field as $f) {
            if (isset($data[$f])) {
                if (($f == 'startdate' || $f == 'lastdate' || $f == 'thisdate' || $f == 'querytime') && $data[$f] == "") { //日期格式不支持传空字符串
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
        // $num = $this->getdb('djye_v')->where($where)->count();
        // $_count = $this->getdb('djye_v')->where($where)->field("count(1) as cnum,FORMAT(sum(sje),2) as sje,FORMAT(sum(tje),2) as tje ,FORMAT(sum(ye),2) as ye")->find();
        // $data = $this->getdb('djye_v')->where($where)->field($field)->order($order)->page($page, $pagesize)->select();
        // $alldata = $this->getdb('djye_v')->where($where)->field($field)->order($order)->page(1, $num)->select();
        $num = 0;
        $_count = [];
        $data = [];
        $alldata = [];

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



    // 保存批量的查封信息
    public function saveCflistone_batch($cflistid = 0, $data = [])
    {



$data = $data['data']??'';
$data = _cv_to_array($data);


        $rt = $this->_rt();


        $table = "postlog";

        $newdata = [];
        $newdata['clientip'] = get_client_ip();
        $newdata['createtime'] = getNowTime();
        $newdata['updatetime'] = getNowTime();
        $newdata['posttime'] = getNowTime();
        $newdata['postdata'] = _cv_to_json($data);

        $strdata = _cv_to_json($newdata['postdata']);
        $datasize = strlen($strdata);

        $datahash = md5($strdata);
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['datasize'] = $datasize;
        $where['datahash'] = $datahash;
        $info = $this->getdb($table)->where($where)->find();

        if ($info) {
            $rt['code'] = self::CODE_SUCCESS;
            $rt['message'] = 'OK';
            $rt['data'] = $info['id'] - 0;
            return $rt;
        }

        $newdata['datasize'] = $datasize;
        $newdata['datahash'] = $datahash;

        $newid = $this->getdb($table)->insertGetId($newdata);
        $ckinfo = [];
        // try {
        $ckinfo = $this->convertBatchDataTockInfo($data);
        // } catch (\Exception $e) {
        //     $error = $e->getMessage();
        //     halt($e);
        //     if (!json_encode($error)) {
        //         $error = _cv_gbk_to_utf8($error);
        //     }

        //     halt($error);
        // }



        if ($newid) {
            $caseinfo = $ckinfo['ajxx']['ah'] ?? '';
            $savedata = [];
            $savedata['caseinfo'] = $caseinfo;
            $savedata['ckinfo'] = _cv_to_json($ckinfo);
            $where = [];
            $where['id']  = $newid;
            $this->getdb($table)->where($where)->update($savedata);
        }



        $rt = $this->_rt();
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $newid - 0;
        return $rt;
    }

    protected function convertBatchDataTockInfo($data = [])
    {


        $info = [];
        $ajxx = $data['ajxx'] ?? [];
        $oldajxx = $data['oldajxx'] ?? [];
        $dsr = $data['dsr'] ?? [];

        $ygtype = ['申请执行人'];
        $bgtype = ['被执行人'];

        $yglist = [];
        $bglist = [];
        foreach ($dsr as $d) {
            $ssdw = $d['ssdw'] ?? '';

            if (in_array($ssdw, $ygtype)) {
                $yglist[] = $d['dsrname'];
            }
            if (in_array($ssdw, $bgtype)) {
                $bglist[] = $d['dsrname'];
            }
        }

        $ygall = implode(',', $yglist);
        $bgall = implode(',', $bglist);


        $ajxx['sqzxr'] = $ygall;
        $ajxx['bzxr'] = $bgall;

        // 寻找oldajxx里，定位当前案件，并获取承办人、承办部门等信息
        $ajxxmore = array_filter($oldajxx, function ($item) use ($ajxx) {
            return strpos($item['ajlxmc'], '本案') > -1;
        });
        // 强行取第一个
        $ajxxmore = array_values($ajxxmore)[0];

        $field_more = ['larq', 'jarq', 'sxrq', 'fyname', 'deptname', 'cbr'];
        foreach ($field_more as $f) {
            $v = $ajxxmore[$f] ?? '';
            $v = trim($v);
            if (!empty($v) && empty($ajxx[$f])) {
                $ajxx[$f] = $v;
            }
        }
        // $info['caseinfo'] = $data['ajxx']
        $cklist = $data['ckxx'] ?? [];
        $caseinfo_zb = "";
        foreach ($oldajxx as $aj) {
            $ah = $aj['ah'] ?? '';
            if (strpos($ah, '执保')) {
                $caseinfo_zb = $aj;
                break;
            }
        }
        $ajxx['zbah'] = $caseinfo_zb; // 保存执保案号
        $newlist = [];
        // 查控的字段映射

        foreach ($cklist as $row) {

            $item = [];
            $item['leixing'] = "总对总";

            // 判断控制情况，是否是成功的
            $dsrmc = $row['mc'] ?? '';
            // 如果有<br ，则取<br前面的文本
            $arr = explode("<br/>", $dsrmc);
            $item['sqzxr'] = $ygall;
            $item['bzxr'] = $arr[0] ?? ''; // 被执行人
            $item['account'] = $row['khzh'] ?? '';
            $children = $row['children'] ?? [];
            if (count($children) < 1) {
                continue;
            }
            foreach ($children as $last) {
                $kzqk = trim($last['kzqk'] ?? '');

                if (strpos($kzqk, '冻结成功') === false) {
                    continue;
                }
                $childItem = $item;
                $childItem['type'] = $last['cclxmc'] ?? '';



                $startdate = $last['tqkzsj'] ?? ''; //开始日期
                $enddate = $last['csjsrq'] ?? ''; // 届满日期

                if (!empty($startdate)) {
                    $startdate = date('Y-m-d', strtotime($startdate));
                }
                if (!empty($enddate)) {
                    $enddate = date('Y-m-d', strtotime($enddate));
                }
                $childItem['startdate'] = $startdate;
                $childItem['enddate'] = $enddate; //届满日期


                $childItem['sjdjje'] = $last['sdje'] ?? 0; // 冻结金额
                $dsrxm = $last['zjxx'] ?? "";
                $bankname = $last['fkxx'] ?? '';
                $bankname = str_replace(['<![CDATA[', '<br/>', ']]>'], '', $bankname);

                // bankname的样式如下 平安银行(2025/10/15 10:53:43)，我需要把它拆成两部分，一个银行，一个反馈时间
                $bankname = str_replace('(', '|', $bankname);
                $bankname = str_replace(')', '|', $bankname);
                $bankname_arr = explode('|', $bankname);
                $bankname = $bankname_arr[0];
                $feedbacktime = $bankname_arr[1] ?? '';

                $note = $childItem['bzxr'] . ',' . $dsrxm . ";" . $bankname . ':' . $feedbacktime . ';' . $childItem['account'];
                $childItem['ccqk'] = $note;
                $childItem['zbah'] = $caseinfo_zb;
                $childItem['bankname'] = $bankname;
                $childItem['bank_feedbacktime'] = $feedbacktime;

                $childItem['action'] = $last['kzcsMc'] ?? '';
                $childItem['kzqk'] = $kzqk;
                $childItem['autocf'] = '1';
                $newlist[] = $childItem;
            }
        }






        $info['ajxx'] = $ajxx;
        $info['oldajxx'] = $oldajxx;
        $info['dsr'] = $dsr;
        $info['cklist'] = $newlist;

        return $info;
    }


    public function getCfBatchList($param = [])
    {

        $rt = $this->_rt();

        $id = $param['id'] ?? 0;

        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['id'] = $id;

        $table = self::TABLE_POSTLOG;
        $field = "*";
        $info = $this->getdb($table)->field($field)->where($where)->find();
        if (!$info) {
            $rt['message'] = '未获取到数据';
            return $rt;
        }



        unset($info['postdata']);

        $ckinfo = _cv_to_array($info['ckinfo']);
        unset($info['ckinfo']); // 移到后面去

        // 检查有没有遗漏的申请执行人

        // 做一些格式的调整



        $info['ckinfo'] = $ckinfo;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $info;
        return $rt;
    }

    protected function batch_save($param = [])
    {

        $rt = $this->_rt();

        $ckList = $param['ckList'] ?? [];
        if (count($ckList) < 1) {
            $rt['message'] = "数据不能为空";
            return $rt;
        }


        $field = ['cbr', 'ah', 'zbah', 'zxyjah', 'sqzxr', 'zxay', 'note'];
        $id = $param['id'] ?? 0;






        $newdata = [];
        $newdata['username'] = $this->userinfo['username'] ?? '';
        if (($param['autocf'] ?? 0) == 1) {
            $newdata['autocf'] = 1;
        }

        $now = getNowTime();
        $newdata['inserttime'] = $now;
        // $newdata['updatetime'] = $now;

        foreach ($field as $f) {
            $newdata[$f] = $param[$f] ?? '';
        }



        $alldata = [];

        $field_detail = ['leixing', 'bzxr', 'account', 'type', 'startdate', 'enddate', 'sjdjje', 'ccqk'];


        foreach ($ckList as $row) {
            $item = [];

            foreach ($newdata as $key => $value) {
                $item[$key] = $value;
            }

            foreach ($field_detail as $f) {
                $item[$f] = $row[$f] ?? '';
            }

            $alldata[] = $item;
        }



        $table = self::TABLE_CFLIST;

        $this->getdb($table)->insertAll($alldata);

        // 保存使用记录

        $newinfo = [];
        $newinfo['usetime'] = getNowTime();
        $newinfo['isused'] = 1;
        $newinfo['username'] = $this->userinfo['username'] ?? '';
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['id'] = $id;
        $this->getdb(self::TABLE_POSTLOG)->where($where)->update($newinfo);


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = count($alldata);
        return $rt;
    }

    protected function getDocTemplateList($param = [])
    {


        $type = $param['type'] ?? 'txcl';


        $rt = $this->_rt();

        $config = config("template");

        $config = $config[$type] ?? [];





        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = 'OK';
        $rt['data'] = $config;
        return $rt;
    }
}
