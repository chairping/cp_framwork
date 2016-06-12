<?php
namespace Lib;

class Redisc
{
    //连接句柄
    public static $redis = false;

    public static function init() {
        if (!self::$redis) {
            //连接redis
            $conf = config("redis");
            if (!$conf) {
                throw new \Exception("REDIS配置信息不存在");
            }
            self::$redis = new \Redis();
            self::$redis->connect($conf['host'], $conf['port']);
        }
        if (self::$redis->ping() != "+PONG") {
            self::reconn();
        }
        return self::$redis;
    }

    public static function reconn(){
        $conf = config("redis");
        if (!$conf) {
            throw new \Exception("REDIS配置信息不存在");
        }
        self::$redis = new \Redis();
        self::$redis->connect($conf['host'], $conf['port']);
        return self::$redis;
    }

    public static function push($key, $value) {
        self::init();
        return self::$redis->rPush($key, $value);
    }

    public static function pop($key) {
        self::init();
        return self::$redis->lpop($key);
    }

    public static function sadd($key, $value) {
        self::init();
        return self::$redis->SADD($key, $value);
    }

    public static function spop($key) {
        self::init();
        return self::$redis->SPOP($key);
    }
}