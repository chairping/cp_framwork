<?php
use App\Model\UserModel;

class PublicController extends \ViewController{

    /**
     * 登录
     */
    public function login() {

        $username = $this->post('username');
        $password = $this->post('password');

        if ($username) {

        }

        if ($password) {

        }

        $userInfo = UserModel::where('user_name', $username)->first();

        if ($userInfo) {

            $_SESSION['user_info'] = $userInfo;

            redirect('Home/Index/index');
        } else {
            redirect('Common/Public/loginPage');
        }
    }

    /**
     * 登录页面
     */
    public function loginPage() {
        $this->display();
    }

    /**
     * 注销
     */
    public function loginout() {
        unset($_SESSION['user_info']);
        redirect('Common/Public/loginPage');
    }

}
