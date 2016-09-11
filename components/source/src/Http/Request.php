<?php

namespace Http;

class Request {

    public $url;
    public $method;
    public $referrer;
    public $ip;
    public $get;
    public $post;
    public $cookies;
    public $pathInfo;

    public function __construct($config = null) {
        if ($config === null) {
            $pathInfo = '';
            $requestUri = '/';

            if (PHP_SAPI != 'cli') {
                $scriptName = $_SERVER['SCRIPT_NAME']; // <-- "/foo/index.php"
                $requestUri = $_SERVER['REQUEST_URI']; // <-- "/foo/bar?test=abc" or "/foo/index.php/bar?test=abc"
                $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''; // <-- "test=abc" or ""

                $physicalPath = str_replace('\\', '', dirname($scriptName)); // nginx 重写部分的目录路径

                $pathInfo = substr_replace($requestUri, '', 0, strlen($physicalPath)); // 去除物理路径
                $pathInfo = str_replace('?' . $queryString, '', $pathInfo); // 去除get请求参数

                $pathInfo = '/' . ltrim($pathInfo, '/');
            }

            $rawInput = @file_get_contents('php://input');

            \mb_parse_str($rawInput, $post);

            $config = [
                'get' => $_GET,
                'post' => $post,
                'cookies' => $_COOKIE,
                'method' => getenv('REQUEST_METHOD') ?: 'GET',
                'ip' => $this->getProxyIpAddress(),
                'pathInfo' => $pathInfo,
                'url' => $requestUri ?: '/',
                'referrer' => getenv('HTTP_REFERER') ?: '',
            ];
        }

        $this->init($config);
    }

    public function getRequest() {
        return array_merge($this->get, $this->post);
    }

    public function getUrl() {
        return $this->url;
    }

    /**
     * 请求数据 可用于单元测试
     * @param array $properties
     */
    public function init($properties = []) {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    public function isPost() {
        return $this->method === 'POST';
    }

    public function isGet() {
        return $this->method === 'GET';
    }

    public function isAjax() {
        return getenv('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
    }

    /**
     * 获取真实的远程ip地址
     * @return string IP address
     */
    private function getProxyIpAddress() {
        static $forwarded = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($forwarded as $key) {
            if (array_key_exists($key, $_SERVER)) {
                sscanf($_SERVER[$key], '%[^,]', $ip);
                if (filter_var($ip, \FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
        return '';
    }
}