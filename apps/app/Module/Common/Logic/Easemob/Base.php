<?php


class Base {

    protected $_URL = 'https://a1.easemob.com/';

    protected $_TOKEN = '';

    protected $_CLIENT_ID = '';
    protected $_CLIENT_SECRET = '';

    protected $_ORM_NAME = '';
    protected $_APP_NAME = '';

    /**
     * @desc 发送请求
     * @param $url     请求地址
     * @param $params  请求参数
     * @param string $action  请求方式 default 'post'
     * @param int $header   头部信息
     * @return bool
     */
    protected function sendRequest($url, $params = array(), $action = 'POST', $header = 0) {

        $header = array_merge((array)$header, array("Authorization:Bearer {$this->_TOKEN}"));

        $i = 0;
        while ($i < 3) {
            $ret = $this->_curl($url, $params, $action, $header);
            if (!$ret['status']) {

                if ($this->_dealAfterFailRequest($ret['http_code'])) {
                    $i++;
                } else {
                    return false;
                }

            } else {
                return $ret['result'];
                break;
            }
        }

        $this->_log('EasemobLogic', $ret['result']);
        return false;
    }

    /**
     * @desc    请求环信接口来获取token值
     * @return \Ambigous|mixed
     */
    protected function _getTokenByRequest() {
        $url = $this->createUrl('token');

        $params = array(
            "grant_type" =>  "client_credentials",
            "client_id" =>  $this->_CLIENT_ID,
            "client_secret" => $this->_CLIENT_SECRET,
        );

        $i = 0;
        while ($i < 3) {
            $result = $this->_curl($url, $params, 'POST');
            if (!$result['status']) {
                $i++;
            } else {
                return $result['result'];
                break;
            }
        }

        $this->_log('EasemobLogic', $result['result']);

        return false;
    }

    /**
     * @desc  生成url
     * @param string $action
     * @return string
     */
    protected function buildUrl($action = '') {
        return $this->_URL . $this->_ORM_NAME . '/' . $this->_APP_NAME . '/' . $action;
    }

    /**
     * @desc    初始化token
     * @return bool|string
     */
    protected function _initToken() {
        if (!$this->_TOKEN) {

            $key = $this->_ORM_NAME . '_' . $this->_APP_NAME;
            if ($token = $this->_tokenByMemc($key)) {
                $this->_TOKEN = $token;
            } else {
                $result = $this->_getTokenByRequest();
                // 过期时间提早20s 防止过期请求的刚好token过期情况发生
                if ($result) {
                    $this->_tokenByMemc($key, $result['access_token'], $result['expires_in'] - 20);
                    $this->_TOKEN = $result['access_token'];
                } else {
                    $this->_TOKEN = false;
                }
            }
        }

        return $this->_TOKEN;
    }

    /**
     * @desc 设置或获取token（memcache）
     *       只传key 则表示获取token  三个参数都传则设置token
     *
     * @param $key
     * @param string $value  token值
     * @param string $expir  过期时间 单位为秒
     *
     * @return bool|string|array
     *      array   获取token时返回值
     *          @var $access_token 访问token
     *          @var $expires_in   过期时间
     *          @var $application  etc.  7f612690-d2be-11e4-95a3-7deb3e16c5c7
     *      bool    设置token时返回值
     */
    protected function _tokenByMemc($key, $value = '', $expir = '') {

        if ($key) {
            if ($value && $expir) {
                return \V4\Core\Memc::set($key, $value, $expir);
            } else {
                return \V4\Core\Memc::get($key);
            }
        }

        return '';
    }


    /**
     * @desc 记录失败信息
     * @param $identity
     * @param $content
     */
    protected function _log($identity, $content) {
        $functionName = $this->_CALLABLE_INFO['functionName'];
        unset($this->_CALLABLE_INFO['functionName']);

        $content = array_merge($this->_CALLABLE_INFO, (array)$content);
        $content['time'] = date("Y-m-d H:i:s");

        \V4\Core\Log::add(array(
            'identity' => $identity . ':' . $functionName,
            'content' => json_encode($content),
        ));
    }

    /**
     * @desc 保存当前调用的方法 和 参数
     * @param $functionName
     * @param array $params
     */
    protected function _setCallableInfo($functionName, $params = array()) {
        $this->_CALLABLE_INFO = array(
            'functionName' => $functionName,
            'params' => (array)$params
        );
    }

    /**
     * @desc  curl 请求
     * @param $url
     * @param array $params
     * @param string $type
     * @param int $header
     * @return array
     */
    protected function _curl($url, $params = array(), $type = "POST", $header = array()) {

        array_push($header, 'Accept:application/json');
        array_push($header, 'Content-Type:application/json');

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在

        switch ($type){
            case "GET" :
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            case "PUT" :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }

        if (count($params) > 0) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));  // Post提交的数据包
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $result = curl_exec($curl); // 执行操作
        $info = curl_getinfo($curl);
        curl_close($curl); // 关闭CURL会话

        $status = true;
        if ($result !== false) {
            $result = json_decode($result, true);
            if ($info['http_code'] != 200) {
                $result['http_code'] = $info['http_code'];
                $status = false;
            }
        } else {
            $status = false;
            $result = array(
                'error' => "request fail"
            );
        }

        return array(
            'status' => $status,
            'result' => $result,
        );
    }

    /**
     * @desc  请求后的后续处理
     * @param $httpCode
     * @return bool
     */
    private function _dealFailRequest($httpCode) {
        switch ((int)$httpCode) {
            case 400: // （错误请求）服务器不理解请求的语法。
            case 403: // （禁止） 服务器拒绝请求。
            case 404: // （未找到） 服务器找不到请求的接口。
            case 408: // （请求超时） 服务器等候请求时发生超时。
            case 501: // （尚未实施） 服务器不具备完成请求的功能。 例如，服务器无法识别请求方法时可能会返回此代码。
                $status = false;
                break;
            case 401: // （未授权） 请求要求身份验证。 对于需要token的接口，服务器可能返回此响应。
                // 可能存在刚好访问时 token失效
                $this->_initToken();
                $status = true;
                break;
            case 502: // （错误网关） 服务器作为网关或代理，从上游服务器收到无效响应。
            case 500: // （服务器内部错误） 服务器遇到错误，无法完成请求。
            case 504: // （网关超时） 服务器作为网关或代理，但是没有及时从上游服务器收到请求。
                $status = true;
                break;
            case 503: // （服务不可用） 请求接口超过调用频率限制, 即 接口被限流.

                $mail = new PhpMailer();
                $mail->IsSMTP();
                $mail->Host = "smtp.exmail.qq.com";
                $mail->SMTPAuth = true;
                $mail->Username = "code@273.cn";        // 用户名
                $mail->Password = "t:vPpTgx6A";         // 密码
                $mail->From = "code@273.cn";            // 发信人
                $mail->FromName = "273";                // 发信人别名
                $mail->CharSet = "UTF-8";
                $mail->AddAddress('chenping@273.cn');
                $mail->WordWrap = 50;
                $mail->IsHTML(true);                    // 以html方式发送
                $mail->Subject = '接口被限流';             // 邮件标题
                $mail->Body = '<pre>' . var_export($this->_CALLABLE_INFO, true) . '</pre>';  // 邮件内空
                $mail->AltBody = "请使用HTML方式查看邮件。";
                $mail->send();

                $status = true;
                break;
            default:
                $status = true;
                break;
        }

        return $status;
    }

}