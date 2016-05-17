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
 * Class Assets 模块资源加载工具
 * @package System\Utils
 */
class Assets {

    /**
     * 加载模块目录下的
     * @param string $name 配置名称,多个名称以'/'分隔
     * @param string $type 配置类型,默认为php
     * @return array 配置为空或者找不到配置时返回空数组
     */
    public static function load($name,$type=SEK::CONF_TYPE_PHP){
        $place = SEK::getCallPlace(SEK::CALL_ELEMENT_FILE,SEK::CALL_PLACE_SELF);
        $targetdir = dirname($place[SEK::CALL_ELEMENT_FILE]);
        $temp = null;
        while(true){
            $assetsDir = "{$targetdir}\\Assets";//如果存在这个目录,说明抵达了这个文件
            if(Storage::has($assetsDir) === Storage::IS_DIR){
                $file = "{$assetsDir}\\{$name}.".$type;
                return SEK::parseConfigFile($file);
            }
            //抵到了根目录的清空下
//            dump("{$targetdir}\\Controller",$targetdir);
            if($targetdir === $temp) break;
            $temp = $targetdir;
            $targetdir = dirname($targetdir);
        }
        return [];
    }

}