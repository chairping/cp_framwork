<?php

namespace App\Middleware;

use Core\Middleware;

class LoginCheck extends Middleware {

    public function call() {

        if (!$this->whiteList()) {
            $userInfo = '';

            if (isset($_SESSION['user_info'])) {
                $userInfo = $_SESSION['user_info'];
            }

            if (!$userInfo) {
                redirect('Common/Public/loginPage');
            }
        }

        $this->next->call();
    }

    protected function whiteList() {
        $path = MODULE . '/' . CONTROLLER . '/' . ACTION;

        return in_array($path, [
            'Common/Public/loginPage',
            'Common/Public/login',
        ]);
    }

}