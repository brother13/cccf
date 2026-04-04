<?php

namespace app\cccf\model;

use \think\Cache;
use \think\Log;

/**
 * Token管理相关，采用Cache来保存
 *
 * @author netknave
 *
 */
class Token
{

    protected $token_expire = 0;
    protected $token_name = "";
    protected $token_prefix = "";
    const LOGIN_EXPIRE_NAME = "autoLogoffTime"; // 自动注销的时间

    public function __construct()
    {

        $config = config("token");
        $cookie_key = "Admin-Token";
        $cookie = $_COOKIE[$cookie_key] ?? 'WEB_TOKEN';
        $this->cookiekey = $cookie;
        $this->token_expiretime = $config['expire'] ?? 86400 * 30; // 默认一个月有效
        $this->token_name = $config['token_name'] ?? 'WEB_TOKEN';
        $this->token_prefix = $config['prefix'] ?? 'web_token_';
        // 如果不存在，只有cookie，则获取cookie值 Admin-Token


        // parent::__construct();
    }


    /**
     * 获取token值，通过request中的 TOKEN来传递
     *
     * @return void
     */
    public function getToken()
    {

        $token = "";
        $param = input("server.");
        $str = "HTTP_" . str_replace('-', '_', $this->token_name); //server取出来的需要如此处理

        // dump($param);
        $token = input("server." . $str);
        if (empty($token)) {
            $token = $this->cookiekey;
        }
        // dump($token);
        return $token;
    }


    /**
     * 生成随机的token值
     * @return [type] [description]
     */
    public function genToken()
    {

        $token = "";
        $token = md5("_wilson_" . uniqid() . "_" . time() . "_token_");

        return $token;
    }
    /**
     * 根据token名称获取保存时的键值
     *
     * @param [type] $token
     * @return void
     */
    protected function getKey($token)
    {
        if (empty($token)) {
            return null;
        }
        return $this->token_prefix . $token;
    }

    /**
     * 获取Token对应的所有数据信息
     *
     * @param string $token
     * @return void
     */
    public function getTokenAllData($token = '')
    {

        $data = [];
        if (empty($token)) {
            return null;
        }

        $key = $this->getKey($token);

        $data = Cache::store('token')->get($key);

        return $data;
    }

    /**
     * 删除指定用户token
     *
     * @param string $token
     * @return void
     */
    public function removeToken($token = '')
    {
        $key = $this->getKey($token);
        if ($key) {
            Cache::store('token')->rm($key);
        } else {
            return false;
        }
        return true;
    }


    /**
     * 保存token的配置信息
     *
     * @param string $token
     * @param string $key
     * @param array $data
     * @return void
     */
    public function setData($token = '', $key = '', $data = [])
    {
        // Log::record("正在开始设置token.setData，token=【{$token}】，key=【{$key}】,data=【". _cv_to_json($data)."】");
        if (empty($token)) {
            $token = $this->getToken();
        }
        if (empty($token)) {
            return false;
        }
        if (empty($key)) {
            return false;
        }
        $tokendata = $this->getTokenAllData($token);
        // Log::record("获取当前token的数据,数据为："._cv_to_json($tokendata));


        if (!$tokendata) {
            // return false;
            $tokendata = [];
        }
        $tokenkey = $this->getKey($token);
        $tokendata[$key] = $data;

        //继续保存进Cache中
        $bool = Cache::store('token')->set($tokenkey, $tokendata, $this->token_expire); //保存进cache


        return $bool;
    }
    /**
     * 保存token的配置信息
     *
     * @param string $token
     * @param string $key
     * @param array $data
     * @return void
     */
    public function removeData($token = '', $key = '')
    {

        if (empty($token)) {
            $token = $this->getToken();
        }
        if (empty($token)) {
            return false;
        }
        if (empty($key)) {
            return false;
        }
        $tokendata = $this->getTokenAllData($token);

        if (!$tokendata) {
            return false;
        }
        $tokenkey = $this->getKey($token);
        unset($tokendata[$key]);

        // $tokendata[$key] = $data;

        //继续保存进Cache中
        $bool = Cache::store('token')->set($tokenkey, $tokendata, $this->token_expire); //保存进cache


        return $bool;
    }

    /**
     * 获取缓存数据内容
     *
     * @param string $token
     * @param string $key
     * @return void
     */
    public function getData($token = '', $key = '', $default = null)
    {
        $data = $this->getTokenAllData($token);
        return $data[$key] ?? $default;
    }

    /**
     * 更新当前用户的最新活动时间，仅在有效活动时可用
     * 有效活动是指查看文章/news/info，修改个人信息、查看通讯录、查询文章列表、 等
     *
     * @return void
     */
    public function updateLoginTime($token='')
    {
        $time = config("autoLogoff_time");
        
        if (!empty($time)) {
            $newtime = time() + $time;
            // $token = $this->getToken();

            $data['token'] = $token;
            $data['time'] = $newtime;
            
            $data['time_string'] = date('Y-m-d H:i:s',$newtime);
            
            $this->setData($token, self::LOGIN_EXPIRE_NAME, $newtime);
            // halt($data);
            return $data;
            // halt("time:".date('Y-m-d H:i:s',$newtime));
        }
        return [];
    }

    /**
     * 检查是否在登录状态
     *
     * @return void
     */
    public function checkAutoLogoff()
    {
        $token = $this->getToken();
        $config = config("autoLogoff_time");
        $time = $this->getData($token, self::LOGIN_EXPIRE_NAME);
        // dump("checkAutoLogoff");
        // dump($token);
        // dump($time);
        if (empty($config)) {
            return true;
        }


        // $time = intval($time);
        // halt(date('Y-m-d H:i:s',$time));
        $now = time();

        $data = [];
        // $data['now'] = $now;
        // $data['now_string'] = date('Y-m-d H:i:s',$now);
        // $data['logout_time'] = $time;
        // $data['logout_time_string'] = date('Y-m-d H:i:s',$time);
        $data['result'] = $time>$now;

        return $data;
        // return $time > $now;
    }
}
