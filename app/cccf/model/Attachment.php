<?php

namespace app\cccf\model;

use PDO;
use \think\Db;
use \think\Debug;

/**
 * 附件相关
 *
 * @author netknave
 *
 */
class Attachment extends Common
{

    protected const TABLE_NAME = "attachment";
    const TABLE_DOWNLOG = "downlog"; // 文件下载记录日志表
    const TABLE_ATTACHMENT = "attachment";
    const DEFAULT_NOIMAGE = "./assets/images/noimage.jpg";

    const allowViewExt = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'pdf']; // 允许直接预览的文件类型

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传文件
     * @param  string $formname [表单中的文件名]
     * @return [type]           [description]
     */
    public function upload($formname = 'file')
    {
        $data = [];
        $data['code'] = parent::CODE_SUCCESS;
        $data['message'] = "OK";
        $data['data'] = "";
        $dir = ROOT_PATH . 'public' . DS . 'uploads';
        // dump($dir);
        // $data['dir']=$dir;

        $upload = [];
        $upload['createtime'] = getNowTime();
        $upload['userid'] = $this->userid;
        $upload['dwid'] = $this->dwid;
        $upload['clientip'] = get_client_ip();

        $files = request()->file($formname);

        $uploads = [];

        if (!is_array($files)) {
            $uploads[] = $files;
        }
        if (is_array($files)) {
            foreach ($files as $file) {
                $uploads[] = $file;
            }
        }


        $param = input("param.");
        if (!is_array($param)) {
            $param = [];
        }

        $filetype = $param['filetype'] ?? 'image';
        $uploadtype = $param['uploadtype'] ?? self::UPLOADTYPE_NEWS; // 
        $caseid = $param['caseid'] ?? '';

        // 如果是thumb的话，要先删掉所有该文章之前的缩略图，再上传

        if ($filetype == self::FILETYPE_THUMB) {
            $this->delNewsThumb($caseid);
        }


        $upload_data = [];


        // dump($file);
        foreach ($uploads as $file) {
            $info = $file->move($dir);
            $infodata = $info->getInfo();
            // dump($infodata);

            $upload['caseid'] = $caseid;
            $upload['uploadtype'] = $uploadtype;


            $upload['oldname'] = $infodata['name'];
            $upload['filepath'] = $info->getSaveName();
            $filename = $info->getFilename();
            $upload['filename'] = $filename;
            $upload['ext'] = strtolower($info->getExtension());
            $upload['filehash'] = $info->hash("md5");
            $upload['filemime'] = $info->getMime();
            $upload['filesize'] = $infodata['size'];
            $upload['downnum'] = 0; // 初始下载次数
            // 如果是图片，则生成缩略图，并保存路径
            $upload['filelen'] = $this->getFileSize($infodata['size']);
            $upload['filetype'] = $filetype;

            if ($filetype == self::FILETYPE_IMAGE || $filetype == self::FILETYPE_THUMB) {

                $thumbfile = $this->getThumbPath($filename);
                $upload['thumb'] = $thumbfile;
            }



            $upload_data[] = $upload;
        }

        $update_info = [];
        foreach ($upload_data as $upload) {
            if (!empty($upload['filename'])) {

                $id = $this->getdb(self::TABLE_NAME)->insertGetId($upload);
                $where = [];
                $where['id'] = $id;
                $field = "id,filename,filepath,oldname,filemime,filehash,filesize";
                $filedata = $this->getdb(self::TABLE_NAME)->field($field)->where($where)->find();
                $update_info[] = $filedata;

                if ($filetype == self::FILETYPE_THUMB) {
                    $this->updateNewsThumb($caseid, $id);
                }




                // $data['data'] = $filedata;
            }
        }
        // 如果是thumb则更新news里的值

        $data['data'] = $update_info;







        return $data;
    }

    /**
     * 通过base64编码的方式上传图片
     *
     * @param string $type
     * @return void
     */
    public function uploadImage_Base64($oldname='',$type='image/jpeg',$filedata=''){

        $rt = $this->_rt();

        $dir = ROOT_PATH . 'public' . DS . 'uploads';
        $date = date('Ymd',time());

        $dir = $dir.'/'.$date;
        if(!file_exists($dir)){
            mkdir($dir);
        }
        // $info = explode("/",$type);
        // $ext = $info[1] ?? 'jpg';

        $ext = "";

        $info = explode('.',$oldname);
        $len = count($info);
        $ext = $info[$len-1];
       

        $filename = uniqid().'.'.$ext;
        $filepath = $dir.'/'. $filename;

        // halt($filepath);
        
        $filedata = base64_decode($filedata);
        file_put_contents($filepath,$filedata);
        $filelen = strlen($filedata);


        $newdata = [];
        $newdata['createtime'] = getNowTime();
        $newdata['updatetime'] = getNowTime();
        $newdata['ext'] = $ext;
        $newdata['filetype'] = self::FILETYPE_IMAGE;
        $newdata['uploadtype'] = self::UPLOADTYPE_NEWS;
        $newdata['userid'] = $this->userid;
        $newdata['filemime'] = $type;
        $newdata['dwid'] = $this->dwid;
        


        $newdata['filesize'] = $filelen;
        $newdata['filelen'] = $this->getFileSize($filelen);
        

        $newdata['filename'] = $filename;
        $newdata['oldname'] = $oldname;
        $newdata['filepath'] = $date.'/'.$filename;
        
        $newdata['filehash'] = md5($filedata);


        // 插入数据

        $id = $this->getdb(self::TABLE_ATTACHMENT)->insertGetId($newdata);

        $newdata['id'] = $id;

        
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $newdata;
        return $rt;


    }
    protected function updateNewsThumb($caseid, $id)
    {
        if (empty($id) || empty($fileid) || empty($caseid)) {
            return false;
        }

        $where = [];
        $where['id'] = $caseid;
        $where['isvoid'] = 0;
        $where['dwid'] = $this->dwid;
        $newdata = [];
        $newdata['thumb'] = $id;

        $this->getdb("news")->where($where)->update($newdata);

        return true;
    }

    protected function delNewsThumb($id)
    {
        if (!$id) {
            return false;
        }
        $where = [];
        $where['caseid'] = $id;
        $where['filetype'] = self::FILETYPE_THUMB;
        $newdata = [];
        $newdata['deltime'] = getNowTime();
        $newdata['isdel'] = 1;

        $this->getdb(self::TABLE_ATTACHMENT)->where($where)->update($newdata);
        return true;
    }
    protected function getFileSize($filesize)
    {
        if ($filesize >= 1073741824) {
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        } elseif ($filesize >= 1048576) {
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        } elseif ($filesize >= 1024) {
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        } else {
            $filesize = $filesize . ' 字节';
        }

        return $filesize;
    }
    /**
     * 获取上传附件信息
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function getInfo($id = 0)
    {
        $data = parent::_create_return();
        if (empty($id) || $id == 0) {
            $data['code'] = parent::CODE_ERROR;
            $data['message'] = "ID不能为空";
            return $data;
        }

        $where = [];
        $where['isdel'] = 0;
        $where['id'] = $id;
        $where['dwid'] = $this->dwid;
        $filedata = $this->getdb(self::TABLE_NAME)->where($where)->find();
        $data['data'] = $filedata;

        if (!$filedata) {
            $data['code'] = self::CODE_ERROR;
            $data['message'] = "未找到数据";
        }
        return $data;
    }


    /**
     * 获取上传的附件列表
     * @param  string  $key      [description]
     * @param  string  $type     [description]
     * @param  integer $page     [description]
     * @param  integer $pagesize [description]
     * @return [type]            [description]
     */
    public function getList($key = '', $type = '', $page = 1, $pagesize = 50)
    {
        $data = $this->_rt();

        $where = [];
        if (!empty($key)) {
            $where['oldname'] = ['like', '%' . $key . '%'];
        }
        $where['isvoid&isdel'] = 0;
        if (!empty($type)) {
            $where['filemime'] = ['like', '%' . $type . '%'];
        }
        $field = "*";
        $count = $this->getdb(self::TABLE_NAME)->where($where)->count();
        $row = $this->getdb(self::TABLE_NAME)->field($field)->where($where)->page($page, $pagesize)->select();
        $rtdata = [];
        $rtdata['limit'] = $count;
        $rtdata['data'] = $row;
        $data['data'] = $rtdata;
        return $data;
    }

    /**
     * 删除文件
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function del($id = 0)
    {
        $data = $this->_rt();

        $where = [];
        $where['id'] = $id;
        $where['dwid'] = $this->dwid;
        $newdata = [];
        $newdata['isdel'] = 1;
        $newdata['deltime'] = getNowTime();
        $this->getdb(self::TABLE_NAME)->where($where)->update($newdata);
        $data['message'] = "删除成功";
        return $data;
    }

    public function getNewsFile($id=''){
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['caseid'] = $id;
        $where['filetype'] = self::FILETYPE_FILE;
        $where['uploadtype'] = self::UPLOADTYPE_NEWS;

        $info = $this->getdb(self::TABLE_ATTACHMENT)->where($where)->find();
        $fileid = $info['id'] ?? 0;
        $this->getFile($fileid);
    }

    /**
     * 下载文件
     *
     * @param string $id
     * @param boolean $small
     * @return void
     */
    public function getFile($id = '', $image = false, $small = true)
    {
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['id'] = $id;
        // $where['dwid'] = $this->dwid;
        // dump($where);

        $data = $this->getdb(self::TABLE_ATTACHMENT)->where($where)->find();
        // halt($data);


        if (!$data && !$image) {


            return false;
        }
        header_remove();
        $filemime = $data['filemime'] ?? 'file';

        $filename = $data['oldname'] ?? 'unname';
        $filepath = $data['filepath'] ?? '';
        $ext = $data['ext'] ?? '';
        $filetype = strtolower($data['filetype'] ?? '');

        $dir = config("upload.dir");
        if (!empty($filepath)) {
            $filepath = $dir . '/' . $filepath;
        }

        // 如果文件不存在，则显示图片
        // dump($filepath);
        if (!file_exists($filepath)) {
            $filepath = self::DEFAULT_NOIMAGE;
            $filemime = "image/jpeg";
            $filename = "noimage.jpg";
            // halt($filepath);

        }

        // 增加下载记录
        $userinfo = $this->getUserinfoFrom_Cookie();

        if(!$userinfo && !$image){
            // 不允许下载！
            header_remove();
            echo "请先登录再下载！";
            exit();
        }





        // dump($filepath);
        // halt(file_exists($filepath));
        if (file_exists($filepath)) {

            header('Content-type: ' . $filemime);
            Header('Accept-Ranges:bytes');

            if (!$image && !in_array($ext, self::allowViewExt)) {
                // halt("直接下载");

                header('Content-Disposition: attachment; filename="' . $filename . '"');
            } else {
                // halt("直接打开");
                header('Content-Disposition: inline; filename="' . $filename . '"');
            }




            if ($image) {
                // 判断是否给出缩略图
                if ($small) {
                    // 生成缩略图
                    // 先取出文本前面部分
                    $filepath = $this->getThumbPath($filepath);
                }
            } else {
                $this->addDownCount($id);

                
                $newsid = $data['caseid'] ?? 0;

                

                $this->addDownLog($newsid,$data,$userinfo);

            }

            // 判断是不是pdf文件，如果是pdf文件，则添加水印

            $addwater = $this->checkPdfAddWatch($data);
            if ($addwater) {
                $temppdf = $this->PdfWater($filepath);
                readfile($temppdf);
                unlink($temppdf);

                exit();
            } else {
                readfile($filepath);
                exit();
            }
        } else {
            return false;
        }

        return $data;
    }

    protected function getUserinfoFrom_Cookie(){
        $cookie = input("cookie.");
        $token = $cookie['WebToken'] ?? '';
        if(empty($token)){
            return false;
        }

        $data = $this->tokenModel->getTokenAllData($token);
        $userinfo = $data['userinfo'] ?? [];
        if(count($userinfo)==0){
            return false;
        }

        return $userinfo;

        // halt($userinfo);



    }

    /**
     * 给pdf添加水印
     *
     * @param [string] $filepath
     * @return string
     */
    protected function PdfWater($filepath = '')
    {
        $ip = get_client_ip();
        $newpath = PDF_add_water($filepath, $ip);

        return $newpath;
    }

    /**
     * 检查是否需要给文件添加水印
     *
     * @param array $info
     * @return void
     */
    protected function checkPdfAddWatch($info = [])
    {

        $ext = $info['ext'] ?? '';
        if ($ext != 'pdf') {
            return false;
        }
        // 判断是否所属的分类，是否需要添加PDF水印。判断是否是文件，且类型为news的
        $caseid = $info['caseid'] ?? 0;
        $filetype = $info['filetype'] ?? '';
        $uploadtype = $info['uploadtype'] ?? '';

        if ($uploadtype != 'news' || $filetype != 'file') {
            return false;
        }

        // 获取新闻的信息
        $field = ["id", "dwid", "typeid", "catid", "newstitle"];

        $where = [];
        $where['id'] = $caseid;
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $where['typeid']  = 1; // 只查普通信息

        $news = $this->getdb("news")->field($field)->where($where)->find();
        if (!$news) {
            return false;
        }

        $catid = $news['catid'] ?? 0;
        if (empty($catid)) {
            return false;
        }

        $where = [];
        $where['id'] = $catid;
        $where['dwid'] = $this->dwid;
        $catinfo = $this->getdb("news_catalog")->where($where)->find();
        if (!$catinfo) {
            return false;
        }

        $addwater = $catinfo['addpdfwater'];

        return $addwater == 1;
    }

    /**
     * 增加阅读量
     *
     * @param [type] $id
     * @return void
     */
    protected function addDownCount($id)
    {
        $where = [];
        $where['isvoid&isdel'] = 0;
        // $where['dwid'] = $this->dwid;
        $where['id'] = $id;

        $this->getdb(self::TABLE_ATTACHMENT)->where($where)->setInc("downnum");
    }

    /**
     * 删除附件
     *
     * @param [type] $id
     * @return void
     */
    public function delFile($id)
    {
        $rt = [];
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['id'] = $id;
        $where['isvoid&isdel'] = 0;

        $newdata = [];
        $newdata['isdel'] = 1;
        $newdata['deltime'] = getNowTime();
        $newdata['deluser'] = $this->userid;
        $this->getdb(self::TABLE_ATTACHMENT)->where($where)->update($newdata);

        $rt['data'] = 1;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        return $rt;
    }

    /**
     * 记录用户下载附件的日志信息
     *
     * @return array
     */
    protected function addDownLog($newsid=0,$fileinfo=[],$userinfo=[]){
        $rt = $this->_rt();

        

        $newdata = [];
        $newdata['createtime'] = getNowTime();
        $newdata['updatetime'] = getNowTime();
        $newdata['dwid'] = $this->dwid;

        $newdata['newsid'] = $newsid;
        $newdata['newstitle'] = $this->getNewsTitle($newsid);

        $newdata['userid'] = $userinfo['userid'] ?? 0;
        $newdata['username'] = $userinfo['username'] ?? '';
        $newdata['deptname'] = $userinfo['deptname'] ?? '';

        $newdata['fileid'] = $fileinfo['id'] ?? 0;
        $newdata['filename'] = $fileinfo['oldname'] ?? '';

        $newdata['filetype'] = strtolower($fileinfo['ext']??'');

        $newdata['ipaddress'] = get_client_ip();
        $newdata['request'] = "";
        $newdata['downloadtime'] = getNowTime();

        $id = $this->getdb(self::TABLE_DOWNLOG)->insertGetId($newdata);

        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $id;

        return $rt;

    }

    /**
     * 获取新闻的标题
     *
     * @param integer $id
     * @return void
     */
    protected function getNewsTitle($id=0){
        $where = [];
        $where['id'] = $id;
        $field = "newstitle";

        $data = $this->getdb("news")->where($where)->field($field)->cache(self::CACHE_TIME)->find();
        $title = $data['newstitle'] ?? '';
        return $title;
    }

}
