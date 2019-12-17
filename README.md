#### 环境要求
* 保证 PHP 版本大于等于 7.2
* 保证 Swoole 拓展版本大于等于 4.3.5
* 使用 Linux / FreeBSD / MacOS 这三类操作系统

#### 作者开发环境
* PHP 7.2
* Swoole 4.3.5
* CentOS 7.2

#### RPC接口协议
* TCP

#### 运行
1.服务端：php Server.php
```
[root@x-x-x-x Rpc]# php Server.php
服务已启动，主进程PID：22646
```
2.客户端：php Client.php
```
[root@x-x-x-x Rpc]# php Client.php
服务端响应数据：Server: Test
```
3.切换回服务端
```
[root@x-x-x-x Rpc]# php Server.php
服务已启动，主进程PID：23280
Client: Connect.
接收客户端数据：Test
Client: Close.
```

#### [教程链接]((https://www.ar414.com))
1. [什么是RPC](https://ar414-com.github.io/php/2-1/)
2. [Php Tcp通讯](https://ar414-com.github.io/php/2-2/)
