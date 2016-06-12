<?php

namespace App\Middleware;
/**
 * 网站维护检测
 * Class Maintenance
 * @package App\Middleware
 */
class Maintenance extends \Middleware {

    public function call() {

//        if (true) {
//            echo $this->template('网站处于维护', '网站处于维护');
//        }
//        exit;

        $this->next->call();
    }

    public function template($title, $body) {
        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>", $title, $title, $body);
    }

}