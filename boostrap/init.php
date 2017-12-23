<?php
//  入口初始化
define('DS', DIRECTORY_SEPARATOR);
// 网站基本路径
define('BASE_PATH', dirname(__DIR__) . DS);
// 应用路径
define('APP_PATH', BASE_PATH . APP_NAME . DS);
// 框架资源路径
define('SOURCES_PATH', BASE_PATH . 'sources' . DS);

$config = [];

// 加载公共配置文件
require BASE_PATH . "source/config.php";

// 加载app应用配置文件 app配置文件优先于公共配置文件  所以在相同key的情况下 app配置文件会覆盖公共配置文件
foreach (glob(APP_PATH . 'config/*.php') as $configFile) {
    require $configFile;
}
