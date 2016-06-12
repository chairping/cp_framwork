<?php
header('Access-Control-Allow-Origin: http://admin.wy.cn');
define('APP_NAME', 'admin_app'); // app应用目录名称

/* @var Application $app */
$app = require '../boostrap/app_start.php';

$app->run();

exit();