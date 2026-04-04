<?php
namespace app\cccf\controller;



use Ratchet\Client\WebSocket;
use Ratchet\Http\OriginCheck;
use Ratchet\Client\Connector;
 
require 'vendor/autoload.php';


class Test1
{
    public function index()
    {

        return "123";
        $wsServer = 'ws://your-websocket-server-url';

        $ws = new WebSocket\Client($wsServer);

        $ws->on('message', function($msg){
            echo 'Received message: $msg\n';
        });

        $ws->send('Hello WebSocket server!');

        $ws->close();
    }
}
