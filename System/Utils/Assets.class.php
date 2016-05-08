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
     * @param string $assetsDirName 资源目录名称
     * @return array 配置为空或者找不到配置时返回空数组
     */
    public static function load($name,$type=SEK::CONF_TYPE_PHP,$assetsDirName='Assets'){
        $place = SEK::getCallPlace(SEK::CALL_ELEMENT_FILE,SEK::CALL_PLACE_SELF);
        $targetdir = dirname($place[SEK::CALL_ELEMENT_FILE]);
        $temp = null;
        while(true){
//            dump($targetdir , $temp,"{$targetdir}\\Controller",file_exists("{$targetdir}\\Controller"),Storage::has("{$targetdir}\\Controller"));
            if(Storage::has("{$targetdir}\\Controller") === Storage::IS_DIR){//存在该目录的清空下基本算抵到模块目录了
                $file = "{$targetdir}\\{$assetsDirName}\\{$name}.".$type;
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