<?php
/**
 * Created by PhpStorm.
 * User: ljx@dotamore.com
 * Date: 2019/3/31
 * Time: 22:47
 */

namespace App\Lib;


use App\Exception\Base;

class Rpc
{
    private $onRequest;
    private $afterRequest;
    private $onException;
    private $actionNotFound;

    private $controllerNameSpace = 'App\\Service\\';

    function onRequest(callable $call): Rpc
    {
        $this->onRequest = $call;
        return $this;
    }

    function afterRequest(callable $call): Rpc
    {
        $this->afterRequest = $call;
        return $this;
    }

    function onException(callable $call): Rpc
    {
        $this->onException = $call;
        return $this;
    }

    function actionNotFound(callable $call): Rpc
    {
        $this->actionNotFound = $call;
        return $this;
    }

    public function setControllerNameSpace(string $nameSpace)
    {
        $this->controllerNameSpace = $nameSpace;
    }

    public function getControllerNameSpace() :string
    {
        return $this->controllerNameSpace;
    }

    public function register(\swoole_server $server,int $fd,int $reactor_id, string $data)
    {
        $response = new Response();
        $request  = new Request($fd);
        //onRequest 全局拦截 比如做权限或者签名验证
        try
        {
            $ret = $this->hookCallback($this->onRequest, $request, $response);
            if ($ret === false)
            {
                goto response;
            }
        }
        catch (\Throwable $throwable)
        {
            $response->setStatus($response::STATUS_SERVICE_ERROR);
            $this->hookCallback($this->onException, $throwable, $request, $response);
            goto response;
        }

        //检测必须参数并赋值 service action
        try
        {
            if ($request->checkSetParam($data) === false)
            {
                $response->setStatus($response::STATUS_SERVICE_REJECT_REQUEST);
                goto response;
            }
        }
        catch (\Throwable $throwable)
        {
            $response->setStatus($response::STATUS_SERVICE_ERROR);
            $this->hookCallback($this->onException, $throwable, $request, $response);
            goto response;
        }

        //调用服务方法
        if($request->getService())
        {
            try
            {
                $service = ucfirst($request->getService());
                $version = 'V'.$request->getVersion();
                $class = "{$this->controllerNameSpace}{$version}\\{$service}";
                if(!class_exists($class))
                {
                    //service不存在
                    $response->setStatus(Response::STATUS_SERVICE_SERVICE_NOT_FOUND);
                    goto response;
                }
                $class  = new \ReflectionClass($class);
                $action = $request->getAction();
                if(!$class->hasMethod($action))
                {
                    //action不存在
                    //重新组装参数
                    $request->proxyActionAssemblyArg();
                    $method = $class->getMethod('__call');
                }
                else
                {
                    $method = $class->getMethod($action);
                }

            }
            catch (\Throwable $throwable)
            {
                $response->setStatus($response::STATUS_SERVICE_ERROR);
                $response->setMessage($throwable->getMessage());
                $this->hookCallback($this->onException, $throwable, $request, $response);
                goto response;
            }


            try
            {
                //调用
                $instance = $class->newInstance($request,$response);
                $ret = $method->invokeArgs($instance,$request->getArg());
                $response->setMessage($ret);
            }
            catch (\Throwable $throwable)
            {
                if($throwable instanceof Base)
                {
                    //自定义异常
                    $response->setStatus($throwable->status);
                    $response->setMessage($throwable->msg);
                }
                else
                {
                    //系统异常
                    $response->setStatus($response::STATUS_SERVICE_ERROR);
                    $response->setMessage($throwable->getMessage());
                    $this->hookCallback($this->onException, $throwable, $request, $response);
                }
                goto response;
            }
        }
        else
        {
            //service为空
            $response->setStatus($response::STATUS_SERVICE_REJECT_REQUEST);
            goto response;
        }

        //最后的afterRequest 已经不影响服务逻辑，因此不主动改变status
        try
        {
            $this->hookCallback($this->afterRequest, $request, $response);
        }
        catch (\Throwable $throwable)
        {
            $this->hookCallback($this->onException, $throwable, $request, $response);
        }

        response:{
        if ($server->exist($fd))
        {
            $message = $response->getMessage();
            $responseData = [
                'status' => $response->getStatus(),
                'data'   => $message
            ];
            $responseData = serialize($responseData);
            $responseData = Request::pack($responseData);
            $server->send($fd,$responseData);
            //判断客户端是否需要长连接
            if(!$request->getIsKeep())
            {
                $server->close($fd);
            }
        }
    }
    }

    private function hookCallback($call, ...$arg)
    {
        if (is_callable($call)) {
            return call_user_func($call, ...$arg);
        } else {
            return null;
        }
    }
}