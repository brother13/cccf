<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;


/**
 * 系统信息相关
 *
 * @author netknave
 *
 */
class System extends Common
{
    const ACTION = "sys";
    CONST FIELD = ['id','modulecode','modulename','note','isvoid'];
    const CLIENT_URL="http://localhost:20780/";
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
    public function index($action='',$data=[]){
        $rt = $this->_rt();

        // halt($this->userinfo);


        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        switch($action){
            case "info": //登录
                $rt['data'] = $this->sysinfo();
            break;
            case 'module': //可用模块
                $rt['data'] = $this->getModule();
            break;
            case 'client': //获取客户端信息
                $rt['data'] = $this->getClientInfo();
            break;
            default:
                $rt['code'] = self::CODE_ERROR;
                $rt['message']="操作【/".self::ACTION."/{$action}】并不存在！";
        }

        return $rt;
    }

    /**
     * 获取当前接口信息
     *
     * @return void
     */
    protected function sysinfo(){
        $data = [];
        $data['system']="财产查封管理";
        $data['version'] = "v1.0";
        $data['lastupdate'] = "20250120";
        $data['servertime'] = getNowTime();
        return $data;
    }

    /**
     * 获取当前单位可用的功能模块，以及当前用户可用的功能模块
     *
     * @param [type] $dwid
     * @param [type] $userid
     * @return void
     */
    public function getModule($dwid=0){
        /**
         * 1.先从系统中，取出单位所有可用的模块
         * 2.根据权限，判断当前单位可用模块
         * 3.返回值格式为 ['modulelist','module']
         *      modulelist - 单位所有模块情况：包含单位未启用的。但必须是本单位已采购的功能模块。有启用时间、结束时间、功能模块名称、功能模块备注，单位是否启用
         *      module - 当前单位可用模块，以文本数组方式组成。用于判断数据是否在模块以内
         * 
         * 权限判断逻辑如下：仅判断当前单位可用模块。如果有部分单位不同角色显示不同的功能，可通过用户权限判断，而非通过单位模块判断。
         * 
         * 
         * 
         */

        if($dwid==0){
            $dwid = $this->dwid;
        }
        
        $rt = [];
        $rt['dwid'] = $this->dwid;
        $rt['module']=[];
        $rt['modulelist'] = [];
        $rt['moduleid'] = [];

        //先取出本单位可用模块列表
        $field = self::FIELD;
        $where = [];
        $where['isvoid&isdel'] = 0;
        $where['dwid'] = $dwid;
        $data = $this->getdb("dwmodule")->where($where)->select();

        $mid = [];
        $today = date('Y-m-d');

        foreach($data as $d){
            if($d['startdate']<$today && $d['enddate']>$today){
              $mid[] = $d['mid'];  
            }

        }

        if(count($mid)<1){
            return $rt;
        }
        $where = [];
        $where['id'] = ['in',$mid];
        $where['isvoid&isdel'] = 0;

        $data = $this->getdb("module")->where($where)->field($field)->select();
        $usemodule = [];
        $moduleid = [];
        foreach($data as $d){
            $usemodule[] = $d['modulecode'];
            $moduleid[] = $d['id'];
        }
        $rt['module']=$usemodule;
        $rt['modulelist'] = $data;
        $rt['moduleid'] = $moduleid;
        
        return $rt;



    }

    /**
     * 获取用户本地客户端信息
     *
     * @return void
     */
    protected function getClientInfo(){
        $data = [];
        $config = config("client");
        if(!$config){
            $config = ["url"=>self::CLIENT_URL];
        }
        if(!$config['url']){
            $config['url'] = self::CLIENT_URL;
        }
        // $url = $config['url'] ?? self::CLIENT_URL;
        return $config;
    }
 
}
