<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/11
 * Time: 23:12
 */

namespace Middleware;


use Core\Middleware;

/**
 * 跨域资源请求设置
 * Class Cors
 * @package Middleware
 */
class Cors extends Middleware {

    public function call() {
        
        $config = $this->container['config'];

        if (isset($config['allow_origin']) && !empty($config['allow_origin'])) {
            header('Access-Control-Allow-Origin: ' . $config['allow_origin']);
        }

        header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
        header('Access-Control-Allow-Headers: Content-type');
        //Access-Control-Allow-11Credentials设置为true时，Access-Control-Allow-Origin不能设置为 *,否则COOKIE无法提交
        header('Access-Control-Allow-Credentials: true');
        
        $this->next->call();
    }
}