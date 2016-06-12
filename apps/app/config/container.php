<?php

// 容器调用配置
$config['container.singleton'] = [

];

$config['container.normal'] = [
    'loginRecord' => function($c) {  // 用户登录成功后执行
        return new \App\Lib\UserLoginRecord();
    }
];
