<?php

namespace app\cccf\model;

use \think\Db;
use \think\Debug;


use \app\cccf\model\System;
/**
 * 权限处理相关
 *
 * @author netknave
 *
 */
class Auth extends Common
{
    CONST RULE_ALL = "ALL"; //所有权限
    CONST TABLE_NAME="rule";
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 判断用户权限是否存在
     *
     * @param string $rule 权限名称
     * @param integer $type 权限名称，或是调用url。 0- 默认，判断用户权限，1- 判断调用url
     * @param integer $userid
     * @return void
     */
    public function checkAuthOrUrl($rule='',$type = 0 ,$userid=0){


        if(empty($rule)){
            return false;
        }

        $userrules = [];
        if(is_array($rule)){
            $userrules = $rule;
        }else if(is_string($rule)){
            $userrules = explode(",",$rule);
        }
        $hasAuth = false;

        $auth = [];
        
    	if($userid==0){
            $userid = $this->userid;
            $auth = [];
            $auth['rulecode'] = $this->userinfo['roles'] ?? [];
            $auth['ruleurl'] = $this->userinfo['roles'] ?? [];
            
    	}else{
            $data = $this->getAuth($userid);
            $data = $data['data'];
            

        }
        
        $key = "rulecode";
        if($type==self::AUTH_TYPE_URL){
            $key = "ruleurl";
        }
        if(!isset($auth[$key])){
            return false;
        }
        $rules = $auth[$key];

        // 开始循环判断用户是否有权限

        foreach($userrules as $r){
            if(in_array($r,$rules)){
                $hasAuth = true;
            break;
            }
        }
        // $hasAuth = in_array($rule,$rules);

        return $hasAuth; 

    }

    /**
     * 判断当前调用的URL是否有权限
     *
     * @param string $url
     * @param integer $userid
     * @return void
     */
    public function checkUrl($url='',$userid=0){
        
        return $this->checkAuthOrUrl($url,self::AUTH_TYPE_URL,$userid);
    }
    /**
     * 判断是否有权限
     *
     * @param string $auth
     * @param integer $userid
     * @return void
     */
    public function checkAuth($auth='',$userid=0){
        // dump($this->userinfo);
        return $this->checkAuthOrUrl($auth,self::AUTH_TYPE_AUTH,$userid);
    }

    /**
     * 获取用户的权限信息
     *
     * @param   [type]  $userid  [$userid description]
     *
     * @return  [type]           [return description]
     */
    public function getAuth($userid=0){
        $rt = $this->_rt();
        if(empty($userid) || $userid==0){
            $userid = $this->userid;
        }
        
        $where = [];
        // $where['dwid'] = $this->dwid;
        $where['userid']=$userid;
        $where['isvoid&isdel']=0;
        // 先根据用户ID获取用户组，然后根据用户组获取权限列表
        $field = ['dwid','userid','usercode','username','usergroup','deptcode'];
        $user = $this->getdb("user")->field($field)->where($where)->find();
        
        if(!$user){
            $rt['code'] = self::CODE_ERROR;
            $rt['message'] = "用户不存在";
            return $rt;
        }
        $dwid = $user['dwid'];
        $groupid = $user['usergroup'];
        $group = explode(",",$groupid);
        $group[] = "-1"; //增加一个-1，以防出错
        //获取用户组的权限信息
        $where = [];
        $where['isdel&isvoid']=0;
        $where['groupid'] = ['in',$group];

        $groups = $this->getdb("group")->where($where)->select();
        //获取用户权限
        $rules = [];
        foreach($groups as $gr){
            $arr = explode(",",$gr['grouprule']);
            foreach($arr as $g){
                
                $rules[] = $g;
            }
        }

        foreach($rules as $rule){
            if($rule == self::RULE_ALL){
                $rules = [];
                $rules[] = $rule;
            break;
            }
        }
        $rules = array_unique($rules); //去除重复
        $getall = false;
        foreach($rules as $rule){
            if($rule == self::RULE_ALL){
                $getall = true;
            break;
            }
        }
        //开始获取权限字段信息
        $rulecode = [];
        $where = [];
        $where['isvoid&isdel']=0;
        if(!$getall){
            $where['ruleid'] = ['in',$rules];
        }
        
        $data = [];
        $order = "rank";
        $field = "ruleid,rulename,ruletitle,note,ruleurl,moduleid";
        $rulecode = [];
        $data = $this->getdb("rule")->where($where)->field($field)->order($order)->select();



        // 在此处判断权限，并去掉不存在限单位模块中的模块
        $data = $this->removeAuthByModule($data,$dwid);


        $ruleurls = [];
        $rules = [];
        foreach($data as $d){
            $rulecode[] = $d['rulename'];
            $rules[] = $d['ruleid'];
            if(!empty($d['ruleurl'])){
                $url = explode(",",$d['ruleurl']);
                foreach($url as $u){
                    $ruleurls[] = $u;
                }
            }
            
        }

        //去重复
        array_unique($ruleurls);
        $newdata = [];
        $newdata['user'] = $user;
        $newdata['ruleid'] = $rules;
        $newdata['rulecode'] = $rulecode;
        $newdata['rule'] = $data;
        $newdata['ruleurl']=$ruleurls;
        $rt['code'] = self::CODE_SUCCESS;
        $rt['data'] = $newdata;
        $rt['message']="OK";
        return $rt;

    }
    /**
     * 获取系统权限列表
     *
     * @param   [type]  $key  [$key 查找关键字]
     *
     * @return  [type]        [return description]
     */
    public function authList($key=''){
        $rt = $this->_rt();

        $where = [];
        $where['isvoid&isdel']=0;
        if(!empty($key)){
            $where['rulename|ruletitle']=['like','%'.$key.'%'];
        }
        $order = "rank,rulename";
        $field = "ruleid,rulename,ruletitle,note";
        $data = $this->getdb(self::TABLE_NAME)->where($where)->field($field)->order($order)->select();

        $ruleid=[];
        $rulename=[];
        $ruledata = [];
        foreach($data as $d){
            $ruleid[] = $d['ruleid'];
            $rulename[] = $d['rulename'];
        }
        $ruledata['data'] = $data;
        $ruledata['id']=$ruleid;
        $ruledata['rule']=$rulename;
        $ruledata['num']=count($data);

        $rt['data'] = $ruledata;

        return $rt;

    }

    /**
     * 登录时用，强行设置当前的token值（因为登录时并没有jzlz-token值）
     *
     * @param string $token
     * @return void
     */
    public function setToken($token=''){
        if(empty($token)){
            return ;
        }
        $this->token = $token;

      $this->dwid = $this->tokenModel->getData($this->token,"dwid",0);
      $this->userid = $this->tokenModel->getData($this->token,"userid",0);
      $this->userinfo = $this->tokenModel->getData($this->token,"userinfo",0);
    }


    /**
     * 判断当前法院所拥有的模块，并移除和此模块有关联的权限
     *
     * @param array $rules
     * @return array
     */
    public function removeAuthByModule($rules=[],$dwid=0){

        //将当前模块可用的移出
        foreach($rules as &$rule){
            $t = $rule['moduleid'];
            if(empty($t)){
                $rule['module'] = [];
            }else{
                $rule['module']=explode(",",$t);
            }
            // $rule['module'] = explode(",",$rule['moduleid']);
            
        }
        // dump($rules);
        // dump($rules);
        // 获取可用的功能模块
        $sysModel = new System();
        $module = $sysModel->getModule($dwid);

        $moduleid = $module['moduleid'];
        $newrules = [];
        // dump($rules);
        foreach($rules as $t_rule){
            $mid = $t_rule['module'] ?? [];
            if(count($mid)<1){
                
                $newrules[] = $t_rule;
                continue;
            }
            // 如果有值，则判断是否是允许的功能模块
            if(count($mid)>0){
                foreach($mid as $id){
                    if(in_array($id,$moduleid)){
                        $newrules[] = $t_rule;
                    break;
                    }
                }
            }
            
        }

        // dump($newrules);
        
        return $newrules;

    }
   

    

    
}
