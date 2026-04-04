<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
/**
 * 用户组相关管理
 */
class Group extends Common
{
    
    protected $table = "group";   
    CONST RULE_ALL = "ALL"; //所有权限
    CONST ACTION = "group";
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


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";
        
        $dwid = $this->dwid;
        switch($action){
            case "list": // 列表
                $rt = $this->getList($key,$isvoid,$page,$pagesize);
            break;
            case "save": // 编辑权限组
                $id = $data['groupid'] ?? 0;
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                $rt = $this->save($id,$data);
            break;
            case "add": // 新增权限组
                $rt = $this->save(0,$data);
            break;
            case "del": // 删除权限组
                $id = $data['groupid'] ?? 0;
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                $rt = $this->del($id);
            break;
            case "info": // 查看信息
                $id = $data['groupid'] ?? 0;
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                $rt = $this->getInfo($id);
            break;
            default:
                $rt['message']="操作【/".self::ACTION."/{$action}】并不存在！";
        }

        return $rt;
    }

	/**
     * 获取用户组列表
     *
     * @param   [type]  $key       [$key 查询关键字]
     * @param   [type]  $isvoid    [$isvoid 是否停用]
     * @param   [type]  $page      [$page 当前页数，默认1]
     * @param   [type]  $pagesize  [$pagesize 分页大小，默认50]
     *
     * @return  [type]             [return ]
     */
    public function getList($key="",$isvoid=0,$page=1,$pagesize=50){
       
        $dwid = $this->dwid;
        
        $field = ["groupid","groupcode","groupname","grouprule","note","createtime","isvoid"];
        $order = "rank";
        $where = [];
        $where['dwid']=$dwid;
        $where['isdel']=0;


        if($isvoid!=''){
            $where['isvoid']=$isvoid;
        }
        if(!empty($key)){
            $where['groupcode|groupname'] = ['like',"%{$key}%"];
        }
       

        $num = $this->getdb($this->table)->where($where)->count();
        $data = $this->getdb($this->table)->field($field)->where($where)->page($page,$pagesize)->order($order)->select();
        
    
        $rt = $this->_rt();
        $rt['code']=parent::CODE_SUCCESS;
        $d = [];
        $d['total']=$num;
        $d['items']=$data;
        $rt['data']=$d;

        return $rt;
    }

    

/**
 * 获取单个信息 - 事件内容
 * @param  [type] $id [ID，对应 eventid]
 * @return [type]     [description]
 */
    public function getInfo($id)
    {
       
        $data = $this->_rt();

        if (empty($id)) {
            $data['code']=parent::CODE_ERROR;
            $data['data']=[];
            $data['message']="ID不能为空";
            return $data;
        }

        $field=["groupid","groupcode","groupname","grouprule","note","createtime","isvoid"];
        $where = array();
        $where ['isdel'] = 0;
        $where ['groupid'] = $id;
        $groupdata = $this->getdb($this->table)->where($where)->field($field)->find();
        
        $groupdata['grouprule'] = explode(",",$groupdata['grouprule']);
        if(!$groupdata){
            $data['code']= parent::CODE_ERROR;
            $data['message'] = "未找到记录";
            $data['data'] = [];
            return $data;
        }
        //将grouprule变更为数组
        // if(!$groupdata['grouprule']=='ALL' && !empty($groupdata['grouprule'])){
        //     $groupdata['grouprule'] = explode(",",$groupdata['grouprule']);
        // }
        
        $data['code']=parent::CODE_SUCCESS;
        $data['message']="OK";
        $data['data'] = $groupdata;
        return $data;

    }
    


    /**
     * 删除权限组
     * @param  integer $id [权限组ID]
     * @return [type]          [description]
     */
    public function del($id=0){
        $rt = $this->_rt();
        
        if(empty($id)){
            $rt['code']=parent::CODE_ERROR;
            $rt['data']="";
            $rt['message']="ID不能为空";
            return $rt;
        }
        
        $where = [];
        $where['groupid']=$id;
        $where['dwid']=$this->dwid;

        $data = [];
        $data['isdel']=1;
        $data['deltime']=getNowTime();
        $d = $this->getdb($this->table)->where($where)->update($data);

        
        $rt['code']=parent::CODE_SUCCESS;
        $rt['data']=$d;
        $rt['message']="删除成功";
        return $rt;
    }

    /**
     * 保存权限组信息，id=0为新增，有值为修改
     * @param  [type] $id [权限组ID]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function save($id,$data){


        $check = [];
        $check['table']=$this->table;
        $check['field']=["groupcode","groupname"];
        $check['comment']=['groupcode'=>"权限组代码","groupname"=>"权限组名称"];
        $check['data']=$data;
        $check['id']=$id;
        $ck = $this->checkFieldData($check);
        if($ck['code']!=self::CODE_SUCCESS){
            return $ck;
        }

        $field=["groupid","groupcode","groupname","grouprule","note","createtime","isvoid"];
        
        $d = [];
        $d['dwid'] = $this->dwid;
        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data']="";
        $rt['message']="操作成功";

        //判断数据是否存在

        if($id>0){
            $where = [];
            $where['groupid'] = $id;
            $where['isdel'] = 0;
            $num  = $this->getdb($this->table)->where($where)->count();
            if($num<1){
                $rt['code']=self::CODE_ERROR;
                $rt['message']="未找到数据";
                return $rt;
            }
        }
        
        foreach($field as $f){
            if(isset($data[$f])){
                $d[$f] = $data[$f];
            }
        }
        if(is_array($d['grouprule'])){
            asort($d['grouprule']);
            $d['grouprule'] = array_unique(($d['grouprule']));
            $d['grouprule'] = implode(",",$d['grouprule']);
        }
        
        
        
        
        $where = [];
        $where['dwid']=$this->dwid;
        $where['groupid']=$id;
        
        if($id!=0){
            //update
            $d['updatetime']=getNowTime();
            
            $this->getdb($this->table)->where($where)->update($d);

        }else{
            $d['createtime']=getNowTime();
            $id = $this->getdb($this->table)->insertGetId($d);

        }
        $rt['data']=$id;
        $rt['message']="保存成功";

        return $rt;
    }

    

    
}
