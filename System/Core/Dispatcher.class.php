<?php
/**
 * Email: linzhv@qq.com
 * Date: 2016/1/21
 * Time: 15:55
 */
namespace System\Core;
use ReflectionMethod;
use System\Core\Exception\ActionNotFoundException;
use System\Core\Exception\ControllerNotFoundException;
use System\Traits\Crux;

/**
 * Class Dispatcher 系统调度器，将URI解析记过调度到指定的URI上
 * @package System\Core
 */
class Dispatcher{

    use Crux;

    const CONF_NAME = 'dispatcher';
    const CONF_CONVENTION = [
        'EMPTY_CONTROLLER'  => null,//控制器不存在时访问的空控制器
        'EMPTY_ACTION'      => null,//控制器中不存在指定方法时反问的方法的名称
    ];

    /**
     * 执行结果
     * @var null
     */
    private static $_result = null;

    /**
     * 执行控制器操作
     * @param null|string|array $modules 模块序列
     * @param null|string $ctrler 控制器名称
     * @param null|string $action 操作名称
     * @param null|array $parameters 参数列表
     * @return mixed|null 返回方法执行结果
     * @throws KbylinException
     */
    public static function execute($modules,$ctrler,$action,array $parameters=null){
        \Kbylin::recordStatus('execute_begin');

        self::checkInitialized(true);

        //完整性检查
        if(!isset($modules,$ctrler,$action)){
            throw new KbylinException([
                '调度器无法获取完整的参数！',
                $modules,$ctrler,$action
            ]);
        }
        is_array($modules) and $modules = implode('\\',$modules);
        empty($parameters) and $parameters = [];

        \Kbylin::recordStatus('execute_instance_build_begin');

        $thisconvention = &self::$_conventions[self::class];

        //控制器名称及存实性检测
        $className = "Application\\{$modules}\\Controller\\{$ctrler}";
        if(!class_exists($className)){
            if($thisconvention['EMPTY_CONTROLLER'] and class_exists($thisconvention['EMPTY_CONTROLLER'])){
                $className = $thisconvention['EMPTY_CONTROLLER'];
            }else{
                throw new ControllerNotFoundException($className);
            }
        }
        $classInstance =  new $className();
        //方法名称及存实性检测
        if(!method_exists($classInstance,$action)){
            if($thisconvention['EMPTY_ACTION'] and method_exists($classInstance,$thisconvention['EMPTY_ACTION'])){
                $action = $thisconvention['EMPTY_ACTION'];
            }else{
                throw new ActionNotFoundException($action);
            }
        }

        //获取实际目标方法
        $targetMethod = new ReflectionMethod($classInstance, $action);

        if ($targetMethod->isPublic() and !$targetMethod->isStatic()) {//仅允许非静态的公开方法
            //方法的参数检测
            if ($targetMethod->getNumberOfParameters()) {//有参数
                $args = self::fetchMethodArguments($targetMethod);
                //执行方法
                self::$_result = $targetMethod->invokeArgs($classInstance, $args);
            } else {//无参数的方法调用
                self::$_result = $targetMethod->invoke($classInstance);
            }
        } else {
            throw new KbylinException($className, $action);
        }

        \Kbylin::recordStatus('execute_end');
        return self::$_result;
    }

    /**
     * 获取传递给盖饭昂奋的参数
     * @param ReflectionMethod $targetMethod
     * @return array
     * @throws KbylinException
     */
    private static function fetchMethodArguments(ReflectionMethod $targetMethod){
        //获取输入参数
        $vars = [];
        $args = [];
        switch(strtoupper($_SERVER['REQUEST_METHOD'])){
            case 'POST':
                $vars    =  array_merge($_GET,$_POST);
                break;
            case 'PUT':
                parse_str(file_get_contents('php://input'), $vars);
                break;
            default:
                $vars  =  $_GET;
        }
        //获取方法的固定参数
        $methodParams = $targetMethod->getParameters();

        //遍历方法的参数
        foreach ($methodParams as $param) {
            $parameterName = $param->getName();
            if(isset($vars[$parameterName])){
                $args[] =   $vars[$parameterName];
            }elseif($param->isDefaultValueAvailable()){
                $args[] =   $param->getDefaultValue();
            }else{
                throw new KbylinException("Method do not get valid  parameter with name of $parameterName !");
            }
        }
        return $args;
    }

}