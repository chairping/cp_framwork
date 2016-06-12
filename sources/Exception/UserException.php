<?php

namespace Exception;

/**
 * 用户异常
 * 实际上并非用户异常 用于未通过应用验证等无权限或数据校验失败等情况抛出，由应用组织并输出给用户
 */
class UserException extends \Exception {

    const OPERATION_FAIL = 1001;
    const MODULE_CLOSE = 2001;

    public function __construct($message, $code = 400) {

//        if (is_array($message)) {
//            $message = json_encode($message);
//        } else {
//            $message = json_encode([
//                'message' => $message
//            ]);
//        }

//        var_dump($message);

        parent::__construct($message, $code);
    }
}
