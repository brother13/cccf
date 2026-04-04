<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;
use \app\cccf\model\Data;

/**
 * 部门操作相关
 *
 * @author netknave
 *
 */
class Dept extends Common
{
    const ACTION = "dept";
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
            case "list": // 部门列表
                $parentid = $data['parentid'] ?? -1;
                $rt = $this->getDeptList($dwid,$parentid,$key,$isvoid,$page,$pagesize);
            break;
            case 'tree': // 新部门列表
                $rt = $this->getList_Tree();
                break;
            case "save": // 编辑部门
                $deptid = $data['deptid'] ?? 0;
                if($deptid==0){
                    $rt['message'] = "部门不能为空！";
                    return $rt;
                }
                $rt = $this->saveDept($deptid,$data);
            break;
            case "add": // 新增部门
                $rt = $this->saveDept(0,$data);
            break;
            case "del": // 删除部门
                $deptid = $data['deptid'] ?? 0;
                if($deptid==0){
                    $rt['message'] = "部门不能为空！";
                    return $rt;
                }
                $rt = $this->delDept($deptid);
            break;
            case 'info': // 部门信息
                $id = $data['deptid'] ?? 0;
                $rt = $this->getDept($id);
            case 'newcode': //获取新代码
                $rt['data'] = $this->getnewcode();
                $rt['code'] = self::CODE_SUCCESS;
            break;
            default:
                $rt['message']="操作【/".$this->ACTION."/{$action}】并不存在！";
        }

        return $rt;
    }

	/**
	 * 获取部门列表
	 */
    public function getDeptList($dwid=0,$parentid=0,$key="",$isvoid=0,$page=1,$pagesize=50,$order=[]){
       if($dwid==0){
            $dwid = $this->dwid;
        }


        $field = ["deptid","deptcode","deptname","parentid","isvoid","telphone"];
        $order = "rank,deptcode";
        $where = [];
        $where['dwid']=$dwid;
        $where['isdel']=0;
        
        if($isvoid!=''){
            $where['isvoid']=$isvoid;
        }
        if(!empty($key)){
            $where['deptcode|deptname']=['like',"%{$key}%"];
        }

        if($parentid!=-1){
            $where['parentid'] = $parentid;

        }
        $db = $this->getdb("dept");
        $num = $db->where($where)->count();
        $data = $db->field($field)->where($where)->order($order)->page($page,$pagesize)->select();
        // $sql = $db->getLastSql();
        // dump($sql);
        //增加用户数量
        $data = $this->_add_user_count($data);

        $rt = [];
        $rt['code']=parent::CODE_SUCCESS;
        $d = [];
        $d['total']=$num;
        $d['items']=$data;
        $rt['data']=$d;

        return $rt;
    }

   
/**
 * 获取部门信息
 * @param  [type] $id [用户ID，对应 userid]
 * @return [type]     [description]
 */
    public function getDept($deptid)
    {
       
        $data = [];

        if (empty($deptid)) {
            $data['code']=parent::CODE_ERROR;
            $data['data']=[];
            $data['message']="部门ID不能为空";
            return $data;
        }

        $field="*";
        $where = array();
        $where ['isdel'] = 0;
        $where ['deptid'] = $deptid;
        $dept = $this->getdb('dept')->where($where)->field($field)->find();
        //获取roles
        return $dept;

    }


    /**
     * 删除用户
     * @param  integer $userid [description]
     * @return [type]          [description]
     */
    public function delDept($deptid=0){
        $rt = $this->_rt();
        $dwid = $this->dwid;
        if(empty($deptid)){
            $rt['code']=parent::CODE_ERROR;
            $rt['data']="";
            $rt['message']="部门不能为空";
            return $rt;
        }
        
        //判断部门下是否还有人
        $deptinfo = $this->getDept($deptid);

        if(!$deptinfo){
            $rt['code']=parent::CODE_ERROR;
            $rt['data']="";
            $rt['message']="部门ID[$deptid]不存在";
            return $rt;
        }
        if($deptinfo['isdel']==1){
            $rt['code']=parent::CODE_ERROR;
            $rt['data']="";
            $rt['message']="部门不存在或已删除";
            return $rt;
        }
        //判断是否有其它用户
        $deptcode = $deptinfo['deptid'];
        $where = [];
        $where['deptcode']=$deptcode;
        $where['isdel']=0;
        $where['dwid']=$dwid;
        $count = $this->getdb("user")->where($where)->count();
        if($count>0){
            $rt['code']=parent::CODE_ERROR;
            $rt['data']="";
            $rt['message']="该部门下面仍有{$count}名用户，用户不为空时不能删除部门";
            return $rt;
        }


        $where = [];
        $where['deptid']=$deptid;
        $where['dwid']=$dwid;

        $data = [];
        $data['isdel']=1;
        $data['deltime']=getNowTime();
        $d = $this->getdb("dept")->where($where)->update($data);

        
        $rt['code']=parent::CODE_SUCCESS;
        $rt['data']=$d;
        $rt['message']="删除成功";
        // $rt['input'] = input("param.");
        return $rt;
    }

    /**
     * 保存用户，userid=0为新增，有值为修改
     * @param  [type] $userid [description]
     * @param  [type] $data   [description]
     * @return [type]         [description]
     */
    public function saveDept($deptid,$data){

        $check = [];
        $check['table']="dept";
        $check['field']=["deptcode"];
        $check['comment']=['deptcode'=>"部门代码","deptname"=>"部门名称"];
        $check['data']=$data;
        $check['id']=$deptid;
        $ck = $this->checkFieldData($check);
        if($ck['code']!=self::CODE_SUCCESS){
            return $ck;
        }


        $field=["deptcode","deptname","telphone","isvoid","parentid","rank"];
        $dwid = $this->dwid;
        $d = [];
        $d['dwid'] = $dwid;
        foreach($field as $f){
            if(isset($data[$f])){
                $d[$f] = $data[$f];
            }
        }
        $rt = [];




        $where = [];
        $where['dwid']=$dwid;
        $where['deptid']=$deptid;
        if($deptid!=0){
            //update
            $d['updatetime']=getNowTime();
            
            $this->getdb("dept")->where($where)->update($d);

        }else{
            $d['createtime']=getNowTime();
            $deptid = $this->getdb("dept")->insert($d);

        }
        $rt = [];
        $rt['code'] = parent::CODE_SUCCESS;
        $rt['data']=$deptid;
        $rt['message']="操作成功";

        return $rt;
    }

    /**
     * 在部门列表中，增加 用户的数据统计
     *
     * @param [type] $data
     * @return void
     */
    protected function _add_user_count($data){
        $deptlist = $data;
        $deptcode = [];
        $deptcode[] = "-1";
        foreach($data as $d){
            $deptcode[] = $d['deptid'];
        }
        $where = [];
        $where['dwid'] = $this->dwid;
        $where['isvoid&isdel'] = 0;
        $where['deptcode']=['in',$deptcode];
        $field = ["deptcode","count(*)"=>"num"];
        $group="deptcode";
        $usercount = $this->getdb("user")->where($where)->group($group)->field($field)->select();
        $deptuser = [];
        foreach($usercount as $user){
            $code = 'id_'.$user['deptcode']."";
            $deptuser[$code] = $user['num']; 
        }

        foreach($deptlist as &$dept){
            $code = 'id_'.$dept['deptid'];
            if(array_key_exists($code,$deptuser)){
                $dept['usernum'] = $deptuser[$code];
            }else{
                $dept['usernum'] = 0;
            }
        }

        return $deptlist;

    }

    /**
     * 获取新部门代码
     *
     * @return void
     */
    protected function getnewcode(){
        $newcode = $this->genNewCode("dept","deptcode",$this->dwid);
        return $newcode;
    }


    /**
     * 获取当前栏目树
     *
     * @param string $type
     * @return void
     */
    public function getList_Tree(){
        
        $rt = $this->_rt();
        // 获取栏目的树结构
        // 先获取所有的数据
        $deptList = $this->getDeptList($this->dwid,-1,"",0,1,9999);

        $deptList = $deptList['data']['items'] ?? [];
        $tree = $this->listToTree($deptList);
        $tree2 = [];
        $tree3 = [];
        foreach($tree as $key =>$item){
            $tree2[] = $item;
        }
        
        
        $this->treeToSelectList($tree2,$tree3);
        

        $treedata = [];
        $treedata['tree'] = $tree2;
        $treedata['list'] = $tree3;
        
        $rt['code'] = self::CODE_SUCCESS;
        $rt['message'] = "OK";
        $rt['data'] = $treedata;
        return $rt;
    }
   
    protected function listToTree($list, $pk = 'deptid', $pid = 'parentid', $child = 'children', $root = 0) {
        $tree = array();
        if (is_array($list)) {
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[ $data[ $pk ] ] = &$list[ $key ];
            }
    
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[ $pid ];
    
                if ($root == $parentId) {
                    $tree[ $data[ $pk ] ] = &$list[ $key ];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[ $parentId ];
                        $parent[ $child ][ $data[ $pk ] ] = &$list[ $key ];
                        // $parent['hasChildren']=true;
                        
                        $parent[ $child ] = array_values($parent[ $child ]);
                    }
                }
            }
        }
    
        return $tree;
    }

    protected function treeToSelectList($tree=[],&$newdata=[],$level=0,$fullname=''){
        foreach($tree as $item){
            $item2 = $item;
            $item2['parentid'] = intval($item2['parentid']);
            $newname = $fullname;
            if($newname){
                $newname .=" > ".$item2['deptname'];
            }else{
                $newname = $item2['deptname'];
            }
            $item2['fullname'] = $newname;
            $item2['id'] = $item2['deptid'] ?? 0;
            
            $item2['deptoldname'] = $item2['deptname'];
            $item2['deptname'] = $this->genSpace($item2['deptname'],$level);
            unset($item2['children']);
            // 添加一栏
            
            
            $child = $item['children'] ?? [];
            
            if(count($child)>0){
                $item2['hasChildren'] = true;
                
                $newdata[] = $item2;
                $this->treeToSelectList($child,$newdata,$level+1,$newname);
            }else{
                $newdata[] = $item2;
            }
            
        
        }
    }

    protected function genSpace($str='',$level=0){
        $str2 = $str;
        $str_tree="┝";
        $str_space="　";
        if($level>0){
            $str2 = str_repeat($str_space,$level).$str_tree.$str_space.$str2;
        }

        return $str2;
    }

}
