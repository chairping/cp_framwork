<?php
namespace Lib;

class Redis
{
    //连接句柄
	public static $instance = null;

	public static function getInstance() {
		
		if (self::$instance == null) {
			$config = config("redis");

			if(!$config) {
				
			
			}

			self::$instance = new \Redis();
			self::$instance->connect($config['host'], $config['port']);
		}	

		return self::$instance;
	
	}

	public function ping() {
		$config = config("redis");
		if(self::$instance->ping() !== "+PONG") {	
			self::$instance = new \Redis();
			self::$instance->connet($config['host'], $config['port']);
		}
	}


    public function push($key, $value) {
        return self::$redis->rPush($key, $value);
    }

    public function pop($key) {
        return self::$redis->lpop($key);
    }

    public function sadd($key, $value) {
        return self::$redis->SADD($key, $value);
    }

    public function spop($key) {
        return self::$redis->SPOP($key);
    }
}
