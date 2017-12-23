<?php
ini_set('always_populate_raw_post_data', -1);

define('APP_NAME', 'app'); // app应用目录名称

$app = require '../vendor/autoload.php';
$app = require '../boostrap/app_start.php';

//xhprofEnable();
//
$app->addMiddleware(new \App\Middleware\Maintenance());  // 检测网站是否开启维护
$app->addMiddleware(new \Middleware\Cors());
$app->run();

//xhprofDisable();

exit();




