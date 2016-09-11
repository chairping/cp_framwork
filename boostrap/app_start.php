<?php
// app 启动文件
 date_default_timezone_set('Asia/Shanghai');

//httpOnly,不允许js读取COOKIE信息，避免xss引起的安全问题
#ini_set("session.cookie_httponly", 1);
#ini_set('always_populate_raw_post_data', -1);

session_start();
require 'init.php';

$app = new \Core\Application($config);

$app->registerComponents()
    ->setErrorLevel()
    ->route();

// 添加应用中间件
//if (isset($config['app.middleware'])) {
//    foreach ($config['app.middleware'] as $middleware) {
//        $app->addMiddleware(new $middleware);
//    }
//}

// 添加核心中间件
//$app->addMiddleware(new \Middleware\IpDistrict());

// 添加前置中间件
//if (isset($config['app.middleware.before'])) {
//    foreach ($config['app.middleware.before'] as $middleware) {
//        $app->addMiddleware(new $middleware);
//    }
//}
// token  验证中间件
//$app->addMiddleware(new \Middleware\Auth());

return $app;
