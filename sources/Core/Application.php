<?php

class Application {

    private $_container;

    protected $middleware = [];

    public function __construct($config) {

        set_error_handler(['Application', 'errorHandler']);
//        set_exception_handler(['Application','exceptionHandler']);
        spl_autoload_register(['Application', 'autoload']);
        register_shutdown_function(['Application', 'registerShutdownFunction']);

//        SeasLog::setBasePath($config['seaslog.basepath']);
//        SeasLog::setLogger($config['seaslog.logger']);

        $this->_route();

        $this->_container = Container::getInstance();

        $this->_container['config'] = $config;                  // 注册配置文件
        $this->_container->set('request',  new Http\Request());  // 注册请求类
        $this->_container->set('response', new \Http\Response());

        $this->_container->singleton('captcha', function($c) {  // 注册验证类
            $config = $c->config->captcha;
            return new \Lib\Captcha\Captcha($config);
        });

        if (isset($config['container.singleton'])) {
            // 注册伪单例组件
            foreach ($config['container.singleton'] as $key => $component) {
                $this->_container->singleton($key, $component);
            }
        }

        if (isset($config['container.normal'])) {
            // 注册普通组件
            foreach ($config['container.normal'] as $key => $component) {
                $this->_container->set($key, $component);
            }
        }

        $this->middleware = [$this];
    }

    /**
     * 控制错误输出
     */
    public function setErrorLevel() {
        if ($this->_container['config']['mode'] == 'development') {
//            error_reporting(-1);
//            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
    }

    /**
     * 路由解析
     */
    private function _route() {
        if (!defined('MODULE') && !defined('CONTROLLER') && !defined('ACTION')) {
            $queryArr = explode('?', getenv('REQUEST_URI'));

            $segmentArr = array_replace(['Home', 'Index', 'index'], array_filter(explode('/', rawurldecode(trim($queryArr[0], '/')))));

            list($c, $m, $a) = array_slice($segmentArr, 0, 3);

            define('MODULE', $c);
            define('CONTROLLER', $m);
            define('ACTION', $a);
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {

        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return ;
        }

        switch ($errno) {
            case E_USER_ERROR:
                echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
                echo "  Fatal error on line $errline in file $errfile";
                echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                echo "Aborting...<br />\n";
                exit(1);
                break;

            case E_USER_WARNING:
                echo "<b>My WARNING</b> [$errno] $errstr  $errfile $errline<br />\n";
                break;

            case E_USER_NOTICE:
                echo "<b>My NOTICE</b> [$errno] $errstr  $errfile $errline<br />\n";
                break;

            default:
                echo "Unknown error type: [$errno] $errstr $errfile $errline<br />\n";
                break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }


    /**
     * @param Exception $exception
     */
    public static function exceptionHandler(\Exception $exception) {

        if ($exception instanceof \Exception\UserException) {
            $response = container('response');

            $response = $response->setStatus($exception->getCode())
                            ->setBody($exception->getMessage())
                            ->setHeader('Content-Type', 'application/json');

            self::response($response);

        } else {

            $exceptionString = $exception->getCode() . " " . $exception->getMessage() . " " . $exception->getFile() . " " . $exception->getLine();

            if (config('mode') == 'development') {
                echo $exceptionString . "<br/> \n";
            }

            //SeasLog::error($exceptionString);
        }
    }

    /**
     * 以下情况 该方法会被调用：
     * 1、当页面被用户强制停止时
     * 2、当程序代码运行超时时
     * 3、当ＰＨＰ代码执行完成时，代码执行存在异常和错误、警告
     */
    public static function registerShutdownFunction() {

    }

    /**
     * 启动
     * @throws Exception
     */
    public function run() {

        $this->middleware[0]->call();

        self::response(container('response'));
    }

    public function addMiddleware(Middleware $newMiddleware) {
        if (in_array($newMiddleware, $this->middleware)) {
            $middleware_class = get_class($newMiddleware);
            throw new \RuntimeException("Circular Middleware setup detected. Tried to queue the same Middleware instance ({$middleware_class}) twice.");
        }

        $newMiddleware->setNextMiddleware($this->middleware[0]);
        // 新的中间件插入数组前端， 优先执行
        array_unshift($this->middleware, $newMiddleware);
    }

    public function call() {
        // 后期看看需不需要直接 require  控制器文件 减少消耗
        $class = 'App\Module\\' . MODULE . '\\Controller\\' . CONTROLLER . 'Controller';

        if (class_exists($class)) {
            $contr = new $class($this->_container);

            if (method_exists($contr, ACTION)) {
                $contr->{ACTION}();
            } else {
                throw new \Exception\UserException("{$class}::{ACTION} method not find！");
            }
        } else {
            throw new \Exception\UserException("{$class} class not find！");
        }
    }

    /**
     * 注册自动加载
     */
    public static function registerAutoloader() {
        spl_autoload_register(['Application', 'autoload']);
    }

    /**
     * 自动加载方法
     * @param $class
     */
    public static function autoload($class) {

        if (isset(self::$classMap[$class])) {
            $filePath = SOURCES_PATH . self::$classMap[$class];
        } else {

            $file = str_replace('\\', DS, $class);
            $topNamespace = substr($file, 0, strpos($file, DS));


            if (defined('MODULE') && (string)$topNamespace == 'App')   // 自动加载app 下的class
            {
                $filePath = APP_PATH .  substr($file, strpos($file, DS)) . '.php';

            }
            elseif ($topNamespace == 'Model')                          // 自动加载公共model下的class
            {
                $filePath = BASE_PATH . 'model' . substr($file, strpos($file, DS)) . '.php';
            }
            else                                                       // 加载source下的class
            {
                $filePath = SOURCES_PATH . $file . '.php';

            }
        }

        if (file_exists($filePath)) {
            require $filePath;
        }
    }

    public static function response($response) {

        list($status, $headers, $body) = $response->finalize();

        if (headers_sent() === false) {
            header(sprintf('HTTP/%s %s', config('http.version'), \Http\Response::getMessageForCode($status)));

            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        echo $body;
    }

    /**
     * 类别名
     */
    public static function classAlias() {
        class_alias('Exception\KernelException', 'KernelException');
        class_alias('Exception\UserException', 'UserException');
    }

    // 核心类类映射 提高效率
    public static $classMap = [
        'Controller' => 'Core/Controller.php',
        'ApiController' => 'Core/ApiController.php',
        'CliController' => 'Core/CliController.php',
        'ViewController' => 'Core/ViewController.php',
        'View' => 'Core/View.php',
        'Container' => 'Core/Container.php',
        'Application' => 'Core/Application.php',
        'Middleware' => 'Core/Middleware.php',
        'MysqliDb' => 'Core/MysqliDb.php',
        'Model' => 'Core/Model.php',
    ];

}