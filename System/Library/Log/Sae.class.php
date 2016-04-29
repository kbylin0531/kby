<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/9/6
 * Time: 10:00
 */
namespace System\Core\Log;
use System\Core\Log\LogInterface;

/**
 * Class SaeDriver 使用SAE的日志系统进行日志记录
 * @package System\Core\LogDriver
 */
class Sae implements LogInterface{

    //TODO:将日志写入到KVDB中或者memcache中

    /**
     * 写入日志信息
     * 如果日志文件已经存在，则追加到文件末尾
     * @param string $logpath 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @param string|array $content 日志内容
     * @return bool 写入是否成功
     */
    public function write($logpath,$content){return false;}


    /**
     * 读取日志文件内容
     * 如果设置了参数二，则参数一将被认定为文件名
     * @param string $logpath 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @return string|null 返回日志内容,指定的日志不存在时返回null
     */
    public function read($logpath){return null;}

}