<?php

/**
 * @param string $name
 *          1. 有效值则获取容器里面的内容
 *          2. 默认获取容器实例
 * @return Container|null
 */
function container($name = '') {
    return Container::getInstance($name);
}

/**
 * @param null $key
 *          1. 默认获取整个配置信息
 *          2. 有效值则获取制定配置信息
 * @return null
 */
function config($key = null) {

    if ($key) {
        return container()->config[$key];
    }

    return container()->config;
}

/**
 * 路由重定向
 * @param string $to
 */
function redirect($to = '') {
    $hostname = $_SERVER['HTTP_HOST'];
    // location 要加http头 否则会失效
    header('Location:'. 'http://'.$hostname . '/'. trim($to, '/'));
    exit;
}

function sendMail($data) {
    static $mail;

    if ($mail == null) {
        $mail = new \Lib\Email\PhpMailer;
        $mail->IsSMTP();                        // 经smtp发送
        $mail->Host = "smtp.exmail.qq.com";     // SMTP 服务器
        $mail->SMTPAuth = true;                 // 打开SMTP 认证
        $mail->Username = "";        // 用户名
        $mail->Password = "";         // 密码
        $mail->From = "";            // 发信人
        $mail->FromName = "";                // 发信人别名
        $mail->Charset = 'UTF-8';
        $mail->WordWrap = 50;
        $mail->AltBody = "请使用HTML方式查看邮件。";
        $mail->IsHTML(true);                    // 以html方式发送
    }

    $tomail  = $data['tomail'];
    $subject = $data['subject'];
    $body    = $data['body'];
    $ccmail  = $data['ccmail'];
    $bccmail = $data['bccmail'];

    $mail->AddAddress($tomail);             // 收信人

    if (!empty($ccmail)) {
        $mail->AddCC($ccmail);              // cc收信人
    }
    if (!empty($bccmail)) {
        $mail->AddCC($bccmail);             // bcc收信人
    }
    $mail->Subject = $subject;              // 邮件标题
    $mail->Body = $body;                    // 邮件内空

    //出错返回错误信息 返回true为邮件发送成功 无论成功失败需要增加log功能
    $return = true;
    if (!$mail->send()) {
        $return = $mail->ErrorInfo;
    }

    return $return;
}

/**
 * 命名风格切换
 * @param $name
 * @param int $type
 * @return string
 */
function parse_name($name, $type=0) {
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function($match){return strtoupper($match[1]);}, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}


/**
 * xss 过滤
 * @param $string
 * @return mixed|string
 */
function xssFilter($string) {

    $string = str_replace(array("&amp;","&lt;","&gt;"),array("&amp;amp;","&amp;lt;","&amp;gt;"),$string);
    // fix &entitiy\n;
    $string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"$1;",$string);
    $string = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"$1$2;",$string);
    $string = html_entity_decode($string, ENT_COMPAT, "UTF-8");

    // remove any attribute starting with "on" or xmlns
    $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $string);

    // remove javascript: and vbscript: protocol
    $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $string);
    $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $string);
    $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu', '$1=$2nomozbinding...', $string);
    $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $string);

    //remove any style attributes, IE allows too much stupid things in them, eg.
    //<span style="width: expression(alert('Ping!'));"></span>
    // and in general you really don't want style declarations in your UGC

    $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
    $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
    $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $string);


    //remove namespaced elements (we do not need them...)
    $string = preg_replace('#</*\w+:\w[^>]*>#i',"",$string);
    //remove really unwanted tags

    do {
        $oldstring = $string;
        $string = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i',"",$string);
    } while ($oldstring != $string);

    return $string;
}

/**
 * 调试打印函数
 */
function dd() {
    array_map(function ($x) {
        var_dump($x);
    }, func_get_args());
    die();
}

/**
 * 获取最后一条sql语句
 */
function lastQuery() {
    var_dump(MysqliDb::getInstance()->getLastQuery());
}

function curlMulti($urls, $referer) {
    $queue = curl_multi_init();
    $map = array();

    foreach ($urls as $key => $url) {
        // create cURL resources
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0");
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_REFERER, $referer);

        // add handle
        curl_multi_add_handle($queue, $ch);
        $map[$key] = $ch;
    }

    $active = null;

    // execute the handles
    do {
        $mrc = curl_multi_exec($queue, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active > 0 && $mrc == CURLM_OK) {
        if (curl_multi_select($queue, 0.5) != -1) {
            do {
                $mrc = curl_multi_exec($queue, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $responses = array();
    foreach ($map as $key=>$ch) {
        $responses[$key] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($queue, $ch);
        curl_close($ch);
    }

    curl_multi_close($queue);
    return $responses;
}

/**
 * 保证单进程
 *
 * @param string $process_ame 进程名
 * @param string $pid_file 进程文件路径
 * @return boolean 是否继续执行当前进程
 */
function single_process($process_name, $pid_file) {
    if (file_exists($pid_file) && $fp = @fopen($pid_file,"rb")) {
        flock($fp, LOCK_SH);
        $last_pid = fread($fp, filesize($pid_file));
        fclose($fp);
        if (!empty($last_pid)) {
            $command = exec("/bin/ps -p $last_pid -o command=");
            if ($command == $process_name) {
                return false;
            }
        }
    }
    $cur_pid = posix_getpid();

    if ($fp = @fopen($pid_file, "wb")) {
        fputs($fp, $cur_pid);
        ftruncate($fp, strlen($cur_pid));
        fclose($fp);
        return true;
    }
    else {
        return false;
    }
}

/**
 * 获取当前进程对应的Command
 *
 * @return string 命令及其参数
 */
function get_current_command() {
    $pid = posix_getpid();
    $command = exec("/bin/ps -p $pid -o command=");
    return $command;
}

function xhprofEnable() {
    
    xhprof_enable();
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

}

function xhprofDisable() {
    // stop profiler
    $xhprof_data = xhprof_disable();

    $XHPROF_ROOT = BASE_PATH . '..' . DS . 'xhporf' . DS . 'xhprof_lib' . DS;

    include_once $XHPROF_ROOT . "utils/xhprof_lib.php";
    include_once $XHPROF_ROOT . "utils/xhprof_runs.php";

    $xhprof_runs = new \XHProfRuns_Default();
    $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");

}