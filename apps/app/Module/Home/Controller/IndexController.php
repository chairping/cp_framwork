<?php
namespace App\Module\Home\Controller;

class IndexController extends \ViewController{

    public function index() {

//	   $province = new \App\Model\ProvinceModel();

	   /* $test = $province->find(1); */
		
     //  $redis = \Lib\Redis::getInstance();
        var_dump('xx');

       $this->display();
    }

    public function test(){
        echo 'test';
    }
}
