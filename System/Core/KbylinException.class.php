<?php
/**
 * User: linzh_000
 * Date: 2016/3/15
 * Time: 15:48
 */
namespace System\Core;
use System\Utils\Response;
use System\Utils\SEK;

/**
 * Class KbylinException 系统异常类
 *
 * 处理系统中发生的错误和异常,本身的实例也是一个可以抛出的异常异常
 *
 * @package System\Core
 */
class KbylinException extends \Exception{
    /**
     * KbylinException constructor.
     * @param ...
     */
    public function __construct(){
        if(func_num_args() > 0){//无参数时仅仅创建对象
            $args = func_get_args();
            $this->message = var_export($args,true);

            if(DEBUG_MODE_ON){
                $place = SEK::getCallPlace(true,SEK::CALL_PLACE_FORWARD);
                dumpout($place,$args);
            }
        }

    }

    /**
     * 设置错误信息
     * @param array $messages
     */
    public function setMessage($messages){
        $this->message = var_export($messages,true);
    }

    /**
     * 判断条件是否满足,满足的条件下将错误抛出
     * 参数一用于判断条件的bool值,为true的情况下将错误抛出,否则不作处理
     * @throws KbylinException
     */
    public static function throwWhile(){
        if(func_num_args() < 1) throw new KbylinException('Wrong with the empty parameters!');//至少需要一个参数
        $args = func_get_args();
        array_shift($args) and self::doThrows($args);
    }

    /**
     * 直接抛出异常信息
     * @throws KbylinException
     */
    public static function throwing(){
        $args = func_get_args();
        $exception = new KbylinException();
//            call_user_func_array([$exception,'_setMessage'],$args);//用于可变参数的情况
        $exception->setMessage($args);
        DEBUG_MODE_ON and dumpout($args);
        throw $exception;
    }

    /**
     * 打印异常信息
     * @param $args
     * @throws KbylinException
     */
    private static function doThrows($args){
        $exception = new KbylinException();
//            call_user_func_array([$exception,'_setMessage'],$args);//用于可变参数的情况
        $exception->setMessage($args);
        DEBUG_MODE_ON and dumpout($args);
        throw $exception;
    }





    /**
     * 系统默认的错误处理函数
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return void
     */
    public static function handleError($errno,$errstr,$errfile,$errline){
        IS_AJAX and Response::failed($errstr);

        //错误信息
        if(!is_string($errstr)) $errstr = serialize($errstr);
        ob_start();
        debug_print_backtrace();
        $vars = [
            'message'   => "{$errno} {$errstr}",
            'position'  => "File:{$errfile}   Line:{$errline}",
            'trace'     => ob_get_clean(),  //回溯信息
        ];
        if(DEBUG_MODE_ON){
            SEK::loadTemplate('error',$vars);
        }else{
            SEK::loadTemplate('user_error');
        }
        //异常处理完成后仍然会继续执行，需要强制退出
        exit;
    }

    /**
     * 处理异常的发生
     * 开放模式下允许将Exception打印打浏览器中
     * 部署模式下不建议这么做，因为回退栈中可能保存敏感信息
     * @param \Exception $e
     * @return void
     */
    public static function handleException(\Exception $e){
        IS_AJAX and Response::failed($e->getMessage());

        Response::cleanOutput();
//        $trace = $e->getTrace();
        $traceString = $e->getTraceAsString();
        //错误信息
        $vars = [
            'message'   => get_class($e).' : '.$e->getMessage(),
            'position'  => 'File:'.$e->getFile().'   Line:'.$e->getLine(),
            'trace'     => $traceString,//回溯信息，可能会暴露数据库等敏感信息
        ];
        if(DEBUG_MODE_ON){
            SEK::loadTemplate('exception',$vars);
        }else{
            SEK::loadTemplate('user_error');
        }
        //异常处理完成后仍然会继续执行，需要强制退出
        exit;
    }


}