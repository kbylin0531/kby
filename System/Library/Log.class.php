<?php
/**
 * Created by Linzh.
 * Email: linzhv@qq.com
 * Date: 2016/2/3
 * Time: 15:10
 */
namespace System\Library;
use System\Core\Log\File;
use System\Traits\Crux;
use System\Utils\SEK;

/**
 * Class Log 日志管理类
 * @package System\Core
 */
class Log{

    use Crux;

    const CONF_NAME = 'log';
    const CONF_CONVENTION = [
        'DRIVER_DEFAULT_INDEX' => 0,//默认的驱动标识符，类型为int或者string
        'DRIVER_CLASS_LIST' => [
            File::class
        ],//驱动类列表
        'DRIVER_CONFIG_LIST' => [],//驱动类配置数组列表,如果不存在对应的但存在唯一的一个配置数组，则上面的driver类均使用该配置项

        'LOG_RATE'      => Log::LOGRATE_DAY,
    ];

    /**
     * 系统预设的级别，用户也可以自定义
     */
    const LOG_LEVEL_DEBUG = 'Debug';//错误和调试
    const LOG_LEVEL_TRACE = 'Trace';//记录日常操作的数据信息，以便数据丢失后寻回

    /**
     * 日志频率
     * LOGRATE_DAY  每天一个文件的日志频率
     * LOGRATE_HOUR 每小时一个文件的日志频率，适用于较频繁的访问
     */
    const LOGRATE_HOUR = 0;
    const LOGRATE_DAY = 1;

    /**
     * 获取日志文件的UID（Unique Identifier）
     * @param string $level 日志界别
     * @param string $datetime 日志时间标识符，如“2016-03-17/09”日期和小时之间用'/'划分
     * @return string 返回UID
     * @throws KbylinException
     */
    protected static function fetchLogUID($level=self::LOG_LEVEL_DEBUG,$datetime=null){
        if(isset($datetime)){
            $path = RUNTIME_PATH."Log/{$level}/{$datetime}.log";
        }else{
            $datetime = SEK::getDate();
            $thisconfig = self::getConventions();
            if(!isset($thisconfig['LOG_RATE'])) $thisconfig['LOG_RATE'] = self::LOGRATE_DAY;

            switch($thisconfig['LOG_RATE']){
                case self::LOGRATE_DAY:
                    $path = RUNTIME_PATH."Log/{$level}/{$datetime[1]}.log";
                    break;
                case self::LOGRATE_HOUR:
                    $path = RUNTIME_PATH."Log/{$level}/{$datetime[1]}/{$datetime[2]}.log";
                    break;
                default:
                    throw new KbylinException("Undefined log making rate constant of '{$thisconfig['LOG_RATE']}'!");
            }
        }
        return $path;
    }

    /**
     * 写入日志信息
     * 如果日志文件已经存在，则追加到文件末尾
     * @param string|array $content 日志内容
     * @param string $level 日志级别
     * @return string 写入内容返回
     * @Exception FileWriteFailedException
     */
    public static function write($content,$level=self::LOG_LEVEL_DEBUG){
        return self::getDriverInstance()->write(self::fetchLogUID($level),$content);
    }

    /**
     * 读取日志文件内容
     * 如果设置了参数二，则参数一将被认定为文件名
     * @param string $datetime 日志文件生成的大致时间，记录频率为天时为yyyy-mm-dd,日志频率为时的时候为yyyy-mmmm-dd:hh
     * @param null|string $level 日志级别
     * @return string|array 如果按小时写入，则返回数组
     */
    public static function read($datetime, $level=self::LOG_LEVEL_DEBUG){
        return self::getDriverInstance()->read(self::fetchLogUID($level,$datetime));
    }

    /**
     * 写入DEBUG信息到日志中
     * @param ...
     * @return void
     * @throws KbylinException
     */
    public static function debug(){
        $content = '';
        $params = func_get_args();
        foreach($params as $val){
            $content .= var_export($val,true);
        }
        self::write($content,self::LOG_LEVEL_DEBUG);
    }

    /**
     * 写入跟踪信息,信息参数可变长
     * @param ...
     * @return void
     */
    public static function trace(){
        $params = func_get_args();
        $content = '';
        foreach($params as $val){
            $content .= '█TRACE█'.var_export($val,true);
        }
        self::write($content,self::LOG_LEVEL_TRACE);
    }

}