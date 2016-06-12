<?php

abstract class Middleware {

    public $container;

    protected $next;

    public function __construct() {
        $this->container = Container::getInstance();
    }

    public function setNextMiddleware($nextMiddleware)
    {
        $this->next = $nextMiddleware;
    }

    public function getNextMiddleware() {
        return $this->next;
    }

    abstract public function call();
}