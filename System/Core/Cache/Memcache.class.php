<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace System\Core\Cache;
use System\Core\KbylinException;
use System\Core\Cache;
use System\Utils\SEK;


/**
 * Class Memcache
 *
 *
 * 注意，访问了错误的服务器而导致无法连接会将报连接池出错
 * 如：MemcachePool::get()
 *
 * 支持memcache集群，详细请见章节
 *
 *
 * @package System\Core\Cache
 */
class Memcache implements CacheInterface{
    /**
     * @var \Memcache
     */
    protected $handler = null;


    protected $options = [
        'host'      => '127.0.0.1',
        'port'      => 11211,
        'expire'    => 0, // 0表示永不过期
        'prefix'    => '',
        'timeout'   => 1000, // 连接超时时间，默认1秒（单位：毫秒） 注意，如果设置为0将无法建立任何连接，并且会出现MemcachePool::get()的错误
        'persistent'=> true,
        'length'    => 0,
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     * @throws KbylinException
     */
    public function __construct(array $options = []) {

        if (!extension_loaded('memcache')) {
            throw new KbylinException('_NOT_SUPPERT_:memcache');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Memcache();
        // 支持集群
        if(false !== strpos($this->options['host'],',')){
            $hosts = explode(',', $this->options['host']);
        }else{
            $hosts = [$this->options['host']];
        }
        if(false !== strpos($this->options['port'],',')){
            $ports = explode(',', $this->options['port']);
        }else{
            $ports = [$this->options['port']];
        }
        if (empty($ports[0]))  $ports[0] = 11211;

        // 建立连接
        foreach ($hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
//            SEK::trace([$host, $port]);
            //添加成功时并不会测试是否可用
            $result = $this->handler->addServer($host, $port, $this->options['persistent'], 1 , $this->options['timeout']);
//            dump($result);
            if(!$result){
                throw new KbylinException('连接到Memcache服务器失败！');
            }
        }
//        dumpout($this->options,$this->handler,$this->handler->set('dsds', ''));
    }

    /**
     * 测试链接是否可用
     *
     * 如果不可用，请检查IP和端口是否正确，并检查防火墙是否限制了该端口的访问
     * @return bool
     */
    public function available(){
        return $this->handler->set('______test_avalable_______', '');
    }

    /**
     * 读取缓存
     *
     * Memcache::get()
     * Returns the string associated with the <b>key</b> or
     * an array of found key-value pairs
     * Returns <b>FALSE</b> on failure, <b>key</b> is not found or
     * <b>key</b> is an empty
     *
     * @access public
     * @param string $name 缓存变量名
     * @return string|array |null 返回false时候表示出现了错误
     */
    public function get($name)
    {
        $val = $this->handler->get($this->options['prefix'] . $name);
        return false === $this->handler->get($this->options['prefix'] . $name)?null:$val;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {

        if (NULL === $expire)  $expire = $this->options['expire'];
        $name = $this->options['prefix'] . $name;

        $result = $this->handler->set($name, $value, 0, $expire);

//        dumpout($this->handler,$result);
        if ($result) {//参数三 MEMCACHE_COMPRESSED
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $queue = $this->handler->get('__info__');
                if (!$queue) {
                    $queue = [];
                }
                if (false === array_search($name, $queue)) {
                    array_push($queue, $name);
                }

                if (count($queue) > $this->options['length']) {
                    // 出列
                    $key = array_shift($queue);
                    // 删除缓存
                    $this->handler->delete($key);
                }
                $this->handler->set('__info__', $queue);
            }
            return true;
        }
        return false;
    }

    /**
     * 删除缓存
     *
     * @param    string  $name 缓存变量名
     * @param int $timeout
     *
     * @return bool
     */
    public function delete($name, $timeout = 0){
        return $this->handler->delete($this->options['prefix'].$name, $timeout);
    }

    /**
     * 清除缓存
     * Flush all existing items at the server
     * @access public
     * @return bool
     */
    public function clean(){
        return $this->handler->flush();
    }
}