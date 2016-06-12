<?php

namespace Middleware;

/**
 * ip检测
 * Class IpCheck
 * @package Middleware
 */
class IpCheck extends \Middleware
{
    public function call() {



        $this->next->call();
    }
}