<?php

namespace Lib;

/**
 * 数组辅助类
 * Class Arr
 * @package Lib
 */
class Arr {

    /**
     * 重定义二维数组的键值
     * @use
     *   $items = [
     *       ['id' => 10, 'name' => 'xxx'],
     *       ['id' => 11, 'name' => 'xxxx'],
     *   ]
     *
     *   $newItems = Arr::getDictionary($itmes, 'id');
     *
     *   $newItems value like this:
     *   [
     *      '10' => ['id' => 10, 'name' => 'xxx'],
     *      '11' => ['id' => 11, 'name' => 'xxxx'],
     *   ]
     *
     * @param array $items 二维数组
     * @param int|string $key         $items子数组里面的键名
     * @return array
     */
    public static function getDictionary(Array $items, $key) {
        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$value[$key]] = $value;
        }

        return $dictionary;
    }

//    public static function getAssociative(Array $masterArr, Array $slaveArr, $foreignKey, $autoDictionary = true) {
//
//        if ($autoDictionary) {
//            self::getDictionary($slaveArr, $foreignKey);
//        }
//
//    }
}