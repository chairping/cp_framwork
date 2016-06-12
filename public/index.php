<?php
ini_set('always_populate_raw_post_data', -1);
//Access-Control-Allow-Credentials设置为true时，Access-Control-Allow-Origin不能设置为 *,否则COOKIE无法提交
//if (isset($_SERVER['HTTP_REFERER']) && strpos('|' . $_SERVER['HTTP_REFERER'], '') == 1) {
//    header('Access-Control-Allow-Origin: http://ht.local');
//} else {
    header('Access-Control-Allow-Origin: http://www.wy.cn');
//}
header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
header('Access-Control-Allow-Headers: Content-type');

header('Access-Control-Allow-Credentials: true');


#xhprof_enable();
#xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
#xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

define('APP_NAME', 'app'); // app 类型

/* @var Application $app */
$app = require '../boostrap/app_start.php';

$app->addMiddleware(new \App\Middleware\Maintenance());  // 检测网站是否开启维护

$app->run();

// stop profiler
#$xhprof_data = xhprof_disable();

#$XHPROF_ROOT = BASE_PATH . '..' . DS . 'xhporf' . DS . 'xhprof_lib' . DS;

#include_once $XHPROF_ROOT . "utils/xhprof_lib.php";
#include_once $XHPROF_ROOT . "utils/xhprof_runs.php";

#$xhprof_runs = new XHProfRuns_Default();
#$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");

exit();




