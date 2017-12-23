<?php
namespace Lib;
/**
* 1  即时生成缓存　２　提前生成缓存　３　永久缓存
* 
*/
class Memcached {
    //连接句柄
    public static $instance = null;

    public static function getInstance() {

	if (self::$instance == null) {
           
	    //连接memcached
            $conf = config("memc");
            if (!$conf) {
                throw new \Exception("MEMCACHED配置信息不存在");
            }
            self::$instance = new \Memcached();
            self::$instance->addServers($conf);
            self::$instance->setOption(\Memcached::OPT_COMPRESSION, 0);
            self::$instance->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000);
            self::$instance->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        }

        return self::$instance;
    }
    
    /**
     * @name 向已存在元素后追加数据
     * @param string $key 用于存储值的键名
     * @param string $value 将要追加的值
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false
     */
    public function append($key, $value, $nameSpace = '') {
        return self::$instance->append($nameSpace . $key, $value);
    }
    
    /**
     * @name 删除一个元素
     * @param string $key 要删除的key
     * @param number $time 服务端等待删除该元素的总时间(或一个Unix时间戳表明的实际删除时间)
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false
     */
    public function delete($key, $time = 0, $nameSpace = '') {
        return self::$instance->delete($nameSpace . $key, $time);
    }
    
    /**
     * @name 检索一个元素
     * @param string $key
     * @return string|boolean string|false返回存储在服务端的元素的值或者在其他情况下返回FALSE
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     */
    public function get($key, $nameSpace = '') {
        return self::$instance->get($nameSpace . $key);
    }
    
    /**
     * @name 检索多个元素
     * @param array $keys 要检索的key的数组
     * @return string|boolean string|false 返回检索到的元素的数组 或者在失败时返回 FALSE
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     */
    public function getMulti($keys, $nameSpace = '') {
        foreach ($keys as $key => $value) {
            $keys[$key] = $nameSpace . $value;
        }
        return self::$instance->getMulti($keys);
    }
    
    /**
     * @name 向一个已存在的元素前面追加数据
     * @param string $key 要向前追加数据的元素的key
     * @param string $value 要追加的字符串
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     */
    public function prepend($key, $value, $nameSpace = '') {
        return self::$instance->prepend($nameSpace . $key, $value);
    }
    
    /**
     * @name 替换已存在key下的元素
     * @param string $key 用于存储值的键名
     * @param string $value 存储的值
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     */
    public function replace($key, $value, $expiration = 0, $nameSpace = '') {
        return self::$instance->replace($nameSpace . $key, $value, time() + $expiration);
    }
    
    /**
     * @name 存储一个元素
     * @param string $key 用于存储值的键名
     * @param string $value 存储的值
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return boolean true|false 成功时返回 TRUE， 或者在失败时返回 FALSEE
     */
    public function set($key, $value, $expiration = 0, $nameSpace = '') {
        return self::$instance->set($nameSpace . $key, $value, time() + $expiration);
    }
    
    /**
     * @name 存储多个元素
     * @param array $keys 存放在服务器上的键／值对数组
     * @param number $expiration 到期时间，默认为 0
     * @param string $nameSpace 模块内命名空间设置 防止缓存相互覆盖
     * @return string|boolean string|false 返回检索到的元素的数组 或者在失败时返回 FALSE
     */
    public function setMulti($keys, $expiration = 0, $nameSpace = '') {
        $items = array();
        foreach ($keys as $key => $value) {
            $items[$nameSpace . $key] = $value;
        }
        return self::$instance->setMulti($items, time() + $expiration);
    }

	public function __call($method, $args = array()) {
	
	}	
  
}
