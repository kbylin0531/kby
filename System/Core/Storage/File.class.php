<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/28
 * Time: 10:11
 */
namespace System\Core\Storage;
use System\Core\Exception\NoAccessPermissionException;
use System\Core\KbylinException;
use System\Core\Storage;
use System\Utils\SEK;

/**
 * Class Common 文件系统驱动类基类
 * @package System\Core\Storage
 */
class File implements StorageInterface {

    /**
     * 惯例配置
     * @var array
     */
    private $convention = [
        'READ_LIMIT_ON'     => true,
        'WRITE_LIMIT_ON'    => true,
        'READABLE_SCOPE'    => BASE_PATH,
        'WRITABLE_SCOPE'    => RUNTIME_PATH,
        'ACCESS_FAILED_MODE'    => MODE_RETURN,

        'READOUT_MAX_SIZE'          => 2097152,//2M限制,对于文本文件已经足够
        'OS_ECNODE'         => 'GB2312', // 文件系统编码格式,如果是英文环境下可能是UTF-8,GBK,GB2312以外的编码格式
        'READOUT_ENCODE'    => 'UTF-8', // 读出时转化的成的编码格式
        'WRITEIN_ENCODE'    => 'UTF-8', // 写入时转化的编码格式
    ];

    /**
     * File constructor.
     * @param array $config
     */
    public function __construct(array $config){
        $this->convention = array_merge($this->convention,$config);
    }

    /**
     * 转换成PHP处理文件系统时所用的编码
     * 即UTF-8转GB2312
     * @param string $str 待转化的字符串
     * @param string $strencode 该字符串的编码格式
     * @return string|false 转化失败返回false
     * @throws KbylinException 编码转换失败时抛出异常
     */
    public function toSystemEncode($str,$strencode='UTF-8'){
        $result = iconv($strencode,$this->convention['OS_ECNODE'].'//IGNORE',$str);
        if(false === $result) throw new KbylinException("{$strencode} ==> GB2312 failed!");
        return $result;
    }

    /**
     * 转换成程序使用的编码
     * 即GB2312转UTF-8
     * @param string $str 待转换的字符串
     * @return string|false 转化失败返回false
     * @throws KbylinException 编码转换失败时抛出异常
     */
    public function toProgramEncode($str){
        $result = iconv('GB2312','UTF-8//IGNORE',$str);
        if(false === $result) throw new KbylinException(['GB2312','UTF-8//IGNORE',$str]);
        return $result;
    }
    /**
     * 检查目标目录是否可读取 并且对目标字符串进行修正处理
     *
     * $accesspath代表的是可以访问的目录
     * $path 表示正在访问的文件或者目录
     *
     * @param string $path 路径
     * @param bool $limiton 是否限制了访问范围
     * @param string $scope 范围
     * @return bool 表示是否可以访问
     * @throws NoAccessPermissionException
     */
    private function checkAccessableWithRevise(&$path,$limiton,$scope){
        $temp = dirname($path);//修改的目录
        $path = $this->toSystemEncode($path);
        if(!$limiton or !$scope) return true;
        return SEK::checkPathContainedInScope($temp,$scope);
    }

    /**
     * 检查是否有读取权限
     * @param string $path 路径
     * @return bool
     * @throws NoAccessPermissionException
     */
    private function checkReadableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->convention['READ_LIMIT_ON'],$this->convention['READABLE_SCOPE']);
    }

    /**
     * 检查是否有写入权限
     * @param string $path 路径
     * @return bool
     * @throws NoAccessPermissionException
     */
    private function checkWritableWithRevise(&$path){
        return $this->checkAccessableWithRevise($path,$this->convention['WRITE_LIMIT_ON'],$this->convention['WRITABLE_SCOPE']);
    }


    /**
     * 获取文件内容
     * 注意：
     *  页面是utf-8，file_get_contents的页面是gb2312，输出时中文乱码
     * @param string $filepath 文件路径,PHP源码中格式是UTF-8，需要转成GB2312才能使用
     * @param string|array $file_encoding 文件内容实际编码,可以是数组集合或者是编码以逗号分开的字符串
     * @return string|false 返回文件时间内容
     */
    public function read($filepath,$file_encoding=null){//,$output_encode='UTF-8'
        if(!$this->checkReadableWithRevise($filepath)) return false;

        $content = file_get_contents($filepath,null,null,null,$this->convention['READOUT_MAX_SIZE']);//限制大小为2M
        if(false === $content) return false;

        if(null === $file_encoding){
            return $content;
        }else{
            if($file_encoding === $this->convention['READOUT_ENCODE']) return $content;
            $readoutEncode = $this->convention['READOUT_ENCODE'].'//IGNORE';
            if(is_string($file_encoding) && false === strpos($file_encoding,',')){
                return iconv($file_encoding,$readoutEncode,$content);
            }
            return mb_convert_encoding($content,$readoutEncode,$file_encoding);
        }
    }

    /**
     * 确定文件或者目录是否存在
     * 相当于 is_file() or is_dir()
     * @param string $filepath 文件路径
     * @return bool
     */
    public function has($filepath){
        if(!$this->checkReadableWithRevise($filepath)) return false;
        return file_exists($filepath);
    }

    /**
     * 设定文件的访问和修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 文件修改时间
     * @param int $atime 文件访问时间，如果未设置，则值设置为mtime相同的值
     * @return bool
     */
    public function touch($filepath, $mtime = null, $atime = null){
        if(!$this->checkReadableWithRevise($filepath)) return false;
        $filepath = $this->toSystemEncode($filepath);
        return touch($filepath, $mtime,$atime);
    }





    /**
     * 将指定内容写入到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
     * @return int 返回写入的字节数目,失败时抛出异常
     */
    public function write($filepath,$content,$write_encode=null,$text_encode='UTF-8'){
        if(!$this->checkWritableWithRevise($filepath)) return false;

        $dir = dirname($filepath);

        dumpout($dir);
        if(!$this->has($dir)) $this->makeDirectory($dir);//父目录不存在时创建

        null === $write_encode and $write_encode = $this->convention['WRITEIN_ENCODE'];

//        dump($write_encode,$text_encode,iconv($text_encode,$write_encode.'//IGNORE',$content));
        if($write_encode !== $text_encode){//写入的编码并非是文本的编码时进行转化
            $content = iconv($text_encode,$write_encode.'//IGNORE',$content);
        }
        $result = file_put_contents($filepath,$content);
        return $result === false?false:$result > 0;
    }

    /**
     * 将指定内容追加到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @return bool 是否成功写入
     * @throws KbylinException
     */
    public function append($filepath,$content,$write_encode='UTF-8'){
        if(!$this->checkWritableWithRevise($filepath)) return null;
//        SEK::dump($filepath,$content,$write_encode);exit;
        if(!$this->has($filepath)){
            return $this->write($filepath,$content,$write_encode);
        }
        if(false === is_writable($temp)){
            throw new KbylinException($filepath);
        }
        $handler = fopen($temp,'a+');//追加方式，如果文件不存在则无法创建
        if($write_encode !== 'UTF-8'){
            $content = iconv('UTF-8',$write_encode,$content);
        }
        $rst = fwrite($handler,$content);
        if(false === fclose($handler)) throw new KbylinException($filepath,$content);
        return $rst > 0;
    }

    /**
     * 删除文件
     * @param string $filepath
     * @return bool
     */
    public function unlink($filepath){
        if(!$this->checkWritableWithRevise($filepath)) return null;
        return is_file($filepath)?unlink($filepath):rmdir($filepath);
    }


    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @return int
     */
    public function mtime($filepath){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        return filemtime($this->toSystemEncode($filepath));
    }

    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int
     */
    public function size($filepath){
        if(!$this->checkReadableWithRevise($filepath)) return null;
        return filesize($this->toSystemEncode($filepath));
    }

    /**
     * 读取文件夹内容，并返回一个数组(不包含'.'和'..')
     * array(
     *      //文件内容  => 文件内容
     *      'filename' => 'file full path',
     * );
     * @param string $path 目录
     * @param bool $recursion
     * @param bool $clear 是否清除之前的配置
     * @return array
     * @throws KbylinException
     */
    public function readDirectory($path,$recursion=false,$clear=true){
        static $_file = [];
        if(!$this->checkReadableWithRevise($path)) return null;
        if($clear){
            $_file = array();
            $path = $this->toSystemEncode($path);//不能多次转换，iconv函数不能自动识别自负编码
        }
        if (is_dir($path)) {
            $handler = opendir($path);
            while (($filename = readdir( $handler )) !== false) {//未读到最后一个文件   继续读
                if ($filename !== '.' && $filename !== '..' ) {//文件除去 .和..
                    $fullpath = $path . $filename;
                    if(file_exists($fullpath)) {
                        $filename = $this->toProgramEncode($filename);
                        $fullpath = $this->toProgramEncode($fullpath);
                        $_file[$filename] = str_replace('\\','/',$fullpath);
                    }
                    if(is_dir($fullpath) and $recursion) {
                        $this->readDirectory($fullpath,$recursion,false);//递归,不清空
                    }
                }
            }
            closedir($handler);//关闭目录指针
        }else{
            throw new KbylinException("Path '{$path}' is not a dirent!");
        }
        return $_file;
    }

    /**
     * 删除文件夹
     * @param string $dirpath 文件夹名路径
     * @param bool $recursion 是否递归删除
     * @return bool
     */
    public function removeDirectory($dirpath,$recursion=false){
        if(!$this->checkWritableWithRevise($dirpath)) return null;
        if(!$this->has($dirpath)) return false;
        //扫描目录
        $dh = opendir($this->toSystemEncode($dirpath));
        while ($file = readdir($dh)) {
            if($file !== '.' && $file !== '..') {
                if(!$recursion) {//存在其他文件或者目录,非true时循环删除
                    closedir($dh);
                    return false;
                }
                $path = str_replace('\\','/',"{$dirpath}/{$file}");
                if(false === (is_dir($this->toSystemEncode($path))?$this->removeDirectory($path,true):$this->unlink($path))){
                    return false;//***全等运算符优先级高于三目
                }
            }
        }
        closedir($dh);
        return $this->unlink($dirpath);
    }
    /**
     * 创建文件夹
     * 如果文件夹已经存在，则修改权限
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件权限，八进制表示
     * @return bool
     */
    public function makeDirectory($dirpath,$auth = 0755){
        $dirpath = $this->toSystemEncode($dirpath);
        if(!$this->checkWritableWithRevise($dirpath)) return null;
        $dirpath = $this->toSystemEncode($dirpath);
        if(is_dir($dirpath)){
            return chmod($dirpath,$auth);
        }else{
            return mkdir($dirpath,$auth,true);
        }
    }
}