<?php


// cli 端入口文件
define('APP_NAME', 'cli_app'); // app应用目录名称


date_default_timezone_set('Asia/ShangHai');


if (PHP_SAPI != 'cli') {
    exit("非cli模式下，禁止运行");
}

$opts = getopt("m:c:a:");

$m = isset($opts['m']) ? $opts['m'] : '';
$c = isset($opts['c']) ? $opts['c'] : '';
$a = isset($opts['a']) ? $opts['a'] : 'index';

if (!$m) {
    exit('缺少模块参数： -m module ' . "\n");
}

if (!$c) {
    exit('缺少控制器参数： -c controller ' . "\n");
}

if (!$a) {
    exit('缺少方法参数: -a action ' . "\n");
}

define('MODULE', $m);
define('CONTROLLER', $c);
define('ACTION', $a);

require 'init.php';

/* @var \Application $app*/
$app = new Application($config);
$app->setErrorLevel();
$app->run();
