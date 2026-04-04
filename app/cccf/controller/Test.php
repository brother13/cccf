<?php
namespace app\cccf\controller;


use app\cccf\model\Data;
use app\cccf\model\Qr;
use app\cccf\model\Common;
use app\cccf\model\Auth;
use app\cccf\model\Import;
use app\cccf\model\Docprint;
use \think\Debug;
use Ratchet\Client\WebSocket;
use Ratchet\Http\OriginCheck;
use Ratchet\Client\Connector;
use react\EventLoop\Factory;
use React\Socket;
class Test
{
   const CODE_SUCCESS = 20000;
   const CODE_ERROR = 0;

  
    /**
     * 创建一个空的返回值
     *
     * @return void
     */
    protected function _rt(){
        $rt = [];
        $rt['code'] = self::CODE_ERROR;
        $rt['action'] = input("param.action","/sys/info");
        $rt['message'] = "";
        $rt['time'] = getNowTime();
        $rt['data'] = "";
        return $rt;
    }
    public function test(){
        //客户端
        $client = new WebSocket\Client("ws://124.222.224.186:8800");
        $client->text("Hello WebSocket.org!");
        echo $client->receive();
        $client->close();
    }
    /**
     * 入口程序
     */
    public function index()
    {
                //客户端
                $loop = React\EventLoop\Factory::create();
                $connector = new React\Socket\Connector($loop);
                 
                $connector('wss://your-websocket-server.com', function(WebSocket $conn) {
                    $conn->on('message', function($msg) use ($conn) {
                        echo "Received message: " . $msg;
                        // 处理接收到的消息
                    });
                 
                    $conn->on('close', function() use ($conn) {
                        // 连接关闭时的回调
                    });
                 
                    $conn->send('Hello, Server!'); // 发送消息到服务器
                });
                 
                $loop->run();
        


    }

    protected function sysinfo(){
        $data = [];
        $data['system']="测试界面";
        $data['version'] = "v1.0";
        $data['lastupdate'] = "20200506";
        $data['servertime'] = getNowTime();
        return $data;
    }
	public function _empty($name=''){
        $rt = $this->commonmModel->_rt_();
        // $rt = [];
        $rt['code'] = Common::CODE_SUCCESS;
        $rt['message'] = "您访问的操作【{$name}】并不存在";
        $rt['data'] = "";
        
        return $rt;
    }
    
    public function testpwd($pass='123456',$salt='6155cd062c818'){
        $websalt = "_RLF2020";
        $data = [];
        $data['pass'] = $pass;
        $data['salt'] = $salt;
        $data['websalt'] = $websalt;
        $data['pass_str'] = $pass.$websalt;
        $data['pass_md5'] = md5($data['pass_str']);
        $data['pass_salt_md5'] = $this->genPassword($data['pass_md5'],$salt);

        return $data;
    }

    public function genPassword($password = '', $salt = '')
    {
        $key = "_wilson_";
        $str = md5($password) . $key . $salt;
        $str = md5($str);
        // 如果盐值是空的，则取当前密码
        if (empty($salt)) {
            return $password;
        }

        return $str;
    }

    public function testtime(){

        $data = [];
        $count = [];
        $week =  time()-(date('w',time())-1)*86400; // 减去当前秒数
        $week = strtotime(date('Y-m-d',$week));
        $count[] = ["label"=>"today","time"=>strtotime("today")]; // 当天
        $count[] = ["label"=>"week","time"=>$week]; // 本周
        $count[] = ["label"=>"month","time"=>strtotime(date('Y-m-01',time()))]; // 本月
        $count[] = ["label"=>"year","time"=>strtotime(date('Y-01-01',time()))]; // 本年

        foreach($count as $f){
            $row = [];
            $row['label'] = $f['label'];
            $row['time'] = $f['time'];
            $row['timetext'] = date('Y-m-d H:i:s',$row['time']);
            $data[] = $row;
        }

        return $data;
    }
    public function plapi(){

        return view("index");

    }
}
