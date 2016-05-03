<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/28
 * Time: 10:11
 */
namespace System\Core\Storage;
use System\Core\Exception\FileWriteFailedException;
use System\Core\KbylinException;
use System\Core\Storage;
use System\Utils\SEK;

/**
 * Class Common 文件系统驱动类基类
 * @package System\Core\Storage
 */
class File implements StorageInterface {

    private $convention = [
        'READ_LIMIT_ON'     => true,
        'WRITE_LIMIT_ON'    => true,
        'READABLE_SCOPE'    => BASE_PATH,
        'WRITABLE_SCOPE'    => RUNTIME_PATH,
        'ACCESS_FAILED_MODE'    => MODE_RETURN,
    ];

    public function __construct(array $config){
        $this->convention = array_merge($this->convention,$config);
    }

    /**
     * 检查目标目录是否可读取
     *
     * $accesspath代表的是可以访问的目录
     * $path 表示正在访问的文件或者目录
     *
     * @param string $path 路径
     * @param bool $isread
     * @return bool 表示是否可以访问
     * @throws DirectoryAccessFailedException
     */
    private function checkAccessable($path,$isread=true){
        if($isread){
            if(!$this->convention['READ_LIMIT_ON']) return true;
            $accesspath = $this->convention['READABLE_SCOPE'];
        }else{
            if(!$this->convention['WRITE_LIMIT_ON']) return true;
            $accesspath = $this->convention['WRITABLE_SCOPE'];
        }
//        dump($accesspath,$path);
        $path = dirname($path);//修改的目录
        $accesspath = rtrim($accesspath,'/');
        $result = IS_WIN?stripos($path,$accesspath):strpos($path,$accesspath);//检查允许访问的目录是否是其一部分
//        dump($result,$accesspath,$path);
        if(0 !== $result and MODE_EXCEPTION === $this->convention['ACCESS_FAILED_MODE']){
            throw new DirectoryAccessFailedException("{$accesspath},{$accesspath}");
        }
        return 0 === $result;
    }

    /**
     * 获取文件内容
     * 注意：
     *  页面是utf-8，file_get_contents的页面是gb2312，输出时中文乱码
     * @param string $filepath 文件路径,PHP源码中格式是UTF-8，需要转成GB2312才能使用
     * @param string|array $file_encoding 文件内容实际编码,可以是数组集合或者是编码以逗号分开的字符串
     * @param string $output_encode 文件内容输出编码
     * @return string 返回文件时间内容
     * @throws KbylinException
     */
    public function read($filepath,$file_encoding='UTF-8',$output_encode='UTF-8'){
        if(!$this->checkAccessable($filepath,true)) return null;

        $content = file_get_contents(SEK::toSystemEncode($filepath));
        if(false === $content){
            throw new KbylinException($filepath);
        }elseif($file_encoding === $output_encode){
            return $content;
        }else{
            if(is_string($file_encoding) && false === strpos($file_encoding,',')){
                return iconv($file_encoding,$output_encode,$content);
            }
            return mb_convert_encoding($content,$output_encode,$file_encoding);
        }
    }

    /**
     * 将指定内容写入到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容(一定是UTF-8编码)
     * @param string $write_encode 写入文件时的编码
     * @return int 返回写入的字节数目,失败时抛出异常
     * @throws FileWriteFailedException
     */
    public function write($filepath,$content,$write_encode='UTF-8'){
        if(!$this->checkAccessable($filepath,false)) return null;
        $dir      =  dirname($filepath);
        if(!$this->has($dir)) $this->makeDirectory($dir);//文件不存在则创建
        if($write_encode !== 'UTF-8'){//非UTF-8时转换编码
            $content = iconv('UTF-8',$write_encode,$content);
        }
        $rst = file_put_contents(SEK::toSystemEncode($filepath),$content);
        if(false === $rst){
            throw new FileWriteFailedException($filepath,$content);
        }
        return $rst;
    }

    /**
     * 将指定内容追加到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @return int 返回写入的字节数目
     * @throws KbylinException
     */
    public function append($filepath,$content,$write_encode='UTF-8'){
        if(!$this->checkAccessable($filepath,false)) return null;
//        SEK::dump($filepath,$content,$write_encode);exit;
        if(!$this->has($filepath)){
            return $this->write($filepath,$content,$write_encode);
        }
        $temp = SEK::toSystemEncode($filepath);
        if(false === is_writable($temp)){
            throw new KbylinException($filepath);
        }
        $handler = fopen($temp,'a+');//追加方式，如果文件不存在则无法创建
        if($write_encode !== 'UTF-8'){
            $content = iconv('UTF-8',$write_encode,$content);
        }
        $rst = fwrite($handler,$content);
        if(false === fclose($handler)) throw new KbylinException($filepath,$content);
        return $rst;
    }
    /**
     * 确定文件或者目录是否存在
     * 相当于 is_file() or is_dir()
     * @param string $filepath 文件路径
     * @return bool
     */
    public function has($filepath){
        if(!$this->checkAccessable($filepath,true)) return null;
        $filepath = SEK::toSystemEncode($filepath);
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
        if(!$this->checkAccessable($filepath,false)) return null;
        $filepath = SEK::toSystemEncode($filepath);
        return touch($filepath, $mtime,$atime);
    }

    /**
     * 删除文件
     * @param string $filepath
     * @return bool
     */
    public function unlink($filepath){
        if(!$this->checkAccessable($filepath,false)) return null;
        $filepath = SEK::toSystemEncode($filepath);
        return is_file($filepath)?unlink($filepath):rmdir($filepath);
    }


    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @return int
     */
    public function mtime($filepath){
        if(!$this->checkAccessable($filepath,false)) return null;
        return filemtime(SEK::toSystemEncode($filepath));
    }

    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int
     */
    public function size($filepath){
        if(!$this->checkAccessable($filepath,true)) return null;
        return filesize(SEK::toSystemEncode($filepath));
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
        if(!$this->checkAccessable($path,true)) return null;
        if($clear){
            $_file = array();
            $path = SEK::toSystemEncode($path);//不能多次转换，iconv函数不能自动识别自负编码
        }
        if (is_dir($path)) {
            $handler = opendir($path);
            while (($filename = readdir( $handler )) !== false) {//未读到最后一个文件   继续读
                if ($filename !== '.' && $filename !== '..' ) {//文件除去 .和..
                    $fullpath = $path . $filename;
                    if(file_exists($fullpath)) {
                        $filename = SEK::toProgramEncode($filename);
                        $fullpath = SEK::toProgramEncode($fullpath);
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
        if(!$this->checkAccessable($dirpath,false)) return null;
        if(!$this->has($dirpath)) return false;
        //扫描目录
        $dh = opendir(SEK::toSystemEncode($dirpath));
        while ($file = readdir($dh)) {
            if($file !== '.' && $file !== '..') {
                if(!$recursion) {//存在其他文件或者目录,非true时循环删除
                    closedir($dh);
                    return false;
                }
                $path = str_replace('\\','/',"{$dirpath}/{$file}");
                if(false === (is_dir(SEK::toSystemEncode($path))?$this->removeDirectory($path,true):$this->unlink($path))){
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
        if(!$this->checkAccessable($dirpath,false)) return null;
        $dirpath = SEK::toSystemEncode($dirpath);
        if(is_dir($dirpath)){
            return chmod($dirpath,$auth);
        }else{
            return mkdir($dirpath,$auth,true);
        }
    }
}