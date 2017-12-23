<?php

namespace Core;

/**
 * 容器
 */
class Container implements \ArrayAccess {

    private static $_instance;
    private $_data = [];

    public function __construct() {

    }

    public static function getInstance($name = '') {

        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        if ($name) {
            return self::$_instance->$name;
        }

        return self::$_instance;
    }

    /**
     * todo 改进 static $object 切换成内存缓存
     * @param $key
     * @param $value
     */
    public function singleton($key, $value) {

        $this->set($key, function($c) use ($value) {
            static $object;

            if (null === $object) {
                $object = $value($c);
            }

            return $object;
        });
    }

    public function set($key, $value, $force = false) {
        if ($this->has($key) && $force === false) {
            throw new AppException("该容器的键值已存在，如果想覆盖原来的值， 请设置第三个参数为true");
        }

        $this->_data[$key] = $value;
    }

    public function get($key, $default = null) {
        if ($this->has($key)) {
            // 匿名函数 相当于一个具有 __invoke 方法的类
            $isInvokable = is_object($this->_data[$key]) && method_exists($this->_data[$key], '__invoke');

            return $isInvokable ? $this->_data[$key]($this) : $this->_data[$key];
        }

        return $default;
    }

    public function has($key) {
        return isset($this->_data[$key]);
    }

    public function remove($key)
    {
        unset($this->_data[$key]);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __unset($key)
    {
        $this->remove($key);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

}