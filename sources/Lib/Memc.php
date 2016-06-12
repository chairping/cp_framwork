<?php
/**
 * memcached缓存基类
 */

namespace Lib;

class Memc {
    //连接句柄
    public static $memcObj = false;
    
    public static function init() {
        if (!self::$memcObj) {
            //连接memcached
            $conf = config("memc");
            if (!$conf) {
                throw new \Exception("MEMCACHE配置信息不存在");
            }
            self::$memcObj = new \Memcached();
            self::$memcObj->addServer($conf['host'], $conf['port']);
            self::$memcObj->setOption(\Memcached::OPT_COMPRESSION, 0);
            self::$memcObj->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
            self::$memcObj->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        }
        return self::$memcObj;
    }
    
    /**
     * @name 向已存在元素后追加数据
     * @param string $key 用于存储值的键名
     * @param string $value 将要追加的值
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function append($key, $value, $nameSpace = '') {
        self::init();
        return self::$memcObj->append($nameSpace . $key, $value);
    }
    
    /**
     * @name 删除一个元素
     * @param string $key 要删除的key
     * @param number $time 服务端等待删除该元素的总时间(或一个Unix时间戳表明的实际删除时间)
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function delete($key, $time = 0, $nameSpace = '') {
        self::init();
        return self::$memcObj->delete($nameSpace . $key, $time);
    }
    
    /**
     * @name 检索一个元素
     * @param string $key
     * @return string|boolean string|false返回存储在服务端的元素的值或者在其他情况下返回FALSE
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function get($key, $nameSpace = '') {
        self::init();
        return self::$memcObj->get($nameSpace . $key);
    }
    
    /**
     * @name 检索多个元素
     * @param array $keys 要检索的key的数组
     * @return string|boolean string|false 返回检索到的元素的数组 或者在失败时返回 FALSE
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function getMulti($keys, $nameSpace = '') {
        self::init();
        foreach ($keys as $key => $value) {
            $keys[$key] = $nameSpace . $value;
        }
        return self::$memcObj->getMulti($keys);
    }
    
    /**
     * @name 向一个已存在的元素前面追加数据
     * @param string $key 要向前追加数据的元素的key
     * @param string $value 要追加的字符串
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public function prepend($key, $value, $nameSpace = '') {
        self::init();
        return self::$memcObj->prepend($nameSpace . $key, $value);
    }
    
    /**
     * @name 替换已存在key下的元素
     * @param string $key 用于存储值的键名
     * @param string $value 存储的值
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function replace($key, $value, $expiration = 0, $nameSpace = '') {
        self::init();
        return self::$memcObj->replace($nameSpace . $key, $value, time() + $expiration);
    }
    
    /**
     * @name 存储一个元素
     * @param string $key 用于存储值的键名
     * @param string $value 存储的值
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function set($key, $value, $expiration = 0, $nameSpace = '') {
        self::init();
        return self::$memcObj->set($nameSpace . $key, $value, time() + $expiration);
    }
    
    /**
     * @name 存储多个元素
     * @param array $keys 存放在服务器上的键／值对数组
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return string|boolean string|false 返回检索到的元素的数组 或者在失败时返回 FALSE
     * @lastModify
     *  @date
     *  @author
     *  @note
     *  @review
     */
    public static function setMulti($keys, $expiration = 0, $nameSpace = '') {
        self::init();
        $items = array();
        foreach ($keys as $key => $value) {
            $items[$nameSpace . $key] = $value;
        }
        return self::$memcObj->setMulti($items, time() + $expiration);
    }
}
