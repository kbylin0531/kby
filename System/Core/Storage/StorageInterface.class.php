<?php
/**
 * User: Lin
 * Date: 2015/12/5
 * Time: 21:11
 */
namespace System\Core\Storage;
use System\Core\KbylinException;
/**
 * Interface StorageInterface
 * 存储类接口
 * 如果使用键值对数据库，则需要模拟文件系统
 * @package System\Core\Storage
 */
interface StorageInterface {


/*****************************************************************************************************************/
//  读取
/*****************************************************************************************************************/

    /**
     * 获取文件内容
     * 注意：
     *  页面是utf-8，file_get_contents的页面是gb2312，输出时中文乱码
     * @param string $filepath 文件路径,PHP源码中格式是UTF-8，需要转成GB2312才能使用
     * @param string|array $file_encoding 文件内容实际编码,可以是数组集合或者是编码以逗号分开的字符串
     * @param bool $recursion 如果读取到的文件是目录,是否进行递归读取,默认为false
     * @return string|array|false|null 返回文件时间内容;返回null表示在访问的范围之外
     */
    public function read($filepath, $file_encoding=null,$recursion=false);

    /**
     * 确定文件或者目录是否存在
     * 相当于 is_file() or is_dir()
     * @param string $filepath 文件路径
     * @return int 0表示目录不存在,<0表示是目录 >0表示是文件,可以用Storage的三个常量判断
     */
    public function has($filepath);
    /**
     * 返回文件内容上次的修改时间
     * @param string $filepath 文件路径
     * @param int $mtime 修改时间
     * @return int|bool|null 如果是修改时间的操作返回的bool;如果是获取修改时间,则返回Unix时间戳;返回null表示在访问的范围之外
     */
    public function mtime($filepath,$mtime=null);

    /**
     * 获取文件按大小
     * @param string $filepath 文件路径
     * @return int|false|null 按照字节计算的单位;返回null表示在访问的范围之外
     */
    public function size($filepath);

/*****************************************************************************************************************/
//  写入
/*****************************************************************************************************************/
    /**
     * 创建文件夹
     * @param string $dirpath 文件夹路径
     * @param int $auth 文件夹权限
     * @return bool|null 返回null表示在访问的范围之外
     */
    public function mkdir($dirpath,$auth = 0755);

    /**
     * 设定文件的访问和修改时间
     * 注意的是:内置函数touch在文件不存在的情况下会创建新的文件,此时创建时间可能大于修改时间和访问时间
     *         但是如果是在上层目录不存在的情况下
     * @param string $filepath 文件路径
     * @param int $mtime 文件修改时间
     * @param int $atime 文件访问时间，如果未设置，则值设置为mtime相同的值
     * @return bool 是否成功|返回null表示在访问的范围之外
     */
    public function touch($filepath, $mtime = null, $atime = null);

    /**
     * 修改文件权限
     * @param string $filepath 文件路径
     * @param int $auth 文件权限
     * @return bool 是否成功修改了该文件|返回null表示在访问的范围之外
     */
    public function chmod($filepath,$auth = 0755);

    /**
     * 删除文件
     * 删除目录时必须保证该目录为空
     * @param string $filepath 文件或者目录的路径
     * @param bool $recursion 删除的目标是目录时,若目录下存在文件,是否进行递归删除,默认为false
     * @return bool 是否成功删除|返回null表示在访问的范围之外
     */
    public function unlink($filepath,$recursion=false);


    /**
     * 将指定内容写入到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
     * @return bool 是否成功写入|返回null表示在访问的范围之外
     */
    public function write($filepath,$content,$write_encode=null,$text_encode='UTF-8');

    /**
     * 将指定内容追加到文件中
     * @param string $filepath 文件路径
     * @param string $content 要写入的文件内容
     * @param string $write_encode 写入文件时的编码
     * @param string $text_encode 文本本身的编码格式,默认使用UTF-8的编码格式
     * @return bool|null 是否成功写入,返回null表示无法访问该范围的文件
     */
    public function append($filepath,$content,$write_encode=null,$text_encode='UTF-8');


}