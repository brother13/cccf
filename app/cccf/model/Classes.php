<?php

namespace app\cccf\model;

use PDO;

/**
 * 基础数据管理
 *
 * @author netknave
 *
 */
class Classes extends Common
{
    const ACTION = "class";
    const COMMENT = "基础数据管理";
    const FIELD = ["id", "dwid", "classtype", "classcode", "classname", "classnote",  "isvoid", "createtime", "updatetime", "rank"];
    const FIELD_FILTER = "classname|classnote"; // 快速搜索字段
    const FIELD_PK = "id"; // 主键
    const FIELD_CHECK = []; //需要检查重复的字段
    const FIELD_CHECK_NOTE = []; // 需要检查重复的字段说明
    const TABLE = "class";




    const CLASSNOTE = [
        "joblevel" => "领导职务",
        "jobauth" => "编制",
        "jobpost" => "岗位",
        "lawfirm" => "律所",
        "othercompany" => '单位',
        "gender" => '性别',
        'cardtype' => '证件类型',
        'zzmm' =>"政治面貌"

    ];

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


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";

        $type = $data['classtype'] ?? '';
        if ($action != 'all') {
            if (empty($type)) {
                $rt['message'] = "类型不能为空！";
                return $rt;
            }
            if (!array_key_exists($type, self::CLASSNOTE)) {
                $rt['message'] = "类型[{$type}]不合法！";
                return $rt;
            }
        }


        switch ($action) {
            case "list": // 列表

                $rt = $this->getList($key, $type, $isvoid, $page, $pagesize);
                break;
            case 'all': // 同时获取多个类型数据
                $rt = $this->getAllList($type);
                break;
            case 'down': //下载，同列表
                $pagesize = 99999999;
                $rt = $this->getList($key, $type, $isvoid, $page, $pagesize);
                break;
            case "save": // 保存
                $id = $data[self::FIELD_PK] ?? '';
                if (empty($id)) {
                    $rt['message'] = "主键不能为空！";
                    return $rt;
                }
                $rt = $this->save($id, $type, $data);
                break;
            case "add": // 新增
                $rt = $this->save('', $type, $data);
                break;
            case "del": // 删除
                $id = $data[self::FIELD_PK] ?? '';
                if (empty($id)) {
                    $rt['message'] = "不能为空！";
                    return $rt;
                }
                $rt = $this->del($id, $type);
                break;
            case 'info': // 获取明细
                $id = $data[self::FIELD_PK] ?? '';
                if (empty($id)) {
                    $rt['message'] = "不能为空！";
                    return $rt;
                }
                $rt['data'] = $this->getinfo($id,$type);
                break;
            case 'newcode': //获取新代码
                $rt['data'] = $this->getnewcode($type);
                $rt['code'] = self::CODE_SUCCESS;
                break;
            default:
                $rt['message'] = "操作【/" . $this->ACTION . "/{$action}】并不存在！";
        }

        return $rt;
    }

    /**
     * 获取列表
     */
    public function getList($key = "", $type = '', $isvoid = 0, $page = 1, $pagesize = 50)
    {



        $field = self::FIELD;
        $order = "rank";
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isdel'] = 0;
        $where['classtype'] = $type;

        if ($isvoid != '') {
            $where['isvoid'] = $isvoid;
        }
        if (!empty($key)) {
            $where[self::FIELD_FILTER] = ['like', "%{$key}%"];
        }



        $db = $this->getdb(self::TABLE);
        $num = $db->where($where)->count();
        $data = $db->field($field)->where($where)->order($order)->page($page, $pagesize)->select();


        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $d = [];
        $d['total'] = $num;
        $d['items'] = $data;
        $rt['data'] = $d;

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

        $field = self::FIELD;
        $where = [];
        $where['isdel'] = 0;
        $where['dwid'] = $this->dwid;
        $where['classtype'] = $type;

        $where[self::FIELD_PK] = $id;
        $data = $this->getdb(self::TABLE)->where($where)->field($field)->find();
        //获取roles
        if (!$data) {
            $rt['message'] = "数据不存在";
            return $rt;
        }
        $rt['data'] = $data;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "";
        return $rt;
    }


    /**
     * 删除
     * @param  integer $id [description]
     * @return [type]          [description]
     */
    public function del($id = 0, $type = '')
    {
        $rt = $this->_rt();
        if (empty($id)) {
            $rt['code'] = self::CODE_ERROR;
            $rt['data'] = "";
            $rt['message'] = "ID不能为空";
            return $rt;
        }



        $where = [];
        $where[self::FIELD_PK] = $id;
        $where['dwid'] = $this->dwid;
        $where['classtype']  = $type;

        $data = [];
        $data['isdel'] = 1;
        $data['deltime'] = getNowTime();
        $d = $this->getdb(self::TABLE)->where($where)->update($data);


        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $d;
        $rt['message'] = "删除成功";
        return $rt;
    }

    /**
     * 保存，id=0为新增，有值为修改
     * @param  [type] $id [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function save($id, $type, $data)
    {

        $check = [];
        $check['table'] = self::TABLE;
        $check['field'] = self::FIELD_CHECK;
        $check['comment'] = self::FIELD_CHECK_NOTE;
        $check['data'] = $data;
        $check['id'] = $id;
        $ck = $this->checkFieldData($check);
        if ($ck['code'] != self::CODE_SUCCESS) {
            return $ck;
        }

        $rt = $this->_rt();
        $classname = $data['classname'] ?? '';
        if (empty($classname)) {
            $rt['message'] = "内容不能为空！";
            return $rt;
        }

        $field = self::FIELD;
        $dwid = $this->dwid;
        $d = [];

        foreach ($field as $f) {
            if (isset($data[$f])) {
                $d[$f] = $data[$f];
            }
        }

        $d['dwid'] = $dwid;
        $d['classtype'] = $type;



        $d['updatetime'] = getNowTime();



        $where = [];
        $where['dwid'] = $this->dwid;
        $where['classtype'] = $type;
        $where['isdel'] = 0;
        $where[self::FIELD_PK] = $id;
        $newid = $id;
        if (!empty($id)) {
            //update


            $this->getdb(self::TABLE)->where($where)->update($d);
        } else {
            // 主键使用uuid生成
            // $d[self::FIELD_PK] = uuid();
            $d['createtime'] = getNowTime();
            $newid = $this->getdb(self::TABLE)->insertGetId($d);
        }
        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data'] = $newid;
        $rt['message'] = "操作成功";

        return $rt;
    }

    /**
     * 获取新代码
     *
     * @return void
     */
    protected function getnewcode($type = '')
    {
        $where = [];
        $where['classtype'] = $type;
        $newcode = $this->genNewCode("class", "classcode", $this->dwid, $where);
        return $newcode;
    }

    protected function getAllList($type = [])
    {
        $rt = $this->_rt();

        $where =  [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        if (is_string($type)) {
            if (!empty($type)) {
                $type = [$type];
            } else {
                $type = [];
            }
        }

        foreach ($type as $row) {
            if (!array_key_exists($row, self::CLASSNOTE)) {
                $rt['message'] = "[{$row}]不存在";
                return $rt;
            }
        }
        $types = [];
        if (count($type) < 1) {
            // 获取所有类型

            foreach (self::CLASSNOTE as $key => $value) {
                $types[] = $key;
            }
        } else {
            if (!is_array($type)) {
                $types = [$type];
            } else {
                $types = $type;
            }
        }
        $where['classtype'] = ['in', $types];

        $order = "classtype,rank,classcode";
        $field = "id,classtype,classcode,classname,classnote,isvoid,updatetime";

        $data = $this->getdb(self::TABLE)->where($where)->order($order)->field($field)->select();
        $alldata = [];
        foreach ($types as $t) {
            $alldata[$t] = [];
        }
        foreach ($data as $row) {
            $t = $row['classtype'];
            $alldata[$t][] = $row;
        }

        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $alldata;
        $rt['message'] = "操作成功";

        return $rt;
    }
}
