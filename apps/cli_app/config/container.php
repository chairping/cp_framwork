<?php

// 容器调用配置
$config['container.singleton'] = [
    'curl' => function($c) {  // 用户登录成功的
        return new \Lib\Curl();
    }
];
