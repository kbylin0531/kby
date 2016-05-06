<?php
/**
 * User: Lin
 * Date: 2015/12/5
 * Time: 21:11
 */
namespace System\Core\Storage;
use System\Core\Exception\FileWriteFailedException;
use System\Core\KbylinException;
/**
 * Interface StorageInterface
 * 存储类接口
 * 如果使用键值对数据库，则需要模拟文件系统
 * @package System\Core\Storage
 */
interface StorageInterface {
    /**
     * 获取文件内容
     * 注意：
     *  页面是utf-8，file_get_contents的页面是gb2312，输出时中文乱码
     * @param string $filepath 文件路径,PHP源码中格式是UTF-8，需要转成GB2312才能使用
     * @param string|array $file_encoding 文件内容实际编码,可以是数组集合或者是编码以逗号分开的字符串
     * @param bool $recursion 如果读取到的文件是目录,是否进行递归读取,默认为false
     * @return string|null 返回文件内容,文件不存在或者在访问范围之内将返回null
     */
    public function read($filepath,$file_encoding=null,$recursion=false);//,$output_encode='UTF-8'

    /**
     * 将指定内容写入到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容(一定是UTF-8编码)
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式
     * @return int 返回写入的字节数目,失败时抛出异常
     * @throws FileWriteFailedException
     */
    public function write($filepath,$content,$write_encode='UTF-8',$text_encode='UTF-8');

    /**
     * 将指定内容追加到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @return int 返回写入的字节数目
     * @throws KbylinException
     */
    public function append($filepath,$content,$write_encode='UTF-8');

    /**
     * 确定文件或者目录是否存在
     * 相当于 is_file() or is_dir()
     * @param string $filepath 文件路径
     * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
     */
    public function has($filepath);

    /**
     * 设定文件的访问和修改时间
     * @param string $filename 文件路径
     * @param int $time
     * @param int $atime
     * @return bool
     */
    public function touch($filename, $time = null, $atime = null);

    /**
     * 删除文件
     * @param string $filepath
     * @return bool
     */
    public function unlink($filepath);

    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 修改时间
     * @return int|bool 如果是修改时间的操作,返回的bool值表示成功与否;如果是获取修改时间,则返回Unix时间戳或者bool值的false表示获取修改时间失败
     */
    public function mtime($filepath,$mtime=null);
    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int
     */
    public function size($filepath);


    /**
     * 创建文件夹
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件夹权限
     * @return null
     */
    public function mkdir($dirpath,$auth = 0755);
    /**
     * 修改文件权限
     * @param string $filepath 文件路径
     * @param int $auth 文件权限
     * @return bool
     */
    public function chmod($filepath,$auth = 0755);
    /**
     * 创建文件夹
     * 如果文件夹已经存在，则修改权限
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件权限，八进制表示
     * @return bool
     */
    public function makeDirectory($dirpath,$auth = 0755);
    /**
     * 读取文件夹内容，并返回一个数组(不包含'.'和'..')
     * array(
     *      //文件内容  => 文件内容
     *      'filename' => 'file full path',
     * );
     * @param string $path 目录
     * @param bool $recursion 是否递归读取
     * @return array
     * @throws KbylinException
     */
    public function readDirectory($path,$recursion=false);

    /**
     * 删除文件夹
     * @param string $dirpath 文件夹名路径
     * @param bool $recursion 是否递归删除
     * @return bool
     */
    public function removeDirectory($dirpath,$recursion=false);


}