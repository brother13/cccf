<?php

namespace app\cccf\controller;

use app\cccf\model\Attachment;
use app\cccf\model\Log;
use \think\Controller;
use \think\Request;

class File extends Controller
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

        $rt['code'] = $data['code'] ?? self::CODE_SUCCESS;
        $rt['message'] = $data['message'] ?? $rt['message'];
        if (isset($data['total'])) {
            $rt['total'] = $data['total'];
        }
        $rt['data'] = $this->upload();
        //$rt['data'] = $data['data'];

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

    /**
     * 显示图片
     *
     * @param [type] $id
     * @return void
     */
    public function getImage($id = 0, $small = 0)
    {
        $model = new Attachment();
        $data = $model->getFile($id, true, $small);
        return $data;
    }

    /**
     * 下载文件
     *
     * @param [type] $id
     * @return void
     */
    public function getfile($id = 0)
    {
        $model = new Attachment();
        $data = $model->getFile($id);
        return $data;
    }
    /**
     * 下载新闻列表文件
     *
     * @param [type] $id
     * @return void
     */
    public function getNewsFile($id = 0)
    {
        $model = new Attachment();
        $data = $model->getNewsFile($id);
        return $data;
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

// 控制器中的上传方法
    public function upload()
    {
        // 获取表单上传文件
        $file = request()->file('file');

        if ($file) {
            $filaname=$file->getInfo();
            $info = $file->move( 'uploads',$filaname['name']);
            
            return json(['status' => 'success', 'file_path' => $info->getSaveName(),'file_name' => $filaname]);
        } else {
            return json(['status' => 'error', 'msg' => 'No file uploaded']);
        }
    }
}
