<?php
namespace Core;

class ViewController extends Controller {

    private $_view = null;
    private $layout = 'Layout';
    private $viewPath = '';

    public function __construct() {
        parent::__construct();

        $this->_view = new View(MODULE);
        $this->viewPath = APP_PATH . 'Module' . DS . MODULE . DS . 'View' . DS;
    }

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    public function getLayout() {
        return $this->layout;
    }

    public function display($data = [], $tplName = '') {

        if (!$tplName) {
            $tplName = ACTION;
        }

        if($this->layout) {
            $layout = new View(MODULE);
            $content = $this->_view->loadView($this->viewPath . CONTROLLER . DS . $tplName . '.php', $data);
            $data['__content__'] = $content;
            $data['__viewpath__'] = $this->viewPath;
            echo $layout->loadView($this->viewPath . $this->layout . DS . 'Layout.php', $data);
        } else {
            echo $this->_view->loadView($this->viewPath . CONTROLLER . DS . $tplName . '.php', $data);
        }

        exit;
    }
}
