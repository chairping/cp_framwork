<?php

namespace Core;

class Controller {

    private $_container;

    public function __construct() {
        $this->_container = Container::getInstance();

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    public function __get($className) {
        return $this->_container->$className;
    }

    /**
     * 获取get请求数据
     * @param null $key
     * @param string $default
     * @return string
     */
    public function query($key = null, $default = '') {
        $request = $this->_container['request'];

        if ($key == null) {
            return $request->get;
        }

        if (isset($request->get[$key])) {
            return $request->get[$key];
        }

        return $default;
    }

    /**
     * 获取post请求数据
     * @param null $key
     * @param string $default
     * @return string
     */
    public function post($key = null, $default = '') {
        $request = $this->_container['request'];

        if ($key == null) {
            return $request->post;
        }

        if (isset($request->post[$key])) {
            return $request->post[$key];
        }

        return $default;
    }

    public function output($data, $status = 200, $headers = ['Content-type' => 'application/json']) {

        $response = $this->response->setStatus($status)->setBody(json_encode($data, JSON_UNESCAPED_UNICODE));

        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }
    }
}