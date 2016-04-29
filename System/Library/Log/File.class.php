<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/9/6
 * Time: 9:59
 */
namespace System\Core\Log;
use System\Core\KbylinException;
use System\Core\Log;
use System\Core\Storage;
use System\Utils\Network;
use System\Utils\SEK;

/**
 * Class File 日志的文件驱动，文件驱动使用Storage类进行文件的IO操作，云服务器不推荐使用Storage，建议使用
 * @package System\Core\Log
 */
class File implements LogInterface{

    /**
     * 写入日志信息
     * 如果日志文件已经存在，则追加到文件末尾
     * @param string $logpath 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @param string|array $content 日志内容
     * @return bool 写入是否成功
     * @throws KbylinException
     */
    public function write($logpath,$content){
        $date = SEK::getDate();
        $ready2write = '';
        if(is_array($content)){//数组写入
            foreach($content as $key=>$val){
                $ready2write .= is_numeric($key)?"{$val}\n":"||--{$key}--||\n{$val}\n";
            }
        }else{
            $ready2write = $content;
        }
        $remoteIp = Network::getClientIP();
        $ready2write = "-------------------------------------------------------------------------------------\r\n {$date[0]}  IP:{$remoteIp}  URL:{$_SERVER['REQUEST_URI']}\r\n-------------------------------------------------------------------------------------\r\n{$ready2write}\r\n\r\n\r\n\r\n";

        if(is_file($logpath)){
            $handler = fopen($logpath,'a+');//追加方式，如果文件不存在则无法创建
            if(false === fwrite($handler,$ready2write)) throw new KbylinException('最佳形式的日志文件写入失败！');
            if(false === fclose($handler)) throw new KbylinException('追加形式的日志文件关闭失败');
            return true;
        }else{
            //写入0个字节或者返回false都被认为失败
            return true == file_put_contents($logpath,$ready2write);
        }
    }


    /**
     * 读取日志文件内容
     * 如果设置了参数二，则参数一将被认定为文件名
     * @param string $logpath 日志文件位置或者标识符（一个日志文件或者日志组是唯一的）
     * @return string 返回日志内容
     */
    public function read($logpath){
        if(is_file($logpath)){
            return file_get_contents($logpath);
        }else{
            return false;
        }
    }

}