<?php

namespace Lib;

use Exception\UserException;

class Validator {
    const ERROR_DEFAULT = 'Invalid';

    protected $_fields = array();

    protected $_errors = array();

    protected $_validations = array();

    protected $_labels = array();

    protected static $_rules = array();

    protected static $_ruleMessages = [
        'required'      => "不能为空",
        'equals'        => "必须和'%s'一致",
        'different'     => "必须和'%s'不一致",
        'accepted'      => "必须接受",
        'numeric'       => "只能是数字",
        'integer'       => "只能是整数(0-9)",
        'length'        => "长度必须大于%d",
        'lengthBetween' => "长度必须大于%d小于%d",
        'min'           => "必须大于%s",
        'max'           => "必须小于%s",
        'in'            => "无效的值",
        'notIn'         => "无效的值",
        'ip'            => "无效IP地址",
        'email'         => "无效邮箱地址",
        'url'           => "无效的URL",
        'urlActive'     => "必须是可用的域名",
        'alpha'         => "只能包括英文字母(a-z)",
        'alphaNum'      => "只能包括英文字母(a-z)和数字(0-9)",
        'slug'          => "只能包括英文字母(a-z)、数字(0-9)、破折号和下划线",
        'regex'         => "无效格式",
        'contains'  => "必须包含%s",
        'chinese'  => "必须为汉字",
        'phone' => "无效",
    ];

    protected $validUrlPrefixes = array('http://', 'https://', 'ftp://');

    public function __construct() {
        // 单例模式下 避免开发人员  操作验证类 而未validate操作产生的数据污染
        $this->reset();
    }

    /**
     * 设置有效的数据
     * @param array $data   输入的数据
     * @param array $fields 数据白名单  $data的key组成的一维数组
     * @return $this
     */
    public function setFields($data, $fields = []) {
        $this->_fields = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data;
        return $this;
    }

    /**
     * Required field validator
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateRequired($field, $value)
    {
        if ($value === null) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    protected function validateEquals($field, $value, array $params)
    {
        $field2 = $params[0];

        return isset($this->_fields[$field2]) && $value == $this->_fields[$field2];
    }

    protected function validateDifferent($field, $value, array $params)
    {
        $field2 = $params[0];

        return isset($this->_fields[$field2]) && $value != $this->_fields[$field2];
    }

    protected function validateNumeric($field, $value)
    {
        return is_numeric($value);
    }

    protected function validateInteger($field, $value)
    {
        return filter_var($value, \FILTER_VALIDATE_INT) !== false;
    }

    protected function validateLength($field, $value, $params)
    {
        $length = $this->stringLength($value);
        // Length between
        if (isset($params[1])) {
            return $length >= $params[0] && $length <= $params[1];
        }
        // Length same
        return $length == $params[0];
    }

    protected function validateLengthBetween($field, $value, $params)
    {
        $length = $this->stringLength($value);

        return $length >= $params[0] && $length <= $params[1];
    }

    protected function validateLengthMin($field, $value, $params)
    {
        return $this->stringLength($value) >= $params[0];
    }

    protected function validateLengthMax($field, $value, $params)
    {
        return $this->stringLength($value) <= $params[0];
    }

    protected function stringLength($value)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    protected function validateMin($field, $value, $params)
    {
        if (function_exists('bccomp')) {
            return !(bccomp($params[0], $value, 14) == 1);
        } else {
            return $params[0] <= $value;
        }
    }

    protected function validateMax($field, $value, $params)
    {
        if (function_exists('bccomp')) {
            return !(bccomp($value, $params[0], 14) == 1);
        } else {
            return $params[0] >= $value;
        }
    }


    protected function validateIn($field, $value, $params)
    {
        $isAssoc = array_values($params[0]) !== $params[0];
        if ($isAssoc) {
            $params[0] = array_keys($params[0]);
        }

        $strict = false;
        if (isset($params[1])) {
            $strict = $params[1];
        }

        return in_array($value, $params[0], $strict);
    }

    protected function validateNotIn($field, $value, $params)
    {
        return !$this->validateIn($field, $value, $params);
    }

    protected function validateContains($field, $value, $params)
    {
        if (!isset($params[0])) {
            return false;
        }
        if (!is_string($params[0]) || !is_string($value)) {
            return false;
        }

        return (strpos($value, $params[0]) !== false);
    }

    /**
     * 验证ip是否有效
     */
    protected function validateIp($field, $value)
    {
        return filter_var($value, \FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 验证EMAIL是否有效
     */
    protected function validateEmail($field, $value)
    {
        return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateUrl($field, $value)
    {
        foreach ($this->validUrlPrefixes as $prefix) {
            if (strpos($value, $prefix) !== false) {
                return filter_var($value, \FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    protected function validateAlpha($field, $value)
    {
        return preg_match('/^([a-z])+$/i', $value);
    }

    protected function validateAlphaNum($field, $value)
    {
        return preg_match('/^([a-z0-9])+$/i', $value);
    }

    protected function validateSlug($field, $value)
    {
        return preg_match('/^([-a-z0-9_-])+$/i', $value);
    }

    protected function validateRegex($field, $value, $params)
    {
        return preg_match($params[0], $value);
    }

    /**
     */
    protected function validateBoolean($field, $value)
    {
        return (is_bool($value)) ? true : false;
    }

    /**
     * 验证是否为中文
     */
    protected function validateChinese($field, $value) {
        return preg_match('/^[\x80-\xff]+$/', $value);
    }

    /**
     * @desc  验证手机号
     */
    protected function validatePhone($field, $value) {
        return preg_match('/^1[34587]\d{9}$/', $value);
    }

    /**
     * 获取数据
     * @return array
     */
    public function getData()
    {
        return $this->_fields;
    }

    public function errors($field = null)
    {
        if ($field !== null) {
            return isset($this->_errors[$field]) ? $this->_errors[$field] : false;
        }

        return $this->_errors;
    }

    public function error($field, $msg, array $params = array())
    {
        $msg = $this->checkAndSetLabel($field, $msg, $params);

        $values = array();

        foreach ($params as $param) {
            if (is_array($param)) {
                $param = "['" . implode("', '", $param) . "']";
            }
            if ($param instanceof \DateTime) {
                $param = $param->format('Y-m-d');
            } else {
                if (is_object($param)) {
                    $param = get_class($param);
                }
            }

            if (is_string($params[0])) {
                if (isset($this->_labels[$param])) {
                    $param = $this->_labels[$param];
                }
            }
            $values[] = $param;
        }

        $this->_errors[$field][] = vsprintf($msg, $values);
    }

    public function message($msg)
    {
        $this->_validations[count($this->_validations) - 1]['message'] = $msg;

        return $this;
    }

    public function reset()
    {
        $this->_fields = array();
        $this->_errors = array();
        $this->_validations = array();
        $this->_labels = array();
    }

    public function validate()
    {
        foreach ($this->_validations as $v) {
            foreach ($v['fields'] as $field) {
                // 无效值null表示
                if ($field === null || !isset($this->_fields[$field])) {
                    $value =  null;
                } else {
                    $value = $this->_fields[$field];
                }

                // 字段不是必须验证的（not required ）且 值为空的跳过验证
                if ($v['rule'] !== 'required' && !$this->hasRule('required', $field) && (! isset($value) || $value === '')) {
                    continue;
                }

                // 用户自定义验证方法
                if (isset(static::$_rules[$v['rule']])) {
                    $callback = static::$_rules[$v['rule']];
                } else {
                    $callback = array($this, 'validate' . ucfirst($v['rule']));
                }

                $result = call_user_func($callback, $field, $value, $v['params']);

                if (!$result) {
                    $this->error($field, $v['message'], $v['params']);
                }
            }
        }

        return count($this->errors()) === 0;
    }

    public function validateWithTry()
    {
        if(!$this->validate()) {
            throw new UserException($this->firstError());
        }
    }

    protected function hasRule($name, $field)
    {
        foreach ($this->_validations as $validation) {
            if ($validation['rule'] == $name) {
                if (in_array($field, $validation['fields'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function addRule($name, $callback, $message = self::ERROR_DEFAULT)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Second argument must be a valid callback. Given argument was not callable.');
        }

        static::$_rules[$name] = $callback;
        static::$_ruleMessages[$name] = $message;
    }

    /**
     * 注册字段验证
     * @param string $rule     验证规则标识符
     * @param $fields
     * @return $this
     */
    public function rule($rule, $fields)
    {
        if (!isset(static::$_rules[$rule])) {
            $ruleMethod = 'validate' . ucfirst($rule);
            if (!method_exists($this, $ruleMethod)) {
                throw new \InvalidArgumentException("验证规则： '" . $rule . "' 不存在");
            }
        }

        // 获取错误提示消息
        $message = isset(static::$_ruleMessages[$rule]) ? static::$_ruleMessages[$rule] : self::ERROR_DEFAULT;

        // 获取其他参数
        $params = array_slice(func_get_args(), 2);

        $this->_validations[] = array(
            'rule' => $rule,
            'fields' => (array) $fields,
            'params' => (array) $params,
            'message' => '{field}' . $message
        );

        return $this;
    }

    public function label($value)
    {
        $lastRules = $this->_validations[count($this->_validations) - 1]['fields'];
        $this->labels(array($lastRules[0] => $value));

        return $this;
    }

    public function labels($labels = array())
    {
        $this->_labels = array_merge($this->_labels, $labels);

        return $this;
    }

    private function checkAndSetLabel($field, $msg, $params)
    {

        if (isset($this->_labels[$field])) {
            $msg = str_replace('{field}', $this->_labels[$field], $msg);

            if (is_array($params)) {
                $i = 1;
                foreach ($params as $k => $v) {
                    $tag = '{field'. $i .'}';
                    $label = isset($params[$k]) && (is_numeric($params[$k]) || is_string($params[$k])) && isset($this->_labels[$params[$k]]) ? $this->_labels[$params[$k]] : $tag;
                    $msg = str_replace($tag, $label, $msg);
                    $i++;
                }
            }
        } else {
            $msg = str_replace('{field}', ucwords(str_replace('_', ' ', $field)), $msg);
        }

        return $msg;
    }

    public function rules($rules)
    {
        foreach ($rules as $ruleType => $params) {
            if (is_array($params)) {
                foreach ($params as $innerParams) {
                    array_unshift($innerParams, $ruleType);
                    call_user_func_array(array($this, 'rule'), $innerParams);
                }
            } else {
                $this->rule($ruleType, $params);
            }
        }
    }

    /**
     * 获取第一个错误的错误说明（errors方法只能回去所有错误的数组）
     * @return string
     */
    public function firstError() {
        $errors = $this->errors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                return $error[0];
            }
        }
        return false;
    }
}