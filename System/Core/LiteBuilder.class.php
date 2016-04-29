<?php
/**
 * User: linzh
 * Date: 2016/3/9
 * Time: 17:16
 */
namespace System\Core;
use System\Utils\StringHelper;

class LiteBuilder {
    /**
     * 编译类文件
     * @param string $content 文件内容
     * @param bool|false $isfile 标记参数一指的是文件内容还是文件路径，默认为文件内容
     * @return string 编译后的文件内容
     */
    public static function compile($content,$isfile=false){
        if($isfile) $content = file_get_contents($content);
        $content    =   php_strip_whitespace($content);//删除PHP代码中的注释和空格
        $content    =   trim(substr($content, 5));//去除 '<?php'
        // 替换命名空间
        if(0===strpos($content,'namespace')){
            $content    =   preg_replace('/namespace\s(.*?);/','namespace \\1{',$content,1);
        }else{
            $content    =   'namespace {'.$content;
        }
        //去除  '? >'   也有可能不会带这个
        if ('?>' == substr($content, -2)){
            $content    = substr($content, 0, -2);
        }
        return "{$content} }";
    }


    public static function compileInBatch(array $filelist){
        $content = '';
        // 编译文件
        foreach ($filelist as $file){
            if(is_file($file)){
                if(!isset($_cache[$file])){
                    $content .= self::compile($file);
                    $_cache[$file] = true;
                }
            }
        }
        // 生成运行Lite文件
        return StringHelper::stripWhiteSpace('<?php '.$content);
    }

    /**
     * 将编译后的文件写入持久化
     * @param string $path 文件路径
     * @param string $content 文件内容
     * @return int
     */
    public static function overwrite($path,$content){
        return file_put_contents($path,$content);
    }
}