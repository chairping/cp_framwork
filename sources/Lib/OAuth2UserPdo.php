<?php

namespace Lib;

use OAuth2\Storage\Pdo;

/**
 * Class Oauth2UserPdo
 * �Զ��� getUser �� checkPassword ����
 * @package Lib
 */
class OAuth2UserPdo extends Pdo {

    public function getUser($username) {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', $this->config['user_table']));
        $stmt->execute(array('username' => $username));

        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        // the default behavior is to use "id" as the user_id
        return array_merge(array(
            'user_id' => $userInfo['id']
        ), $userInfo);
    }

    protected function checkPassword($user, $password)
    {
        return password_verify($password, $user['password']);
    }
}