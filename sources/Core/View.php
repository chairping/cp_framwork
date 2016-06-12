<?php

class View {

    protected $viewName;

    public function __construct() {
        
    }

    public function loadView($viewName, $data) {

        if (!$viewName) {
            return '';
        }

        ob_start();

        if (is_array($data)) {
            extract($data, EXTR_SKIP);
        }

        try {
            require $viewName;
        } catch(Exception $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

}