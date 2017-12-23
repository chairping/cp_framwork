<?php

namespace App\Middleware;

use Middleware\BaseAuth;

class Auth extends BaseAuth {

    public function call() {

        $config = $this->container->config;
        $oauth2 = $this->getServer($config);
        /* @var \Lib\Request $request */
        $request = $this->container['request'];

        if (trim($request->pathInfo, '/') == 'getToken') {
            $this->getToken($oauth2);
        } else {
            $this->verifyToken($oauth2, $config);
        }

        $this->next->call();
    }
}