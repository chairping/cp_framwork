<?php

namespace Middleware;
use Exception\UserException;
use Lib\Contracts\UserLoginRecordInterface;

/**
 * 身份验证
 * Class Auth
 * @package Middleware
 */
class BaseAuth extends \Middleware {


    public function call() {

    }

    /**
     * 获取token
     * @use
     *    请求方式：POST
     *    请求url： http://api.wy.cn/getToken
     *    参数：
     *        grant_type=password 验证类型
     *        client_id=testclient          客户端id
     *        client_secret=testpass        客户端密码
     *        username=testpass             登录用户名称
     *        password=testpass             登录用户密码
     *    返回正确结果e.g：
     *    {
     *         "access_token":"b76940abfa9e69958dc757ab8a9d204386034790",
     *         "expires_in":7200,
     *         "token_type":"Bearer",
     *         "scope":null,
     *         "refresh_token":"e41e9270236f69bf49326444b4ad1f469ed0d8d3"
     *    }
     *
     *    返回错误结果e.g:
     *    {
     *        "error":"invalid_grant",           // 授权验证失败
     *        "error_description":"Invalid username and password combination" // 用户 密码错误
     *    }
     *
     *    {
     *        "error":"invalid_client",         // 授权验证失败
     *        "error_description":"The client credentials are invalid"  // client信息有误
     *    }
     *
     *    {
     *        "error":"unsupported_grant_type", // 该授权类型不支持
     *        "error_description":"Grant type \"passord\" not supported"
     *    }
     */
    public function getToken($oauth2) {

        /* @var \OAuth2\Response $response*/
        $response = $oauth2->handleTokenRequest(\OAuth2\Request::createFromGlobals());

        $loginRecord = $this->container->loginRecord; // 获取登录日志记录组件
        if ($loginRecord) {
            if ($loginRecord instanceof  UserLoginRecordInterface) {

                /* @var \OAuth2\GrantType\UserCredentials $userCredentials */
                $userCredentials = $oauth2->getGrantType('password');
                $userInfo = $userCredentials->getUserInfo();


                $accessToken = $response->getParameters();
                $stausCode = $response->getStatusCode();

                if ($stausCode != 200) {

                    if ($accessToken['error_description'] == 'Invalid username and password combination') {
                        $accessToken['error_description'] = "密码或账号错误";
                    } elseif ($accessToken['error_description'] == 'Missing parameters: "username" and "password" required') {
                        $accessToken['error_description'] = "账号或密码不能为空";
                    }

                    throw new UserException($accessToken['error_description'], $stausCode);
                }


                $loginRecord->success($userInfo, $accessToken); // 成功后记录信息
            } else {
                throw new UserException(get_class($loginRecord) . '必须继承' .  UserLoginRecordInterface::class);
            }
        }

        throw new UserException(json_encode(['data' => [
            'userInfo' => $userInfo,
            'token' => $accessToken
        ]]), $stausCode);
    }

    public function verifyToken($oauth2, $config) {
        $userInfo = \MysqliDb::getInstance()->rawQueryOne('SELECT * from user WHERE id = ? LIMIT 1', [1]);
        $this->container['loginUser'] = $userInfo;
        return ;
        $whiteList = isset($config['oauth.white_list']) ? $config['oauth.white_list'] : [];

        $pathInfo = trim(strtolower($this->container['request']->pathInfo), '/');

        //  白名单无需access_token 即可访问
        if (!in_array($pathInfo, $whiteList)) {
            /* @var \OAuth2\Server $oauth2 */
            if(!$oauth2->verifyResourceRequest(\OAuth2\Request::createFromGlobals())) {
                exit(json_encode(array('code' => 999, 'msg' => 'token无效或者已过期')));
            } else {
                $token = $oauth2->getResourceController()->getToken();

                $currentUserTable = $config['oauth.user_table'];

                $userInfo = \MysqliDb::getInstance()->rawQueryOne('SELECT * from ' . $currentUserTable . ' WHERE id = ? LIMIT 1', [$token['user_id']]);

                if ($userInfo['token'] != $token['access_token']) {
                    throw new UserException("您已在其他地方登录", 401);
                }

                if (isset($userInfo['password'])) {
                    unset($userInfo['password']);
                }

                $this->container['loginUser'] = $userInfo;
            }
        }
    }

    /**
     * 获取oauth2.0 服务器
     * @return \OAuth2\Server
     */
    public function getServer($config) {

        $db = $config['db'];
        $dsn = sprintf('mysql:dbname=%s;host=%s', $db['database'], $db['host']);

        $username = $db['user'];
        $password = $db['pwd'];

        $storage = new \Lib\OAuth2UserPdo(
            ['dsn' => $dsn, 'username' => $username, 'password' => $password],
            ['user_table' => $config['oauth.user_table']]
        );

        $server = new \OAuth2\Server($storage, [
            'refresh_token_lifetime' => $config['oauth.refresh_token_lifetime'],
            'access_lifetime' => $config['oauth.access_lifetime'],
        ]);

        $server->addGrantType(new \OAuth2\GrantType\UserCredentials($storage));
        $server->addGrantType(new \OAuth2\GrantType\RefreshToken($storage), [
            'always_issue_new_refresh_token' => $config['oauth.always_issue_new_refresh_token'],
        ]);

        return $server;
    }

}