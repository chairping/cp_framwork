<?php

namespace App\Module\Home\Controller;

use Exception\UserException;
use Model\Config;
use Model\IpDistrict;

class UserController extends \ApiController {
    /**
     * 用户注册
     * @throws \AppException
     */
    public function register() {
        $post = $this->post();
        /* @var \Lib\Validator $validator */
        $validator = $this->validator
            ->setFields($post, ['username', 'password', 'real_name', 'phone'])
            ->labels([
                'username' => '登录账户',
                'password' => '登录密码',
                'real_name' => '真实姓名',
                'phone' => '手机号',
            ]);

        $validator->rule('required', ['username', 'password', 'real_name', 'phone']);
        $validator->rule('lengthBetween', 'username', 4, 20);
        $validator->rule('alphaNum', 'username');

        $validator->rule('lengthBetween', 'password', 4, 20);
        $validator->rule('alphaNum', 'password');

        $validator->rule('chinese', 'real_name');
        $validator->rule('phone', 'phone');

        $validator->addRule('usernameUnique', function ($field, $value) {
            $id = \Model\User::where('username', $value)->first('id');
            return $id ? false : true;
        }, '账号已被注册');

        $validator->rule('usernameUnique', 'username');

        $validator->validateWithTry();

        $data = $validator->getData();

        $user = new \Model\User();
        $status = $user->register($data);

        if ($status) {
            $this->output(['message' => '用户注册成功']);
        } else {
            $this->output(['message' => '注册失败，请重试'], UserException::OPERATION_FAIL);
        }
    }

    public function editInfo() {
        $post = $this->post();
        /* @var \Lib\Validator $validator */
        $validator = $this->validator
            ->setFields($post, ['password', 'real_name', 'phone', 'brank_name', 'brand_account'])
            ->labels([
                'password' => '密码',
                'real_name' => '姓名',
                'phone' => '手机号',
                'brank_name' => '银行卡所属名称',
                'brand_account' => '银行卡账号',
            ]);

        $validator->rule('required', ['password', 'real_name', 'phone', 'brank_name', 'brand_account']);
        $validator->rule('lengthBetween', 'password', 4, 20);
        $validator->rule('alphaNum', 'password');

        $validator->rule('chinese', 'real_name');
        $validator->rule('phone', 'phone');

        $validator->rule('chinese', 'brank_name');
        $validator->rule('numeric', 'brand_account');

        $validator->addRule('brank_name_check', function ($field, $value) {
            $result = \Model\User::find($id);
            if (!empty($result['brank_name'])) {
                return false;
            }
            return true;
        }, '银行卡信息需要联系客服修改');
        $validator->rule('brank_name_check', 'brank_name');

        $validator->addRule('brank_account_check', function ($field, $value) {
            $result = \Model\User::find($id);
            if (!empty($result['brand_account'])) {
                return false;
            }
            return true;
        }, '银行卡信息需要联系客服修改');
        $validator->rule('brank_account_check', 'brand_account');

        $validator->validateWithTry();
        $data = $validator->getData();
        $data['update_time'] = time();

        $user = new \Model\User();
        $status = $user->where("id", $id)->update($data);
        if ($status >= 0) {
            $this->output(['message' => '修改成功']);
        } else {
            $this->output(['message' => '修改失败，请重试'], UserException::OPERATION_FAIL);
        }
    }

    public function editPwd() {
        $post = $this->post();
        /* @var \Lib\Validator $validator */
        $validator = $this->validator
            ->setFields($post, ['oldPwd', 'newPwd', 'rePwd'])
            ->labels([
                'oldPwd' => '旧密码',
                'newPwd' => '新密码',
                'rePwd' => '确认新密码',
            ]);
        $validator->rule('required', ['oldPwd', 'newPwd', 'rePwd');
        $validator->rule('lengthBetween', 'oldPwd', 4, 20);
        $validator->rule('alphaNum', 'oldPwd');
        $validator->rule('lengthBetween', 'newPwd', 4, 20);
        $validator->rule('alphaNum', 'newPwd');

        $validator->rule('equals', array("newPwd", "rePwd"));
        $validator->validateWithTry();
        $data = $validator->getData();

        $pwd = [
            'password' => password_hash($data['newPwd'], PASSWORD_DEFAULT),
            'update_time' => time(),
        ];

        $user = new \Model\User();
        $status = $user->where("id", $id)->update($pwd);
        if ($status >= 0) {
            $this->output(['message' => '修改成功']);
        } else {
            $this->output(['message' => '修改失败，请重试'], UserException::OPERATION_FAIL);
        }
    }

    public function getCaptcha() {
        $this->captcha->generate();
    }


    /**
     * 用户ip 地域设置
     */
    public function setIpDistrict() {
        $ips = $this->post('ips', '');
        $districts = $this->post('districts', '');

        if ($ips) {

            $ips = explode(' ', $ips);
            $ipsFilter = array_filter($ips, function($ip) {
                return filter_var($ip, \FILTER_VALIDATE_IP);
            });

            $errIps = array_diff($ips, $ipsFilter);

            if (!$errIps) {
                throw new UserException("ip格式不正确，请检测该ip地址:" . implode(' ', $errIps));
            }

            $ips = implode(' ', $ipsFilter);
        }

        $districts = xssFilter($districts);
        if ($districts) {
            // 去除过多的空格
            $districts = preg_replace('/\s+/', ' ', $districts);
        }
    public function verifyCaptcha() {
        \SeasLog::info('ssdfdfdffsf', [], 'xx');
    }
        if (!$ips && !$districts) {
            throw new UserException("ip 或 地域设置不能为空");
        }

        $config = new Config();

        if($ips) {
            $result = $config->insert([
                'title' => '用户ip限制设置',
                'key' => 1 . $this->loginUser['username'] . Config::IP_LIMIT,
                'val' => $ips
            ]);

            if (!$result) {
                $code = UserException::OPERATION_FAIL;
                $data = ['message' => '设置失败， 请重试'];
            }
        }

        if ($districts) {
            $result = $config->insert([
                'title' => '用户ip限制设置',
                'key' => 1 . $this->loginUser['username'] . Config::DISTRICT_LIMIT,
                'val' => $districts
            ]);

            if (!$result) {
                $code = UserException::OPERATION_FAIL;
                $data = ['message' => '设置失败， 请重试'];
            }
        }

        $code = 200;
        $data = ['message' => 'ip、地域设置成功'];

        $this->output($data, $code);
    }
}