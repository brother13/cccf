<?php

namespace app\cccf\controller;

use app\cccf\model\Attachment;
use app\cccf\model\Cfcc;
use app\cccf\model\Classes;
use app\cccf\model\Dagl;
use app\cccf\model\Data;
use app\cccf\model\Dept;
use app\cccf\model\Group;
use app\cccf\model\Log;
use app\cccf\model\Node;
use app\cccf\model\Sjcl;
use app\cccf\model\System;
use app\cccf\model\Test;
use app\cccf\model\Plugins;
use app\cccf\model\Thqd;
use app\cccf\model\Dkp;
use app\cccf\model\Lixijs;
use app\cccf\model\Lpr;
use app\cccf\model\Benchmark;
use app\cccf\model\Update;
//测试用的
use app\cccf\model\User;
use \think\Controller;
use \think\Db;
use \think\Request;

class Index extends Controller
{
    const CODE_SUCCESS = 20000;
    const CODE_ERROR = 0;

    /**
     * 创建一个空的返回值
     *
     * @return void
     */
    protected function _rt()
    {
        $rt = [];
        $rt['code'] = self::CODE_ERROR;
        $rt['action'] = input("param.action", "/sys/info");
        $rt['action'] = input("param.action", "/upload/info");
        $rt['message'] = "";
        $rt['time'] = getNowTime();
        $rt['page'] = input("param.page", 1);
        $rt['pagesize'] = input("param.pagesize", 100);
        $rt['total'] = 1;
        $rt['data'] = "";
        return $rt;
    }
    /**
     * 入口程序
     */
    public function index()
    {
        $rt = $this->_rt();
        $rt['code'] = self::CODE_ERROR;
        $rt['message'] = "";
        $action = input("param.action", "/sys/info");
        $action = input("param.action", "/upload/id");
        $token = input("param.token", "");

        $action_arr = explode("/", $action);

        if (count($action_arr) < 3) {
            $rt['message'] = "操作【{$action}】不合法！";
            return $rt;
        }
        $param = input("param.");
        $postdata = isset($param['data']) ? $param['data'] : [];

        $data = [];

        // 统一记录日志入口
        $logModel = new Log();
        $logModel->Log($action_arr, $postdata);

        switch ($action_arr[1]) {

            case 'sys': //系统相关信息
                $model = new System();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'sjcl': //系统相关信息
                $model = new Sjcl();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'thqd': //退回清单
                $model = new Thqd();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'dkp': //待开收据
                $model = new Dkp();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'lixijs': //利息计算
                $model = new Lixijs();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'lpr': //LPR利率
                $model = new Lpr();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'benchmark': //贷款基准利率
                $model = new Benchmark();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'cfcc': //系统相关信息
                $model = new Cfcc();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'data': //数据相关信息
                $model = new Data();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'urldata': //数据相关信息
                $model = new Data();
                $data = $model->index($action_arr[2], $param);
                break;
            case 'user': //用户相关信息
                $model = new User();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'dept': //部门相关信息
                $model = new Dept();
                $data = $model->index($action_arr[2], $postdata);
                break;

            case 'group': //用户组相关信息
                $model = new Group();
                $data = $model->index($action_arr[2], $postdata);
                break;

            case 'node': //节点
                $model = new Node();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'nodetype': //节点分类
                $model = new Node();
                $data = $model->index($action_arr[2], $postdata, true);
                break;

            case 'log': //操作日志
                $model = new Log();
                $data = $model->index($action_arr[2], $postdata);
                break;

            case 'class': //基础资料管理
                $model = new Classes();
                $data = $model->index($action_arr[2], $postdata);
                break;
            case 'cccf': // 档案管理相关
                $model = new Dagl();
                $data = $model->index($action_arr[2], $postdata);
                break;

            case 'upload': //测试用
                $data['data'] = $this->upload();
                break;
            case 'html': // 获取页面数据
                if ($action_arr[2] == 'gdlcinfo') {
                    $ajbs = $postdata['ajbs'] ?? '';
                    $cklsh = $postdata['cklsh'] ?? '';
                    // $data = [];
                    $data['code'] = self::CODE_SUCCESS;
                    $data['message'] = "OK";
                    $data['data'] = $this->gdlcinfo($ajbs, $cklsh, true);
                }

                break;
            case 'plugins':
                $model = new Plugins();
                $data = $model->index($action_arr[2], $postdata);
                break;
            default:
                $rt['message'] = "操作【{$action}】不合法！";
                return $rt;
        }

        $rt['code'] = $data['code'] ?? self::CODE_SUCCESS;
        $rt['message'] = $data['message'] ?? $rt['message'];
        if (isset($data['total'])) {
            $rt['total'] = $data['total'];
        }
        $rt['data'] = $data['data'];

        return $rt;
    }

    /**
     * 空指针，用于处理异常
     *
     * @param string $name
     * @return void
     */
    public function _empty($name = '')
    {
        $rt = $this->_rt();
        // $rt = [];
        $rt['code'] = 0;
        $rt['message'] = "您访问的操作【{$name}】并不存在";
        $rt['data'] = "";

        return $rt;
    }

    // 控制器中的上传方法
    public function upload()
    {
        // 获取表单上传文件
        $file = request()->file('file');
        $cflistid = input("param.cflistid", 0);
        $savepath = "";

        if ($file) {
            $filaname = $file->getInfo();
            $savepath = 'uploads/' . $cflistid;
            $info = $file->move($savepath, $filaname['name']);
            $saveurl = $savepath . '/' . $filaname['name'];
            return json(['status' => 'success', 'SaveName' => $info->getSaveName(), 'filename' => $filaname['name'], 'filepath' => $saveurl, 'cflistid' => $cflistid]);
        } else {
            return json(['status' => 'error', 'msg' => 'No file uploaded']);
        }
    }

    public function uploadimage($filename = 'file')
    {
        $model = new Attachment();
        $rt = $model->upload($filename);
        return $rt;
    }
    public function uploadfile($filename = 'file')
    {
        $model = new Attachment();
        $rt = $model->upload($filename);
        return $rt;
    }

    /**
     * 连接数据
     *
     * @param [type] $name
     * @return object
     */
    protected function getdb($name)
    {
        return db($name);
    }

    public function genUserPass($pass = '', $salt = '')
    {

        $model = new User();
        $data = [];
        $data['password'] = $pass;
        $data['salt'] = $salt;

        $pass1 = md5($pass . '_RLF2020');
        $data['pass1'] = $pass1;
        $pass2 = $model->genPassword($pass1, $salt);
        $data['pass2'] = $pass2;
        return $data;
    }


    public function update($version = '20260130')
    {
        $model = new Update();
        $data = $model->updateDBVersion($version);
        return $data;
    }



    public function test_batchsave_cf($id = 0)
    {

        $model = new Data();

        // 获取test目录下所有的txt文件并存在一个数组中
        $files = glob('./test/*.txt');

        if ($id < 0) {
            $id = 0;
        }
        if ($id >= count($files)) {
            $id = count($files) - 1;
        }
        $file = $files[$id];
        $filedata = file_get_contents($file);
        $filedata = _cv_to_array($filedata);

        // die($filedata);
        $rt = $model->saveCflistone_batch(0, $filedata);



        return $rt;
    }
}
