<?php
namespace Core;

class ViewController extends Controller {

    private $_view = null;

    public function __construct() {
        parent::__construct();

        $this->_view = new View(MODULE);
    }

    public function display($data = [], $tplName = '') {

        if (!$tplName) {
            $tplName = ACTION;
        }

        echo $this->_view->loadView(APP_PATH . 'Module' . DS . MODULE . DS . 'View' . DS . CONTROLLER . DS . $tplName . '.php', $data);
        exit;
    }



}