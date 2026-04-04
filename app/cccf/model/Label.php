<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
/**
 * 标签相关管理
 */
class Label extends Common
{
    
    protected $table = "label";   
    const FIELD = ["id","dwid","labeltext","note","isvoid"];
    CONST ACTION = "label";
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
        $id = $data['id'] ?? 0;
        $dwid = $this->dwid;

        switch($action){
            case "list": // 列表
                $rt = $this->getList($key,$isvoid,$page,$pagesize);
            break;
            case "save": // 编辑节点
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                
                $rt = $this->save($id,$data);
            break;
            case "add": // 新增节点
                $rt = $this->save(0,$data);
            break;
            case "del": // 删除节点
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                $rt = $this->del($id);
            break;
            case "info": // 查看信息
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
     * 获取标签列表信息
     *
     * @param string $key 搜索关键字
     * @param integer $isvoid 是否停用
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    public function getList($key="",$isvoid=0,$page=1,$pagesize=50){
       
        $dwid = $this->dwid;
        
        $field = self::FIELD;
        $order = "rank";
        $where = [];
        $where['dwid']=$this->dwid;
       
        $where['isdel']=0;

        if($isvoid!=''){
            $where['isvoid']=$isvoid;
        }
        if(!empty($key)){
            $where['labeltext'] = ['like',"%{$key}%"];
        }
       
        

        
        $num = $this->getdb($this->table)->where($where)->count();
        $data = $this->getdb($this->table)->field($field)->where($where)->page($page,$pagesize)->order($order)->select();
        
        $rt = $this->_rt();
        $rt['code']=self::CODE_SUCCESS;
        $d = [];
        $d['total']=$num;
        $d['items']=$data;
        $rt['data']=$d;

        return $rt;
    }

/**
 * 获取单个信息 - 标签
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

        $field=self::FIELD;
        $where = array();
        $where ['isdel'] = 0;
        $where ['id'] = $id;
        $groupdata = $this->getdb($this->table)->where($where)->field($field)->find();
        
        if(!$groupdata){
            $data['code']= parent::CODE_ERROR;
            $data['message'] = "未找到记录";
            $data['data'] = [];
            return $data;
        }
  
        
        $data['code']=parent::CODE_SUCCESS;
        $data['message']="OK";
        $data['data'] = $groupdata;
        return $data;

    }
    


    /**
     * 删除标签
     * @param  integer $id [节点ID]
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
        $where['id']=$id;
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
     * 保存标签信息，id=0为新增，有值为修改
     * @param  [type] $id [ID]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function save($id,$data){


        

        $field=self::FIELD;
        
        $d = [];
        $d['dwid'] = $this->dwid;
        $rt = $this->_rt();
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data']="";
        $rt['message']="操作成功";

        //判断数据是否存在

        if($id>0){
            $where = [];
            $where['id'] = $id;
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
       
        
        unset($d['id']);
        
        $where = [];
        $where['dwid']=$this->dwid;
        $where['id']=$id;
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
