<?php

namespace Model\Config;

use Exception\UserException;

/**
 * ip地域设置 独立存放 仅Model/Config使用
 * Class IpDistrictTrait
 * @package Model\Config
 */
trait IpDistrictTrait {

    public static $IP_LIMIT = 'ip_limit';             // ipkey
    public static $DISTRICT_LIMIT = 'district_limit'; // 地域key

    /**
     * 获取ip限制配置信息
     * @param $type   1 会员 2 代理 3 管理
     * @param $username
     * @return mixed
     */
    public function getIpLimit($userType, $username) {

        return $this->where('`key`', $userType . $username . self::$IP_LIMIT)->first();
    }

    /**
     * 获取地域配置信息
     * @param $userType
     * @param $username
     * @return mixed
     */
    public function getDistrictLimit($userType, $username) {
        return $this->where('`key`', $userType . $username . self::$DISTRICT_LIMIT)->first();
    }

    /**
     * ip 地域设置
     * @param string $ips
     * @param string $districts
     * @return mixed
     * @throws UserException
     */
    public function setIpLimit($ips = '', $districts = '') {

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

        if (!$ips && !$districts) {
            throw new UserException("ip 或 地域设置不能为空");
        }

        if($ips) {
             return $this->insert([
                'key' => 1 . config('loginUser')['username'] . self::$IP_LIMIT,
                'val' => $ips
            ]);
        }

        if ($districts) {
             return $this->insert([
                'key' => 1 . config('loginUser')['username'] . self::$DISTRICT_LIMIT,
                'val' => $districts
            ]);
        }

        return true;
    }

}