<?php

//建立连接
$fp = stream_socket_client('tcp://127.0.0.1:20001');
//发送数据
fwrite($fp, 'Test');
//主动获取响应
$data = fread($fp, 65533);

echo "服务端响应数据：{$data}\n";

//断开连接
fclose($fp);
