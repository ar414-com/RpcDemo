<?php

//创建Server对象，监听 0.0.0.0:20001端口
$serv = new Swoole\Server("0.0.0.0", 20001);

$serv->on('Start', function ($serv) {
    echo "服务已启动，主进程PID：{$serv->master_pid}\n";
});

//监听连接进入事件
$serv->on('Connect', function ($serv, $fd) {
    echo "Client: Connect.\n";
});

//监听数据接收事件
$serv->on('Receive', function ($serv, $fd, $from_id, $data) {
    echo "接收客户端数据：{$data}\n";
    $serv->send($fd, "Server: ".$data);
});

//监听连接关闭事件
$serv->on('Close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start();

