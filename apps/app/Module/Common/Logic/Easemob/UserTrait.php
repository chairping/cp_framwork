<?php
/**
 * @desc   用户集成管理
 */

namespace App\User\Logic\HuanXin\Action;


trait UserTrait
{
    /**
     * @desc    环信注册接口
     * @param   stirng $username    用户名（用户id）
     * @param   string $passsword   密码
     * @param   string $nickname    昵称
     * @return array
     */
    public function register($username, $passsword, $nickname = '', $msg = "老板好，小秘终于等到您啦~欢迎来到车人脉，小秘陪您边交朋友边做买卖^_^ \n重磅推荐：【消息-通讯录-服务号】中可以查出险记录和查4s店维修记录哈~") {

        $params = array(
            'username' => $username,
            'password' => $passsword,
            'nickname' => $nickname,
        );
        $this->_setCallableInfo(__FUNCTION__, $params);

        $url = $this->createUrl('users');
        if ($this->sendRequest($url, $params) !== false) {
            return $this->sendMessage(array(
                "target_type" => "users","target" => array($username),
                "msg" => array (
                    "type" => "txt",
                    "msg" => $msg
                ),
                "from" => \App\User\Logic\FriendsLogic::$mi,
            ));
        }
    }

    /**
     * @desc  获取环信用户信息
     * @param $username
     * @return array
     */
    public function getUserInfo($username) {
        $params = '';
        $this->_setCallableInfo(__FUNCTION__, $params);

        $url = $this->createUrl('users/' . $username);
        return $this->sendRequest($url, $params, 'GET');
    }

    /**
     * @desc  添加好友关系
     * @param $username
     * @param $friedName
     * @return \Ambigous
     */
    public function addFriend($username, $friedName) {
        $this->_setCallableInfo(__FUNCTION__, array('username' => $username, 'friedName' => $friedName));

        $url = $this->createUrl('users/' . $username . '/contacts/users/' . $friedName);
        return $this->sendRequest($url, array(), 'POST');

    }

    /**
     * @desc  解除好友关系
     * @param $username
     * @param $friedName
     * @return \Ambigous
     */
    public function deleteFriend($username, $friedName) {

        $this->_setCallableInfo(__FUNCTION__, array('username' => $username, 'friedName' => $friedName));

        $url = $this->createUrl('users/' . $username . '/contacts/users/' . $friedName);
        return $this->sendRequest($url, array(), 'DELETE');
    }

    public function updateUsername($id, $nickname) {

        $this->_setCallableInfo(__FUNCTION__, $id);

        $url = $this->createUrl('users/'. $id);
        return $this->sendRequest($url, array('nickname' => $nickname), 'PUT');

    }
}