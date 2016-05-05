<?php
/**
 * Created by PhpStorm.
 * User: kbylin
 * Date: 5/5/16
 * Time: 11:36 AM
 */
namespace System\Utils;
use System\Core\Storage;

/**
 * Class Assets 资源加载工具
 * @package System\Utils
 */
class Assets {

    /**
     * @param string $name 配置名称,多个名称以'/'分隔
     * @param string $type 配置类型,默认为php
     * @return array
     */
    public static function load($name,$type=SEK::CONF_TYPE_PHP){
        $place = SEK::getCallPlace(SEK::CALL_ELEMENT_FILE,SEK::CALL_PLACE_FORWARD);
        $targetdir = dirname($place[SEK::CALL_ELEMENT_FILE]);
        $temp = null;
        while(true){
            dump("{$targetdir}\\Controller",file_exists("{$targetdir}\\Controller"),Storage::has("{$targetdir}\\Controller"));
            if(Storage::has("{$targetdir}\\Controller")){//存在该目录的清空下基本算抵到模块目录了
                $file = "{$targetdir}\\{$name}.".$type;
                return SEK::parseConfigFile($file,$type);
            }
            //抵到了根目录的清空下
//            dump("{$targetdir}\\Controller",$targetdir);
            if($targetdir === $temp) break;
            $temp = $targetdir;
            $targetdir = dirname($targetdir);
        }
        return null;
//        SEK::parseConfigFile();

    }

}