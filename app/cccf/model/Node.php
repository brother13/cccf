<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
/**
 * 节点相关管理
 */
class Node extends Common
{
    
    protected $table = "nodelist";   
    const FIELD = ["id","dwid","datatype","nodename","nodetype","ajlb","note","nodetime","isvoid"];
    CONST ACTION = "node";
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
    public function index($action='',$data=[],$isnodetype=false){
        $rt = $this->_rt();


        $page = $data['page'] ?? 1;
        $pagesize = $data['pagesize'] ?? 100;
        $key = $data['keyword'] ?? "";
        $isvoid = $data['isvoid'] ?? "";
        $id = $data['id'] ?? 0;
        $dwid = $this->dwid;
        $myaction = $isnodetype ? 'nodetype' : 'node';
        $type = $isnodetype ? 0 : 1;
        switch($action){
            case "list": // 列表
                $nodetype = $data['nodetype'] ?? '';
                $ajlb = $data['ajlb'] ?? '';
                $all = $data['all'] ?? 0;
                $rt = $this->getList($key,$type,$nodetype,$ajlb,$isvoid,$all,$page,$pagesize);
            break;
            case "save": // 编辑节点
                if($id==0){
                    $rt['message'] = "ID不能为空！";
                    return $rt;
                }
                
                $rt = $this->save($id,$type,$data);
            break;
            case "add": // 新增节点
                $rt = $this->save(0,$type,$data);
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
            case "nodelist":
                $rt = $this->getNodeList();
            break;
            default:
                $rt['message']="操作【/".$myaction."/{$action}】并不存在！";
        }

        return $rt;
    }

    
    /**
     * 获取节点信息
     *
     * @param string $key 搜索关键字
     * @param integer $type 类型，0-节点分类，1-节点
     * @param string $nodetype 节点分类名称
     * @param string $ajlb 案件类型，可为文本型，通过,分割，也可为数组
     * @param integer $isvoid 是否停用
     * @param integer $page
     * @param integer $pagesize
     * @return void
     */
    public function getList($key="",$type=1,$nodetype='',$ajlb='',$isvoid=0,$all=0,$page=1,$pagesize=50){
       
        $dwid = $this->dwid;
        
        $field = self::FIELD;
        $order = "rank";
        $where = [];
        $all+=0;
        if($all==0){
            $where['dwid']=$dwid; //可查看所有单位的
        }else{
            $where['dwid'] = ['in',[0,$dwid]];
        }
       
        $where['isdel']=0;

        $where['datatype'] = $type;
        if($isvoid!=''){
            $where['isvoid']=$isvoid;
        }
        if(!empty($key)){
            $where['nodename|nodetype'] = ['like',"%{$key}%"];
        }
       
        if(is_string($nodetype)){
            $nodetype = explode(",",$nodetype);
        }
        if(is_array($nodetype) && count($nodetype)>0){
            $where['nodetype']=['in',$nodetype];
        }
        // if(!empty($nodetype)){
        //     $where['nodetype'] = $nodetype;
        // }
        if(is_string($ajlb) && !empty($ajlb)){
            $ajlb =  explode(",",$ajlb);
        }
        if(is_array($ajlb) && count($ajlb)>0){
            $where['ajlb'] = ['in',$ajlb];
        }

        
        $order = "dwid desc,rank";
        $num = $this->getdb($this->table)->where($where)->count();
        $data = $this->getdb($this->table)->field($field)->where($where)->page($page,$pagesize)->order($order)->select();
        
        $data = $this->add_ajlbmc($data);
        $rt = $this->_rt();
        $rt['code']=parent::CODE_SUCCESS;
        $d = [];
        $d['total']=$num;
        $d['items']=$data;
        $rt['data']=$d;

        return $rt;
    }

    /**
     * 添加案件类别名称
     *
     * @param [type] $list
     * @return void
     */
    protected function add_ajlbmc($list){

        $where = [];
        $where['classtype']='ajlb';
        $where['isvoid&isdel']=0;
        $field=['classcode','classname'];

        $ajlblist = $this->getdb("class")->where($where)->field($field)->select();
        $ajlb = [];
        foreach($ajlblist as $aj){
            $ajlb[$aj['classcode']] = $aj['classname'];
        }

        foreach($list as &$data){
            $lb = $data['ajlb'];
            $data['ajlbmc'] = $ajlb[$lb] ?? "";
        }
        return $list;
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
     * 删除节点
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
     * 保存节点信息，id=0为新增，有值为修改
     * @param  [type] $id [节点ID]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function save($id,$datatype=1,$data){


        

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
            $where['datatype'] = $datatype;
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
       
        
        
        
        $where = [];
        $where['dwid']=$this->dwid;
        $where['id']=$id;
        $where['datatype'] = $datatype;
        if($id!=0){
            //update
            $d['updatetime']=getNowTime();
            
            $this->getdb($this->table)->where($where)->update($d);

        }else{
            $d['createtime']=getNowTime();
            $d['datatype']=$datatype;
            $id = $this->getdb($this->table)->insertGetId($d);

        }
        $rt['data']=$id;
        $rt['message']="保存成功";

        return $rt;
    }

    
    /**
     * 获取当前单位的可用节点
     *
     * @param integer $dwid
     * @return void
     */
    protected function getNodeList($dwid=0){
        if($dwid == 0){
            $dwid = $this->dwid;
        }

        $where = [];
        $where['isvoid&isdel'] = 0;
        $dwidlist = [];
        $dwidlist[] = 0;
        $dwidlist[] = $dwid;
        $where['dwid'] = ['in',$dwidlist];
        $order = "rank";
        $field = "id,datatype,nodename,nodetype,ajlb,note";
        
        $data = [];
        $nodetypelist = [];
        $nodelist = [];
        $ajlblist = [];
        

        // 节点信息
        $nodedata = $this->getdb($this->table)->where($where)->field($field)->order($order)->select();
        
        foreach($nodedata as $node){
            if($node['datatype']==0){
                // 分类
                $nodetypelist[] = $node;
            }else{
                // 节点
                $nodelist[] = $node;
            }
        }
        // 获取案件类型

        $where = [];
        $where['classtype'] = "ajlb";
        $where['isvoid&isdel'] = 0;
        $field = "classcode,classname";
        $ajlblist = $this->getdb("class")->where($where)->field($field)->select();

        $data['nodetype'] = $nodetypelist;
        $data['node'] =$nodelist;
        $data['ajlb']=$ajlblist;


        $newdata = [];
        foreach($ajlblist as $ajlb){
            $d = [];
            $d['code']=$ajlb['classcode'];
            $d['name'] = $ajlb['classname'];
            $d['nodetype'] = [];
            foreach($nodetypelist as $nodetype){
                if($nodetype['ajlb']==$d['code']){
                    $nodedata = [];
                    $nodedata['name'] = $nodetype['nodename'];
                    $nodedata['note'] = $nodetype['note'] ?? "";
                    $nodedata['node'] = [];
                    foreach($nodelist as $node){
                        if($node['nodetype']==$nodedata['name']){
                            $tnode = [];
                            $tnode['name'] = $node['nodename'];
                            $tnode['note'] = $node['note'] ?? "";
                            $nodedata['node'][] = $tnode;
                        }
                        
                    }
                    $d['nodetype'][] = $nodedata;
                }
            }
            $newdata[] = $d;
        }

        $data['nodelist'] = $newdata;
        $rt = $this->_rt();
        $rt['message'] = "OK";
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $data;
        return $rt;
    }
    
}
