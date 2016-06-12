<?php

namespace App\Module\Bet\Controller;

class SettleController extends \Controller {

    public function settle() {
        $opts = getopt("m:c:a:f:");

        $f = $opts['f'];

        $this->{$f}();
    }

    /**
     * 篮球
     */
    public function test() {
        if (single_process(get_current_command(), APP_PATH . 'Log/test.pid')) {

        }

        echo "sleep 15s \n";
        sleep(15);
    }

}