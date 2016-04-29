<?php
/**
 * Created by PhpStorm.
 * User: linzh
 * Date: 2016/3/9
 * Time: 17:31
 */
namespace System\Utils;
use System\Core\KbylinException;

class StringHelper {

    /**
     * 检验参数是否都是字母开头的标识符
     * @return bool
     * @throws KbylinException
     */
    public static function checkBeginWithChar(){
        $args = func_get_args();
        foreach($args as $val){
            if(is_array($val)){
                foreach($val as $k=>$v){
                    if(!self::checkBeginWithChar($v)){
                        return false;
                    }
                }
            }elseif(is_string($val)){
                if(!preg_match('/^[A-Za-z](\/|\w)*$/',$val)){
                    return false;
                }
            }else{
                throw new KbylinException('参数仅限于数组和字符串！');
            }
        }
        return true;
    }

    /**
     * 将C风格字符串转换成JAVA风格字符串
     * C风格      如： sub_string
     * JAVA风格   如： SubString
     * @param $str
     * @return string
     */
    public static function toJavaStyle($str){
        static $cache = [];
        if(!isset($cache[$str])){
            $cache[$str] = ucfirst(preg_replace_callback('/_([a-zA-Z])/',function($match){return strtoupper($match[1]);},$str));
        }
        return $cache[$str];
    }
    /**
     * JAVA风格字符串转换成将C风格字符串
     * C风格      如： sub_string
     * JAVA风格   如： SubString
     * @param $str
     * @return string
     */
    public static function toCStyle($str){
        static $cache = [];
        if(!isset($cache[$str])) {
            return strtolower(ltrim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
        }
        return $cache[$str];
    }



    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * ** from codeginiter
     * @param	string $str
     * @param	bool $url_encoded 是否将urlencode过的字符串也进行过滤，默认为true
     * @return	string
     */
    public static function removeInvisibleCharacters($str, $url_encoded = TRUE){
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded){
            $non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do{
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }while ($count);

        return $str;
    }

    /**
     * 对字符串进行递归解码
     * 可以针对urlencode(urlencode($str))的情况
     *
     * 注意：
     *
     * URL Decode
     * Just in case stuff like this is submitted:
     * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a> // www.google.com
     * Note: Use rawurldecode() so it does not remove plus signs //不会把加号（'+'）解码为空格,而urldecode可以
     * @param string $str 待解码的字符串
     * @param bool|true $saveplus 是否将加号解释成空格，默认为否
     * @return string 返回解码后的字符串
     */
    public static function urlDecodeInRecursion($str,$saveplus=true){
        do {
            $str = $saveplus?rawurldecode($str):urldecode($str);
        } while (preg_match('/%[0-9a-f]{2,}/i', $str));
        return $str;
    }



    /**
     * 去除代码中的空白和注释
     * @param string $content 代码内容
     * @return string
     */
    public static function stripWhitespace($content) {
        $stripStr   = '';
        //分析php源码
        $tokens     = token_get_all($content);
        $last_space = false;
        for ($i = 0, $j = count($tokens); $i < $j; $i++) {
            if (is_string($tokens[$i])) {
                $last_space = false;
                $stripStr  .= $tokens[$i];
            } else {
                switch ($tokens[$i][0]) {
                    //过滤各种PHP注释
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        break;
                    //过滤空格
                    case T_WHITESPACE:
                        if (!$last_space) {
                            $stripStr  .= ' ';
                            $last_space = true;
                        }
                        break;
                    case T_START_HEREDOC:
                        $stripStr .= "<<<THINK\n";
                        break;
                    case T_END_HEREDOC:
                        $stripStr .= "THINK;\n";
                        for($k = $i+1; $k < $j; $k++) {
                            if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                                $i = $k;
                                break;
                            } else if($tokens[$k][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                        break;
                    default:
                        $last_space = false;
                        $stripStr  .= $tokens[$i][1];
                }
            }
        }
        return $stripStr;
    }
}