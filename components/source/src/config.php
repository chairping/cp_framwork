<?php

$config['mode'] = 'development';  // 开发者模式 development:   线上模式production:

$config['log.path'] = BASE_PATH . 'logs' . DS . APP_NAME . DS;

// 数据库配置
$config['db'] = [
    'host' => 'localhost',
    'user' => 'root',
    'pwd' => '',
    'database' => 'test',
    'port' => '3306',
    'charset' => 'utf8',
];

// 验证码配置
$config['captcha'] = [
    'width'			=> 120,
    'height'		=> 30,
    'fontSize'		=> [16, 18],        //字体随机范围
    'xspace'		=> [5, 15],         //文字起始位置随机范围
    'fonts'			=> ['en', 'en1'],   //随机文字名称
    'level'		=> 4,               //验证码识别难度
    'length'		=> 4                // 验证码长度
];


// oauth2.0 配置
$config['oauth.enable'] = true;
$config['oauth.user_table'] = 'user';
$config['oauth.access_lifetime'] = 7200;
$config['oauth.refresh_token_lifetime'] = 2419200;
$config['oauth.always_issue_new_refresh_token'] = true;


// seaslog 日志配置
$config['seaslog.basepath'] = BASE_PATH . 'logs' . DS;
$config['seaslog.logger'] = 'logger';


$config['http.version'] = '1.1';

// MEMC配置
$config['memc'] = [
    [
        'host' => '172.18.0.2',
        'port' => 11211,
    ],
    [
        'host' => '172.18.0.3',
        'port' => '11211'
    ]
];

// REDIS配置
$config['redis'] = [
    'host' => '127.0.0.1',
    'port' => 6379,
];
